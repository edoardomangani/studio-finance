<?php

namespace App\Actions\Studiofinance;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Database\Seeders\StudiofinanceTemplatesSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Action atomica di completamento onboarding:
 * - sincronizza users.name (nome professionista = nome account)
 * - crea il ProfessionalProfile con i dati fiscali
 * - seeda i template iniziali (expense items + recurring deadlines).
 *
 * Tutto in una singola transazione: se il seed fallisce, niente profilo a
 * metà, niente template orfani.
 *
 * @phpstan-type OnboardingPayload array{name: string, profitability_coefficient: float|int|string, business_start_year: int}
 */
class CompleteOnboarding
{
    /**
     * @param  OnboardingPayload  $payload
     */
    public function __invoke(User $user, array $payload): ProfessionalProfile
    {
        return DB::transaction(function () use ($user, $payload): ProfessionalProfile {
            $user->update(['name' => $payload['name']]);

            $profile = $user->professionalProfile()->create([
                'profitability_coefficient' => $payload['profitability_coefficient'],
                'business_start_year' => (int) $payload['business_start_year'],
            ]);

            (new StudiofinanceTemplatesSeeder)->seedForUser($user);

            return $profile;
        });
    }
}
