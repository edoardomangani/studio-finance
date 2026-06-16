<?php

use App\Models\Client;
use App\Models\ProfessionalProfile;
use App\Models\User;

function onboardedClient(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

it('mostra la pagina clients con tabella paginata + props search', function (): void {
    $user = onboardedClient();
    Client::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get('/clients')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/Index')
            ->has('clients.data', 3)
            ->has('clients.current_page')
            ->has('clients.last_page')
            ->has('clients.total')
            ->has('clients.links')
            ->has('search'),
        );
});

it('reindirizza a /onboarding se non onboarded', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/clients')
        ->assertRedirect('/onboarding');
});

it('filtra clienti per denominazione', function (): void {
    $user = onboardedClient();
    Client::factory()->for($user)->create(['name' => 'Acme Architettura srl']);
    Client::factory()->for($user)->create(['name' => 'Beta Studio sas']);
    Client::factory()->for($user)->create(['name' => 'Acme Subsidiary']);

    $this->actingAs($user)
        ->get('/clients?search=acme')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clients.data', 2));
});

it('filtra clienti per P.IVA', function (): void {
    $user = onboardedClient();
    Client::factory()->for($user)->create([
        'name' => 'A',
        'vat_number' => '12345678901',
    ]);
    Client::factory()->for($user)->create([
        'name' => 'B',
        'vat_number' => '99999999999',
    ]);

    $this->actingAs($user)
        ->get('/clients?search=123')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clients.data', 1));
});

it('crea un cliente con P.IVA', function (): void {
    $user = onboardedClient();

    $this->actingAs($user)->post('/clients', [
        'name' => 'Acme srl',
        'vat_number' => '12345678901',
        'tax_code' => null,
        'bank_withholding' => false,
        'notes' => null,
    ])->assertRedirect();

    $client = $user->clients()->where('name', 'Acme srl')->first();
    expect($client)->not->toBeNull();
    expect($client->vat_number)->toBe('12345678901');
});

it('crea un cliente con solo CF', function (): void {
    $user = onboardedClient();

    $this->actingAs($user)->post('/clients', [
        'name' => 'Mario Rossi',
        'vat_number' => null,
        'tax_code' => 'RSSMRA80A01H501Z',
        'bank_withholding' => true,
        'notes' => 'Cliente storico',
    ])->assertRedirect();

    expect($user->clients()->where('name', 'Mario Rossi')->exists())->toBeTrue();
});

it('rifiuta cliente senza P.IVA né CF', function (): void {
    $user = onboardedClient();

    // required_without applicato solo a vat_number (asimmetrico) per
    // mostrare un singolo errore quando entrambi mancano.
    $this->actingAs($user)->post('/clients', [
        'name' => 'No identifier',
        'vat_number' => null,
        'tax_code' => null,
        'bank_withholding' => false,
    ])->assertSessionHasErrors(['vat_number'])
        ->assertSessionDoesntHaveErrors(['tax_code']);
});

it('valida name required', function (): void {
    $user = onboardedClient();

    $this->actingAs($user)->post('/clients', [
        'vat_number' => '12345678901',
        'bank_withholding' => false,
    ])->assertSessionHasErrors(['name']);
});

it('aggiorna un cliente', function (): void {
    $user = onboardedClient();
    $client = Client::factory()->for($user)->create([
        'name' => 'Old name',
    ]);

    $this->actingAs($user)->patch("/clients/{$client->id}", [
        'name' => 'New name',
        'vat_number' => $client->vat_number,
        'tax_code' => null,
        'bank_withholding' => true,
        'notes' => 'Note aggiornate',
    ])->assertRedirect();

    expect($client->fresh()->name)->toBe('New name');
    expect($client->fresh()->bank_withholding)->toBeTrue();
});

it('mostra la pagina show di un cliente', function (): void {
    $user = onboardedClient();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->get("/clients/{$client->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/Show')
            ->where('client.id', $client->id)
            ->where('client.name', $client->name)
            ->where('client.created_at', $client->created_at->toDateString()),
        );
});

it('archivia (soft delete) un cliente', function (): void {
    $user = onboardedClient();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/clients/{$client->id}")
        ->assertRedirect('/clients');

    expect($client->fresh()->trashed())->toBeTrue();
});

it('blocca cross-user via tenancy scope (route binding 404)', function (): void {
    $altro = onboardedClient();
    $clientAltro = Client::factory()->for($altro)->create();

    $user = onboardedClient();

    $this->actingAs($user)
        ->get("/clients/{$clientAltro->id}")
        ->assertNotFound();
});

it('blocca utenti non onboarded sulle mutazioni', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/clients', [
        'name' => 'X',
        'vat_number' => '12345678901',
        'bank_withholding' => false,
    ])->assertRedirect('/onboarding');
});
