<?php

namespace App\Services;

use App\Enums\DeadlineKind;
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
     */
    public function build(Collection $deadlines): DeadlineContext
    {
        $payable = $deadlines->filter(
            fn (Deadline $d): bool => $d->kind === DeadlineKind::Payment && $d->annualExpense !== null,
        );

        if ($payable->isEmpty()) {
            return new DeadlineContext([], new Collection, $deadlines);
        }

        $userId = (int) $payable->first()->user_id;
        $amountsByYear = $this->loadYears($payable, $userId);

        $expenseIds = $payable->pluck('annual_expense_id')->unique()->values();
        $paidPayments = Payment::query()
            ->where('user_id', $userId)
            ->where('status', PaymentStatus::Paid)
            ->whereIn('annual_expense_id', $expenseIds)
            ->with('deadline')
            ->get();

        return new DeadlineContext($amountsByYear, $paidPayments, $deadlines);
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
