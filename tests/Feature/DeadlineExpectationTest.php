<?php

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\ExpenseCalculationType;
use App\Enums\QuotaType;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Year;
use App\Services\DeadlineContextBuilder;
use App\Services\DeadlineExpectation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Importo previsto (suggerimento) delle scadenze, RB8. Scenari a numeri puliti:
 * dove serve il reddito IRPEF si usa coefficiente 100 per leggibilità.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/** Anno dell'utente con coefficiente dato. */
function aYear(int $year, float $coefficient = 100.0): Year
{
    return Year::factory()->create([
        'user_id' => test()->user->id,
        'year' => $year,
        'profitability_coefficient' => $coefficient,
    ]);
}

/** @param  array<string, mixed>  $attrs */
function anExpense(Year $year, array $attrs = []): AnnualExpense
{
    return AnnualExpense::factory()->create([
        'user_id' => $year->user_id,
        'year_id' => $year->id,
        ...$attrs,
    ]);
}

/** Scadenza di pagamento collegata a una spesa, con un quota_type. */
function aPaymentDeadline(AnnualExpense $expense, ?QuotaType $quota, string $dueAt = '2026-06-30'): Deadline
{
    return Deadline::factory()->payment()->create([
        'user_id' => $expense->user_id,
        'year_id' => $expense->year_id,
        'annual_expense_id' => $expense->id,
        'quota_type' => $quota,
        'due_at' => $dueAt,
    ]);
}

/** Pagamento pagato collegato a una scadenza: registrarlo la completa (come
 *  fa RegisterPayment), così non resta anche "aperta". */
function aPaidPayment(Deadline $deadline, float $amount): Payment
{
    $deadline->update(['status' => DeadlineStatus::Completed]);

    return Payment::factory()->paid($amount)->create([
        'user_id' => $deadline->user_id,
        'annual_expense_id' => $deadline->annual_expense_id,
        'deadline_id' => $deadline->id,
    ]);
}

function expectationFor(Deadline $deadline): ?float
{
    $deadlines = Deadline::query()->with(['annualExpense.year', 'payment'])->get();
    $context = app(DeadlineContextBuilder::class)->build($deadlines);

    return app(DeadlineExpectation::class)->for($deadline, $context);
}

it('quota intera: una scadenza unica suggerisce il definitivo pieno', function () {
    $year = aYear(2026);
    $expense = anExpense($year, ['calculation_type' => ExpenseCalculationType::FixedAnnual, 'amount' => 300.00]);
    $deadline = aPaymentDeadline($expense, QuotaType::FullAmount);

    expect(expectationFor($deadline))->toBe(300.0);
});

it('quota intera: due rate dividono il definitivo (maternità €72 → €36)', function () {
    $year = aYear(2026);
    $expense = anExpense($year, ['calculation_type' => ExpenseCalculationType::FixedAnnual, 'amount' => 72.00]);
    $first = aPaymentDeadline($expense, QuotaType::FullAmount, '2026-06-30');
    aPaymentDeadline($expense, QuotaType::FullAmount, '2026-09-30');

    expect(expectationFor($first))->toBe(36.0);
});

it('minimo contributivo: minimale diviso per il numero di rate', function () {
    $year = aYear(2026);
    $expense = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => true,
        'rate' => 14.50,
        'minimum' => 2435.00,
    ]);
    $first = aPaymentDeadline($expense, QuotaType::ContributionMinimum, '2026-06-30');
    aPaymentDeadline($expense, QuotaType::ContributionMinimum, '2026-09-30');

    expect(expectationFor($first))->toBe(1217.5);
});

it('minimo contributivo: nessun suggerimento se la voce non ha minimale', function () {
    $year = aYear(2026);
    $expense = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::FixedAnnual,
        'amount' => 72.00,
        'minimum' => null,
    ]);
    $deadline = aPaymentDeadline($expense, QuotaType::ContributionMinimum);

    expect(expectationFor($deadline))->toBeNull();
});

