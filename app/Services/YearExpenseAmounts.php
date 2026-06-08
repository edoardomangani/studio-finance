<?php

namespace App\Services;

use App\Enums\ExpenseCalculationType;
use App\Models\AnnualExpense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcolatore PURO della famiglia importi (expected/calculated/definitive/paid/...)
 * per le spese di un anno: riceve i dati già caricati, non interroga il DB. Il
 * caricamento (query) vive in [[YearAmountsLoader]], che è l'unico punto-query
 * usato dagli orchestratori (vista anno, lista scadenze).
 *
 * Le basi e i derivati restano delegati a [[YearStatement]]/[[ExpenseCalculator]];
 * qui vive solo l'orchestrazione di calcolo (quali deduzioni e quale precisione
 * per spesa).
 */
class YearExpenseAmounts
{
    public function __construct(
        private readonly YearStatement $yearStatement,
    ) {}

    /**
     * Dati d'anno (fatture + figure + mappa importi) calcolati dai dati ricevuti.
     * `expected` nella mappa è 0: la somma dei mesi è una preoccupazione della
     * vista anno, che la sovrascrive; i derivati (definitive/paid/due) non ne
     * dipendono.
     *
     * @param  Collection<int, AnnualExpense>  $expenses  spese dell'anno
     * @param  Collection<int, Invoice>  $invoices  fatture dell'anno (già ordinate per la vista)
     * @param  Collection<int, Payment>  $paidPayments  union dei pagamenti `paid` rilevanti
     *                                                  per l'anno (per cassa ∪ per competenza,
     *                                                  RB9): unica query del loader. I due
     *                                                  sottoinsiemi sono derivati qui in memoria.
     */
    public function compute(int $year, float $coefficient, Collection $expenses, Collection $invoices, Collection $paidPayments): YearAmounts
    {
        $expenseIds = $expenses->pluck('id');

        // Per cassa: pagati nell'anno solare (contributi, RB9). Per competenza:
        // collegati alle spese dell'anno, a prescindere dall'anno di cassa.
        $cashPayments = $paidPayments->filter(
            fn (Payment $p): bool => $p->paid_at !== null && (int) $p->paid_at->year === $year,
        );
        $expensePayments = $paidPayments->filter(
            fn (Payment $p): bool => $expenseIds->contains($p->annual_expense_id),
        );

        $figures = $this->yearStatement->figures($invoices, $coefficient, $cashPayments);
        $monthsElapsed = self::monthsElapsed($year);
        $paymentsByExpense = $expensePayments->groupBy('annual_expense_id');

        $map = $expenses
            ->mapWithKeys(fn (AnnualExpense $e): array => [
                $e->id => $this->amounts($e, $figures, $paymentsByExpense->get($e->id, new Collection), $monthsElapsed),
            ])
            ->all();

        return new YearAmounts($invoices, $figures, $monthsElapsed, $expenses, $map, $paidPayments->values());
    }

    /**
     * Famiglia importi di una singola spesa (deduzioni + precisione coerenti
     * con la vista anno).
     *
     * @param  Collection<int, Payment>  $payments  pagamenti pagati della spesa
     * @return array<string, mixed>
     */
    private function amounts(AnnualExpense $expense, YearFigures $figures, Collection $payments, int $monthsElapsed): array
    {
        return $this->yearStatement->expenseAmounts(
            $expense,
            0.0,
            $figures->grossBases,
            $figures->netBases,
            $payments,
            $monthsElapsed,
            $this->expenseDeductions($expense, $figures->withholdings),
            self::isImpostaSostitutiva($expense) ? 0 : 2,
        );
    }

    /**
     * Deduzioni della spesa: solo l'imposta sostitutiva scala ritenute bancarie
     * + credito anno precedente dal calcolato; le altre 0.
     */
    public function expenseDeductions(AnnualExpense $expense, float $withholdings): float
    {
        if (! self::isImpostaSostitutiva($expense)) {
            return 0.0;
        }

        return round($withholdings + (float) ($expense->previous_year_credit ?? 0), 2);
    }

    /**
     * L'imposta sostitutiva: voce % su reddito IRPEF non previdenziale.
     */
    public static function isImpostaSostitutiva(AnnualExpense $expense): bool
    {
        return ! $expense->is_pension_contribution
            && $expense->calculation_type === ExpenseCalculationType::PercentageOfIrpefIncome;
    }

    /**
     * Mesi trascorsi per la proporzione "ad oggi": 12 se anno passato, 0 se
     * futuro, altrimenti il mese corrente.
     */
    public static function monthsElapsed(int $year): int
    {
        $current = (int) Carbon::now()->year;

        return match (true) {
            $year < $current => 12,
            $year > $current => 0,
            default => (int) Carbon::now()->month,
        };
    }
}
