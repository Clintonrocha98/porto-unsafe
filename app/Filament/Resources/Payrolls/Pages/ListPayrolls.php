<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Enums\MunicipalDepartment;
use App\Filament\Exports\PayrollExporter;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Jobs\ProcessPayrollScrapeJob;
use App\Models\Payroll;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ListPayrolls extends ListRecords
{
    private const array MONTH_NAMES = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
    protected static string $resource = PayrollResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start_scrape')
                ->label('Iniciar Extração')
                ->icon('heroicon-o-cloud-arrow-down')
                ->disabled(fn (): bool => DB::table('job_batches')->where('name', 'like', 'Payroll Scrape%')->where('pending_jobs', '>', 0)->exists())
                ->schema([
                    Flex::make([
                        Select::make('entity')
                            ->label(__('validation.attributes.entity'))
                            ->options(MunicipalDepartment::class)
                            ->required()
                            ->default(MunicipalDepartment::Administracao->value)
                            ->live(),
                        Select::make('year')
                            ->label(__('validation.attributes.year'))
                            ->options(array_combine(range(2024, Date::now()->year), range(2024, Date::now()->year)))
                            ->required()
                            ->live(),
                        Select::make('month')
                            ->label(__('validation.attributes.month'))
                            ->options(function (Get $get): array {
                                $year = (int) $get('year');
                                $entity = $get('entity');
                                $now = Date::now();

                                $startMonth = $year === 2024 ? 3 : 1;
                                $endMonth = $year === $now->year ? $now->month : 12;

                                $scrapedMonths = $entity && $year
                                    ? Payroll::query()
                                        ->where('entity', $entity)
                                        ->where('year', $year)
                                        ->distinct()
                                        ->pluck('month')
                                        ->toArray()
                                    : [];

                                $options = [];
                                foreach (range($startMonth, $endMonth) as $month) {
                                    if (! in_array($month, $scrapedMonths, true)) {
                                        $options[$month] = self::MONTH_NAMES[$month];
                                    }
                                }

                                return $options;
                            })
                            ->required()
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $exists = Payroll::query()
                                    ->where('entity', $get('entity'))
                                    ->where('year', (int) $get('year'))
                                    ->where('month', (int) $value)
                                    ->exists();

                                if ($exists) {
                                    $fail('Este mês já foi extraído para esta entidade e ano.');
                                }
                            }),
                    ]),
                ])
                ->action(function (array $data): void {
                    $jobs = [];
                    foreach (range(1, 3) as $regime) {
                        $jobs[] = new ProcessPayrollScrapeJob($data['entity'], (int) $data['month'], (int) $data['year'], $regime);
                    }

                    $user = auth()->user();

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
