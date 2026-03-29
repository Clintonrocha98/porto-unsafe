<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseType;
use Database\Factories\ExpenseSummaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSummary extends Model
{
    /** @use HasFactory<ExpenseSummaryFactory> */
    use HasFactory;

    protected $fillable = [
        'expense_date',
        'empenho_number',
        'element_code',
        'element_description',
        'creditor',
        'creditor_document',
        'committed',
        'annulled',
        'reinforced',
        'liquidated',
        'paid',
        'expense_type',
        'bidding_modality',
        'process_number',
        'month',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'empenho_number' => 'integer',
            'committed' => 'decimal:2',
            'annulled' => 'decimal:2',
            'reinforced' => 'decimal:2',
            'liquidated' => 'decimal:2',
            'paid' => 'decimal:2',
            'expense_type' => ExpenseType::class,
            'month' => 'integer',
            'year' => 'integer',
        ];
    }
}
