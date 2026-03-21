<?php

declare(strict_types=1);

namespace App\Parsers;

use App\DTO\PayrollDTO;
use DateTimeImmutable;
use DOMElement;
use Illuminate\Support\Facades\Date;

class PayrollParser
{
    private const array TEXT_FIELDS = [1, 2, 5, 6];

    private const array DATE_FIELDS = [3, 4];

    private const array MONEY_FIELDS = [8, 9, 10, 11, 12];

    private const array INT_FIELDS = [7, 13, 14];

    public static function clean(string $value, int $index): string
    {
        $value = self::normalize($value);

        if (in_array($index, self::TEXT_FIELDS, true)) {
            return self::cleanText($value);
        }

        if (in_array($index, self::DATE_FIELDS, true)) {
            return self::cleanDate($value);
        }

        if (in_array($index, self::MONEY_FIELDS, true)) {
            return self::cleanMoney($value);
        }

        if (in_array($index, self::INT_FIELDS, true)) {
            return self::cleanInt($value);
        }

        return $value;
    }

    public static function parseRow(DOMElement $node, int $month, int $year): PayrollDTO
    {
        $data = [];

        foreach ($node->getElementsByTagName('td') as $index => $td) {
            $data[] = self::clean($td->textContent, $index);
        }

        return PayrollDTO::make([
            'registration' => $data[0],
            'name' => $data[1],
            'role' => $data[2],
            'admission_date' => $data[3] !== '' ? Date::parse($data[3]) : null,
            'resignation_date' => $data[4] !== '' ? Date::parse($data[4]) : null,
            'employment_regime' => $data[5],
            'workplace' => $data[6],
            'workload_hours' => $data[7] !== '' ? (int) $data[7] : null,
            'base_salary' => $data[8],
            'allowances' => $data[9],
            'deductions' => $data[10],
            'taxes' => $data[11],
            'net_salary' => $data[12],
            'month' => $month,
            'year' => $year,
        ]);
    }

    private static function normalize(string $value): string
    {
        $value = str_replace("\u{A0}", ' ', $value);
        $value = mb_trim((string) preg_replace('/\s+/u', ' ', $value));

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        } else {
            $decoded = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            if (
                $decoded !== $value
                && mb_check_encoding($decoded, 'UTF-8')
                && ! mb_check_encoding($decoded, 'ASCII')
            ) {
                $value = $decoded;
            }
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_trim($value);
    }

    private static function cleanText(string $value): string
    {
        $value = preg_replace('/\s*\/\s*$/', '', $value);

        return mb_convert_case((string) $value, MB_CASE_TITLE, 'UTF-8');
    }

    private static function cleanDate(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $date = DateTimeImmutable::createFromFormat('d/m/Y', $value);
        if ($date === false) {
            return $value;
        }

        return $date->format('Y-m-d');
    }

    private static function cleanMoney(string $value): string
    {
        $value = str_replace('.', '', $value);

        return str_replace(',', '.', $value);
    }

    private static function cleanInt(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}
