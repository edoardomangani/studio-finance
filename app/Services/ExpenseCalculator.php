<?php

namespace App\Services;

use App\Enums\ExpenseCalculationType;
use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use Illuminate\Support\Collection;

/**
 * Calcolatore puro degli importi di spesa. Riceve base, parametri e pagamenti;
 * non tocca il DB. Le basi (reddito IRPEF, volume affari, bolli) arrivano da
 * RevenueCalculator tramite il chiamante.
 */
class ExpenseCalculator
{
    /**
     * Importo previsto per voci percentuali: MAX(MIN(base, massimale) × aliquota, minimale).
     * Il massimale limita la base, il minimale è un pavimento sul contributo (dovuto anche a base 0).
     * Precisione al centesimo; l'arrotondamento all'unità (IS, valori Unico) è presentazione (RB12).
     */
    public function percentageAmount(float $base, float $rate, ?float $minimum = null, ?float $maximum = null): float
    {
        $cappedBase = $maximum !== null ? min($base, $maximum) : $base;
        $amount = $cappedBase * $rate / 100;

        if ($minimum !== null) {
            $amount = max($amount, $minimum);
        }

        return round($amount, 2);
    }

    /**
     * Importo della spesa secondo il calculation_type, sulle basi date (mese o anno).
     * $applyLimits decide se applicare minimali/massimali: l'annuale sì, l'accantonamento mensile no.
     */
    public function expectedAmount(AnnualExpense $expense, RevenueBases $bases, bool $applyLimits): float
    {
        $minimum = $applyLimits && $expense->minimum !== null ? (float) $expense->minimum : null;
        $maximum = $applyLimits && $expense->maximum !== null ? (float) $expense->maximum : null;

        return match ($expense->calculation_type) {
            ExpenseCalculationType::PercentageOfIrpefIncome => $this->percentageAmount($bases->irpefIncome, (float) $expense->rate, $minimum, $maximum),
            ExpenseCalculationType::PercentageOfIvaRevenue => $this->percentageAmount($bases->vatTurnover, (float) $expense->rate, $minimum, $maximum),
            ExpenseCalculationType::FixedAnnual => round((float) $expense->amount, 2),
            ExpenseCalculationType::SumOfBolli => round($bases->stampDuty, 2),
        };
    }

    /**
     * Importo definitivo (effettivo): il manuale prevale sul calcolato, se presente.
     */
    public function definitiveAmount(?float $manualAmount, float $calculatedAmount): float
    {
        return $manualAmount ?? $calculatedAmount;
    }

    /**
     * Importo pagato: somma dei pagamenti in stato "pagato".
     */
    public function paidAmount(Collection $payments): float
    {
        return round((float) $payments
            ->where('status', PaymentStatus::Paid)
            ->sum('amount'), 2);
    }

    /**
     * Importo maturato ad oggi. Voci fisse: quota proporzionale ai mesi trascorsi
     * (12 = anno concluso). Altre: il definitivo (la base è già "ad oggi").
     */
    public function amountToDate(AnnualExpense $expense, float $definitiveAmount, int $monthsElapsed): float
    {
        if ($expense->calculation_type !== ExpenseCalculationType::FixedAnnual) {
            return $definitiveAmount;
        }

        return round($definitiveAmount / 12 * $monthsElapsed, 2);
    }

    /**
     * Importo da pagare: definitivo − pagato (può essere negativo se sovra-pagato).
     */
    public function amountDue(float $definitiveAmount, float $paidAmount): float
    {
        return round($definitiveAmount - $paidAmount, 2);
    }

    /**
     * Importo da pagare ad oggi: maturato ad oggi − pagato.
     */
    public function amountDueToDate(float $amountToDate, float $paidAmount): float
    {
        return round($amountToDate - $paidAmount, 2);
    }
}
