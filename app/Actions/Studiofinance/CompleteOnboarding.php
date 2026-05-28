<?php

namespace App\Actions\Studiofinance;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Database\Seeders\StudiofinanceTemplatesSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Action atomica di completamento onboarding:
 * - sincronizza User.name (nome professionista = nome account)
 * - crea il ProfessionalProfile con i dati fiscali (coefficiente, anno)
 * - seed dei template iniziali (voci di spesa + scadenze tipo) per l'utente.
 *
 * Tutto in una singola transazione: se il seed fallisce, niente profilo a
 * metà, niente template orfani.
 *
 * @phpstan-type OnboardingPayload array{nome: string, coefficiente_redditivita: float|int|string, anno_inizio_attivita: int}
 */
class CompleteOnboarding
{
    /**
     * @param  OnboardingPayload  $payload
     */
    public function __invoke(User $user, array $payload): ProfessionalProfile
    {
        return DB::transaction(function () use ($user, $payload): ProfessionalProfile {
            $user->forceFill(['name' => $payload['nome']])->save();

            $profile = $user->professionalProfile()->create([
                'coefficiente_redditivita' => $payload['coefficiente_redditivita'],
                'anno_inizio_attivita' => (int) $payload['anno_inizio_attivita'],
            ]);

            (new StudiofinanceTemplatesSeeder)->seedForUser($user);

            return $profile;
        });
    }
}
