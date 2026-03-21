<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Exports\PayrollExporter;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Jobs\ProcessPayrollScrapeJob;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('process_history')
                ->label('Histórico')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->slideOver()
                ->modalHeading('Histórico de Processamento')
                ->modalContent(view('filament.pages.process-history-modal')),
            Action::make('start_scrape')
                ->label('Iniciar Extração')
                ->icon('heroicon-o-cloud-arrow-down')
                ->disabled(fn (): bool => DB::table('job_batches')->where('name', 'like', 'Payroll Scrape%')->where('pending_jobs', '>', 0)->exists())
                ->form([
                    TextInput::make('entity')->label(__('validation.attributes.entity'))->required()->default('pm_portoseguro'),
                    Select::make('month')->label(__('validation.attributes.month'))->options(array_combine(range(1, 12), range(1, 12)))->required(),
                    Select::make('year')->label(__('validation.attributes.year'))->options(array_combine(range(Date::now()->format('Y') - 5, Date::now()->format('Y')), range(Date::now()->format('Y') - 5, Date::now()->format('Y'))))->required(),
                ])
                ->action(function (array $data): void {
                    $jobs = [];
                    foreach (range(1, 3) as $regime) {
                        $jobs[] = new ProcessPayrollScrapeJob($data['entity'], (int) $data['month'], (int) $data['year'], $regime);
                    }

                    $user = \Illuminate\Support\Facades\Auth::user();

                    Bus::batch($jobs)
                        ->name('Payroll Scrape: '.$data['month'].'/'.$data['year'])
                        ->then(function (Batch $batch) use ($user, $data): void {
                            Notification::make()
                                ->title('Scraping Finalizado')
                                ->body("O scraping do mês {$data['month']}/{$data['year']} foi finalizado com sucesso.")
                                ->success()
                                ->sendToDatabase($user);
                        })
                        ->dispatch();

                    Notification::make()
                        ->title('Extração iniciada em segundo plano')
                        ->success()
                        ->send();
                }),
            ExportAction::make()
                ->exporter(PayrollExporter::class),
        ];
    }
}
