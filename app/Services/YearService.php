<?php

namespace App\Services;

use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Year;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service per il dominio Year.
 *
 * Owner di: lista pluriennale mappata per la Index, anno suggerito di
 * default per il wizard, mapping `forShow` (placeholder Fase 6, esteso in
 * Fase 9 con i KPI fiscali). La logica transazionale di apertura vive
 * nell'action [[App\Actions\Studiofinance\OpenYear]]; il "piano" editabile
 * in [[YearOpeningPlanner]].
 */
class YearService
{
    public function __construct(
        private readonly YearOpeningPlanner $planner,
        private readonly RevenueCalculator $revenueCalculator,
        private readonly MonthlyStatement $monthlyStatement,
        private readonly YearStatement $yearStatement,
        private readonly YearAmountsLoader $amountsLoader,
        private readonly DeadlineContextBuilder $deadlineContextBuilder,
        private readonly DeadlineExpectation $deadlineExpectation,
    ) {}

    /**
     * Anni dell'utente in ordine DESC, con i conteggi di spese e scadenze.
     * Niente paginazione: i volumi attesi sono pochi anni.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return Year::query()
            ->withCount(['annualExpenses', 'deadlines'])
            ->orderByDesc('year')
            ->get()
            ->map(fn (Year $year): array => [
                'id' => $year->id,
                'year' => $year->year,
                'profitability_coefficient' => (float) $year->profitability_coefficient,
                'pre_opened' => $year->pre_opened,
                'expenses_count' => $year->annual_expenses_count,
                'deadlines_count' => $year->deadlines_count,
            ])
            ->all();
    }

    /**
     * Piano editabile di apertura per un anno (spese da template + scadenze
     * con date calcolate + rilevazione cross-year).
     *
     * @return array<string, mixed>
     */
    public function plan(User $user, int $year): array
    {
        return $this->planner->plan($user, $year);
    }

    /**
     * Anno (modello) per numero, 404 se non esiste per l'utente. Tenancy via
     * global scope [[App\Concerns\BelongsToUser]].
     */
    public function findByYear(int $year): Year
    {
        return Year::query()->where('year', $year)->firstOrFail();
    }

    /**
     * Anno da proporre di default nel wizard: l'anno corrente se non ancora
     * aperto, altrimenti il primo anno successivo libero (F6 step 1).
     */
    public function suggestedYear(): int
    {
        $current = (int) Carbon::now()->year;

        $openYears = Year::query()->pluck('year')->all();

        $candidate = $current;
        while (in_array($candidate, $openYears, true)) {
            $candidate++;
        }

        return $candidate;
    }

    /**
     * Mapping completo della vista anno: meta, righe mensili, totali d'anno,
     * spese annuali (con famiglia importi) e scadenze. Le query stanno qui
     * (punto unico); i calcoli sono delegati ai calcolatori e a YearStatement.
     *
     * @return array<string, mixed>
     */
    public function forShow(Year $year): array
    {
        $year->loadCount('deadlines');
        $coefficient = (float) $year->profitability_coefficient;

        // Carico l'anno una volta (fatture + figure + importi spesa) dal loader
        // condiviso: lo stesso anno richiesto dalle scadenze non viene ricaricato.
        $amounts = $this->amountsLoader->load($year);
        $invoices = $amounts->invoices;

        $months = $this->months($invoices, $amounts->expenses, $coefficient);
        $expenseRows = $this->expenseRows($amounts, $months);

        $deadlines = $year->deadlines()->orderBy('due_at')->orderBy('id')->with(['payment', 'annualExpense.year'])->get();
        // Scope completo: sono tutte le scadenze dell'anno → conteggio rate
        // in-memory, niente query aggiuntiva.
        $context = $this->deadlineContextBuilder->build($deadlines, completeScope: true);

        return [
            'id' => $year->id,
            'year' => $year->year,
            'profitability_coefficient' => $coefficient,
            'pre_opened' => $year->pre_opened,
            'note' => $year->note,
            'deadlines_count' => $year->deadlines_count,
            'invoices' => $invoices->map(fn (Invoice $invoice): array => $this->invoiceRow($invoice))->all(),
            'months' => $months,
            'totals' => $this->yearStatement->totals($amounts->figures, $expenseRows),
            'expenses' => $expenseRows,
            'deadlines' => $this->deadlineRows($deadlines, $context),
        ];
    }

    /**
     * Righe scadenze/pagamenti, con l'importo previsto (suggerimento) calcolato
     * dal tipo quota (RB8). null per gli adempimenti e quando i dati necessari
     * non esistono.
     *
     * @param  Collection<int, Deadline>  $deadlines
     * @return array<int, array<string, mixed>>
     */
    private function deadlineRows(Collection $deadlines, DeadlineContext $context): array
    {
        return $deadlines
            ->map(fn (Deadline $d): array => [
                'id' => $d->id,
                'name' => $d->name,
                'due_at' => $d->due_at->toDateString(),
                'kind' => $d->kind->value,
                'quota_type' => $d->quota_type?->value,
                'status' => $d->status->value,
                'annual_expense_id' => $d->annual_expense_id,
                'expected_amount' => $this->deadlineExpectation->for($d, $context),
                'payment' => $d->payment === null ? null : [
                    'id' => $d->payment->id,
                    'status' => $d->payment->status->value,
                    'amount' => $d->payment->amount !== null ? (float) $d->payment->amount : null,
                    'paid_at' => $d->payment->paid_at?->toDateString(),
                ],
            ])
            ->all();
    }

