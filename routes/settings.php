<?php

use App\Http\Controllers\Settings\ExpenseFamilyController;
use App\Http\Controllers\Settings\ExpenseItemController;
use App\Http\Controllers\Settings\ProfessionalController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RecurringDeadlineController;
use App\Http\Controllers\Settings\SecurityController;
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

        // Cataloghi di sistema — expense items template (Index.vue dedicato,
        // CTA in topbar, sidebar in modalità settings).
        Route::get('settings/expense-items', [ExpenseItemController::class, 'index'])
            ->name('settings.expense-items.index');
        Route::post('settings/expense-items', [ExpenseItemController::class, 'store'])
            ->name('settings.expense-items.store');
        Route::patch('settings/expense-items/{expenseItem}', [ExpenseItemController::class, 'update'])
            ->name('settings.expense-items.update');
        Route::delete('settings/expense-items/{expenseItem}', [ExpenseItemController::class, 'destroy'])
            ->name('settings.expense-items.destroy');

        // Famiglie di spesa: i 4 tipi fissi, solo rinominabili.
        Route::get('settings/families', [ExpenseFamilyController::class, 'index'])
            ->name('settings.families.index');
        Route::patch('settings/families/{expenseFamily}', [ExpenseFamilyController::class, 'update'])
            ->name('settings.families.update');

        // Recurring deadlines template (Index.vue dedicato).
        Route::get('settings/recurring-deadlines', [RecurringDeadlineController::class, 'index'])
            ->name('settings.recurring-deadlines.index');
        Route::post('settings/recurring-deadlines', [RecurringDeadlineController::class, 'store'])
            ->name('settings.recurring-deadlines.store');
        Route::patch('settings/recurring-deadlines/{recurringDeadline}', [RecurringDeadlineController::class, 'update'])
            ->name('settings.recurring-deadlines.update');
        Route::delete('settings/recurring-deadlines/{recurringDeadline}', [RecurringDeadlineController::class, 'destroy'])
            ->name('settings.recurring-deadlines.destroy');
    });
});