it('acconto imposta: IS netta dell anno precedente diviso il numero di acconti', function () {
    // Anno N-1: IS 10% su reddito IRPEF, una fattura → definitivo netto 100.
    $previous = aYear(2025);
    $isPrev = anExpense($previous, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => false,
        'rate' => 10.00,
        'amount' => null,
    ]);
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'issued_at' => '2025-05-10',
        'amount' => 1000.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
    ]);

    // Anno N: la voce IS e i due acconti che la pagano.
    $year = aYear(2026);
    $isCurrent = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => false,
        'rate' => 10.00,
        'amount' => null,
    ]);
    $first = aPaymentDeadline($isCurrent, QuotaType::TaxAdvance, '2026-06-30');
    aPaymentDeadline($isCurrent, QuotaType::TaxAdvance, '2026-11-30');

    // netIS(2025) = round(1002 × 10%) all'unità = 100 → /2 = 50.
    expect(expectationFor($first))->toBe(50.0)
        ->and($isPrev->fresh())->not->toBeNull();
});

it('acconto imposta: nessun suggerimento al primo anno (manca N-1)', function () {
    $year = aYear(2026);
    $is = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => false,
        'rate' => 10.00,
        'amount' => null,
    ]);
    $deadline = aPaymentDeadline($is, QuotaType::TaxAdvance);

    expect(expectationFor($deadline))->toBeNull();
});

it('saldo imposta: definitivo meno gli acconti già versati', function () {
    $year = aYear(2026);
    $is = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => false,
        'rate' => 10.00,
        'amount' => null,
    ]);
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'issued_at' => '2026-05-10',
        'amount' => 1000.00,
        'stamp_amount' => 2.00,
        'art_15_amount' => 0.00,
    ]);

    // Due acconti pagati 30 ciascuno (totale 60).
    $advance1 = aPaymentDeadline($is, QuotaType::TaxAdvance, '2026-06-30');
    $advance2 = aPaymentDeadline($is, QuotaType::TaxAdvance, '2026-11-30');
    aPaidPayment($advance1, 30.00);
    aPaidPayment($advance2, 30.00);

    $balance = aPaymentDeadline($is, QuotaType::TaxBalance, '2027-06-30');

    // definitivo 100 − acconti versati 60 = 40.
    expect(expectationFor($balance))->toBe(40.0);
});

it('conguaglio contributivo: definitivo meno i minimi già versati', function () {
    $year = aYear(2026);
    $contribution = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => true,
        'rate' => 10.00,
        'minimum' => 500.00,
        'amount' => null,
    ]);
    // Reddito IRPEF lordo 20000 → contributo 10% = 2000 (sopra il minimale).
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'issued_at' => '2026-05-10',
        'amount' => 20000.00,
        'stamp_amount' => 0.00,
        'art_15_amount' => 0.00,
    ]);

    $rata1 = aPaymentDeadline($contribution, QuotaType::ContributionMinimum, '2026-06-30');
    $rata2 = aPaymentDeadline($contribution, QuotaType::ContributionMinimum, '2026-09-30');
    aPaidPayment($rata1, 250.00);
    aPaidPayment($rata2, 250.00);

    $adjustment = aPaymentDeadline($contribution, QuotaType::ContributionAdjustment, '2027-12-31');

    // definitivo 2000 − minimi versati 500 = 1500.
    expect(expectationFor($adjustment))->toBe(1500.0);
});

it('conguaglio: stima i minimi ancora aperti (da pagare ma non versati)', function () {
    $year = aYear(2026);
    $contribution = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => true,
        'rate' => 10.00,
        'minimum' => 500.00,
        'amount' => null,
    ]);
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'issued_at' => '2026-05-10',
        'amount' => 20000.00,
        'stamp_amount' => 0.00,
        'art_15_amount' => 0.00,
    ]);

    // Due rate del minimo ancora APERTE (nessun pagamento): vanno comunque
    // sottratte al saldo come previsto (verranno versate), non lasciate dentro.
    aPaymentDeadline($contribution, QuotaType::ContributionMinimum, '2026-06-30');
    aPaymentDeadline($contribution, QuotaType::ContributionMinimum, '2026-09-30');
    $adjustment = aPaymentDeadline($contribution, QuotaType::ContributionAdjustment, '2027-12-31');

    // definitivo 2000 − minimi previsti (2 × 250) = 1500, non 2000.
    expect(expectationFor($adjustment))->toBe(1500.0);
});

