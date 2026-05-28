<?php

namespace App\Actions\Studiofinance;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action atomica di completamento onboarding:
 * - sincronizza User.name (nome professionista = nome account)
 * - crea il ProfessionalProfile con i dati fiscali (coefficiente, anno).
 *
 * In Fase 2 verrà esteso per chiamare lo StudiofinanceTemplatesSeeder
 * (8 voci di spesa + scadenze tipo) nella stessa transazione.
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

            return $user->professionalProfile()->create([
                'coefficiente_redditivita' => $payload['coefficiente_redditivita'],
                'anno_inizio_attivita' => (int) $payload['anno_inizio_attivita'],
            ]);
        });
    }
}
