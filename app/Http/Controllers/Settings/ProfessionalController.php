<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Studiofinance\PropagateProfile;
use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PropagateProfileRequest;
use App\Http\Requests\Settings\UpdateProfessionalProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Profilo professionale — modifica post-onboarding di nome, coefficiente di
 * redditività e anno di inizio attività. La UI vive in settings/Profile.vue
 * (tab "Profilo professionale"): update endpoint + propagazione (F11) agli
 * anni esistenti tramite dialog di conferma con checklist.
 */
class ProfessionalController extends Controller
{
    use FlashesToast;

    public function update(UpdateProfessionalProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Nome professionista = users.name (single source of truth). Email
        // pure su users. Il resto sulla tabella professional_profiles.
        // Wrapped in transaction: se l'update di professionalProfile fallisce
        // (lock, DB validation), rollback anche dell'update User per evitare
        // stati inconsistenti.
        DB::transaction(function () use ($user, $data): void {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $user->loadMissing('professionalProfile');
            $user->professionalProfile->update([
                'profitability_coefficient' => $data['profitability_coefficient'],
                'business_start_year' => $data['business_start_year'],
            ]);
        });

        $this->flashSuccess('Profilo professionale aggiornato.');

        return to_route('profile.edit');
    }

    /**
     * Propaga coefficiente e/o anno-inizio (già salvati sul profilo dall'update)
     * agli anni selezionati nella checklist del dialog F11.
     */
    public function propagate(PropagateProfileRequest $request, PropagateProfile $action): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $user->loadMissing('professionalProfile');

        $action->handle(
            $user->professionalProfile,
            $data['year_ids'],
            $data['coefficient'],
            $data['start_year'],
        );

        $this->flashSuccess('Modifiche propagate agli anni selezionati.');

        return to_route('profile.edit');
    }
}
