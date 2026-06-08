<?php

use App\Actions\Studiofinance\OpenYear;
use App\Actions\Studiofinance\RegisterManualPayment;
use App\Models\AnnualExpense;
use App\Models\User;
use App\Models\Year;
use App\Services\AnnualExpenseService;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Utente onboarded con un anno aperto (spese generate dai template). */
function userWithYearForExpenses(int $year = 2026): User
{
    $user = onboardedUserWithTemplates();
    test()->actingAs($user);
    app(OpenYear::class)($user, app(YearOpeningPlanner::class)->plan($user, $year));

    return $user;
}

it('modifica i valori di una spesa percentuale', function () {
    userWithYearForExpenses();
    $expense = AnnualExpense::query()
        ->where('calculation_type', 'percentage_of_irpef_income')
        ->where('is_pension_contribution', false)
        ->firstOrFail();

    $this->from(route('years.show', 2026))
        ->patch(route('annual-expenses.update', $expense), [
            'name' => $expense->name,
            'rate' => '20',
            'minimum' => null,
            'maximum' => null,
        ])
        ->assertRedirect(route('years.show', 2026));

    expect((float) $expense->fresh()->rate)->toBe(20.0);
});

it('modifica nome e importo di una spesa fissa', function () {
    userWithYearForExpenses();
    $expense = AnnualExpense::query()
        ->where('calculation_type', 'fixed_annual')
        ->firstOrFail();

    $this->from(route('years.show', 2026))
        ->patch(route('annual-expenses.update', $expense), [
            'name' => 'Commercialista (rettifica)',
            'amount' => '500',
        ])
        ->assertRedirect();

    $expense->refresh();
    expect((float) $expense->amount)->toBe(500.0)
        ->and($expense->name)->toBe('Commercialista (rettifica)');
});

it('imposta e azzera l override manuale di una spesa', function () {
    userWithYearForExpenses();
    $expense = AnnualExpense::query()
        ->where('calculation_type', 'percentage_of_irpef_income')
        ->where('is_pension_contribution', false)
        ->firstOrFail();

    // Forza l'importo: il definitivo deve seguire l'override, non il calcolato.
    $this->from(route('years.show', 2026))
        ->patch(route('annual-expenses.update', $expense), [
            'name' => $expense->name,
            'rate' => '20',
            'effective_amount' => '1234.56',
        ])
        ->assertRedirect();

    expect((float) $expense->fresh()->effective_amount)->toBe(1234.56);

    // Vuoto → null: torna al calcolato.
    $this->patch(route('annual-expenses.update', $expense), [
        'name' => $expense->name,
        'rate' => '20',
        'effective_amount' => null,
    ])->assertRedirect();

    expect($expense->fresh()->effective_amount)->toBeNull();
});

it('richiede l aliquota per una voce percentuale', function () {
    userWithYearForExpenses();
    $expense = AnnualExpense::query()
        ->where('calculation_type', 'percentage_of_iva_revenue')
        ->firstOrFail();

    $this->patch(route('annual-expenses.update', $expense), ['name' => $expense->name])
        ->assertSessionHasErrors('rate');
});

it('non permette di modificare la spesa di un altro utente', function () {
    $other = userWithYearForExpenses();
    $otherExpense = AnnualExpense::query()->where('user_id', $other->id)->firstOrFail();

    $me = onboardedUserWithTemplates();
    test()->actingAs($me);

    $this->patch(route('annual-expenses.update', $otherExpense), [
        'name' => 'Furto',
        'amount' => '1',
    ])->assertNotFound();
});

it('crea una spesa una-tantum per l anno', function () {
    userWithYearForExpenses();
    $year = Year::where('year', 2026)->firstOrFail();

    $this->from(route('years.show', 2026))
        ->post(route('annual-expenses.store'), [
            'year_id' => $year->id,
            'name' => 'Contributo straordinario',
            'amount' => '300',
        ])->assertRedirect(route('years.show', 2026));

    $expense = AnnualExpense::query()->where('name', 'Contributo straordinario')->firstOrFail();
    expect($expense->calculation_type->value)->toBe('fixed_annual')
        ->and($expense->expense_item_id)->toBeNull()
        ->and((float) $expense->amount)->toBe(300.0)
        ->and($expense->year_id)->toBe($year->id);
});

it('elimina una spesa una-tantum senza pagamenti', function () {
    userWithYearForExpenses();
    $year = Year::where('year', 2026)->firstOrFail();
    $expense = app(AnnualExpenseService::class)->create($year, 'Una tantum', 100);

    $this->from(route('years.show', 2026))
        ->delete(route('annual-expenses.destroy', $expense))
        ->assertRedirect();

    expect(AnnualExpense::find($expense->id))->toBeNull()
        ->and(AnnualExpense::withTrashed()->find($expense->id))->not->toBeNull();
});

it('vieta l eliminazione di una voce da template', function () {
    userWithYearForExpenses();
    $template = AnnualExpense::query()->whereNotNull('expense_item_id')->firstOrFail();

    $this->delete(route('annual-expenses.destroy', $template))->assertForbidden();

    expect(AnnualExpense::find($template->id))->not->toBeNull();
});

it('vieta l eliminazione di una una-tantum con pagamenti', function () {
    userWithYearForExpenses();
    $year = Year::where('year', 2026)->firstOrFail();
    $expense = app(AnnualExpenseService::class)->create($year, 'Una tantum pagata', 100);
    app(RegisterManualPayment::class)($expense, ['amount' => '100', 'paid_at' => '2026-04-10', 'description' => null]);

    $this->delete(route('annual-expenses.destroy', $expense))->assertForbidden();

    expect(AnnualExpense::find($expense->id))->not->toBeNull();
});
