<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\ExpenseKind;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\ExpenseFamily;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Year;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Orchestratore della Dashboard generale (Fase 9.b). Carica ogni anno aperto
 * UNA volta (via [[YearAmountsLoader]], scoped + cache per anno) e le scadenze
 * aperte in una query; tutto il resto è calcolo in memoria sui calcolatori puri.
 * Niente query sparse: il punto-query è il loader condiviso.
 *
 * Tre risposte: "questo mese" (mese in corso), "da coprire" (due numeri
 * cross-anno: spese da pagare a oggi = competenza, scadenze da pagare = cassa)
 * e "anno" (cumulato + serie netto + proiezione semplice + reddito IRPEF).
 */
class DashboardService
{
    public function __construct(
        private readonly YearAmountsLoader $amountsLoader,
        private readonly RevenueCalculator $revenueCalculator,
        private readonly MonthlyStatement $monthlyStatement,
        private readonly DeadlineContextBuilder $deadlineContextBuilder,
        private readonly DeadlineExpectation $deadlineExpectation,
    ) {}

    /**
     * Payload completo della dashboard. `has_data` false quando l'utente non ha
     * ancora alcun anno aperto (empty state, F10).
     *
     * @return array<string, mixed>
     */
    public function forShow(): array
    {
        $years = Year::query()->where('pre_opened', false)->orderByDesc('year')->get();

        if ($years->isEmpty()) {
            return ['has_data' => false];
        }

        $calendarYear = (int) Carbon::now()->year;
        $calendarMonth = (int) Carbon::now()->month;

        // Anno dei pannelli mese/anno: l'anno solare se aperto, altrimenti l'ultimo.
        $current = $years->firstWhere('year', $calendarYear) ?? $years->first();

        // Ogni anno aperto caricato una volta (cache del loader): i totali a oggi
        // cross-anno si sommano in memoria, niente query per anno ripetute.
        $amountsByYear = $years->mapWithKeys(
            fn (Year $y): array => [$y->year => $this->amountsLoader->load($y)],
        );

        // Scadenze aperte + previsti: una sola query e un solo context, riusati
        // sia per i totali "da coprire" (su TUTTE) sia per la lista (solo le
        // prossime: già ordinate per data crescente).
        $deadlineRows = $this->openDeadlineRows();

        $monthExpenses = $current->year === $calendarYear
            ? $this->monthExpenses($amountsByYear->get($current->year), (float) $current->profitability_coefficient, $calendarMonth)
            : [];

        // Prossime 3 scadenze aperte (le altre nella pagina Scadenze); il box
        // Spese si allunga via flex per allineare il fondo.
        $deadlineLimit = 3;

        return [
            'has_data' => true,
            'calendar_year' => $calendarYear,
            'calendar_month' => $calendarMonth,
            'current_year' => $current->year,
            'this_month' => $this->thisMonth($current, $amountsByYear, $calendarYear, $calendarMonth),
            'to_cover' => $this->toCover($amountsByYear, $deadlineRows),
            'year' => $this->yearPanel($current, $amountsByYear->get($current->year)),
            'month_expenses' => $monthExpenses,
            'open_deadlines' => array_slice($deadlineRows, 0, $deadlineLimit),
            'recent_invoices' => $this->recentInvoices($amountsByYear->get($current->year)),
            'recent_payments' => $this->recentPayments($amountsByYear->get($current->year)),
        ];
    }

