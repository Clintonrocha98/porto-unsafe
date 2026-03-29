<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

class ExpenseSummaryDTO
{
    public function __construct(
        public ?Carbon $expense_date,
        public ?int $empenho_number,
        public ?string $element_code,
        public ?string $element_description,
        public ?string $creditor,
        public ?string $creditor_document,
        public ?float $committed,
        public ?float $annulled,
        public ?float $reinforced,
        public ?float $liquidated,
        public ?float $paid,
        public ?string $bidding_modality,
        public ?string $process_number,
        public int $month,
        public int $year,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            expense_date: $data['expense_date'] ?? null,
            empenho_number: $data['empenho_number'] ?? null,
            element_code: $data['element_code'] ?? null,
            element_description: $data['element_description'] ?? null,
            creditor: $data['creditor'] ?? null,
            creditor_document: $data['creditor_document'] ?? null,
            committed: $data['committed'] ?? null,
            annulled: $data['annulled'] ?? null,
            reinforced: $data['reinforced'] ?? null,
            liquidated: $data['liquidated'] ?? null,
            paid: $data['paid'] ?? null,
            bidding_modality: $data['bidding_modality'] ?? null,
            process_number: $data['process_number'] ?? null,
            month: $data['month'],
            year: $data['year'],
        );
    }

    public function toArray(): array
    {
        return [
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'empenho_number' => $this->empenho_number,
            'element_code' => $this->element_code,
            'element_description' => $this->element_description,
            'creditor' => $this->creditor,
            'creditor_document' => $this->creditor_document,
            'committed' => $this->committed,
            'annulled' => $this->annulled,
            'reinforced' => $this->reinforced,
            'liquidated' => $this->liquidated,
            'paid' => $this->paid,
            'bidding_modality' => $this->bidding_modality,
            'process_number' => $this->process_number,
            'month' => $this->month,
            'year' => $this->year,
        ];
    }
}
