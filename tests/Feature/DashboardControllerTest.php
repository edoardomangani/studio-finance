<?php

use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Models\AnnualExpense;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Year;
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
            ->where('dashboard.this_month.month', 6)
            ->has('dashboard.this_month.invoice_total')           // solo la fattura di giugno
            ->has('dashboard.this_month.yoy_percent')             // null senza anno N-1
            ->has('dashboard.year.invoice_total')                 // cumulato anno
            ->where('dashboard.year.months_elapsed', 6)
            ->has('dashboard.year.bank_income')                   // netto bancario (no più IRPEF)
            ->missing('dashboard.year.irpef_income_net')
            ->has('dashboard.month_expenses', 3)                 // una riga per voce di spesa
            ->has('dashboard.month_expenses.0.label')
            ->has('dashboard.month_expenses.0.amount')
            ->where('dashboard.to_cover.open_deadlines_count', 0)
            ->has('dashboard.to_cover.expenses_due_to_date')
            ->has('dashboard.recent_invoices', 2)
            ->has('dashboard.recent_payments'));
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});
