<?php

namespace Database\Factories;

use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseYearOffset;
use App\Models\RecurringDeadline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringDeadline>
 */
class RecurringDeadlineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'day' => fake()->numberBetween(1, 28),
            'month' => fake()->numberBetween(1, 12),
            'kind' => DeadlineKind::Fulfillment,
            'expense_item_id' => null,
            'due_year_offset' => DueYearOffset::Current,
            'expense_year_offset' => ExpenseYearOffset::Current,
            'active' => true,
        ];
    }

    public function payment(): static
    {
        return $this->state(fn () => ['kind' => DeadlineKind::Payment]);
    }

    public function dueNextYear(): static
    {
        return $this->state(fn () => [
            'due_year_offset' => DueYearOffset::Next,
        ]);
    }

    public function expenseNextYear(): static
    {
        return $this->state(fn () => [
            'expense_year_offset' => ExpenseYearOffset::Next,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
