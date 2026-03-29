<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpenseSummaries\Pages;

use App\Enums\ExpenseType;
use App\Filament\Resources\ExpenseSummaries\ExpenseSummaryResource;
use App\Jobs\ProcessExpenseScrapeJob;
use App\Models\ExpenseSummary;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Flex;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;

class ListExpenseSummaries extends ListRecords
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

    public string $activeType = ExpenseType::ResumoOrcamentario->value;

    protected static string $resource = ExpenseSummaryResource::class;

    protected string $view = 'filament.resources.expense-summaries.pages.list-expense-summaries';

    protected function getTableQuery(): Builder
    {
        return ExpenseSummary::query()->where('expense_type', $this->activeType);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start_scrape')
                ->label('Iniciar Extração')
                ->icon('heroicon-o-cloud-arrow-down')
                ->schema([
                    Flex::make([
                        Select::make('year')
                            ->label(__('validation.attributes.year'))
                            ->options(array_combine(range(2021, Date::now()->year), range(2021, Date::now()->year)))
                            ->required()
                            ->default(Date::now()->year),
                        Select::make('month')
                            ->label(__('validation.attributes.month'))
                            ->options(self::MONTH_NAMES)
                            ->required()
                            ->default(Date::now()->month),
                    ]),
                ])
                ->action(function (array $data): void {
                    $type = ExpenseType::from($this->activeType);

                    Bus::batch([new ProcessExpenseScrapeJob($type, (int) $data['year'], (int) $data['month'])])
                        ->name("Expense Scrape: {$data['month']}/{$data['year']}")
                        ->then(function (Batch $batch) use ($type, $data): void {
                            Notification::make()
                                ->title('Extração concluída!')
                                ->body("Despesas de {$type->getLabel()} ({$data['month']}/{$data['year']}) importadas com sucesso.")
                                ->success()
                                ->sendToDatabase(auth()->user());
                        })
                        ->dispatch();

                    Notification::make()
                        ->title('Extração iniciada!')
                        ->body("Estamos buscando as despesas de {$type->getLabel()}. Você receberá uma notificação quando o processo concluir.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
