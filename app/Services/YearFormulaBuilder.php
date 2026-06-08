<?php

namespace App\Services;

use App\Enums\ExpenseCalculationType;
use App\Models\AnnualExpense;

/**
 * Costruisce i dati del "FormulaBlock" (trasparenza calcoli, 9.a): per una
 * spesa annuale espone base, aliquota, limiti e deduzioni che portano al
 * definitivo, così la vista anno può mostrare come è stato ottenuto l'importo.
 * Puro: legge le figure d'anno già calcolate, non interroga il DB. La frase
 * leggibile la compone il frontend dai campi strutturati.
 */
class YearFormulaBuilder
{
    /**
     * @param  array<string, mixed>  $amounts  riga famiglia importi della spesa (calculated/deductions/manual/definitive)
     * @return array<string, mixed>
     */
    public function for(AnnualExpense $expense, YearFigures $figures, array $amounts): array
    {
        $bases = $expense->is_pension_contribution ? $figures->grossBases : $figures->netBases;
        [$baseLabel, $base] = $this->baseFor($expense, $bases);

        $maximum = $expense->maximum !== null ? (float) $expense->maximum : null;
        $minimum = $expense->minimum !== null ? (float) $expense->minimum : null;
        $cappedBase = $base !== null && $maximum !== null ? min($base, $maximum) : $base;

        return [
            'calculation_type' => $expense->calculation_type->value,
            'base_label' => $baseLabel,
            'base' => $base,
            'rate' => $expense->rate !== null ? (float) $expense->rate : null,
            'minimum' => $minimum,
            'maximum' => $maximum,
            'capped_base' => $cappedBase,
            'fixed_amount' => $expense->calculation_type === ExpenseCalculationType::FixedAnnual && $expense->amount !== null
                ? (float) $expense->amount
                : null,
            'calculated' => (float) $amounts['calculated'],
            'deductions' => (float) $amounts['deductions'],
            'manual' => $amounts['manual'] !== null ? (float) $amounts['manual'] : null,
            'definitive' => (float) $amounts['definitive'],
        ];
    }

    /**
     * Etichetta e valore della base secondo il tipo di calcolo. null/null per
     * le voci senza base (importo fisso).
     *
     * @return array{0: string|null, 1: float|null}
     */
    private function baseFor(AnnualExpense $expense, RevenueBases $bases): array
    {
        return match ($expense->calculation_type) {
            ExpenseCalculationType::PercentageOfIrpefIncome => [
                $expense->is_pension_contribution ? 'Reddito IRPEF' : 'Reddito IRPEF netto',
                $bases->irpefIncome,
            ],
            ExpenseCalculationType::PercentageOfIvaRevenue => ['Volume affari IVA', $bases->vatTurnover],
            ExpenseCalculationType::SumOfBolli => ['Bolli fatture', $bases->stampDuty],
            ExpenseCalculationType::FixedAnnual => [null, null],
        };
    }
}
