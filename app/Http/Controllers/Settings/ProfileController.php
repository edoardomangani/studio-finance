<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use App\Services\YearService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class ProfileController extends Controller
{
    use FlashesToast;

    /**
     * Pagina monolite delle impostazioni personali: anagrafica + sicurezza
     * (password, 2FA, passkeys) + aspetto + profilo professionale.
     */
    public function edit(TwoFactorAuthenticationRequest $request, YearService $years): Response
    {
        $user = $request->user();

        $props = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'professionalProfile' => $user->professionalProfile,
            // Anni esistenti per la checklist di propagazione (F11); vuoto se
            // non onboarded.
            'professionalYears' => $user->professionalProfile
                ? $years->forPropagationChecklist()
                : [],
            ...$this->securityProps($user),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $user->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('settings/Profile', $props);
    }

    /**
     * Aggiorna anagrafica utente (nome + email). Se l'email cambia,
     * email_verified_at viene azzerato e l'utente dovrà ri-verificare.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $this->flashSuccess('Anagrafica aggiornata.');

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Props per la sezione "Sicurezza" della pagina monolite: passkeys
     * + flag feature + password rules. La parte 2FA arriva separata in
     * edit() perché richiede ensureStateIsValid sul request.
     *
     * @return array<string, mixed>
     */
    private function securityProps(User $user): array
    {
        return [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys()
                ? $this->mapPasskeys($user)
                : [],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapPasskeys(User $user): array
    {
        return $user->passkeys()
            // `authenticator` è un accessor derivato dal JSON `credential`,
            // non serve includerlo nella SELECT.
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->values()
            ->all();
    }
}
