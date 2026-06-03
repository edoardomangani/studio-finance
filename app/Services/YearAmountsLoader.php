<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Year;

/**
 * Unico punto-query per i dati d'anno: carica fatture e pagamenti e delega il
 * calcolo al puro [[YearExpenseAmounts]]. Usato dagli orchestratori (vista anno,
 * lista scadenze). Bind `scoped` (una sola istanza per richiesta) + cache per
 * year_id: ogni anno viene caricato una volta sola, anche se referenziato sia
 * dalla vista sia dalle scadenze.
 */
class YearAmountsLoader
{
    /** @var array<int, YearAmounts> cache per year_id entro la richiesta */
    private array $cache = [];

    public function __construct(
        private readonly YearExpenseAmounts $calculator,
    ) {}

    public function load(Year $year): YearAmounts
    {
        if (isset($this->cache[$year->id])) {
            return $this->cache[$year->id];
        }

        $year->loadMissing(['annualExpenses' => fn ($q) => $q->orderBy('id')]);

        $invoices = Invoice::query()
            ->where('user_id', $year->user_id)
            ->whereYear('issued_at', $year->year)
            ->orderByDesc('issued_at')
            ->orderByDesc('number_sort')
            ->orderByDesc('id')
            ->get();

        $cashPayments = Payment::query()
            ->where('user_id', $year->user_id)
            ->where('status', PaymentStatus::Paid)
            ->whereYear('paid_at', $year->year)
            ->with('annualExpense')
            ->get();

        $expensePayments = Payment::query()
            ->where('user_id', $year->user_id)
            ->where('status', PaymentStatus::Paid)
            ->whereIn('annual_expense_id', $year->annualExpenses->pluck('id'))
            ->get();

        return $this->cache[$year->id] = $this->calculator->compute(
            $year->year,
            (float) $year->profitability_coefficient,
            $year->annualExpenses,
            $invoices,
            $cashPayments,
            $expensePayments,
        );
    }
}