    /**
     * Righe spesa annuali: meta + famiglia importi (dal loader; expected dalla
     * somma dei mesi, sovrascritto qui).
     *
     * @param  array<int, array<string, mixed>>  $months
     * @return array<int, array<string, mixed>>
     */
    private function expenseRows(YearAmounts $amounts, array $months): array
    {
        $expected = $this->expectedByExpense($months);

        return $amounts->expenses
            ->map(function (AnnualExpense $e) use ($expected, $amounts): array {
                // I derivati (definitive, paid, due) vengono dal loader; expected
                // dalla somma dei mesi, che è una preoccupazione della vista anno.
                $row = $amounts->expenseAmounts[$e->id];
                $row['expected'] = $expected[$e->id] ?? 0.0;

                return [
                    'id' => $e->id,
                    'name' => $e->name,
                    'calculation_type' => $e->calculation_type->value,
                    'calculation_type_label' => $e->calculation_type->label(),
                    'rate' => $e->rate !== null ? (float) $e->rate : null,
                    'minimum' => $e->minimum !== null ? (float) $e->minimum : null,
                    'maximum' => $e->maximum !== null ? (float) $e->maximum : null,
                    'amount' => $e->amount !== null ? (float) $e->amount : null,
                    'is_pension_contribution' => $e->is_pension_contribution,
                    'is_imposta_sostitutiva' => YearExpenseAmounts::isImpostaSostitutiva($e),
                    'previous_year_credit' => $e->previous_year_credit !== null ? (float) $e->previous_year_credit : null,
                    ...$row,
                ];
            })
            ->all();
    }

    /**
     * Previsto annuo per spesa = somma dei valori mensili.
     *
     * @param  array<int, array<string, mixed>>  $months
     * @return array<int, float>
     */
    private function expectedByExpense(array $months): array
    {
        $totals = [];
        foreach ($months as $month) {
            foreach ($month['expenses'] as $row) {
                $totals[$row['id']] = round(($totals[$row['id']] ?? 0.0) + $row['expected'], 2);
            }
        }

        return $totals;
    }

    /**
     * 12 righe mensili con le figure del mese (assemblaggio, non calcolo).
     *
     * @param  Collection<int, Invoice>  $invoices
     * @param  Collection<int, AnnualExpense>  $expenses
     * @return array<int, array<string, mixed>>
     */
    private function months(Collection $invoices, Collection $expenses, float $coefficient): array
    {
        $byMonth = $invoices->groupBy(fn (Invoice $invoice): int => (int) $invoice->issued_at->month);

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = $this->monthRow($month, $byMonth->get($month, new Collection), $expenses, $coefficient);
        }

        return $months;
    }

    /**
     * Riga del mese: figure da fatture + spese accantonate, con l'elenco delle
     * fatture e delle spese (col previsto del mese).
     *
     * @param  Collection<int, Invoice>  $invoices  fatture del mese
     * @param  Collection<int, AnnualExpense>  $expenses  spese dell'anno
     * @return array<string, mixed>
     */
    private function monthRow(int $month, Collection $invoices, Collection $expenses, float $coefficient): array
    {
        $bases = $this->revenueCalculator->bases($invoices, $coefficient);
        $invoiceTotal = $this->revenueCalculator->invoiceTotal($invoices);

        $expenseRows = $expenses
            ->map(fn (AnnualExpense $expense): array => [
                'id' => $expense->id,
                'name' => $expense->name,
                'expected' => $this->monthlyStatement->monthlyAccrual($expense, $bases),
            ])
            ->values()
            ->all();
        $totalExpenses = round(array_sum(array_column($expenseRows, 'expected')), 2);
        $net = round($invoiceTotal - $totalExpenses, 2);

        return [
            'month' => $month,
            'taxable_amount' => $this->revenueCalculator->taxableAmount($invoices),
            'stamp_duty' => $bases->stampDuty,
            'vat_turnover' => $bases->vatTurnover,
            'irpef_income' => $bases->irpefIncome,
            'invoice_total' => $invoiceTotal,
            'total_expenses' => $totalExpenses,
            'net' => $net,
            'net_to_gross_ratio' => $invoiceTotal > 0 ? round($net / $invoiceTotal, 4) : 0.0,
            'invoices' => $invoices->map(fn (Invoice $invoice): array => $this->invoiceRow($invoice))->values()->all(),
            'expenses' => $expenseRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceRow(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'issued_at' => $invoice->issued_at->toDateString(),
            'amount' => (float) $invoice->amount,
            'total' => (float) $invoice->total,
        ];
    }
}
