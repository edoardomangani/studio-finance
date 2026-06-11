<?php

use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Models\AnnualExpense;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Year;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/** Anno aperto dell'utente con un coefficiente dato. */
function dashboardYear(User $user, int $year = 2026, float $coefficient = 100.0): Year
{
    return Year::factory()->create([
        'user_id' => $user->id,
        'year' => $year,
        'profitability_coefficient' => $coefficient,
        'pre_opened' => false,
    ]);
}

it('mostra empty state senza anni aperti', function () {
    onboardedUserWithTemplates();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('dashboard.has_data', false));
});

it('assembla il payload della dashboard per l anno in corso', function () {
    // Oggi è 2026-06-06 → l'anno 2026 è "in corso", mese 6.
    $user = onboardedUserWithTemplates();
    $year = dashboardYear($user, 2026);

    AnnualExpense::factory()->create([
        'user_id' => $user->id, 'year_id' => $year->id,
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'kind' => ExpenseKind::Tax, 'rate' => 5.00, 'amount' => null,
    ]);
    AnnualExpense::factory()->create([
        'user_id' => $user->id, 'year_id' => $year->id,
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'kind' => ExpenseKind::Pension, 'rate' => 10.00, 'minimum' => 2000.00, 'amount' => null,
    ]);
    AnnualExpense::factory()->create([
        'user_id' => $user->id, 'year_id' => $year->id,
        'calculation_type' => ExpenseCalculationType::FixedAnnual, 'amount' => 1200.00,
    ]);

    foreach (['2026-03-10', '2026-06-02'] as $date) {
        Invoice::factory()->create([
            'user_id' => $user->id, 'issued_at' => $date,
            'amount' => 1000.00, 'stamp_amount' => 0.00, 'art_15_amount' => 0.00,
        ]);
    }

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('dashboard.has_data', true)
            ->where('dashboard.current_year', 2026)
            ->where('dashboard.calendar_month', 6)
            ->where('dashboard.display_year', 2026)
            ->where('dashboard.display_month', 6)                 // giugno ha fatturato → mese in corso
            ->where('dashboard.this_month.month', 6)
            ->has('dashboard.this_month.invoice_total')           // solo la fattura di giugno
            ->has('dashboard.this_month.yoy_percent')             // null senza anno N-1
            ->has('dashboard.year.invoice_total')                 // cumulato anno
            ->where('dashboard.year.months_elapsed', 6)
            ->missing('dashboard.year.bank_income')               // netto bancario uscito dalla dashboard (resta su pagina anno)
            ->missing('dashboard.year.irpef_income_net')
            ->has('dashboard.month_expenses', 3)                 // una riga per voce di spesa
            ->has('dashboard.month_expenses.0.label')
            ->has('dashboard.month_expenses.0.amount')
            ->where('dashboard.to_cover.upcoming_deadlines_count', 0)
            ->has('dashboard.to_cover.expenses_due_to_date')
            ->has('dashboard.to_cover.expenses_due')              // spese da pagare in tutto (definitivo − pagato)
            ->has('dashboard.recent_invoices', 2)
            ->has('dashboard.recent_payments')
            ->has('dashboard.net_trend.points')                  // sparkline netto 12 mesi
            ->has('dashboard.net_trend.percent')                 // null senza storico (anno N-1 assente)
            ->has('dashboard.year_trend.months', 12)             // andamento anno: sempre 12 mesi
            ->where('dashboard.year_trend.current_month', 6)
            ->where('dashboard.year_trend.months.5.month', 6)
            ->where('dashboard.year_trend.months.5.current', fn ($total) => $total > 0)  // giugno ha fatturato
            ->where('dashboard.year_trend.months.5.previous', null)                       // anno N-1 non aperto
            ->where('dashboard.year_trend.months.11.current', null));                     // dicembre futuro → niente barra
});

it('costruisce la sparkline netto sui 12 mesi a cavallo dell anno precedente', function () {
    // Mese mostrato = giugno 2026 → finestra netto = lug 2025 … giu 2026.
    $this->travelTo(Carbon::create(2026, 6, 10));

    $user = onboardedUserWithTemplates();
    dashboardYear($user, 2026);
    dashboardYear($user, 2025);

    // Fattura in un mese dentro la finestra dell'anno precedente (set 2025) e una
    // nell'anno corrente (giu 2026): la serie deve attraversare i due anni.
    Invoice::factory()->create([
        'user_id' => $user->id, 'issued_at' => '2025-09-12',
        'amount' => 1000.00, 'stamp_amount' => 0.00, 'art_15_amount' => 0.00,
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id, 'issued_at' => '2026-06-02',
        'amount' => 1000.00, 'stamp_amount' => 0.00, 'art_15_amount' => 0.00,
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.display_month', 6)
            ->has('dashboard.net_trend.points', 12)                              // 12 mesi pieni: i due anni sono aperti
            ->has('dashboard.net_trend.percent')                                 // chiave presente (null se primo punto a zero)
            ->where('dashboard.year_trend.months.8.previous', fn ($t) => $t > 0) // settembre fantasma 2025
            ->where('dashboard.year_trend.months.5.current', fn ($t) => $t > 0)); // giugno 2026
});

it('mostra il mese precedente quando il mese in corso non ha ancora fatture', function () {
    // Oggi è giugno 2026: senza fattura a giugno, il pannello "Questo mese"
    // ripiega su maggio (chi fattura a fine mese non vede un mese vuoto).
    $this->travelTo(Carbon::create(2026, 6, 10));

    $user = onboardedUserWithTemplates();
    dashboardYear($user, 2026);

    // Solo maggio ha fatturato; giugno è vuoto.
    Invoice::factory()->create([
        'user_id' => $user->id, 'issued_at' => '2026-05-15',
        'amount' => 1000.00, 'stamp_amount' => 0.00, 'art_15_amount' => 0.00,
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.calendar_month', 6)
            ->where('dashboard.display_month', 5)                 // ripiego su maggio
            ->where('dashboard.display_year', 2026)
            ->where('dashboard.this_month.month', 5)
            ->where('dashboard.this_month.invoice_total', fn ($total) => $total > 0)); // maggio, non giugno (vuoto)
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});