    /**
     * Mese in corso (solo se l'anno solare è aperto): stipendio = netto del mese,
     * fatturato e spese del mese, delta vs stesso mese dell'anno precedente.
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @return array<string, mixed>|null
     */
    private function thisMonth(Year $current, Collection $amountsByYear, int $calendarYear, int $calendarMonth): ?array
    {
        if ($current->year !== $calendarYear) {
            return null;
        }

        $amounts = $amountsByYear->get($calendarYear);
        $month = $this->monthFigures($amounts, (float) $current->profitability_coefficient, $calendarMonth);

        // YoY: % fatturato vs stesso mese N-1, solo se l'anno precedente è aperto
        // e aveva fatturato in quel mese.
        $yoy = null;
        $previous = $amountsByYear->get($calendarYear - 1);
        if ($previous !== null) {
            $prevTotal = $this->monthInvoiceTotal($previous, $calendarMonth);
            if ($prevTotal > 0) {
                $yoy = round(($month['invoice_total'] - $prevTotal) / $prevTotal * 100, 1);
            }
        }

        return [
            'month' => $calendarMonth,
            'net' => $month['net'],
            'invoice_total' => $month['invoice_total'],
            'expenses' => $month['expenses'],
            'yoy_percent' => $yoy,
        ];
    }

    /**
     * I due numeri cross-anno di "Da coprire": spese da pagare a oggi (competenza,
     * maturato − pagato su tutti gli anni) e scadenze da pagare (cassa, somma dei
     * previsti di tutte le scadenze di pagamento aperte) + conteggio.
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @param  array<int, array<string, mixed>>  $deadlineRows  scadenze aperte già mappate (con previsto)
     * @return array<string, mixed>
     */
    private function toCover(Collection $amountsByYear, array $deadlineRows): array
    {
        $dueToDate = 0.0;
        $paidToDate = 0.0;
        foreach ($amountsByYear as $amounts) {
            foreach ($amounts->expenseAmounts as $row) {
                $dueToDate += $row['due_to_date'];
                $paidToDate += $row['paid'];
            }
        }

        return [
            'expenses_due_to_date' => round($dueToDate, 2),
            'paid_to_date' => round($paidToDate, 2),
            'deadlines_due' => round((float) array_sum(array_column($deadlineRows, 'expected_amount')), 2),
            'open_deadlines_count' => count($deadlineRows),
        ];
    }

    /**
     * Pannello Anno: cumulato, mesi trascorsi (progress), proiezione semplice
     * (YTD / mesi × 12), netto bancario (lo "stipendio" che le banche leggono).
     *
     * @return array<string, mixed>
     */
    private function yearPanel(Year $current, YearAmounts $amounts): array
    {
        $monthsElapsed = $amounts->monthsElapsed;
        $invoiceTotal = $amounts->figures->invoiceTotal;

        return [
            'year' => $current->year,
            'invoice_total' => $invoiceTotal,
            'months_elapsed' => $monthsElapsed,
            'projection' => $monthsElapsed > 0 ? round($invoiceTotal / $monthsElapsed * 12, 0) : 0.0,
            'bank_income' => $this->bankIncome($amounts),
        ];
    }

    /**
     * Netto bancario d'anno: reddito IRPEF netto − imposta sostitutiva (calcolata),
     * cioè `volume d'affari × coeff − contributi − imposta`. È il netto post-imposta
     * che le banche usano come stipendio. Mid-anno è una stima (l'IS matura coi ricavi).
     */
    private function bankIncome(YearAmounts $amounts): float
    {
        $impostaSostitutiva = $amounts->expenses
            ->filter(fn (AnnualExpense $e): bool => $e->isImpostaSostitutiva())
            ->sum(fn (AnnualExpense $e): float => (float) ($amounts->expenseAmounts[$e->id]['calculated'] ?? 0));

        return round($amounts->figures->irpefIncomeNet - $impostaSostitutiva, 2);
    }

