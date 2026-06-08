<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Default state: fattura ordinaria senza ritenuta, importi realistici per
     * studio architetto (imponibile 1k–5k). Cassa Inarcassa precompilata a
     * 4% e bollo a €2 — i default applicativi del form (vedi RB3).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 5000);
        $stamp = $amount > 77.47 ? 2.00 : 0.00;

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'number' => (string) fake()->numberBetween(1, 999),
            'issued_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'amount' => $amount,
            // Cassa Inarcassa: base = imponibile + bollo (RB3).
            'inarcassa_amount' => round(($amount + $stamp) * 0.04, 2),
            'stamp_amount' => $stamp,
            // Rivalsa bollo: default a carico del cliente (bollo nel totale).
            'stamp_charged_to_client' => true,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ];
    }

    /**
     * Bollo a carico dello studio: fuori dal totale, ma resta nei bolli da
     * pagare. La base cassa Inarcassa esclude il bollo (solo imponibile).
     */
    public function stampNotChargedToClient(): static
    {
        return $this->state(fn (array $attrs): array => [
            'stamp_charged_to_client' => false,
            'inarcassa_amount' => round(((float) $attrs['amount']) * 0.04, 2),
        ]);
    }

    /**
     * Fattura con ritenuta bancaria 8% attivata.
     */
    public function withBankWithholding(): static
    {
        return $this->state(fn () => ['bank_withholding' => true]);
    }

    /**
     * Fattura emessa in un anno specifico (utile per testare unicità numero
     * scoped per anno e aggregati annuali).
     */
    public function inYear(int $year): static
    {
        return $this->state(fn () => [
            'issued_at' => fake()->dateTimeBetween("$year-01-01", "$year-12-31"),
        ]);
    }
}
