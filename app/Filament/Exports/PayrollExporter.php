<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Payroll;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PayrollExporter extends Exporter
{
    protected static ?string $model = Payroll::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('entity')->label(__('validation.attributes.entity')),
            ExportColumn::make('registration')->label(__('validation.attributes.registration')),
            ExportColumn::make('name')->label(__('validation.attributes.name')),
            ExportColumn::make('role')->label(__('validation.attributes.role')),
            ExportColumn::make('admission_date')->label(__('validation.attributes.admission_date'))
                ->formatStateUsing(fn (Payroll $record): string => $record->admission_date ? $record->admission_date->format('d/m/Y') : ''),
            ExportColumn::make('resignation_date')->label(__('validation.attributes.resignation_date'))
                ->formatStateUsing(fn (Payroll $record): string => $record->resignation_date ? $record->resignation_date->format('d/m/Y') : ''),
            ExportColumn::make('employment_regime')->label(__('validation.attributes.employment_regime')),
            ExportColumn::make('workplace')->label(__('validation.attributes.workplace')),
            ExportColumn::make('workload_hours')->label(__('validation.attributes.workload_hours')),
            ExportColumn::make('base_salary')->label(__('validation.attributes.base_salary'))
                ->formatStateUsing(fn (Payroll $record): string => Number::currency((float) ($record->base_salary ?? 0), 'BRL')),
            ExportColumn::make('allowances')->label(__('validation.attributes.allowances'))
                ->formatStateUsing(fn (Payroll $record): string => Number::currency((float) ($record->allowances ?? 0), 'BRL')),
            ExportColumn::make('deductions')->label(__('validation.attributes.deductions'))
                ->formatStateUsing(fn (Payroll $record): string => Number::currency((float) ($record->deductions ?? 0), 'BRL')),
            ExportColumn::make('taxes')->label(__('validation.attributes.taxes'))
                ->formatStateUsing(fn (Payroll $record): string => Number::currency((float) ($record->taxes ?? 0), 'BRL')),
            ExportColumn::make('net_salary')->label(__('validation.attributes.net_salary'))
                ->formatStateUsing(fn (Payroll $record): string => Number::currency((float) ($record->net_salary ?? 0), 'BRL')),
            ExportColumn::make('month')->label(__('validation.attributes.month')),
            ExportColumn::make('year')->label(__('validation.attributes.year')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payroll export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
