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
 * Tre risposte: "questo mese" (mese mostrato = in corso se ha fatturato,
 * altrimenti il precedente), "da coprire" (numeri
 * cross-anno: spese a oggi e in tutto = competenza, scadenze = cassa)
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
        private readonly DeadlineService $deadlineService,
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

        // Mese mostrato nei pannelli "Questo mese" / "Spese del mese": quello in
        // corso se ha già fatturato, altrimenti il precedente (vedi displayMonth).
        // Chi fattura a fine mese vedrebbe altrimenti un mese vuoto (e netto
        // negativo) per quasi tutto il mese.
        [$displayYear, $displayMonth] = $this->displayMonth($amountsByYear, $calendarYear, $calendarMonth)
            ?? [$calendarYear, $calendarMonth];
        $displayAmounts = $amountsByYear->get($displayYear);

        $thisMonth = null;
        $monthExpenses = [];
        if ($displayAmounts !== null) {
            $coefficient = (float) $years->firstWhere('year', $displayYear)->profitability_coefficient;
            $thisMonth = $this->thisMonth($amountsByYear, $displayYear, $displayMonth, $coefficient);
            $monthExpenses = $this->monthExpenses($displayAmounts, $coefficient, $displayMonth);
        }

        // Prossime 4 scadenze aperte (le altre nella pagina Scadenze); il box
        // Spese si allunga via flex per allineare il fondo.
        $deadlineLimit = 4;

        return [
            'has_data' => true,
            'calendar_year' => $calendarYear,
            'calendar_month' => $calendarMonth,
            'display_year' => $displayYear,
            'display_month' => $displayMonth,
            'current_year' => $current->year,
            'this_month' => $thisMonth,
            'to_cover' => $this->toCover($amountsByYear, $deadlineRows, $calendarYear),
            'year' => $this->yearPanel($current, $amountsByYear->get($current->year)),
            'net_trend' => $this->netTrend($amountsByYear, $years, $displayYear, $displayMonth),
            'year_trend' => $this->yearTrend($amountsByYear, $current, $displayYear, $displayMonth),
            'month_expenses' => $monthExpenses,
            'open_deadlines' => array_slice($deadlineRows, 0, $deadlineLimit),
            'recent_invoices' => $this->recentInvoices($amountsByYear->get($current->year)),
            'recent_payments' => $this->recentPayments($amountsByYear->get($current->year)),
        ];
    }

    /**
     * Mese da mostrare: quello in corso se ha già almeno una fattura, altrimenti
     * il mese di calendario precedente (mai più indietro, così non si rischiano
     * mesi vecchi). Null se l'anno solare non è aperto. Il fallback può scavalcare
     * l'anno (gennaio → dicembre dell'anno prima) ed è valido solo se quell'anno
     * è caricato; altrimenti si resta sul mese in corso (cold start senza storico).
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @return array{0: int, 1: int}|null [anno, mese]
     */
    private function displayMonth(Collection $amountsByYear, int $calendarYear, int $calendarMonth): ?array
    {
        $current = $amountsByYear->get($calendarYear);
        if ($current === null) {
            return null;
        }

        if ($this->monthInvoices($current, $calendarMonth)->isNotEmpty()) {
            return [$calendarYear, $calendarMonth];
        }

        [$year, $month] = $calendarMonth > 1
            ? [$calendarYear, $calendarMonth - 1]
            : [$calendarYear - 1, 12];

        return $amountsByYear->has($year) ? [$year, $month] : [$calendarYear, $calendarMonth];
    }

    /**
     * Pannello "Questo mese": stipendio = netto del mese, fatturato e spese del
     * mese, delta vs lo stesso mese dell'anno precedente. Il mese è quello scelto
     * da [[displayMonth]].
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @return array<string, mixed>
     */
    private function thisMonth(Collection $amountsByYear, int $year, int $month, float $coefficient): array
    {
        $amounts = $amountsByYear->get($year);
        $figures = $this->monthFigures($amounts, $coefficient, $month);

        // YoY: % fatturato vs stesso mese N-1, solo se l'anno precedente è aperto
        // e aveva fatturato in quel mese.
        $yoy = null;
        $previous = $amountsByYear->get($year - 1);
        if ($previous !== null) {
            $prevTotal = $this->monthInvoiceTotal($previous, $month);
            if ($prevTotal > 0) {
                $yoy = round(($figures['invoice_total'] - $prevTotal) / $prevTotal * 100, 1);
            }
        }

        return [
            'month' => $month,
            'net' => $figures['net'],
            'invoice_total' => $figures['invoice_total'],
            'expenses' => $figures['expenses'],
            'yoy_percent' => $yoy,
        ];
    }

    /**
     * I numeri cross-anno di "Da coprire": spese da pagare a oggi (competenza,
     * maturato − pagato), spese da pagare in tutto (definitivo − pagato sull'anno
     * intero, distinto dalle scadenze perché senza minimi/acconti) e scadenze da
     * pagare (cassa, somma dei previsti delle scadenze aperte).
     *
     * Per gli anni chiusi (anteriori a quello solare) ogni voce contribuisce solo
     * col residuo ≥ 0: un sovra-pagamento (tipico dell'imposta sostitutiva, dove
     * pagato > definitivo) è un credito già scalato sugli anni successivi, non un
     * meno sul "da coprire" attuale. Il clamp è per voce, non per anno: un credito
     * d'imposta non compensa un'altra spesa ancora da saldare nello stesso anno
     * chiuso. L'anno in corso (e i futuri) tengono il valore grezzo, negativi
     * inclusi (sovra-acconti che riducono davvero quel che resta da versare).
     *
     * Il conteggio `upcoming_deadlines_count` è la base unica del badge (stessa di
     * sidebar): scadenze prossime di ogni tipo, non solo i pagamenti sommati qui.
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @param  array<int, array<string, mixed>>  $deadlineRows  scadenze aperte già mappate (con previsto)
     * @return array<string, mixed>
     */
    private function toCover(Collection $amountsByYear, array $deadlineRows, int $calendarYear): array
    {
        $dueToDate = 0.0;
        $due = 0.0;
        foreach ($amountsByYear as $year => $amounts) {
            $closed = $year < $calendarYear;
            foreach ($amounts->expenseAmounts as $row) {
                $dueToDate += $closed ? max(0.0, $row['due_to_date']) : $row['due_to_date'];
                $due += $closed ? max(0.0, $row['due']) : $row['due'];
            }
        }

        return [
            'expenses_due_to_date' => round($dueToDate, 2),
            'expenses_due' => round($due, 2),
            'deadlines_due' => round((float) array_sum(array_column($deadlineRows, 'expected_amount')), 2),
            'upcoming_deadlines_count' => $this->deadlineService->upcomingCount(),
        ];
    }

    /**
     * Pannello Anno: cumulato, mesi trascorsi (progress), proiezione semplice
     * (YTD / mesi × 12). Il netto bancario vive sulla pagina anno, non qui.
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
        ];
    }

    /**
     * Serie del netto degli ultimi 12 mesi (sparkline hero), chiusa sul mese
     * mostrato. La finestra scavalca sempre l'anno precedente: ogni mese usa
     * l'anno e il coefficiente giusti. I mesi il cui anno non è aperto vengono
     * saltati (serie più corta a cold start). `percent` = variazione del netto
     * dal primo all'ultimo punto (≈ un anno), null se il primo punto è zero.
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @param  Collection<int, Year>  $years  anni aperti (per il coefficiente del mese)
     * @return array{points: array<int, float>, percent: float|null}
     */
    private function netTrend(Collection $amountsByYear, Collection $years, int $endYear, int $endMonth): array
    {
        $endIndex = $endYear * 12 + ($endMonth - 1);

        $points = [];
        for ($index = $endIndex - 11; $index <= $endIndex; $index++) {
            $year = intdiv($index, 12);
            $amounts = $amountsByYear->get($year);
            if ($amounts === null) {
                continue;
            }

            $coefficient = (float) $years->firstWhere('year', $year)->profitability_coefficient;
            $points[] = $this->monthFigures($amounts, $coefficient, $index % 12 + 1)['net'];
        }

        $first = $points[0] ?? null;
        $last = $points === [] ? null : $points[count($points) - 1];
        $percent = ($first !== null && abs($first) > 0.0)
            ? round(($last - $first) / abs($first) * 100, 1)
            : null;

        return ['points' => $points, 'percent' => $percent];
    }

    /**
     * Andamento anno (grafico dashboard): fatturato per mese 1–12 dell'anno
     * corrente con il fantasma dell'anno precedente dietro. Un mese senza dato
     * (futuro, oppure anno non aperto) è `null`: niente barra. `current_month`
     * evidenzia il mese mostrato, solo se appartiene all'anno del grafico.
     *
     * @param  Collection<int, YearAmounts>  $amountsByYear
     * @return array{months: array<int, array{month: int, current: float|null, previous: float|null}>, current_month: int|null}
     */
    private function yearTrend(Collection $amountsByYear, Year $current, int $displayYear, int $displayMonth): array
    {
        $currentAmounts = $amountsByYear->get($current->year);
        $previousAmounts = $amountsByYear->get($current->year - 1);

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = [
                'month' => $month,
                'current' => $this->monthTotalOrNull($currentAmounts, $month),
                'previous' => $this->monthTotalOrNull($previousAmounts, $month),
            ];
        }

        return [
            'months' => $months,
            'current_month' => $displayYear === $current->year ? $displayMonth : null,
        ];
    }

    /**
     * Fatturato del mese, o null se l'anno non è caricato o il mese è senza
     * fatture (così il grafico non disegna una barra a zero).
     */
    private function monthTotalOrNull(?YearAmounts $amounts, int $month): ?float
    {
        if ($amounts === null) {
            return null;
        }

        $invoices = $this->monthInvoices($amounts, $month);

        return $invoices->isEmpty() ? null : $this->revenueCalculator->invoiceTotal($invoices);
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
