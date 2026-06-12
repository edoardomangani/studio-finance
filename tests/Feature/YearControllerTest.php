<?php

use App\Actions\Studiofinance\RegisterManualPayment;
use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Year;
use App\Services\YearOpeningPlanner;
use App\Services\YearService;
use Illuminate\Support\Facades\DB;
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

it('mostra il confronto pluriennale', function () {
    $user = onboardedUserWithTemplates();
    $user->years()->create(['year' => 2025, 'profitability_coefficient' => 78, 'pre_opened' => false]);

    $this->get(route('years.compare'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Compare')
            ->has('years', 1)
            ->where('years.0.year', 2025)
            ->where('years.0.time_state', 'past')
            // Focus Personale + UNICO: i KPI di confronto.
            ->has('years.0.invoice_total')
            ->has('years.0.net')
            ->has('years.0.due')
            ->has('years.0.vat_turnover')
            ->has('years.0.irpef_income_net')
            ->has('years.0.imposta_sostitutiva')
            ->has('years.0.previous_year_credit')
            ->has('years.0.bank_income'));
});

it('atterra sull anno corrente entrando nella sezione', function () {
    $user = onboardedUserWithTemplates();
    $user->years()->create(['year' => 2025, 'profitability_coefficient' => 78, 'pre_opened' => false]);

    // currentYear: 2026 (oggi) non aperto → ultimo aperto = 2025.
    $this->get(route('years.index'))->assertRedirect(route('years.show', 2025));
});

it('mostra il confronto come empty state se non c è alcun anno aperto', function () {
    onboardedUserWithTemplates();

    $this->get(route('years.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Compare')
            ->has('years', 0));
});

it('mostra la pagina di apertura anno con l anno suggerito', function () {
    onboardedUserWithTemplates();

    $this->get(route('years.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Create')
            ->where('suggestedYear', 2026));
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

it('espone le figure mensili calcolate nella vista anno', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();

    Invoice::factory()->create([
        'issued_at' => '2026-01-15',
        'amount' => 1000.00,
        'inarcassa_amount' => 40.08,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
    ]);

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Show')
            ->has('year.months', 12)
            ->where('year.months.0.month', 1)
            ->where('year.months.0.taxable_amount', 1000)     // imponibile
            ->where('year.months.0.stamp_duty', 2)            // bolli
            ->where('year.months.0.vat_turnover', 1002)       // volume affari = 1000 + 2
            ->where('year.months.0.invoice_total', 1042.08)   // 1000 + 40.08 + 2
            ->has('year.months.0.invoices', 1)                // elenco fatture del mese
            ->has('year.months.0.expenses', 8)                // elenco spese col previsto
            ->where('year.months.1.taxable_amount', 0)        // febbraio vuoto
            ->where('year.totals.taxable_amount', 1000)       // totale anno = somma mesi
            ->where('year.totals.vat_turnover', 1002)
            ->where('year.totals.irpef_income_gross', 782)    // round(1002 × 0.78) all'unità
            ->where('year.totals.pension_contributions_paid', 0)  // nessun pagamento
            ->where('year.expenses.0.expected', 117.23)       // IS previsto = somma mesi (lordo): 781.56 × 15%
            ->where('year.expenses.0.calculated', 117)        // IS calcolato all'unità (RB12): round(782 × 15%)
            ->where('year.expenses.0.definitive', 117)        // IS definitivo all'unità
            ->where('year.expenses.0.paid', 0)
            ->where('year.expenses.0.due', 117)
            ->where('year.totals.expenses_paid', 0)           // nessun pagamento
            ->where('year.totals.bank_income', 665)           // netto 782 − IS 117 (all'unità)
            ->has('year.deadlines', 20)                       // scadenze generate dal wizard
            ->where('year.deadlines.0.quota_type', 'full_amount')   // Assicurazione (31/3, prima per data)
            ->where('year.deadlines.0.expected_amount', 350));      // fissa €350, scadenza unica → previsto pieno
});

it('espone il netto mensile dell anno precedente per il confronto sparkline', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();
    // Anno precedente aperto (riga diretta, niente template): il netto = fatturato.
    $user->years()->create(['year' => 2025, 'profitability_coefficient' => 78, 'pre_opened' => false]);

    Invoice::factory()->create([
        'user_id' => $user->id, 'issued_at' => '2025-03-10',
        'amount' => 1000.00, 'inarcassa_amount' => 0.00, 'stamp_amount' => 0.00, 'art_15_amount' => 0.00,
    ]);

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('years/Show')
            ->has('year.previous_net', 12)                         // 12 mesi del 2025
            ->where('year.previous_net.2', fn ($net) => $net > 0)   // marzo 2025 ha netto
            ->where('year.previous_net.0', fn ($net) => $net <= 0)); // gennaio 2025 senza fatturato
});

it('previous_net è null senza anno precedente aperto', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('year.previous_net', null));
});

