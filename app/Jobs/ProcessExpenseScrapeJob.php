<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExpenseType;
use App\Models\ExpenseSummary;
use App\Services\Scraping\ExpenseSummaryScraperService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessExpenseScrapeJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ExpenseType $expenseType,
        public int $year,
        public int $month,
    ) {}

    public function handle(ExpenseSummaryScraperService $scraper): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $records = [];

        foreach ($scraper->scrape($this->expenseType, $this->year, $this->month) as $dto) {
            $record = ['expense_type' => $this->expenseType->value, ...$dto->toArray()];
            $key = implode('|', [$this->expenseType->value, $record['empenho_number'], $record['year']]);
            $records[$key] = $record;
        }

        if ($records === []) {
            return;
        }

        ExpenseSummary::query()->upsert(
            array_values($records),
            ['expense_type', 'empenho_number', 'year'],
            [
                'expense_date', 'element_code', 'element_description', 'creditor',
                'creditor_document', 'committed', 'annulled', 'reinforced',
                'liquidated', 'paid', 'bidding_modality', 'process_number',
            ]
        );
    }
}
