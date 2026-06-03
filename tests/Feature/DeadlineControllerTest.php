<?php

use App\Actions\Studiofinance\OpenYear;
use App\Models\User;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/** Utente onboarded con un anno aperto (spese + scadenze generate). */
function userWithOpenYear(int $year = 2026): User
{
    $user = onboardedUserWithTemplates();
    test()->actingAs($user);
    $plan = app(YearOpeningPlanner::class)->plan($user, $year);
    app(OpenYear::class)($user, $plan);

    return $user;
}

it('mostra la lista scadenze con il previsto calcolato', function () {
    userWithOpenYear();

    // Assicurazione: voce fissa €350, scadenza unica → previsto pieno.
    $this->get(route('deadlines.index', ['search' => 'Assicurazione']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('deadlines/Index')
            ->has('deadlines.data', 1)
            ->where('deadlines.data.0.name', 'Assicurazione professionale')
            ->where('deadlines.data.0.quota_type', 'full_amount')
            ->where('deadlines.data.0.expected_amount', 350)
            ->where('deadlines.data.0.status', 'open')
            ->where('deadlines.data.0.kind', 'payment')
            ->etc());
});

it('lascia il previsto nullo quando mancano i dati (acconto senza anno precedente)', function () {
    userWithOpenYear();

    // Acconti imposta del primo anno: serve l'IS netta di N-1, che non esiste.
    $this->get(route('deadlines.index', ['search' => 'acconto imposta']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 2)
            ->where('deadlines.data.0.quota_type', 'tax_advance')
            ->where('deadlines.data.0.expected_amount', null)
            ->etc());
});

it('filtra per tipo adempimento', function () {
    userWithOpenYear();

    // Adempimenti seedati: Dichiarazione redditi + Comunicazione Inarcassa.
    $this->get(route('deadlines.index', ['kind' => 'fulfillment']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 2)
            ->where('deadlines.data.0.kind', 'fulfillment')
            ->where('deadlines.data.0.expected_amount', null)
            ->etc());
});

it('espone gli anni e le opzioni di filtro', function () {
    userWithOpenYear();

    $this->get(route('deadlines.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('availableYears')
            ->has('statusOptions', 3)
            ->has('kindOptions', 2)
            ->etc());
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('deadlines.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->get(route('deadlines.index'))
        ->assertRedirect(route('onboarding.show'));
});
