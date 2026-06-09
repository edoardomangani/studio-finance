<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function onboardedImportUser(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

function fatturaXmlContents(string $name): string
{
    return file_get_contents(fixture('FatturaPA/'.$name)) ?: '';
}

it('show: rende la pagina import vuota con clienti per il picker', function (): void {
    $user = onboardedImportUser();
    Client::factory()->for($user)->count(2)->create();

    $this->actingAs($user)
        ->get('/invoices/import')
        ->assertInertia(fn ($p) => $p
            ->component('invoices/Import')
            ->has('clients', 2)
            ->missing('previews'),
        );
});

it('parse: ritorna anteprima parsata + match cliente esistente', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $existing = Client::factory()->for($user)->create([
        'name' => 'Acme Esistente',
        'vat_number' => '09021270013',
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'fattura1.xml',
        fatturaXmlContents('minimal.xml'),
    );

    // parse() ora restituisce back()+flash. Seguiamo il redirect per
    // ispezionare le previews nella render successiva.
    $this->followingRedirects()
        ->post('/invoices/import/parse', ['files' => [$file]])
        ->assertInertia(fn ($p) => $p
            ->component('invoices/Import')
            ->has('previews', 1)
            ->where('previews.0.parsed', true)
            ->where('previews.0.number', '2026/001')
            ->where('previews.0.matched_client_id', $existing->id),
        );
});

it('parse: anteprima non matchata se cliente non esiste', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent(
        'fattura1.xml',
        fatturaXmlContents('minimal.xml'),
    );

    $this->followingRedirects()
        ->post('/invoices/import/parse', ['files' => [$file]])
        ->assertInertia(fn ($p) => $p
            ->where('previews.0.parsed', true)
            ->where('previews.0.matched_client_id', null)
            ->where('previews.0.client_from_xml.vat_number', '09021270013'),
        );
});

it('parse: ritorna parsed=false + error per XML malformato', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent('broken.xml', '<not-fattura/>');

    $this->followingRedirects()
        ->post('/invoices/import/parse', ['files' => [$file]])
        ->assertInertia(fn ($p) => $p
            ->where('previews.0.parsed', false)
            ->has('previews.0.error'),
        );
});

it('parse: rileva discrepanze quando il XML omette bollo e cassa standard', function (): void {
    // minimal.xml ha amount=1000 ma niente DatiBollo né DatiCassaPrevidenziale.
    // Calcolatore standard: bollo €2 (amount > 77,47), cassa (1000+2)×4% = 40.08.
    // Entrambe divergono di > €0.01 → discrepancies su stamp_amount + inarcassa_amount.
    $user = onboardedImportUser();
    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent(
        'fattura1.xml',
        fatturaXmlContents('minimal.xml'),
    );

    $this->followingRedirects()
        ->post('/invoices/import/parse', ['files' => [$file]])
        ->assertInertia(fn ($p) => $p
            ->where('previews.0.parsed', true)
            ->where('previews.0.discrepancies.stamp_amount.xml', 0)
            ->where('previews.0.discrepancies.stamp_amount.expected', 2)
            ->where('previews.0.discrepancies.inarcassa_amount.xml', 0)
            // expectedInarcassa = (amount + stampXml) × 4% — usa il bollo
            // del XML (qui 0), NON il bollo canonico, per non confondere
            // chi ha emesso fattura con override bollo. Vedi commento
            // in ImportXmlController::detectDiscrepancies().
            ->where('previews.0.discrepancies.inarcassa_amount.expected', 40),
        );
});

it('parse: rifiuta >20 file per batch', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $files = collect(range(1, 21))
        ->map(fn ($i) => UploadedFile::fake()->createWithContent("f{$i}.xml", fatturaXmlContents('minimal.xml')))
        ->all();

    $this->post('/invoices/import/parse', ['files' => $files])
        ->assertSessionHasErrors(['files']);
});

it('store: crea fattura usando cliente esistente', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();

    $response = $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/100',
                'issued_at' => '2026-03-15',
                'amount' => 1000.00,
                'inarcassa_amount' => 40.08,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'existing',
                'existing_client_id' => $client->id,
            ],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertStatus(302);

    expect(Invoice::count())->toBe(1);
    expect(Invoice::first()->client_id)->toBe($client->id);
});

it('store: rispetta stamp_charged_to_client=false (bollo fuori dal totale)', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();

    $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/300',
                'issued_at' => '2026-03-15',
                'amount' => 1000.00,
                'inarcassa_amount' => 40.08,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'stamp_charged_to_client' => false,
                'bank_withholding' => false,
                'client_mode' => 'existing',
                'existing_client_id' => $client->id,
            ],
        ],
    ])->assertRedirect('/invoices');

    $invoice = Invoice::first();
    expect($invoice->stamp_charged_to_client)->toBeFalse()
        ->and((float) $invoice->total)->toBe(1040.08);
});

it('store: default stamp_charged_to_client=true quando omesso', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $client = Client::factory()->for($user)->create();

    $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/301',
                'issued_at' => '2026-03-16',
                'amount' => 1000.00,
                'inarcassa_amount' => 40.08,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'existing',
                'existing_client_id' => $client->id,
            ],
        ],
    ])->assertRedirect('/invoices');

    expect(Invoice::first()->stamp_charged_to_client)->toBeTrue();
});

