<?php

namespace App\Services;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuotaType;
use App\Models\Deadline;
use App\Models\Payment;
use App\Models\Year;
use Illuminate\Support\Collection;

/**
 * Assembla il [[DeadlineContext]] per un insieme di scadenze: carica (una volta
 * per anno, via [[YearAmountsLoader]]) gli anni referenziati dalle spese
 * collegate — più l'anno N-1 dove servono gli acconti imposta — e i pagamenti
 * pagati sulle spese coinvolte. È la parte "query" che gli orchestratori
 * (vista anno, lista scadenze) condividono; il calcolo poi è puro.
 */
class DeadlineContextBuilder
{
    public function __construct(
        private readonly YearAmountsLoader $loader,
    ) {}

    /**
     * @param  Collection<int, Deadline>  $deadlines  con `annualExpense.year` caricato
     * @param  bool  $completeScope  true quando $deadlines contiene già TUTTE le
     *                               scadenze referenziate (es. vista anno): i
     *                               conteggi rate si fanno in-memory, senza query.
     *                               false (lista paginata) → conteggio via DB.
     * @param  Collection<int, Payment>|null  $paidPayments  pagamenti `paid` già caricati
     *                                                       (con `deadline`) da riusare invece di riquerare: la vista
     *                                                       anno passa qui l'union del loader. Filtrati per-spesa dal
     *                                                       calcolo, quindi un superset è sicuro. null → query.
     */
    public function build(Collection $deadlines, bool $completeScope = false, ?Collection $paidPayments = null): DeadlineContext
    {
        $payable = $deadlines->filter(
            fn (Deadline $d): bool => $d->kind === DeadlineKind::Payment && $d->annualExpense !== null,
        );

        if ($payable->isEmpty()) {
            return new DeadlineContext([], new Collection, [], [], []);
        }

        $userId = (int) $payable->first()->user_id;
        $amountsByYear = $this->loadYears($payable, $userId);

        $expenseIds = $payable->pluck('annual_expense_id')->unique()->values();
        $paidPayments ??= Payment::query()
            ->where('user_id', $userId)
            ->where('status', PaymentStatus::Paid)
            ->whereIn('annual_expense_id', $expenseIds)
            ->with('deadline')
            ->get();

        // Rate con quota (acconti/minimi/saldi/…): una sola fonte (in-memory per
        // la vista anno, una query per la lista paginata dove i fratelli possono
        // stare su altre pagine), da cui derivo conteggio totale e conteggio
        // delle sole aperte (servono al saldo per stimare gli acconti da versare).
        $siblings = $completeScope
            ? $payable->filter(fn (Deadline $d): bool => $d->quota_type !== null)->values()
            : Deadline::query()
                ->where('user_id', $userId)
                ->whereIn('annual_expense_id', $expenseIds)
                ->whereNotNull('quota_type')
                ->get(['id', 'annual_expense_id', 'quota_type', 'status', 'due_at']);

        $key = fn (Deadline $d): string => $d->annual_expense_id.':'.$d->quota_type->value;
        $siblingCounts = $siblings->groupBy($key)->map->count()->all();
        $openSiblingCounts = $siblings
            ->filter(fn (Deadline $d): bool => $d->status === DeadlineStatus::Open)
            ->groupBy($key)
            ->map->count()
            ->all();

        // Scadenze per spesa (id, due_at, status): la catena bolli ne ha bisogno
        // per dare a ogni rata solo il suo periodo invece del cumulato.
        $siblingDeadlines = $siblings->groupBy('annual_expense_id')->all();

        return new DeadlineContext($amountsByYear, $paidPayments, $siblingCounts, $openSiblingCounts, $siblingDeadlines);
    }

    /**
     * Mappa numero anno => YearAmounts per gli anni delle spese collegate, più
     * l'anno precedente quando c'è un acconto imposta (serve l'IS netta di N-1).
     *
     * @param  Collection<int, Deadline>  $payable
     * @return array<int, YearAmounts>
     */
    private function loadYears(Collection $payable, int $userId): array
    {
        // Anni delle spese collegate: già caricati come modelli (annualExpense.year).
        $years = $payable
            ->map(fn (Deadline $d): Year => $d->annualExpense->year)
            ->unique('id')
            ->keyBy(fn (Year $y): int => $y->year);

        // Anni N-1 richiesti dagli acconti imposta, se non già presenti.
        $previousNumbers = $payable
            ->filter(fn (Deadline $d): bool => $d->quota_type === QuotaType::TaxAdvance)
            ->map(fn (Deadline $d): int => $d->annualExpense->year->year - 1)
            ->unique()
            ->reject(fn (int $n): bool => $years->has($n));

        if ($previousNumbers->isNotEmpty()) {
            Year::query()
                ->where('user_id', $userId)
                ->whereIn('year', $previousNumbers->all())
                ->get()
                ->each(fn (Year $y) => $years->put($y->year, $y));
        }

        return $years
            ->map(fn (Year $y): YearAmounts => $this->loader->load($y))
            ->all();
    }
}