    /**
     * Spese del mese raggruppate per **famiglia** (kind): accantonamento del mese
     * sommato per tipo, etichetta = nome famiglia dell'utente. Bounded a 4 voci,
     * stabile mese su mese. Il totale lo deriva il frontend.
     *
     * @return array<int, array{kind: string, label: string, amount: float}>
     */
    private function monthExpenses(YearAmounts $amounts, float $coefficient, int $month): array
    {
        $bases = $this->revenueCalculator->bases($this->monthInvoices($amounts, $month), $coefficient);
        $familyNames = ExpenseFamily::namesByKind();

        $byKind = [];
        foreach ($amounts->expenses as $expense) {
            $kind = $expense->kind->value;
            $byKind[$kind] = round(($byKind[$kind] ?? 0.0) + $this->monthlyStatement->monthlyAccrual($expense, $bases), 2);
        }

        return collect($byKind)
            ->map(fn (float $amount, string $kind): array => [
                'kind' => $kind,
                'label' => $familyNames[$kind] ?? ExpenseKind::from($kind)->label(),
                'amount' => $amount,
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * Scadenze di pagamento aperte (tutti gli anni), con previsto: una query +
     * un context condiviso (anni dalla cache del loader). Riusate da lista e totali.
     *
     * @return array<int, array<string, mixed>>
     */
    private function openDeadlineRows(): array
    {
        $deadlines = $this->openPaymentDeadlines();
        $context = $this->deadlineContextBuilder->build($deadlines);

        return $deadlines
            ->map(fn (Deadline $d): array => [
                'id' => $d->id,
                'name' => $d->name,
                'due_at' => $d->due_at->toDateString(),
                'year' => $d->annualExpense?->year?->year,
                'kind_label' => $d->kind->label(),
                'expected_amount' => $this->deadlineExpectation->for($d, $context),
            ])
            ->all();
    }

    /**
     * @return Collection<int, Deadline>
     */
    private function openPaymentDeadlines(): Collection
    {
        return Deadline::query()
            ->where('kind', DeadlineKind::Payment)
            ->where('status', DeadlineStatus::Open)
            ->with(['annualExpense.year', 'payment'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Figure del mese (assemblaggio sui calcolatori puri): fatturato, spese
     * accantonate (RB11), netto.
     *
     * @return array{invoice_total: float, expenses: float, net: float}
     */
    private function monthFigures(YearAmounts $amounts, float $coefficient, int $month): array
    {
        $invoices = $this->monthInvoices($amounts, $month);
        $bases = $this->revenueCalculator->bases($invoices, $coefficient);
        $invoiceTotal = $this->revenueCalculator->invoiceTotal($invoices);
        $expenses = round($amounts->expenses->sum(
            fn (AnnualExpense $e): float => $this->monthlyStatement->monthlyAccrual($e, $bases),
        ), 2);

        return [
            'invoice_total' => $invoiceTotal,
            'expenses' => $expenses,
            'net' => round($invoiceTotal - $expenses, 2),
        ];
    }

    private function monthInvoiceTotal(YearAmounts $amounts, int $month): float
    {
        return $this->revenueCalculator->invoiceTotal($this->monthInvoices($amounts, $month));
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function monthInvoices(YearAmounts $amounts, int $month): Collection
    {
        return $amounts->invoices->filter(fn (Invoice $i): bool => (int) $i->issued_at->month === $month);
    }

    /**
     * Ultime fatture (anno corrente, già ordinate desc dal loader).
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentInvoices(YearAmounts $amounts): array
    {
        return $amounts->invoices
            ->take(5)
            ->map(fn (Invoice $i): array => [
                'id' => $i->id,
                'number' => $i->number,
                'issued_at' => $i->issued_at->toDateString(),
                'total' => (float) $i->total,
                'client_name' => $i->client?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Ultimi pagamenti (più recenti per data, dall'union già caricata).
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentPayments(YearAmounts $amounts): array
    {
        return $amounts->payments
            ->sortByDesc(fn (Payment $p): string => $p->paid_at?->toDateString() ?? '')
            ->take(5)
            ->map(fn (Payment $p): array => [
                'id' => $p->id,
                'description' => $p->description,
                'annual_expense_name' => $p->annualExpense?->name,
                'amount' => (float) $p->amount,
                'paid_at' => $p->paid_at?->toDateString(),
            ])
            ->values()
            ->all();
    }
}
