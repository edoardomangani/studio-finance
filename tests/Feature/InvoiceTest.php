<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Services\InvoiceService;

function onboardedInvoiceUser(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

it('crea una fattura tramite factory associata a un cliente', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user);

    $invoice = Invoice::factory()->for($client)->create();

    expect($invoice->user_id)->toBe($user->id)
        ->and($invoice->client_id)->toBe($client->id)
        ->and($invoice->client->name)->toBe($client->name);
});

it('forza user_id sull\'auth corrente anche se passato esplicitamente (no IDOR)', function (): void {
    $owner = onboardedInvoiceUser();
    $attacker = onboardedInvoiceUser();
    $client = Client::factory()->for($owner)->create();

    $this->actingAs($attacker);

    $invoice = Invoice::factory()->for($client)->create(['user_id' => $owner->id]);

    expect($invoice->user_id)->toBe($attacker->id);
});

it('scopa le query al solo utente loggato (global scope)', function (): void {
    $alice = onboardedInvoiceUser();
    $bob = onboardedInvoiceUser();

    $this->actingAs($alice);
    Invoice::factory()->for(Client::factory()->for($alice))->count(2)->create();

    $this->actingAs($bob);
    Invoice::factory()->for(Client::factory()->for($bob))->count(1)->create();

    expect(Invoice::count())->toBe(1);

    $this->actingAs($alice);
    expect(Invoice::count())->toBe(2);
});

it('calcola total come imponibile + cassa + bollo + art.15', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $invoice = Invoice::factory()->for(Client::factory()->for($user))->create([
        'amount' => 1000.00,
        'inarcassa_amount' => 40.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 10.00,
    ]);

    expect($invoice->total)->toBe('1052.00');
});

it('calcola withholding_amount scorporando l\'IVA, con aliquota per data', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $base = [
        'issued_at' => '2026-03-15',
        'amount' => 1000.00,
        'inarcassa_amount' => 40.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
    ];

    $withRitenuta = Invoice::factory()->for(Client::factory()->for($user))->create(
        [...$base, 'bank_withholding' => true],
    );
    $senza = Invoice::factory()->for(Client::factory()->for($user))->create(
        [...$base, 'bank_withholding' => false],
    );

    // total = 1042 → 1042 / 1.22 × 11% (dal 1/3/2024) = 93.95
    expect($withRitenuta->withholding_amount)->toBe('93.95')
        ->and($senza->withholding_amount)->toBe('0.00');
});

it('soft-delete del cliente non cancella le sue fatture', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($client)->create();

    $client->delete();

    expect(Invoice::find($invoice->id))->not->toBeNull()
        ->and(Client::withTrashed()->find($client->id)->trashed())->toBeTrue();
});

it('store: crea fattura con payload valido', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/invoices', [
            'number' => '2026/001',
            'issued_at' => '2026-03-15',
            'client_id' => $client->id,
            'amount' => 1000.00,
            'inarcassa_amount' => 40.00,
            'stamp_amount' => 2.00,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ])
        ->assertRedirect();

    expect(Invoice::count())->toBe(1);
    $invoice = Invoice::first();
    expect($invoice->number)->toBe('2026/001')
        ->and($invoice->client_id)->toBe($client->id);
});

it('store: rifiuta numero duplicato nello stesso anno', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    Invoice::factory()->for($client)->create([
        'number' => '2026/001',
        'issued_at' => '2026-03-15',
    ]);

    $this->post('/invoices', [
        'number' => '2026/001',
        'issued_at' => '2026-09-20',
        'client_id' => $client->id,
        'amount' => 500.00,
        'inarcassa_amount' => 20.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
        'bank_withholding' => false,
    ])->assertSessionHasErrors(['number']);

    expect(Invoice::count())->toBe(1);
});

it('store: ammette stesso numero in anni diversi', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    Invoice::factory()->for($client)->create([
        'number' => '001',
        'issued_at' => '2025-12-31',
    ]);

    $this->post('/invoices', [
        'number' => '001',
        'issued_at' => '2026-01-02',
        'client_id' => $client->id,
        'amount' => 500.00,
        'inarcassa_amount' => 20.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
        'bank_withholding' => false,
    ])->assertSessionDoesntHaveErrors();

    expect(Invoice::count())->toBe(2);
});

