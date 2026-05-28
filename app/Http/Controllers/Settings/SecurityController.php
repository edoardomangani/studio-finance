<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\FlashesToast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;

class SecurityController extends Controller
{
    use FlashesToast;

    /**
     * Update the user's password. La pagina UI di sicurezza vive dentro
     * settings/Profile.vue (sezione "Sicurezza"): qui solo l'update endpoint.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        $this->flashSuccess('Password aggiornata.');

        return back();
    }
}
