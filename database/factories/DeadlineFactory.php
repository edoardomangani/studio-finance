<?php

namespace Database\Factories;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\User;
use App\Models\Year;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deadline>
 */
class DeadlineFactory extends Factory
{
    /**
     * Default: adempimento aperto, nessun pagamento collegato.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'year_id' => Year::factory(),
            'recurring_deadline_id' => null,
            'name' => fake()->unique()->words(2, true),
            'due_at' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'kind' => DeadlineKind::Fulfillment,
            'annual_expense_id' => null,
            'status' => DeadlineStatus::Open,
        ];
    }

    /**
     * Scadenza di pagamento (richiede un annual_expense_id valorizzato dal
     * chiamante o dalla relazione).
     */
    public function payment(): static
    {
        return $this->state(fn () => ['kind' => DeadlineKind::Payment]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => DeadlineStatus::Completed]);
    }

    public function notDue(): static
    {
        return $this->state(fn () => ['status' => DeadlineStatus::NotDue]);
    }
}