it('scala ritenute e credito dal definitivo della imposta sostitutiva', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();
    $year = Year::where('year', 2026)->first();

    // Fattura con ritenuta bancaria 8% sul totale (1042.08 × 0.08 = 83.37).
    Invoice::factory()->withBankWithholding()->create([
        'issued_at' => '2026-03-10',
        'amount' => 1000.00,
        'inarcassa_amount' => 40.08,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
    ]);

    // Credito IS dell'anno precedente sulla voce imposta sostitutiva.
    AnnualExpense::where('year_id', $year->id)
        ->where('calculation_type', 'percentage_of_irpef_income')
        ->where('kind', 'tax')
        ->update(['previous_year_credit' => 20.00]);

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('year.expenses.0.calculated', 117)       // IS calcolato all'unità: round(782 × 15%)
            ->where('year.expenses.0.deductions', 114)       // ritenuta 94 (anno, unità) + credito 20
            ->where('year.expenses.0.definitive', 3)         // round(117.3 − 114) all'unità
            ->where('year.totals.withholdings', 94)          // 93.96 per-fattura → 94 sull'anno
            ->where('year.totals.previous_year_credit', 20));
});

it('espone pagamenti, formula, meta e switcher nella vista anno', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();
    $year = Year::where('year', 2026)->first();

    // Registro un pagamento su una scadenza (pianificato → pagato).
    $planned = Payment::where('status', PaymentStatus::Planned)->whereNotNull('deadline_id')->first();
    $planned->update(['status' => PaymentStatus::Paid, 'amount' => 250.00, 'paid_at' => '2026-06-16']);

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('year.payments', 1)
            ->where('year.payments.0.amount', 250)
            ->where('year.payments.0.is_manual', false)
            ->where('year.payments.0.expense_year', 2026)
            // FormulaBlock (9.a): base + aliquota della voce IS.
            ->where('year.expenses.0.formula.base_label', 'Reddito IRPEF netto')
            ->where('year.expenses.0.formula.rate', 15)
            ->has('year.expenses.0.formula.definitive')
            // Meta + switcher.
            ->where('year.meta.next_year', 2027)
            ->where('year.meta.can_open_next', true)   // 2027 esiste pre-aperto → formalizzabile
            ->where('year.years_nav.0.year', 2027)
            ->where('year.years_nav.0.pre_opened', true)
            ->where('year.years_nav.1.year', 2026));
});

it('legge i pagamenti dell anno con una sola query', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();
    $planned = Payment::where('status', PaymentStatus::Planned)->whereNotNull('deadline_id')->first();
    $planned->update(['status' => PaymentStatus::Paid, 'amount' => 250.00, 'paid_at' => '2026-06-16']);

    DB::enableQueryLog();
    $this->get(route('years.show', 2026))->assertOk();

    $paymentQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q): bool => str_contains($q['query'], 'from "payments"'))
        ->count();

    // Tre popolazioni distinte, nessuna ripetuta: (1) union del 2026 (cassa ∪
    // competenza) dal loader, (2) la 1:1 per-scadenza (tutti gli stati) che il
    // set paid-only non copre, (3) union del 2027 — una scadenza 2026 paga la
    // spesa cross-year del 2027 e l'aspettativa la richiede. Il ContextBuilder
    // NON riquery (riusa l'union via `paidPayments`): se ricomparisse, salirebbe.
    expect($paymentQueries)->toBe(3);
});

it('il tab pagamenti mostra solo la competenza dell anno, non la cassa di altri anni', function () {
    $user = onboardedUserWithTemplates();
    $this->post(route('years.store'), openYearPayload(planFor($user, 2026)))->assertRedirect();

    $expense2026 = AnnualExpense::where('year_id', Year::where('year', 2026)->value('id'))->firstOrFail();
    $expense2027 = AnnualExpense::where('year_id', Year::where('year', 2027)->value('id'))->firstOrFail();

    // Competenza 2026, pagato nel 2027 → deve comparire nel cockpit 2026.
    app(RegisterManualPayment::class)($expense2026, ['amount' => '111', 'paid_at' => '2027-02-01', 'description' => 'comp2026']);
    // Competenza 2027 (cross-year), pagato nel 2026 → NON deve comparire.
    app(RegisterManualPayment::class)($expense2027, ['amount' => '222', 'paid_at' => '2026-02-01', 'description' => 'comp2027']);

    $this->get(route('years.show', 2026))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('year.payments', fn ($payments) => collect($payments)->contains('description', 'comp2026')
                && ! collect($payments)->contains('description', 'comp2027'))
            ->etc());
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('years.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->get(route('years.index'))
        ->assertRedirect(route('onboarding.show'));
});

it('maturato a oggi di un contributo a minimo è income-based, non il minimo', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Anno in corso (2026), coefficiente 100 per numeri puliti.
    $year = Year::factory()->create(['user_id' => $user->id, 'year' => 2026, 'profitability_coefficient' => 100]);
    AnnualExpense::factory()->create([
        'user_id' => $user->id,
        'year_id' => $year->id,
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'kind' => ExpenseKind::Pension,
        'rate' => 10.00,
        'minimum' => 2000.00,
        'amount' => null,
    ]);
    // Reddito IRPEF 5000 → 10% = 500, sotto il minimale 2000.
    Invoice::factory()->create([
        'user_id' => $user->id,
        'issued_at' => '2026-05-10',
        'amount' => 5000.00,
        'stamp_amount' => 0.00,
        'art_15_amount' => 0.00,
    ]);

    $totals = app(YearService::class)->forShow($year->fresh())['totals'];

    expect($totals['expenses_definitive'])->toBe(2000.0)        // definitivo = minimo
        ->and($totals['expenses_amount_to_date'])->toBe(500.0)  // a oggi income-based, niente minimo
        ->and($totals['net_to_date'])->toBe(round($totals['invoice_total'] - 500.0, 2));
});
