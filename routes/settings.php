<?php

use App\Http\Controllers\Settings\ProfessionalController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ScadenzeTipoController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\VociSpesaController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings personali (unica pagina monolite)
|--------------------------------------------------------------------------
| Tutte le impostazioni personali vivono in /settings/profile come singola
| pagina con sezioni FormSection (Profilo professionale, Anagrafica,
| Sicurezza, Aspetto). Niente sub-sidebar interna.
|
| Le route legacy /settings/security e /settings/appearance fanno redirect
| a /settings/profile?tab=<nome> per gestire deep-link e bookmark.
*/

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Legacy deep-link → tab della pagina monolite. Niente RequirePassword
    // qui (la conferma password viva sui veri endpoint sensibili come
    // user-password.update / two-factor.* / passkeys.*, già configurati da
    // Fortify); proteggere solo la closure di redirect sarebbe teatro.
    Route::get('settings/security', fn (): RedirectResponse => redirect('/settings/profile?tab=security'))
        ->name('security.edit');

    Route::get('settings/appearance', fn (): RedirectResponse => redirect('/settings/profile?tab=appearance'))
        ->name('appearance.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    // Profilo professionale: editato dentro la pagina monolite,
    // submit dedicato. Richiede onboarding completato.
    Route::middleware(['onboarded'])->group(function () {
        Route::get('settings/professional', fn (): RedirectResponse => redirect('/settings/profile?tab=professional'))
            ->name('professional.edit');
        Route::patch('settings/professional', [ProfessionalController::class, 'update'])
            ->name('professional.update');

        // Cataloghi di sistema — voci di spesa template (Index.vue dedicato,
        // CTA in topbar, sidebar in modalità settings).
        Route::get('settings/voci-spesa', [VociSpesaController::class, 'index'])
            ->name('settings.voci-spesa.index');
        Route::post('settings/voci-spesa', [VociSpesaController::class, 'store'])
            ->name('settings.voci-spesa.store');
        Route::patch('settings/voci-spesa/{voceSpesa}', [VociSpesaController::class, 'update'])
            ->name('settings.voci-spesa.update');
        Route::delete('settings/voci-spesa/{voceSpesa}', [VociSpesaController::class, 'destroy'])
            ->name('settings.voci-spesa.destroy');

        // Scadenze tipo template (Index.vue dedicato).
        Route::get('settings/scadenze-tipo', [ScadenzeTipoController::class, 'index'])
            ->name('settings.scadenze-tipo.index');
        Route::post('settings/scadenze-tipo', [ScadenzeTipoController::class, 'store'])
            ->name('settings.scadenze-tipo.store');
        Route::patch('settings/scadenze-tipo/{scadenzaTipo}', [ScadenzeTipoController::class, 'update'])
            ->name('settings.scadenze-tipo.update');
        Route::delete('settings/scadenze-tipo/{scadenzaTipo}', [ScadenzeTipoController::class, 'destroy'])
            ->name('settings.scadenze-tipo.destroy');
    });
});
