<?php

use App\Models\Client;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('mostra i clienti archiviati e li ripristina', function () {
    $user = onboardedUserWithTemplates();
    $client = Client::factory()->create(['user_id' => $user->id, 'name' => 'Studio Rossi']);
    $client->delete();

    $this->get(route('archivio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('archivio/Index')
            ->has('archive.clients', 1)
            ->where('archive.clients.0.name', 'Studio Rossi')
            ->has('archive.invoices', 0));

    $this->post(route('archivio.restore', ['type' => 'clients', 'id' => $client->id]))
        ->assertRedirect();

    expect(Client::find($client->id))->not->toBeNull()
        ->and(Client::find($client->id)->trashed())->toBeFalse();
});

it('non ripristina record di altri utenti', function () {
    $owner = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $owner->id]);
    $client->delete();

    onboardedUserWithTemplates(); // l'attaccante è ora autenticato

    $this->post(route('archivio.restore', ['type' => 'clients', 'id' => $client->id]))
        ->assertNotFound();

    $fresh = Client::withoutGlobalScopes()->withTrashed()->find($client->id);
    expect($fresh->trashed())->toBeTrue();
});

it('404 su un tipo non archiviabile', function () {
    onboardedUserWithTemplates();

    $this->post(route('archivio.restore', ['type' => 'users', 'id' => 1]))
        ->assertNotFound();
});
