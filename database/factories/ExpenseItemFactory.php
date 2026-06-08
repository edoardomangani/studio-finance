<?php

namespace Database\Factories;

use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseItem>
 */
class ExpenseItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'calculation_type' => ExpenseCalculationType::FixedAnnual,
            'kind' => ExpenseKind::Fixed,
            'default_rate' => null,
            'default_minimum' => null,
            'default_maximum' => null,
            'default_amount' => 500.00,
            'active' => true,
            'position' => 0,
        ];
    }

    public function irpefPercentage(): static
    {
        return $this->state(fn () => [
            'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
            'default_rate' => 15.00,
            'default_amount' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
