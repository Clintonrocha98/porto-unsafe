<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExpenseSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseSummary>
 */
class ExpenseSummaryFactory extends Factory
{
    private static int $empenhoSequence = 1;

    public function definition(): array
    {
        $elementCodes = ['33903600', '33903000', '33901400', '33903900', '44905200'];
        $elementDescriptions = [
            'Outros Serviços de Terceiros - Pessoa Física',
            'Material de Consumo',
            'Diárias - Civil',
            'Outros Serviços de Terceiros - Pessoa Jurídica',
            'Equipamentos e Material Permanente',
        ];

        $index = fake()->numberBetween(0, 4);
        $month = fake()->numberBetween(1, 12);
        $year = fake()->numberBetween(2020, 2026);

        return [
            'expense_date' => fake()->dateTimeBetween("-{$year} years", 'now')->format('Y-m-d'),
            'empenho_number' => self::$empenhoSequence++,
            'element_code' => $elementCodes[$index],
            'element_description' => $elementDescriptions[$index],
            'creditor' => fake('pt_BR')->name(),
            'creditor_document' => fake('pt_BR')->cpf(false),
            'committed' => fake()->randomFloat(2, 100, 50000),
            'annulled' => 0.00,
            'reinforced' => 0.00,
            'liquidated' => fake()->randomFloat(2, 0, 50000),
            'paid' => 0.00,
            'month' => $month,
            'year' => $year,
        ];
    }
}
