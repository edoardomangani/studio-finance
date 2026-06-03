<?php

use App\Actions\Studiofinance\OpenYear;
use App\Actions\Studiofinance\RegisterPayment;
use App\Enums\DeadlineKind;
use App\Models\Deadline;
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
            ->has('kindOptions', 2)
            ->etc());
});

it('il toggle stato "closed" mostra completate e non dovute, non le aperte', function () {
    $user = userWithOpenYear();
    // Una scadenza di pagamento la registro (→ completata), un'altra resta aperta.
    $paid = Deadline::query()
        ->where('kind', DeadlineKind::Payment)
        ->whereNotNull('annual_expense_id')
        ->firstOrFail();
    app(RegisterPayment::class)($paid, ['amount' => 100, 'paid_at' => '2026-03-15']);

    $this->get(route('deadlines.index', ['state' => 'closed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 1)
            ->where('deadlines.data.0.status', 'completed')
            ->etc());

    $this->get(route('deadlines.index', ['state' => 'open']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines.data', fn ($rows) => collect($rows)->every(fn ($r) => $r['status'] === 'open'))
            ->etc());
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('deadlines.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->get(route('deadlines.index'))
        ->assertRedirect(route('onboarding.show'));
});
