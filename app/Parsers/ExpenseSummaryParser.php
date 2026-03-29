<?php

declare(strict_types=1);

namespace App\Parsers;

use App\DTO\ExpenseSummaryDTO;
use Carbon\Carbon;
use DOMElement;

class ExpenseSummaryParser
{
    public static function parseRow(DOMElement $node, int $month, int $year): ExpenseSummaryDTO
    {
        $cells = [];

        foreach ($node->getElementsByTagName('td') as $td) {
            $cells[] = self::normalize($td->textContent ?? '');
        }

        $elementParts = explode(' - ', $cells[2], 2);

        return ExpenseSummaryDTO::make([
            'expense_date' => Carbon::createFromFormat('d/m/Y', $cells[0]),
            'empenho_number' => (int) $cells[1],
            'element_code' => $elementParts[0] ?? null,
            'element_description' => $elementParts[1] ?? null,
            'creditor' => $cells[3],
            'creditor_document' => $cells[4],
            'committed' => self::parseMoney($cells[5]),
            'annulled' => self::parseMoney($cells[6]),
            'reinforced' => self::parseMoney($cells[7]),
            'liquidated' => self::parseMoney($cells[8]),
            'paid' => self::parseMoney($cells[9]),
            'bidding_modality' => $cells[10] ?? null,
            'process_number' => $cells[11] ?? null,
            'month' => $month,
            'year' => $year,
        ]);
    }

    private static function parseMoney(string $value): float
    {
        $cleaned = str_replace(['R$', ' ', '.'], '', $value);
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    private static function normalize(string $value): string
    {
        return mb_trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
