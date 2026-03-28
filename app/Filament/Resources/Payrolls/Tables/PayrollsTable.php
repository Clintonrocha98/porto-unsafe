<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity')->label(__('validation.attributes.entity'))->searchable()->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\Entity ? $state->getLabel() : $state),
                TextColumn::make('registration')->label(__('validation.attributes.registration'))->searchable(),
                TextColumn::make('name')->label(__('validation.attributes.name'))->searchable(),
                TextColumn::make('role')->label(__('validation.attributes.role'))->searchable(),
                TextColumn::make('employment_regime')->label(__('validation.attributes.employment_regime'))->searchable(),
                TextColumn::make('admission_date')->label(__('validation.attributes.admission_date'))->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('base_salary')->label(__('validation.attributes.base_salary'))->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('allowances')->label(__('validation.attributes.allowances'))->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deductions')->label(__('validation.attributes.deductions'))->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('taxes')->label(__('validation.attributes.taxes'))->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_salary')->label(__('validation.attributes.net_salary'))->money('BRL')->sortable(),
                TextColumn::make('month')->label(__('validation.attributes.month'))->sortable(),
                TextColumn::make('year')->label(__('validation.attributes.year'))->sortable(),
            ])
            ->filters([

            ])
            ->recordActions([
                // Read-only: No actions here
            ])
            ->toolbarActions([
                // Read-only: No bulk actions here
            ]);
    }
}
