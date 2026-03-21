<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PayrollScrapeProgressWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.payroll-scrape-progress-widget';

    protected int|string|array $columnSpan = 'full';

    public function getBatchesProperty()
    {
        return DB::table('job_batches')
            ->where('name', 'like', 'Payroll Scrape%')
            ->where(function ($query) {
                $query->where('pending_jobs', '>', 0)
                    ->orWhere('failed_jobs', '>', 0)
                    ->orWhere('created_at', '>=', now()->subMinutes(5)->timestamp);
            })
            ->latest()
            ->limit(3)
            ->get();
    }
}
