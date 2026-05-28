<?php

namespace Database\Factories;

use App\Enums\AnnoDataScadenza;
use App\Enums\AnnoRiferimentoSpesa;
use App\Enums\TipoScadenza;
use App\Models\ScadenzaTipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScadenzaTipo>
 */
class ScadenzaTipoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome' => fake()->unique()->words(2, true),
            'giorno' => fake()->numberBetween(1, 28),
            'mese' => fake()->numberBetween(1, 12),
            'tipo' => TipoScadenza::Adempimento,
            'voce_spesa_id' => null,
            'anno_data_scadenza' => AnnoDataScadenza::Corrente,
            'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente,
            'attiva' => true,
        ];
    }

    public function pagamento(): static
    {
        return $this->state(fn () => ['tipo' => TipoScadenza::Pagamento]);
    }

    public function spesaAnnoSuccessivo(): static
    {
        return $this->state(fn () => [
            'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Successivo,
        ]);
    }

    public function dataAnnoSuccessivo(): static
    {
        return $this->state(fn () => [
            'anno_data_scadenza' => AnnoDataScadenza::Successivo,
        ]);
    }

    public function inattiva(): static
    {
        return $this->state(fn () => ['attiva' => false]);
    }
}
