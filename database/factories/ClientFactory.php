<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            // P.IVA italiana fake: 11 cifre.
            'vat_number' => (string) fake()->numerify('###########'),
            'tax_code' => null,
            'bank_withholding' => false,
            'notes' => null,
        ];
    }

    public function withTaxCode(): static
    {
        return $this->state(fn () => [
            'vat_number' => null,
            'tax_code' => fake()->bothify('??????##?##?###?'),
        ]);
    }

    public function withBankWithholding(): static
    {
        return $this->state(fn () => ['bank_withholding' => true]);
    }
}
