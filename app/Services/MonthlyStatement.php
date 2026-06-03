<?php

namespace App\Services;

use App\Enums\ExpenseCalculationType;
use App\Models\AnnualExpense;

/**
 * Calcoli mensili delle spese (accantonamento). Puro: riceve spese e basi del mese.
 */
class MonthlyStatement
{
    public function __construct(private ExpenseCalculator $expenseCalculator) {}

    /**
     * Accantonamento del mese per una singola spesa (Mese::quotaSpesaAnnuale): fisse = quota/12;
     * altre = quota sulle basi del mese, senza minimali (RB11).
     */
    public function monthlyAccrual(AnnualExpense $expense, RevenueBases $monthBases): float
    {
        if ($expense->calculation_type === ExpenseCalculationType::FixedAnnual) {
            return round((float) $expense->amount / 12, 2);
        }

        return $this->expenseCalculator->expectedAmount($expense, $monthBases, applyLimits: false);
    }
}
