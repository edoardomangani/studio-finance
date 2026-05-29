<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Year;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Year>
 */
class YearFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'year' => fake()->numberBetween(2020, 2026),
            'profitability_coefficient' => 78.00,
            'pre_opened' => false,
            'note' => null,
        ];
    }

    /**
     * Anno specifico (utile per testare l'unicità per utente e i flussi
     * cross-year N / N+1).
     */
    public function forYear(int $year): static
    {
        return $this->state(fn () => ['year' => $year]);
    }

    /**
     * Anno "pre-aperto" (creato implicitamente per coprire una scadenza
     * cross-year, RB10).
     */
    public function preOpened(): static
    {
        return $this->state(fn () => ['pre_opened' => true]);
    }
}
