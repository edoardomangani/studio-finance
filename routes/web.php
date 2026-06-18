<?php

use App\Http\Controllers\AnnualExpenseController;
use App\Http\Controllers\ArchivioController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeadlineController;
use App\Http\Controllers\ImportXmlController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\YearController;
use App\Services\ArchiveService;
use Illuminate\Support\Facades\Route;

// Ingresso app: niente landing. Loggato → dashboard, altrimenti → login.
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

// Service worker. È servito da dentro /build (così gli URL relativi del SW
// verso workbox e gli asset precache risolvono correttamente), ma a un path
// che NON esiste come file statico: il web server fa fallthrough a Laravel,
// che aggiunge `Service-Worker-Allowed: /` per dargli scope sull'intera app.
// Il file reale è generato da vite-plugin-pwa in public/build/sw.js.
Route::get('build/serviceworker.js', function () {
    $path = public_path('build/sw.js');

    abort_unless(file_exists($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ]);
})->name('sw');

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding: NON protetto da EnsureOnboarded (loop). Il controller fa
    // la redirect inversa se l'utente è già onboarded.
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    Route::middleware(['onboarded'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
        Route::get('years/create', [YearController::class, 'create'])
            ->name('years.create')
            ->middleware('throttle:60,1');
        // Confronto pluriennale (vista report secondaria). Statica → PRIMA del
        // segmento dinamico `years/{year}`.
        Route::get('years/compare', [YearController::class, 'compare'])
            ->name('years.compare')
            ->middleware('throttle:60,1');
        // Ingresso sezione: redirect all'anno corrente (o confronto se vuoto).
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

        // Spese annuali dalla vista anno: creazione una-tantum (costo specifico
        // dell'anno), modifica valori (correzione post-apertura), eliminazione
        // (solo una-tantum senza pagamenti). Le voci ricorrenti restano i template.
        Route::post('annual-expenses', [AnnualExpenseController::class, 'store'])
            ->name('annual-expenses.store')
            ->middleware('throttle:60,1');
        Route::patch('annual-expenses/{annualExpense}', [AnnualExpenseController::class, 'update'])
            ->name('annual-expenses.update')
            ->middleware('throttle:60,1');
        Route::delete('annual-expenses/{annualExpense}', [AnnualExpenseController::class, 'destroy'])
            ->name('annual-expenses.destroy')
            ->middleware('throttle:60,1');

        // Scadenze — vista cronologica pluriennale (lista, importo previsto per
        // riga). Lo show (side-sheet) e la registrazione pagamento arrivano nei
        // passi successivi della fase. URL in inglese, label sidebar in italiano.
        Route::get('deadlines', [DeadlineController::class, 'index'])
            ->name('deadlines.index')
            ->middleware('throttle:60,1');
        // Creazione scadenza ad-hoc (non da template): obbligo imprevisto.
        Route::post('deadlines', [DeadlineController::class, 'store'])
            ->name('deadlines.store')
            ->middleware('throttle:60,1');
        // Modifica (nome/data sempre, spesa solo se ad-hoc non pagata) e
        // archiviazione (solo ad-hoc, solo se non pagata).
        Route::patch('deadlines/{deadline}', [DeadlineController::class, 'update'])
            ->name('deadlines.update')
            ->middleware('throttle:60,1');
        Route::delete('deadlines/{deadline}', [DeadlineController::class, 'destroy'])
            ->name('deadlines.destroy')
            ->middleware('throttle:60,1');
        // Registrazione pagamento dal side-sheet: planned→paid, open→completed.
        Route::post('deadlines/{deadline}/payment', [DeadlineController::class, 'registerPayment'])
            ->name('deadlines.payment')
            ->middleware('throttle:60,1');
        // Reversibilità (F9): riapri (da completata/non dovuta) e marca non dovuta.
        Route::post('deadlines/{deadline}/reopen', [DeadlineController::class, 'reopen'])
            ->name('deadlines.reopen')
            ->middleware('throttle:60,1');
        Route::post('deadlines/{deadline}/mark-not-due', [DeadlineController::class, 'markNotDue'])
            ->name('deadlines.mark-not-due')
            ->middleware('throttle:60,1');
        // Adempimento svolto: open→completed (solo kind=fulfillment).
        Route::post('deadlines/{deadline}/fulfill', [DeadlineController::class, 'markFulfilled'])
            ->name('deadlines.fulfill')
            ->middleware('throttle:60,1');

        // Pagamenti — vista pluriennale (RB9) + registrazione manuale extra-
        // scadenza (F8). URL in inglese, label sidebar in italiano. Lo store
        // crea cassa reale: stesso throttle umano delle altre creazioni (60,1).
        Route::get('payments', [PaymentController::class, 'index'])
            ->name('payments.index')
            ->middleware('throttle:60,1');
        Route::post('payments', [PaymentController::class, 'store'])
            ->name('payments.store')
            ->middleware('throttle:60,1');
        // Modifica/elimina: SOLO pagamenti manuali (i pagamenti da scadenza si
        // gestiscono dalla scadenza, che possiede il ciclo di vita). Il guard
        // è nel controller; il route-model binding applica la tenancy.
        Route::patch('payments/{payment}', [PaymentController::class, 'update'])
            ->name('payments.update')
            ->middleware('throttle:60,1');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])
            ->name('payments.destroy')
            ->middleware('throttle:60,1');

        // Archivio (F11): record archiviati (soft delete) di tutte le entità,
        // con ripristino. Il `type` è validato da una whitelist nel service.
        Route::get('archivio', [ArchivioController::class, 'index'])
            ->name('archivio.index')
            ->middleware('throttle:60,1');
        Route::post('archivio/{type}/{id}/restore', [ArchivioController::class, 'restore'])
            ->name('archivio.restore')
            ->whereNumber('id')
            ->whereIn('type', ArchiveService::types())
            ->middleware('throttle:60,1');

        // Account (mobile): pagina-menu raggiunta dall'avatar nel topbar sotto
        // lg. Aggrega profilo/sicurezza/aspetto + impostazioni di sistema +
        // archivio + logout. Solo rendering: i link puntano a route esistenti.
        Route::inertia('account', 'account/Index')->name('account');

        // Design system page: showroom dei componenti, accessibile solo in dev.
        // In production la rotta non esiste affatto.
        if (app()->environment('local')) {
            Route::inertia('design-system', 'DesignSystem')->name('design-system');
        }
    });
});

require __DIR__.'/settings.php';