it('store: rifiuta client_id di altro utente (no IDOR via FK)', function (): void {
    $alice = onboardedInvoiceUser();
    $bob = onboardedInvoiceUser();
    $bobClient = Client::factory()->for($bob)->create();

    $this->actingAs($alice)
        ->post('/invoices', [
            'number' => '2026/001',
            'issued_at' => '2026-03-15',
            'client_id' => $bobClient->id,
            'amount' => 1000.00,
            'inarcassa_amount' => 40.00,
            'stamp_amount' => 2.00,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ])
        ->assertSessionHasErrors(['client_id']);

    expect(Invoice::count())->toBe(0);
});

it('update: ammette stesso numero della fattura corrente (esclude se stessa dal check)', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->for($client)->create([
        'number' => '2026/001',
        'issued_at' => '2026-03-15',
    ]);

    $this->patch("/invoices/{$invoice->id}", [
        'number' => '2026/001',
        'issued_at' => '2026-03-15',
        'client_id' => $client->id,
        'amount' => 1500.00,
        'inarcassa_amount' => 60.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
        'bank_withholding' => false,
    ])->assertSessionDoesntHaveErrors();

    expect($invoice->fresh()->amount)->toEqual(1500.00);
});

it('destroy: archivia (soft delete) la fattura', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $invoice = Invoice::factory()
        ->for(Client::factory()->for($user))
        ->create();

    $this->delete("/invoices/{$invoice->id}")
        ->assertRedirect('/invoices');

    expect(Invoice::find($invoice->id))->toBeNull()
        ->and(Invoice::withTrashed()->find($invoice->id))->not->toBeNull();
});

it('index: rende la pagina con paginazione + filtri + lista anni', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();
    Invoice::factory()->for($client)->count(3)->inYear(2026)->create();
    Invoice::factory()->for($client)->inYear(2025)->create();

    $this->get('/invoices')
        ->assertInertia(fn ($p) => $p
            ->component('invoices/Index')
            ->has('invoices.data', 4)
            ->has('availableYears', 2)
            ->has('clientsForFilter')
            ->where('filters.year', [])
            ->where('filters.client_id', null),
        );
});

it('index: filtra per anno', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();
    Invoice::factory()->for($client)->count(2)->inYear(2026)->create();
    Invoice::factory()->for($client)->count(3)->inYear(2025)->create();

    $this->get('/invoices?year=2025')
        ->assertInertia(fn ($p) => $p
            ->has('invoices.data', 3)
            ->where('filters.year', [2025]), // faccetta multi-select (scalare normalizzato ad array)
        );
});

it('show: rende la pagina con dati cliente + totale derivato', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($client)->create([
        'issued_at' => '2026-03-15',
        'amount' => 1000.00,
        'inarcassa_amount' => 40.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
        'bank_withholding' => true,
    ]);

    $this->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($p) => $p
            ->component('invoices/Show')
            ->where('invoice.id', $invoice->id)
            // JSON normalizza 1042.0 → 1042 (no fractional). Confronto loose
            // via callback per accettare entrambe le rappresentazioni.
            ->where('invoice.total', fn ($v) => (float) $v === 1042.0)
            ->where('invoice.withholding_amount', fn ($v) => (float) $v === 93.95)
            ->where('invoice.client.name', $client->name),
        );
});

it('client show: ora include lo storico fatturato in DESC issued_at', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();
    Invoice::factory()->for($client)->create([
        'number' => '2025/001',
        'issued_at' => '2025-06-15',
    ]);
    Invoice::factory()->for($client)->create([
        'number' => '2026/001',
        'issued_at' => '2026-02-10',
    ]);

    $this->get("/clients/{$client->id}")
        ->assertInertia(fn ($p) => $p
            ->component('clients/Show')
            ->has('invoices', 2)
            // La più recente in cima (DESC su issued_at).
            ->where('invoices.0.number', '2026/001')
            ->where('invoices.1.number', '2025/001'),
        );
});

it('harden: importo 0 ammesso (RB3 dice >=0)', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/invoices', [
            'number' => '2026/001',
            'issued_at' => '2026-03-15',
            'client_id' => $client->id,
            'amount' => 0.00,
            'inarcassa_amount' => 0.00,
            'stamp_amount' => 0.00,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ])
        ->assertSessionDoesntHaveErrors();

    expect(Invoice::count())->toBe(1);
    expect((float) Invoice::first()->total)->toBe(0.0);
});

it('harden: numero con caratteri speciali (slash, dash, lettere) accettato', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    foreach (['2026/001', 'INV-2026-A1', 'A/1.001', 'PA 2026/12'] as $number) {
        $this->post('/invoices', [
            'number' => $number,
            'issued_at' => '2026-01-'.str_pad((string) random_int(1, 28), 2, '0', STR_PAD_LEFT),
            'client_id' => $client->id,
            'amount' => 100.00,
            'inarcassa_amount' => 4.00,
            'stamp_amount' => 2.00,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ])->assertSessionDoesntHaveErrors();
    }

    expect(Invoice::count())->toBe(4);
});

