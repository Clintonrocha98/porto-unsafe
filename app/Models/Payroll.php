<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'entity',
        'registration',
        'name',
        'role',
        'admission_date',
        'resignation_date',
        'employment_regime',
        'workplace',
        'workload_hours',
        'base_salary',
        'allowances',
        'deductions',
        'taxes',
        'net_salary',
        'month',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'resignation_date' => 'date',
            'workload_hours' => 'integer',
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
            'taxes' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'month' => 'integer',
            'year' => 'integer',
        ];
    }
}
