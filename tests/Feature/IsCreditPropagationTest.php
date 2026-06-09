<?php

use App\Actions\Studiofinance\OpenYear;
use App\Models\AnnualExpense;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Year;
use App\Services\YearAmountsLoader;
use App\Services\YearOpeningPlanner;
use App\Services\YearStatement;

function openYearFor(User $user, int $year): Year
{
    $plan = app(YearOpeningPlanner::class)->plan($user, $year);

    return app(OpenYear::class)($user, $plan);
}

function isExpenseOf(Year $year): AnnualExpense
{
    return AnnualExpense::where('year_id', $year->id)
        ->get()
        ->first(fn (AnnualExpense $e): bool => $e->isImpostaSostitutiva());
}

it('calcola il credito di fine anno = max(0, ritenute + credito N-1 - IS)', function () {
    $user = onboardedUserWithTemplates();
    $year = $user->years()->create(['year' => 2026, 'profitability_coefficient' => 78, 'pre_opened' => false]);

    AnnualExpense::factory()->impostaSostitutiva()->create([
        'user_id' => $user->id, 'year_id' => $year->id,
        'rate' => 15, 'minimum' => null, 'maximum' => null,
        'previous_year_credit' => 500,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id, 'issued_at' => '2026-06-01',
        'amount' => 10000, 'stamp_amount' => 0, 'inarcassa_amount' => 400,
        'art_15_amount' => 0, 'bank_withholding' => true, 'stamp_charged_to_client' => true,
    ]);

    // IS = round(10000*0.78,0)*0.15 = 1170; ritenute = round(10400/1.22*0.11)=938;
    // credito N-1 = 500. carry = max(0, (938 + 500) - 1170) = 268.
    $amounts = app(YearAmountsLoader::class)->load($year);

    expect(app(YearStatement::class)->creditCarriedForward($amounts))->toBe(268.0);
});

it('propaga il credito IS da un anno al successivo (3 anni consecutivi)', function () {
    $user = onboardedUserWithTemplates();

    $y2025 = openYearFor($user, 2025);
    $y2026 = openYearFor($user, 2026);

    // Primo anno della catena: nessun anno precedente → niente credito ereditato.
    expect(isExpenseOf($y2025)->previous_year_credit)->toBeNull();

    // Override manuale del credito sul 2026 (RB5: usato per il carry verso il 2027).
    isExpenseOf($y2026)->update(['previous_year_credit' => 3000]);

    // Aprendo il 2027 l'IS eredita il credito di fine 2026 (IS 2026 = 0 senza fatture).
    $y2027 = openYearFor($user, 2027);

    expect((float) isExpenseOf($y2027)->previous_year_credit)->toBe(3000.0);
});

it('rispetta un override manuale del credito IS dal cockpit (RB5)', function () {
    $user = onboardedUserWithTemplates();
    $y2026 = openYearFor($user, 2026);
    isExpenseOf($y2026)->update(['previous_year_credit' => 3000]);
    $y2027 = openYearFor($user, 2027);

    $is = isExpenseOf($y2027); // auto-ereditato a 3000

    $this->patch(route('annual-expenses.update', $is), [
        'name' => 'Imposta sostitutiva',
        'rate' => 15,
        'previous_year_credit' => 500,
    ])->assertRedirect();

    expect((float) $is->fresh()->previous_year_credit)->toBe(500.0);
});