it('harden: payload sopra il max (>9.999.999,99) viene rifiutato', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/invoices', [
            'number' => '2026/001',
            'issued_at' => '2026-03-15',
            'client_id' => $client->id,
            'amount' => 10_000_000.00,
            'inarcassa_amount' => 0.00,
            'stamp_amount' => 0.00,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ])
        ->assertSessionHasErrors(['amount']);
});

it('harden: importo negativo rifiutato', function (): void {
    $user = onboardedInvoiceUser();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/invoices', [
            'number' => '2026/001',
            'issued_at' => '2026-03-15',
            'client_id' => $client->id,
            'amount' => -1.00,
            'inarcassa_amount' => 0.00,
            'stamp_amount' => 0.00,
            'art_15_amount' => 0.00,
            'bank_withholding' => false,
        ])
        ->assertSessionHasErrors(['amount']);
});

it('harden: client archiviato non compare nel ClientPicker (clientsForPicker)', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    $active = Client::factory()->for($user)->create(['name' => 'Cliente Attivo']);
    $archived = Client::factory()->for($user)->create(['name' => 'Cliente Archiviato']);
    $archived->delete(); // soft delete

    $this->get('/invoices/create')
        ->assertInertia(fn ($p) => $p
            ->component('invoices/Create')
            ->has('clients', 1)
            ->where('clients.0.id', $active->id),
        );
});

it('harden: deep link ?client=X inesistente non rompe la pagina (preselectedClientId=null)', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);

    Client::factory()->for($user)->create();

    $this->get('/invoices/create?client=999')
        ->assertInertia(fn ($p) => $p
            ->component('invoices/Create')
            ->where('preselectedClientId', null),
        );
});

it('harden: deep link ?client=X di altro utente non viene accettato', function (): void {
    $alice = onboardedInvoiceUser();
    $bob = onboardedInvoiceUser();
    $bobClient = Client::factory()->for($bob)->create();

    $this->actingAs($alice)
        ->get("/invoices/create?client={$bobClient->id}")
        ->assertInertia(fn ($p) => $p->where('preselectedClientId', null));
});

it('harden: deep link ?client=X valido viene mantenuto', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);
    $client = Client::factory()->for($user)->create();

    $this->get("/invoices/create?client={$client->id}")
        ->assertInertia(fn ($p) => $p->where('preselectedClientId', $client->id));
});

it('naturalSortKey: zero-padda i run di cifre per ordine umano', function (): void {
    expect(Invoice::naturalSortKey('9'))->toBe(str_pad('9', 20, '0', STR_PAD_LEFT))
        ->and(Invoice::naturalSortKey('10'))->toBe(str_pad('10', 20, '0', STR_PAD_LEFT))
        // '9' < '10' lessicograficamente una volta paddati (era il bug).
        ->and(Invoice::naturalSortKey('9') < Invoice::naturalSortKey('10'))->toBeTrue()
        // Suffisso lettere dopo il numero, lowercased.
        ->and(Invoice::naturalSortKey('10A'))->toBe(str_pad('10', 20, '0', STR_PAD_LEFT).'a')
        ->and(Invoice::naturalSortKey('10') < Invoice::naturalSortKey('10A'))->toBeTrue();
});

it('number_sort è derivato da number ad ogni save (anche update)', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);
    $invoice = Invoice::factory()->for(Client::factory()->for($user))->create(['number' => '7B']);

    expect($invoice->fresh()->number_sort)->toBe(Invoice::naturalSortKey('7B'));

    $invoice->update(['number' => '42']);
    expect($invoice->fresh()->number_sort)->toBe(Invoice::naturalSortKey('42'));
});

it('paginate: a parità di data ordina i numeri in modo naturale (10 prima di 9)', function (): void {
    $user = onboardedInvoiceUser();
    $this->actingAs($user);
    $client = Client::factory()->for($user)->create();

    foreach (['9', '10', '10A', '10B', '2'] as $number) {
        Invoice::factory()->for($client)->create([
            'number' => $number,
            'issued_at' => '2026-03-15',
        ]);
    }

    $numbers = app(InvoiceService::class)
        ->paginate()
        ->pluck('number')
        ->all();

    // DESC naturale: 10B, 10A, 10, 9, 2.
    expect($numbers)->toBe(['10B', '10A', '10', '9', '2']);
});
