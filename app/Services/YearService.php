<?php

namespace App\Services;

use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\ExpenseFamily;
use App\Models\Invoice;
use App\Models\Payment;
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
        private readonly YearFormulaBuilder $formulaBuilder,
    ) {}

    /**
     * Anni dell'utente in ordine DESC, con i conteggi e i KPI dei due focus
     * della tabella confronto (Personale e UNICO/fiscale). Ogni anno è caricato
     * una volta dal loader condiviso (cache); i volumi attesi sono pochi anni.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $calendar = (int) Carbon::now()->year;
        $familyNames = ExpenseFamily::namesByKind();

        return Year::query()
            ->withCount(['annualExpenses', 'deadlines'])
            // Eager load così il loadMissing interno al loader è un no-op (−N query).
            ->with(['annualExpenses' => fn ($q) => $q->orderBy('id')])
            ->orderByDesc('year')
            ->get()
            ->map(function (Year $year) use ($calendar, $familyNames): array {
                $coefficient = (float) $year->profitability_coefficient;
                $amounts = $this->amountsLoader->load($year);
                $months = $this->months($amounts->invoices, $amounts->expenses, $coefficient);
                $totals = $this->yearStatement->totals($amounts->figures, $this->expenseRows($amounts, $months, $familyNames));

                $timeState = $year->year < $calendar ? 'past' : ($year->year > $calendar ? 'future' : 'current');
                // Anno in corso: importi maturati "a oggi" (stima). Chiuso: definitivi.
                $current = $timeState === 'current';

                return [
                    'id' => $year->id,
                    'year' => $year->year,
                    'profitability_coefficient' => $coefficient,
                    'pre_opened' => $year->pre_opened,
                    'time_state' => $timeState,
                    'expenses_count' => $year->annual_expenses_count,
                    'deadlines_count' => $year->deadlines_count,
                    // Focus Personale.
                    'invoice_total' => $totals['invoice_total'],
                    'expenses' => $current ? $totals['expenses_amount_to_date'] : $totals['expenses_definitive'],
                    'net' => $current ? $totals['net_to_date'] : $totals['net'],
                    'paid' => $totals['expenses_paid'],
                    'due' => $current ? $totals['expenses_due_to_date'] : $totals['expenses_due'],
                    // Focus UNICO (dichiarazione).
                    'vat_turnover' => $totals['vat_turnover'],
                    'irpef_income_net' => $totals['irpef_income_net'],
                    'pension_contributions_paid' => $totals['pension_contributions_paid'],
                    'imposta_sostitutiva' => $totals['imposta_sostitutiva'],
                    'withholdings' => $totals['withholdings'],
                    'previous_year_credit' => $totals['previous_year_credit'],
                    'bank_income' => $totals['bank_income'],
                    // Mensile: ÷ mesi trascorsi (in corso, stima), ÷12 (chiuso).
                    'bank_income_monthly' => round($totals['bank_income'] / ($amounts->monthsElapsed ?: 12), 2),
                ];
            })
            ->all();
    }

    /**
     * Anni esistenti per la checklist di propagazione profilo (F11): id, numero
     * e coefficiente. Lista leggera (niente KPI), scoped via BelongsToUser.
     *
     * @return array<int, array{id: int, year: int, profitability_coefficient: float}>
     */
    public function forPropagationChecklist(): array
    {
        return Year::query()
            ->orderByDesc('year')
            ->get(['id', 'year', 'profitability_coefficient'])
            ->map(fn (Year $y): array => [
                'id' => $y->id,
                'year' => $y->year,
                'profitability_coefficient' => (float) $y->profitability_coefficient,
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
     * Spese dell'anno come opzioni per l'autocomplete (scadenza ad-hoc /
     * pagamento manuale dal cockpit): solo le voci di QUEST'anno, non l'intero
     * elenco pluriennale. Riusa la relazione già caricata dal loader.
     *
     * @return array<int, array{id: int, name: string, year: int}>
     */
    public function expensePickerForYear(Year $year): array
    {
        $year->loadMissing('annualExpenses');

        return $year->annualExpenses
            ->sortBy('name')
            ->map(fn (AnnualExpense $e): array => [
                'id' => $e->id,
                'name' => $e->name,
                'year' => $year->year,
            ])
            ->values()
            ->all();
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
     * Anno su cui atterrare quando si entra nella sezione: l'anno solare se
     * aperto formalmente, altrimenti l'ultimo anno aperto (non pre-aperto).
     * null se non esiste alcun anno aperto (primo utilizzo). I pre-aperti non
     * contano come "corrente": sono in attesa di apertura formale.
     */
    public function currentYear(): ?int
    {
        $open = Year::query()
            ->where('pre_opened', false)
            ->orderByDesc('year')
            ->pluck('year');

        $calendar = (int) Carbon::now()->year;

        return $open->contains($calendar) ? $calendar : $open->first();
    }

    /**
     * Mapping completo della vista anno (cockpit): meta, switcher anni, righe
     * mensili, totali d'anno, spese annuali (con famiglia importi e formula),
     * scadenze e pagamenti. Le query stanno qui (punto unico); i calcoli sono
     * delegati ai calcolatori e a YearStatement.
     *
     * Pagamenti: una sola query (l'union del loader) alimenta sia i calcoli sia
     * il tab pagamenti sia il context delle scadenze (passata a `build`, niente
     * riquery). Le scadenze portano `payment` (tutti gli stati, 1:1) che il set
     * `paid`-only del loader non contiene: popolazione distinta, non duplicato.
     *
     * @return array<string, mixed>
     */
    public function forShow(Year $year): array
    {
        $coefficient = (float) $year->profitability_coefficient;

        // Carico l'anno una volta (fatture + figure + importi spesa + pagamenti)
        // dal loader condiviso: lo stesso anno richiesto dalle scadenze non viene
        // ricaricato.
        $amounts = $this->amountsLoader->load($year);
        $invoices = $amounts->invoices;

        $familyNames = ExpenseFamily::namesByKind();
        $months = $this->months($invoices, $amounts->expenses, $coefficient);
        $expenseRows = $this->expenseRows($amounts, $months, $familyNames);

        $nav = $this->navYears();

        $deadlines = $year->deadlines()->orderBy('due_at')->orderBy('id')->with(['payment', 'annualExpense.year'])->get();
        // Scope completo: sono tutte le scadenze dell'anno → conteggio rate
        // in-memory. paidPayments riusa l'union del loader → niente query.
        $context = $this->deadlineContextBuilder->build(
            $deadlines,
            completeScope: true,
            paidPayments: $amounts->payments,
        );

        return [
            'id' => $year->id,
            'year' => $year->year,
            'profitability_coefficient' => $coefficient,
            'pre_opened' => $year->pre_opened,
            'note' => $year->note,
            'deadlines_count' => $deadlines->count(),
            'invoices' => $invoices->map(fn (Invoice $invoice): array => $this->invoiceRow($invoice))->all(),
            'months' => $months,
            'totals' => $this->yearStatement->totals($amounts->figures, $expenseRows),
            'expenses' => $expenseRows,
            'deadlines' => $this->deadlineRows($deadlines, $context, $year->year),
            // Solo i pagamenti di COMPETENZA dell'anno (imputati a una spesa di
            // quest'anno), a prescindere dalla data di cassa: coerente col resto
            // del cockpit (spese, riserva). I pagamenti per sola cassa di altri
            // anni vivono nella lista globale, non qui.
            'payments' => $this->paymentRows(
                $amounts->payments->whereIn('annual_expense_id', $amounts->expenses->pluck('id')),
            ),
            'meta' => $this->meta($year, $nav),
            'years_nav' => $nav
                ->map(fn (Year $y): array => ['year' => $y->year, 'pre_opened' => $y->pre_opened])
                ->all(),
            // Mappa kind => nome famiglia (rinominabili): alimenta i picker famiglia.
            'families' => $familyNames,
        ];
    }

    /**
     * Tutti gli anni dell'utente (DESC), per lo switcher e per `meta`. Caricati
     * una volta sola in `forShow` e passati a chi serve: niente query ripetuta.
     *
     * @return Collection<int, Year>
     */
    private function navYears(): Collection
    {
        return Year::query()->orderByDesc('year')->get(['id', 'year', 'pre_opened']);
    }

    /**
     * Meta per le azioni del cockpit: stato dell'anno N+1 per pilotare "Apri
     * prossimo anno" (assente → da aprire; pre-aperto → da formalizzare; già
     * aperto → niente azione) + stato temporale per la scelta dei KPI.
     *
     * @param  Collection<int, Year>  $nav  anni già caricati (no riquery)
     * @return array<string, mixed>
     */
    private function meta(Year $year, Collection $nav): array
    {
        $next = $nav->firstWhere('year', $year->year + 1);
        $calendar = (int) Carbon::now()->year;

        return [
            'next_year' => $year->year + 1,
            'can_open_next' => $next === null || $next->pre_opened,
            // Stato temporale: pilota quali KPI mostrare (un anno chiuso non ha
            // "da accantonare a oggi"). I pre-aperti restano marcati a parte.
            'time_state' => $year->year < $calendar ? 'past' : ($year->year > $calendar ? 'future' : 'current'),
        ];
    }

    /**
     * Righe del tab pagamenti nella stessa forma della lista Pagamenti
     * (`PaymentListItem`): la vista anno riusa la stessa tabella e azioni. Sono
     * i pagamenti `paid` di competenza dell'anno (imputati a una spesa
     * dell'anno), più recenti per data; `is_manual` distingue i manuali
     * (modificabili) dai pagamenti da scadenza.
     *
     * @param  Collection<int, Payment>  $payments  pagamenti di competenza dell'anno
     * @return array<int, array<string, mixed>>
     */
    private function paymentRows(Collection $payments): array
    {
        return $payments
            ->sortByDesc(fn (Payment $p): string => $p->paid_at?->toDateString() ?? '')
            ->values()
            ->map(fn (Payment $p): array => [
                'id' => $p->id,
                'description' => $p->description,
                'annual_expense_id' => $p->annual_expense_id,
                'annual_expense_name' => $p->annualExpense?->name,
                'expense_year' => $p->annualExpense?->year?->year,
                'amount' => (float) $p->amount,
                'paid_at' => $p->paid_at?->toDateString(),
                'is_manual' => $p->deadline_id === null,
            ])
            ->all();
    }

    /**
     * Righe scadenze/pagamenti nella stessa forma della lista Scadenze
     * (`DeadlineListItem`): la vista anno riusa così il side-sheet di dettaglio
     * e registrazione. L'importo previsto (suggerimento) è calcolato dal tipo
     * quota (RB8); null per gli adempimenti e quando i dati non esistono.
     *
     * @param  Collection<int, Deadline>  $deadlines
     * @return array<int, array<string, mixed>>
     */
    private function deadlineRows(Collection $deadlines, DeadlineContext $context, int $yearNumber): array
    {
        return $deadlines
            ->map(fn (Deadline $d): array => [
                'id' => $d->id,
                'name' => $d->name,
                'due_at' => $d->due_at->toDateString(),
                'kind' => $d->kind->value,
                'kind_label' => $d->kind->label(),
                'quota_type' => $d->quota_type?->value,
                'quota_type_label' => $d->quota_type?->label(),
                'status' => $d->status->value,
                'status_label' => $d->status->label(),
                'year' => $yearNumber,
                'is_custom' => $d->isAdHoc(),
                'annual_expense_id' => $d->annual_expense_id,
                'annual_expense_name' => $d->annualExpense?->name,
                'expected_amount' => $this->deadlineExpectation->for($d, $context),
                'payment' => $d->payment === null ? null : [
                    'id' => $d->payment->id,
                    'status' => $d->payment->status->value,
                    'status_label' => $d->payment->status->label(),
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
     * @param  array<string, string>  $familyNames  mappa kind => nome famiglia
     * @return array<int, array<string, mixed>>
     */
    private function expenseRows(YearAmounts $amounts, array $months, array $familyNames): array
    {
        $expected = $this->expectedByExpense($months);

        return $amounts->expenses
            ->map(function (AnnualExpense $e) use ($expected, $amounts, $familyNames): array {
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
                    'kind' => $e->kind->value,
                    'family_name' => $familyNames[$e->kind->value] ?? $e->kind->label(),
                    'is_imposta_sostitutiva' => $e->isImpostaSostitutiva(),
                    'previous_year_credit' => $e->previous_year_credit !== null ? (float) $e->previous_year_credit : null,
                    // una-tantum (creata a mano, senza template): eliminabile.
                    'is_custom' => $e->expense_item_id === null,
                    ...$row,
                    'formula' => $this->formulaBuilder->for($e, $amounts->figures, $row),
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
        // Forma piena InvoiceListItem: la vista anno riusa la stessa tabella
        // (e azioni) della lista Fatture.
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'issued_at' => $invoice->issued_at->toDateString(),
            'amount' => (float) $invoice->amount,
            'inarcassa_amount' => (float) $invoice->inarcassa_amount,
            'stamp_amount' => (float) $invoice->stamp_amount,
            'art_15_amount' => (float) $invoice->art_15_amount,
            'total' => (float) $invoice->total,
            'bank_withholding' => $invoice->bank_withholding,
            'client' => [
                'id' => $invoice->client->id,
                'name' => $invoice->client->name,
            ],
        ];
    }
}
