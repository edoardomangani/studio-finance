<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DeadlineController;
use App\Http\Controllers\ImportXmlController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\YearController;
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

        // Anni — vista pluriennale, piano di apertura (JSON per il wizard-
        // dialog), apertura, vista anno. Le rotte statiche (index, plan, store)
        // sono definite PRIMA di `years/{year}` per evitare la collisione con
        // il segmento dinamico; `whereNumber` sul parametro chiude il vincolo.
        // URL segment in inglese (convenzione progetto); label sidebar in
        // italiano. Store con throttle più stretto (apre un anno = transazione
        // pesante, non spammabile).
        Route::get('years/plan', [YearController::class, 'plan'])
            ->name('years.plan')
            ->middleware('throttle:60,1');
        Route::get('years', [YearController::class, 'index'])
            ->name('years.index')
            ->middleware('throttle:60,1');
        Route::post('years', [YearController::class, 'store'])
            ->name('years.store')
            ->middleware('throttle:20,1');
        Route::get('years/{year}', [YearController::class, 'show'])
            ->whereNumber('year')
            ->name('years.show')
            ->middleware('throttle:60,1');

        // Scadenze — vista cronologica pluriennale (lista, importo previsto per
        // riga). Lo show (side-sheet) e la registrazione pagamento arrivano nei
        // passi successivi della fase. URL in inglese, label sidebar in italiano.
        Route::get('deadlines', [DeadlineController::class, 'index'])
            ->name('deadlines.index')
            ->middleware('throttle:60,1');

        // Design system page: showroom dei componenti, accessibile solo in dev.
        // In production la rotta non esiste affatto.
        if (app()->environment('local')) {
            Route::inertia('design-system', 'DesignSystem')->name('design-system');
        }
    });
});

require __DIR__.'/settings.php';
