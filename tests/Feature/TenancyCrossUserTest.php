<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Isolamento cross-user: il global scope BelongsToUser deve impedire a un utente
 * di vedere o toccare le risorse di un altro. Le risorse del proprietario sono
 * create senza autenticazione (così il creating-hook usa lo user_id esplicito);
 * poi un secondo utente, autenticato, tenta l'accesso → 404 sul binding scoped.
 */
beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('isola i clienti di un altro utente', function () {
    $client = Client::factory()->create(['user_id' => $this->owner->id]);

    onboardedUserWithTemplates(); // l'attaccante è ora autenticato

    $this->get(route('clients.show', $client))->assertNotFound();
    $this->patch(route('clients.update', $client), [])->assertNotFound();
    $this->delete(route('clients.destroy', $client))->assertNotFound();

    // L'index dell'attaccante non contiene il cliente altrui.
    $this->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('clients.data', 0));
});

it('isola le fatture di un altro utente', function () {
    $invoice = Invoice::factory()->create(['user_id' => $this->owner->id]);

    onboardedUserWithTemplates();

    $this->get(route('invoices.show', $invoice))->assertNotFound();
    $this->patch(route('invoices.update', $invoice), [])->assertNotFound();
    $this->delete(route('invoices.destroy', $invoice))->assertNotFound();
});

it('isola i pagamenti di un altro utente', function () {
    $payment = Payment::factory()->paid()->create(['user_id' => $this->owner->id]);

    onboardedUserWithTemplates();

    $this->patch(route('payments.update', $payment), [])->assertNotFound();
    $this->delete(route('payments.destroy', $payment))->assertNotFound();
});

it('non vede l anno di un altro utente con lo stesso numero', function () {
    // Il proprietario apre il 2024; l'attaccante non ha quel numero d'anno.
    $this->owner->years()->create([
        'year' => 2024, 'profitability_coefficient' => 78, 'pre_opened' => false,
    ]);

    onboardedUserWithTemplates();

    $this->get(route('years.show', 2024))->assertNotFound();
});