it('store: crea cliente nuovo + fattura in transazione', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/200',
                'issued_at' => '2026-04-10',
                'amount' => 500.00,
                'inarcassa_amount' => 20.00,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => true,
                'client_mode' => 'new',
                'new_client' => [
                    'name' => 'Studio Nuovo srl',
                    'vat_number' => '99988877766',
                    'tax_code' => null,
                    'bank_withholding' => true,
                ],
            ],
        ],
    ])->assertRedirect('/invoices');

    expect(Client::count())->toBe(1)
        ->and(Invoice::count())->toBe(1);

    $client = Client::first();
    expect($client->name)->toBe('Studio Nuovo srl')
        ->and($client->user_id)->toBe($user->id);
});

it('store: dedup cliente nuovo intra-batch su P.IVA', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $newClient = [
        'name' => 'Studio Verdi',
        'vat_number' => '11223344556',
        'tax_code' => null,
        'bank_withholding' => false,
    ];

    $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/A1',
                'issued_at' => '2026-03-15',
                'amount' => 500.00,
                'inarcassa_amount' => 20.00,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'new',
                'new_client' => $newClient,
            ],
            [
                'number' => '2026/A2',
                'issued_at' => '2026-04-15',
                'amount' => 800.00,
                'inarcassa_amount' => 32.00,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'new',
                'new_client' => $newClient,
            ],
        ],
    ])->assertRedirect('/invoices');

    expect(Client::count())->toBe(1)
        ->and(Invoice::count())->toBe(2);
});

it('store: cliente nuovo con P.IVA già esistente è riusato (no duplicato DB)', function (): void {
    $user = onboardedImportUser();
    $this->actingAs($user);

    $existing = Client::factory()->for($user)->create([
        'vat_number' => '55566677788',
        'name' => 'Cliente Già Presente',
    ]);

    $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/X1',
                'issued_at' => '2026-05-15',
                'amount' => 300.00,
                'inarcassa_amount' => 12.00,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'new',
                'new_client' => [
                    'name' => 'Cliente Nuovo Nome',
                    'vat_number' => '55566677788',
                    'tax_code' => null,
                    'bank_withholding' => false,
                ],
            ],
        ],
    ])->assertRedirect('/invoices');

    expect(Client::count())->toBe(1)
        ->and(Invoice::first()->client_id)->toBe($existing->id);
});

it('store: rifiuta numero già usato per quell\'anno (RB3 stesso vincolo del form manuale)', function (): void {
    $user = onboardedImportUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    // Pre-esistente: stessa coppia (number=2026/050, anno=2026).
    Invoice::factory()->for($user)->for($client)->create([
        'number' => '2026/050',
        'issued_at' => '2026-02-10',
    ]);

    $response = $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2026/050',
                'issued_at' => '2026-08-20',
                'amount' => 1000.00,
                'inarcassa_amount' => 40.08,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'existing',
                'existing_client_id' => $client->id,
            ],
        ],
    ]);

    $response->assertSessionHasErrors(['previews.0.number']);
    expect(Invoice::count())->toBe(1); // solo quella pre-esistente
});

it('store: numero uguale ma anni diversi sono ammessi', function (): void {
    $user = onboardedImportUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    // Pre-esistente: 2025/001 (anno 2025).
    Invoice::factory()->for($user)->for($client)->create([
        'number' => '2025/001',
        'issued_at' => '2025-03-10',
    ]);

    // Import: 2025/001 ma anno 2026 (numerazione resettata o sovrapposta).
    $response = $this->post('/invoices/import', [
        'previews' => [
            [
                'number' => '2025/001',
                'issued_at' => '2026-03-10',
                'amount' => 800.00,
                'inarcassa_amount' => 32.08,
                'stamp_amount' => 2.00,
                'art_15_amount' => 0.00,
                'bank_withholding' => false,
                'client_mode' => 'existing',
                'existing_client_id' => $client->id,
            ],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(Invoice::count())->toBe(2);
});

it('store: rifiuta duplicati intra-batch su stesso numero+anno', function (): void {
    $user = onboardedImportUser();
    $client = Client::factory()->for($user)->create();
    $this->actingAs($user);

    $row = fn (string $number, string $issuedAt) => [
        'number' => $number,
        'issued_at' => $issuedAt,
        'amount' => 500.00,
        'inarcassa_amount' => 20.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
        'bank_withholding' => false,
        'client_mode' => 'existing',
        'existing_client_id' => $client->id,
    ];

    $response = $this->post('/invoices/import', [
        'previews' => [
            $row('2026/777', '2026-01-15'),
            $row('2026/777', '2026-06-20'), // stesso numero, stesso anno → duplicato intra-batch
        ],
    ]);

    // Entrambe le righe segnalate (UX: l'utente vede tutti i conflitti).
    $response->assertSessionHasErrors([
        'previews.0.number',
        'previews.1.number',
    ]);
    expect(Invoice::count())->toBe(0); // transazione fallita, nulla persistito
});

it('store: rifiuta existing_client_id di altro utente (no IDOR)', function (): void {
    $alice = onboardedImportUser();
    $bob = onboardedImportUser();
    $bobClient = Client::factory()->for($bob)->create();

    $this->actingAs($alice)
        ->post('/invoices/import', [
            'previews' => [
                [
                    'number' => '2026/Z1',
                    'issued_at' => '2026-05-15',
                    'amount' => 100.00,
                    'inarcassa_amount' => 4.00,
                    'stamp_amount' => 2.00,
                    'art_15_amount' => 0.00,
                    'bank_withholding' => false,
                    'client_mode' => 'existing',
                    'existing_client_id' => $bobClient->id,
                ],
            ],
        ])
        ->assertSessionHasErrors(['previews.0.existing_client_id']);

    expect(Invoice::count())->toBe(0);
});
