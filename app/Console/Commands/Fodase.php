<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ExpenseType;
use App\Services\Scraping\ExpenseSummaryScraperService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class Fodase extends Command
{
    protected $signature = 'app:fodase';

    protected $description = 'Command description';

    public function __construct(private ExpenseSummaryScraperService $scraper)
    {
        parent::__construct();
    }

    /**
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $records = $this->scraper->scrape(ExpenseType::ResumoOrcamentario, 2026, 1);

        foreach ($records as $dto) {
            $this->info(json_encode($dto->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }
}
