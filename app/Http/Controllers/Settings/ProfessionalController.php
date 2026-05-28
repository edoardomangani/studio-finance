<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfessionalProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Profilo professionale — modifica post-onboarding di nome, coefficiente
 * di redditività e anno di inizio attività. La pagina UI vive dentro
 * settings/Profile.vue (tab "Profilo professionale"): qui solo l'update
 * endpoint, niente edit() Inertia view.
 *
 * La propagazione del coefficiente / anno-inizio agli Anni esistenti
 * arriverà in Fase 10 (F11 specifiche) con un dialog di conferma.
 */
class ProfessionalController extends Controller
{
    use FlashesToast;

    public function update(UpdateProfessionalProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Nome professionista = User.name (single source of truth). Email
        // pure su User. Il resto sulla tabella professional_profiles.
        // Wrapped in transaction: se l'update di professionalProfile fallisce
        // (lock, validation DB, ecc.) rollback anche dell'update User per
        // evitare stati inconsistenti.
        DB::transaction(function () use ($user, $data): void {
            $user->fill([
                'name' => $data['nome'],
                'email' => $data['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $user->loadMissing('professionalProfile');
            $user->professionalProfile->update([
                'coefficiente_redditivita' => $data['coefficiente_redditivita'],
                'anno_inizio_attivita' => $data['anno_inizio_attivita'],
            ]);
        });

        $this->flashSuccess('Profilo professionale aggiornato.');

        return to_route('profile.edit');
    }
}
