<?php

namespace Database\Factories;

use App\Models\Deadline;
use App\Models\DeadlineNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeadlineNotification>
 */
class DeadlineNotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'deadline_id' => Deadline::factory(),
            'days_until_due' => 7,
            'sent_on' => now('Europe/Rome')->toDateString(),
        ];
    }
}
