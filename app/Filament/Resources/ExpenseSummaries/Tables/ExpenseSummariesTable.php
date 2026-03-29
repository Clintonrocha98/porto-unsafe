<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpenseSummaries\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseSummariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')
                    ->label(__('validation.attributes.expense_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('empenho_number')
                    ->label(__('validation.attributes.empenho_number'))
                    ->searchable(),
                TextColumn::make('element_code')
                    ->label(__('validation.attributes.element_code'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('element_description')
                    ->label(__('validation.attributes.element_description'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creditor')
                    ->label(__('validation.attributes.creditor'))
                    ->searchable(),
                TextColumn::make('creditor_document')
                    ->label(__('validation.attributes.creditor_document'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('committed')
                    ->label(__('validation.attributes.committed'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('liquidated')
                    ->label(__('validation.attributes.liquidated'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('paid')
                    ->label(__('validation.attributes.paid'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('annulled')
                    ->label(__('validation.attributes.annulled'))
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reinforced')
                    ->label(__('validation.attributes.reinforced'))
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bidding_modality')
                    ->label(__('validation.attributes.bidding_modality'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('process_number')
                    ->label(__('validation.attributes.process_number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('month')
                    ->label(__('validation.attributes.month'))
                    ->sortable(),
                TextColumn::make('year')
                    ->label(__('validation.attributes.year'))
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
