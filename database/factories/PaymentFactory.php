<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Default: pagamento pianificato (generato dal wizard), importo e data
     * vuoti, non collegato a scadenza. Non concorre ai totali (RB9).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'annual_expense_id' => AnnualExpense::factory(),
            'deadline_id' => null,
            'description' => null,
            'amount' => null,
            'paid_at' => null,
            'status' => PaymentStatus::Planned,
        ];
    }

    /**
     * Pagamento registrato: importo e data effettiva valorizzati, stato pagato.
     */
    public function paid(float $amount = 1000.00): static
    {
        return $this->state(fn () => [
            'amount' => $amount,
            'paid_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => PaymentStatus::Paid,
        ]);
    }

    public function notDue(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::NotDue]);
    }
}
