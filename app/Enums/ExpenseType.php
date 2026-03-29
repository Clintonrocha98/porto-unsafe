<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpenseType: string implements HasLabel
{
    case ResumoOrcamentario = 'resumoOrc';
    case Empenhos = 'empenhos';
    case Liquidacoes = 'liquidacoes';
    case Pagamentos = 'pagamentos';
    case ExtraOrcamentario = 'extraorcamentario';
    case RepasseFinanceiro = 'transferenciafinanceira';

    public function getLabel(): ?string
    {
        return match ($this) {
            ExpenseType::ResumoOrcamentario => 'Resumo Orçamentário',
            ExpenseType::Empenhos => 'Empenhos',
            ExpenseType::Liquidacoes => 'Liquidações',
            ExpenseType::Pagamentos => 'Pagamentos',
            ExpenseType::ExtraOrcamentario => 'Extra Orçamentário',
            ExpenseType::RepasseFinanceiro => 'Repasse Financeiro',
        };
    }

    public function anchor(): string
    {
        return '#'.$this->value;
    }

    public function formKey(): string
    {
        return match ($this) {
            ExpenseType::ResumoOrcamentario => 'ResumoOrc',
            ExpenseType::Empenhos => 'Empenhos',
            ExpenseType::Liquidacoes => 'Liquidacoes',
            ExpenseType::Pagamentos => 'Pagamentos',
            ExpenseType::ExtraOrcamentario => 'ExtraOrcamentario',
            ExpenseType::RepasseFinanceiro => 'TransferenciaFinanceira',
        };
    }
}
