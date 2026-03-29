<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MunicipalDepartment;
use App\Models\Payroll;
use App\Services\Scraping\PayrollScraperService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayrollScrapeJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public MunicipalDepartment $entity,
        public int $month,
        public int $year,
        public int $regime
    ) {}

    public function handle(PayrollScraperService $scraper): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $records = [];

        foreach ($scraper->scrape($this->entity->value, $this->month, $this->year, $this->regime) as $dto) {
            $record = ['entity' => $this->entity->value, ...$dto->toArray()];
            $key = implode('|', [$this->entity->value, $record['registration'], $record['role'], $record['month'], $record['year']]);
            $records[$key] = $record;
        }

        if ($records === []) {
            return;
        }

        Payroll::query()->upsert(array_values($records), ['entity', 'registration', 'role', 'month', 'year'], [
            'name', 'admission_date', 'resignation_date',
            'employment_regime', 'workplace', 'workload_hours',
            'base_salary', 'allowances', 'deductions', 'taxes', 'net_salary',
        ]);
    }
}
