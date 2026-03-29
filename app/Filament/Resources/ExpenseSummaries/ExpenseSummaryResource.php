<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpenseSummaries;

use App\Filament\Resources\ExpenseSummaries\Pages\ListExpenseSummaries;
use App\Filament\Resources\ExpenseSummaries\Tables\ExpenseSummariesTable;
use App\Models\ExpenseSummary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExpenseSummaryResource extends Resource
{
    protected static ?string $model = ExpenseSummary::class;

    protected static ?string $modelLabel = 'Despesa';

    protected static ?string $pluralModelLabel = 'Despesas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ExpenseSummariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseSummaries::route('/'),
        ];
    }
}
