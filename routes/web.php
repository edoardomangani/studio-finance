<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding: NON protetto da EnsureOnboarded (loop). Il controller fa
    // la redirect inversa se l'utente è già onboarded.
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    Route::middleware(['onboarded'])->group(function () {
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');

        // Clienti — CRUD completo (resource controller). Soft delete = archivia.
        // Rate limit difensivo (60 req/min per utente loggato) per evitare
        // script che riempiono il DB di clienti senza significato. Le route
        // GET (index/show) hanno lo stesso throttle: i target sono lookup
        // umani, non interrogazioni programmaticamente massicce.
        Route::resource('clients', ClientController::class)
            ->except(['create', 'edit'])
            ->middleware('throttle:60,1');

        // Design system page: showroom dei componenti, accessibile solo in dev.
        // In production la rotta non esiste affatto.
        if (app()->environment('local')) {
            Route::inertia('design-system', 'DesignSystem')->name('design-system');
        }
    });
});

require __DIR__.'/settings.php';
