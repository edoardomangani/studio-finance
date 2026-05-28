<?php

namespace Database\Factories;

use App\Enums\TipoCalcoloVoceSpesa;
use App\Models\User;
use App\Models\VoceSpesa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoceSpesa>
 */
class VoceSpesaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome' => fake()->unique()->words(2, true),
            'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale,
            'aliquota_default' => null,
            'minimale_default' => null,
            'massimale_default' => null,
            'quota_default' => 500.00,
            'attiva' => true,
            'ordine' => 0,
        ];
    }

    public function percentualeReddito(): static
    {
        return $this->state(fn () => [
            'tipo_calcolo' => TipoCalcoloVoceSpesa::PercRedditoIrpef,
            'aliquota_default' => 15.00,
            'quota_default' => null,
        ]);
    }

    public function inattiva(): static
    {
        return $this->state(fn () => ['attiva' => false]);
    }
}
