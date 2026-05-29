<?php

namespace Database\Factories;

use App\Enums\ExpenseCalculationType;
use App\Models\AnnualExpense;
use App\Models\User;
use App\Models\Year;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnualExpense>
 */
class AnnualExpenseFactory extends Factory
{
    /**
     * Default: spesa fissa annuale (es. Commercialista) con una quota. Le
     * istanze sono copie indipendenti dal template (RB5).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'year_id' => Year::factory(),
            'expense_item_id' => null,
            'name' => fake()->unique()->words(2, true),
            'calculation_type' => ExpenseCalculationType::FixedAnnual,
            'rate' => null,
            'minimum' => null,
            'maximum' => null,
            'amount' => 300.00,
            'effective_amount' => null,
            'previous_year_credit' => null,
        ];
    }

    /**
     * Voce percentuale sul reddito IRPEF (es. Imposta sostitutiva, Inarcassa
     * Soggettivo).
     */
    public function percentageOfIrpef(): static
    {
        return $this->state(fn () => [
            'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
            'rate' => 15.00,
            'amount' => null,
        ]);
    }

    /**
     * Voce Imposta sostitutiva con credito anno precedente precompilato.
     */
    public function impostaSostitutiva(float $previousYearCredit = 0.0): static
    {
        return $this->state(fn () => [
            'name' => 'Imposta sostitutiva',
            'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
            'rate' => 5.00,
            'amount' => null,
            'previous_year_credit' => $previousYearCredit,
        ]);
    }
}
