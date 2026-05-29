<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ImportXmlController;
use App\Http\Controllers\InvoiceController;
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

        // Fatture — CRUD completo come pagina dedicata (la Create vive su
        // /invoices/create, dialog troppo stretto per 8 campi). Rate limit
        // identico ai clienti: 60 req/min per utente. Soft delete = archivia.
        //
        // Import XML: rotte DEFINITE PRIMA del resource per evitare la
        // collisione `/invoices/import` ↔ `/invoices/{invoice}`. Throttle
        // differenziato: la GET show è una pagina come le altre
        // (throttle:60,1 standard); parse + store processano file utente
        // o batch creano fatture, quindi bucket più stretto (10,1).
        Route::get('invoices/import', [ImportXmlController::class, 'show'])
            ->name('invoices.import.show')
            ->middleware('throttle:60,1');
        Route::post('invoices/import/parse', [ImportXmlController::class, 'parse'])
            ->name('invoices.import.parse')
            ->middleware('throttle:10,1');
        Route::post('invoices/import', [ImportXmlController::class, 'store'])
            ->name('invoices.import.store')
            ->middleware('throttle:10,1');

        Route::resource('invoices', InvoiceController::class)
            ->middleware('throttle:60,1');

        // Design system page: showroom dei componenti, accessibile solo in dev.
        // In production la rotta non esiste affatto.
        if (app()->environment('local')) {
            Route::inertia('design-system', 'DesignSystem')->name('design-system');
        }
    });
});

require __DIR__.'/settings.php';
