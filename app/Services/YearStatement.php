<?php

namespace App\Services;

use App\Models\AnnualExpense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * Calcoli d'anno, ricalcolati sulle fatture dell'anno (non somma dei mesi).
 * Puro: riceve i dati, niente query.
 */
class YearStatement
{
    public function __construct(
        private RevenueCalculator $revenueCalculator,
        private ExpenseCalculator $expenseCalculator,
    ) {}

    /**
     * Figure d'anno calcolate una volta: scalari fattura, reddito IRPEF lordo/netto,
     * contributi e basi lordo/netto. Riusate poi da totals e dalle righe spesa.
     *
     * @param  Collection<int, Invoice>  $invoices  fatture dell'anno
     * @param  Collection<int, Payment>  $cashPayments  pagamenti pagati per cassa nell'anno
     */
    public function figures(Collection $invoices, float $coefficient, Collection $cashPayments): YearFigures
    {
        $taxable = $this->revenueCalculator->taxableAmount($invoices);
        $stamp = $this->revenueCalculator->stampDuty($invoices);
        $vat = $this->revenueCalculator->vatTurnover($invoices);
        $invoiceTotal = $this->revenueCalculator->invoiceTotal($invoices);
        $grossIrpef = round($vat * $coefficient / 100, 0);
        $pensionContributionsPaid = $this->pensionContributionsPaid($cashPayments);
        $netIrpef = round($grossIrpef - $pensionContributionsPaid, 0);

        return new YearFigures(
            taxableAmount: $taxable,
            stampDuty: $stamp,
            vatTurnover: $vat,
            invoiceTotal: $invoiceTotal,
            withholdings: round($this->revenueCalculator->withholding($invoices), 0), // anno all'unità
            irpefIncomeGross: $grossIrpef,
            irpefIncomeNet: $netIrpef,
            pensionContributionsPaid: $pensionContributionsPaid,
            grossBases: new RevenueBases(taxableAmount: $taxable, irpefIncome: $grossIrpef, vatTurnover: $vat, stampDuty: $stamp),
            netBases: new RevenueBases(taxableAmount: $taxable, irpefIncome: $netIrpef, vatTurnover: $vat, stampDuty: $stamp),
        );
    }

    /**
     * Totali per il JSON: scalari da $figures (già calcolati) + somma delle righe spesa.
     *
     * @param  array<int, array<string, mixed>>  $expenseRows  righe spesa già calcolate
     * @return array<string, mixed>
     */
    public function totals(YearFigures $figures, array $expenseRows): array
    {
        $sum = fn (string $key): float => round(array_sum(array_column($expenseRows, $key)), 2);
        $definitive = $sum('definitive');
        $amountToDate = $sum('amount_to_date');
        $is = $this->impostaSostitutivaRow($expenseRows);

        return [
            'taxable_amount' => $figures->taxableAmount,
            'stamp_duty' => $figures->stampDuty,
            'vat_turnover' => $figures->vatTurnover,
            'irpef_income_gross' => $figures->irpefIncomeGross,
            'irpef_income_net' => $figures->irpefIncomeNet,
            'pension_contributions_paid' => $figures->pensionContributionsPaid,
            'withholdings' => $figures->withholdings,
            'previous_year_credit' => (float) ($is['previous_year_credit'] ?? 0),
            'invoice_total' => $figures->invoiceTotal,
            'expenses_expected' => $sum('expected'),
            'expenses_definitive' => $definitive,
            'expenses_paid' => $sum('paid'),
            'expenses_due' => $sum('due'),
            // "A oggi": quota maturata (proporzionale ai mesi per le fisse) e
            // scoperto attuale = maturato − pagato. Guidano "da accantonare a
            // oggi" della vista anno, distinto dal residuo di fine anno.
            'expenses_amount_to_date' => $amountToDate,
            'expenses_due_to_date' => $sum('due_to_date'),
            'net' => round($figures->invoiceTotal - $definitive, 2),
            // Netto a oggi (anno in corso): fatturato − maturato a oggi. La banda
            // KPI usa questo in corso, `net` (− definitivo) sul consuntivo.
            'net_to_date' => round($figures->invoiceTotal - $amountToDate, 2),
            'bank_income' => round($figures->irpefIncomeNet - (float) ($is['calculated'] ?? 0), 2),
        ];
    }

    /**
     * Famiglia di importi annui di una spesa. expected = previsto (somma dei mesi,
     * passato dal chiamante); calculated = ricalcolato sull'anno (lordo/netto secondo
     * is_pension, coi minimali); definitive = manuale ?? calculated.
     *
     * @param  Collection<int, Payment>  $payments  pagamenti pagati collegati alla spesa
     * @param  float  $deductions  ritenute + crediti (solo IS); scalate dal calcolato
     * @param  int  $decimals  precisione di calcolato/definitivo (0 per l'IS, all'unità; 2 le altre, RB12)
     * @return array<string, mixed>
     */
    public function expenseAmounts(AnnualExpense $expense, float $expected, RevenueBases $grossBases, RevenueBases $netBases, Collection $payments, int $monthsElapsed, float $deductions = 0.0, int $decimals = 2): array
    {
        $bases = $expense->is_pension_contribution ? $grossBases : $netBases;
        $calculated = round($this->expenseCalculator->expectedAmount($expense, $bases, applyLimits: true), $decimals);
        // Maturato a oggi: stesso calcolo income-based ma SENZA minimo (il minimo
        // è un pavimento annuo, non matura nel tempo — KPI.md).
        $calculatedToDate = round($this->expenseCalculator->expectedAmount($expense, $bases, applyLimits: true, applyMinimum: false), $decimals);
        $manual = $expense->effective_amount !== null ? (float) $expense->effective_amount : null;
        $definitive = round($this->expenseCalculator->definitiveAmount($manual, $calculated - $deductions), $decimals);
        $paid = $this->expenseCalculator->paidAmount($payments);
        $toDate = $this->expenseCalculator->amountToDate($expense, $definitive, $calculatedToDate, $deductions, $manual, $monthsElapsed);

        return [
            'expected' => $expected,
            'calculated' => $calculated,
            'deductions' => $deductions,
            'manual' => $manual,
            'definitive' => $definitive,
            'amount_to_date' => $toDate,
            'paid' => $paid,
            'due' => $this->expenseCalculator->amountDue($definitive, $paid),
            'due_to_date' => $this->expenseCalculator->amountDueToDate($toDate, $paid),
        ];
    }

    /**
     * Contributi previdenziali pagati nell'anno: somma dei pagamenti per cassa
     * collegati a spese con is_pension_contribution.
     *
     * @param  Collection<int, Payment>  $cashPayments
     */
    public function pensionContributionsPaid(Collection $cashPayments): float
    {
        return round((float) $cashPayments
            ->filter(fn (Payment $payment): bool => (bool) $payment->annualExpense?->is_pension_contribution)
            ->sum('amount'), 2);
    }

    /**
     * La riga dell'imposta sostitutiva (voce % su reddito IRPEF non previdenziale),
     * trovata una volta: serve sia al reddito bancario (calculated) sia al credito.
     *
     * @param  array<int, array<string, mixed>>  $expenseRows
     * @return array<string, mixed>|null
     */
    private function impostaSostitutivaRow(array $expenseRows): ?array
    {
        return collect($expenseRows)->first(fn (array $row): bool => $row['is_imposta_sostitutiva']);
    }
}
