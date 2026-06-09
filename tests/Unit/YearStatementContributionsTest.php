<?php

use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Models\AnnualExpense;
use App\Models\Payment;
use App\Services\ExpenseCalculator;
use App\Services\RevenueCalculator;
use App\Services\YearStatement;
use Illuminate\Support\Collection;

/**
 * Il contributo integrativo (Inarcassa 4% sul volume d'affari) è la rivalsa
 * addebitata al cliente, non un costo deducibile del professionista: pur
 * essendo kind=pension non deve abbassare il reddito IRPEF netto. Questi test
 * bloccano l'esclusione nella somma dei contributi pagati e dedotti.
 */
function pensionPayment(float $amount, ExpenseCalculationType $calc): Payment
{
    $expense = new AnnualExpense([
        'kind' => ExpenseKind::Pension,
        'calculation_type' => $calc,
    ]);

    $payment = new Payment(['amount' => $amount]);
    $payment->setRelation('annualExpense', $expense);

    return $payment;
}

function yearStatement(): YearStatement
{
    return new YearStatement(new RevenueCalculator, new ExpenseCalculator);
}

it('somma soggettivo e maternità ma esclude l\'integrativo', function (): void {
    $payments = new Collection([
        pensionPayment(2435.00, ExpenseCalculationType::PercentageOfIrpefIncome), // soggettivo
        pensionPayment(72.00, ExpenseCalculationType::FixedAnnual),               // maternità
        pensionPayment(815.00, ExpenseCalculationType::PercentageOfIvaRevenue),   // integrativo (escluso)
    ]);

    // 2435 + 72, l'integrativo non entra nella deduzione.
    expect(yearStatement()->pensionContributionsPaid($payments))->toBe(2507.0);
});

it('esclude del tutto un anno di soli pagamenti integrativi', function (): void {
    $payments = new Collection([
        pensionPayment(815.00, ExpenseCalculationType::PercentageOfIvaRevenue),
        pensionPayment(400.00, ExpenseCalculationType::PercentageOfIvaRevenue),
    ]);

    expect(yearStatement()->pensionContributionsPaid($payments))->toBe(0.0);
});

it('ignora i pagamenti non previdenziali', function (): void {
    $taxExpense = new AnnualExpense([
        'kind' => ExpenseKind::Tax,
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome,
    ]);
    $taxPayment = new Payment(['amount' => 5000.00]);
    $taxPayment->setRelation('annualExpense', $taxExpense);

    $payments = new Collection([
        pensionPayment(2435.00, ExpenseCalculationType::PercentageOfIrpefIncome),
        $taxPayment,
    ]);

    expect(yearStatement()->pensionContributionsPaid($payments))->toBe(2435.0);
});