it('conguaglio: non sottrae il minimo non dovuto (resta nel saldo)', function () {
    $year = aYear(2026);
    $contribution = anExpense($year, [
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
        'is_pension_contribution' => true,
        'rate' => 10.00,
        'minimum' => 500.00,
        'amount' => null,
    ]);
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'issued_at' => '2026-05-10',
        'amount' => 20000.00,
        'stamp_amount' => 0.00,
        'art_15_amount' => 0.00,
    ]);

    // Una rata aperta (stimata) + una "non dovuta" (esclusa: rientra nel saldo).
    aPaymentDeadline($contribution, QuotaType::ContributionMinimum, '2026-06-30');
    $notDue = aPaymentDeadline($contribution, QuotaType::ContributionMinimum, '2026-09-30');
    $notDue->update(['status' => DeadlineStatus::NotDue]);

    $adjustment = aPaymentDeadline($contribution, QuotaType::ContributionAdjustment, '2027-12-31');

    // definitivo 2000 − solo la rata aperta (250) = 1750.
    expect(expectationFor($adjustment))->toBe(1750.0);
});

it('bolli: somma dei bolli emessi entro la data della scadenza', function () {
    $year = aYear(2026);
    $bolli = anExpense($year, ['calculation_type' => ExpenseCalculationType::SumOfBolli, 'amount' => null]);

    foreach (['2026-01-15', '2026-04-15', '2026-08-15'] as $date) {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'issued_at' => $date,
            'amount' => 1000.00,
            'stamp_amount' => 2.00,
            'art_15_amount' => 0.00,
        ]);
    }

    $q1 = aPaymentDeadline($bolli, QuotaType::FullAmount, '2026-05-31');
    $q2 = aPaymentDeadline($bolli, QuotaType::FullAmount, '2026-09-30');

    // Q1 (≤ 31/5): gennaio + aprile = €4. Q2 mostra SOLO il proprio periodo
    // (agosto = €2): Q1 è aperta ma verrà pagata, quindi non si cumula.
    expect(expectationFor($q1))->toBe(4.0)
        ->and(expectationFor($q2))->toBe(2.0);
});

it('bolli: una rata non dovuta fa scivolare l arretrato sulla successiva', function () {
    $year = aYear(2026);
    $bolli = anExpense($year, ['calculation_type' => ExpenseCalculationType::SumOfBolli, 'amount' => null]);

    foreach (['2026-01-15', '2026-04-15', '2026-08-15'] as $date) {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'issued_at' => $date,
            'amount' => 1000.00,
            'stamp_amount' => 2.00,
            'art_15_amount' => 0.00,
        ]);
    }

    $q1 = aPaymentDeadline($bolli, QuotaType::FullAmount, '2026-05-31');
    $q1->update(['status' => DeadlineStatus::NotDue]); // saltata
    $q2 = aPaymentDeadline($bolli, QuotaType::FullAmount, '2026-09-30');

    // Q1 non dovuta (gen+apr = €4 non versati) → Q2 porta l'arretrato: €4 + ago €2 = €6.
    expect(expectationFor($q2))->toBe(6.0);
});

it('bolli: scala i bolli già pagati (saldo che si azzera pagando)', function () {
    $year = aYear(2026);
    $bolli = anExpense($year, ['calculation_type' => ExpenseCalculationType::SumOfBolli, 'amount' => null]);

    foreach (['2026-01-15', '2026-04-15', '2026-08-15'] as $date) {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'issued_at' => $date,
            'amount' => 1000.00,
            'stamp_amount' => 2.00,
            'art_15_amount' => 0.00,
        ]);
    }

    $q1 = aPaymentDeadline($bolli, QuotaType::FullAmount, '2026-05-31');
    aPaidPayment($q1, 4.00); // pagato il maturato Q1
    $q2 = aPaymentDeadline($bolli, QuotaType::FullAmount, '2026-09-30');

    // Q2 (≤ 30/9): maturato €6 − già pagato €4 = €2.
    expect(expectationFor($q2))->toBe(2.0);
});

it('adempimenti e scadenze senza spesa non hanno previsto', function () {
    $year = aYear(2026);

    $fulfillment = Deadline::factory()->create([
        'user_id' => $this->user->id,
        'year_id' => $year->id,
        'kind' => DeadlineKind::Fulfillment,
    ]);
    $orphan = Deadline::factory()->payment()->create([
        'user_id' => $this->user->id,
        'year_id' => $year->id,
        'annual_expense_id' => null,
        'quota_type' => QuotaType::FullAmount,
    ]);

    expect(expectationFor($fulfillment))->toBeNull()
        ->and(expectationFor($orphan))->toBeNull();
});
