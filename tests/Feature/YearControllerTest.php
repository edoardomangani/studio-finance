<?php

use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Payment;
use App\Models\User;
use App\Models\Year;
use App\Services\YearOpeningPlanner;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Costruisce il payload del wizard a partire dal piano del planner: tutte le
 * voci incluse, cross-year confermato.
 *
 * @param  array<string, mixed>  $plan
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function openYearPayload(array $plan, array $overrides = []): array
{
    return array_merge([
        'year' => $plan['year'],
        'profitability_coefficient' => $plan['profitability_coefficient'],
        'note' => null,
        'cross_year_confirmed' => true,
        'expenses' => array_map(
            fn (array $e): array => $e + ['included' => true],
            $plan['expenses'],
        ),
        'deadlines' => $plan['deadlines'],
    ], $overrides);
}

function planFor(User $user, int $year): array
{
    return app(YearOpeningPlanner::class)->plan($user, $year);
}

it('mostra la lista anni', function () {
    $user = onboardedUserWithTemplates();
    $user->years()->create(['year' => 2025, 'profitability_coefficient' => 78, 'pre_opened' => false]);

    $this->get(route('years.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Index')
            ->has('years', 1)
            ->where('years.0.year', 2025));
});

it('restituisce il piano JSON per l anno richiesto', function () {
    onboardedUserWithTemplates();

    $this->getJson(route('years.plan', ['year' => 2026]))
        ->assertOk()
        ->assertJsonPath('year', 2026)
        ->assertJsonCount(8, 'expenses')
        ->assertJsonCount(20, 'deadlines')
        ->assertJsonPath('next_year_needs_preopen', true);
});

it('apre un anno e reindirizza alla vista anno', function () {
    $user = onboardedUserWithTemplates();
    $payload = openYearPayload(planFor($user, 2026));

    $this->post(route('years.store'), $payload)
        ->assertRedirect(route('years.show', 2026));

    $year = Year::where('year', 2026)->first();
    expect($year)->not->toBeNull()
        ->and($year->pre_opened)->toBeFalse();

    expect(AnnualExpense::where('year_id', $year->id)->count())->toBe(8)
        ->and(Deadline::where('year_id', $year->id)->count())->toBe(20)
        ->and(Payment::where('status', PaymentStatus::Planned)->count())->toBe(18);

    // Cross-year: 2027 pre-aperto.
    expect(Year::where('year', 2027)->first()?->pre_opened)->toBeTrue();
});

it('esclude le voci non incluse dal payload', function () {
    $user = onboardedUserWithTemplates();
    $plan = planFor($user, 2026);

    $payload = openYearPayload($plan);
    // Escludo l'Assicurazione (1 scadenza di pagamento collegata).
    foreach ($payload['expenses'] as $i => $expense) {
        if ($expense['name'] === 'Assicurazione professionale') {
            $payload['expenses'][$i]['included'] = false;
        }
    }

    $this->post(route('years.store'), $payload)->assertRedirect();

    $year = Year::where('year', 2026)->first();
    expect(AnnualExpense::where('year_id', $year->id)->count())->toBe(7)
        ->and(Deadline::where('year_id', $year->id)->where('name', 'Assicurazione professionale')->exists())->toBeFalse();
});

it('blocca l apertura di un anno già aperto formalmente con errore inline', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();

    // Secondo tentativo sullo stesso anno → errore di validazione su `year`.
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))
        ->assertSessionHasErrors(['year']);
});

it('completa un anno pre-aperto senza bloccare', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();

    // 2027 esiste come pre-aperto: aprirlo formalmente NON deve bloccare.
    $this->post(route('years.store'), openYearPayload(planFor($user, 2027)))
        ->assertRedirect(route('years.show', 2027));

    $year2027 = Year::where('year', 2027)->first();
    expect($year2027->pre_opened)->toBeFalse()
        ->and(AnnualExpense::where('year_id', $year2027->id)->count())->toBe(8);
});

it('mostra la vista anno con le spese generate', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Show')
            ->where('year.year', 2026)
            ->has('year.expenses', 8));
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('years.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->get(route('years.index'))
        ->assertRedirect(route('onboarding.show'));
});
