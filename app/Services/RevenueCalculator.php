<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Calcolatore puro delle basi da fatturazione. Riceve le fatture (di un mese o
 * di un anno) e calcola; non interroga il DB. Il fetch e il raggruppamento per
 * mese vivono nel chiamante.
 */
class RevenueCalculator
{
    /**
     * Imponibile: somma dei compensi (Invoice::amount).
     */
    public function taxableAmount(Collection $invoices): float
    {
        return round((float) $invoices->sum('amount'), 2);
    }

    /**
     * Bolli: somma di Invoice::stamp_amount.
     */
    public function stampDuty(Collection $invoices): float
    {
        return round((float) $invoices->sum('stamp_amount'), 2);
    }

    /**
     * Volume d'affari IVA: imponibile + bolli, all'unità.
     * Base per Inarcassa Integrativo e per il reddito IRPEF.
     */
    public function vatTurnover(Collection $invoices): float
    {
        return $this->vatFrom($this->taxableAmount($invoices), $this->stampDuty($invoices));
    }

    /**
     * Regola unica dello scorporo: volume d'affari = imponibile + bolli, all'unità.
     */
    private function vatFrom(float $taxable, float $stamp): float
    {
        return round($taxable + $stamp, 0);
    }

    /**
     * Totale fatture: imponibile + cassa + bollo + art.15 (mirror di Invoice::total).
     */
    public function invoiceTotal(Collection $invoices): float
    {
        return round($invoices->sum(fn (Invoice $invoice): float => (float) $invoice->total), 2);
    }

    /**
     * Ritenute bancarie: somma delle ritenute per-fattura (Invoice::withholdingAmount).
     */
    public function withholding(Collection $invoices): float
    {
        return round($invoices->sum(fn (Invoice $invoice): float => (float) $invoice->withholding_amount), 2);
    }

    /**
     * Reddito IRPEF previsto: volume d'affari × coefficiente redditività (es. 78.00), all'unità.
     */
    public function irpefIncome(Collection $invoices, float $coefficient): float
    {
        return round($this->vatTurnover($invoices) * $coefficient / 100, 2);
    }

    /**
     * Tutte le basi del periodo in un colpo, da passare a ExpenseCalculator.
     */
    public function bases(Collection $invoices, float $coefficient): RevenueBases
    {
        $taxable = $this->taxableAmount($invoices);
        $stamp = $this->stampDuty($invoices);
        $vat = $this->vatFrom($taxable, $stamp);

        return new RevenueBases(
            taxableAmount: $taxable,
            irpefIncome: round($vat * $coefficient / 100, 2),
            vatTurnover: $vat,
            stampDuty: $stamp,
        );
    }
}
