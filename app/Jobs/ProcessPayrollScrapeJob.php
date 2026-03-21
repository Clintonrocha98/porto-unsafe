<?php

declare(strict_types=1);

namespace App\Jobs;

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
        public string $entity,
        public int $month,
        public int $year,
        public int $regime
    ) {}

    public function handle(PayrollScraperService $scraper): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $dtos = $scraper->scrape($this->entity, $this->month, $this->year, $this->regime);

        foreach ($dtos as $dto) {
            $this->batch()->add(new SavePayrollRecordJob($this->entity, $dto));
        }
    }
}
