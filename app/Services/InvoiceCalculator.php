<?php

namespace App\Services;

/**
 * InvoiceCalculator — fonte di verità autoritativa per i calcoli fattura.
 *
 * Centralizza in PHP la logica che vive (per UX) anche in
 * `resources/js/composables/useInvoiceTotals.ts`. Il frontend ricalcola
 * live come mirror; il backend canonicalizza prima del salvataggio
 * (vedi `InvoiceService::create/update`) per evitare divergenze e
 * difendersi da payload manomessi.
 *
 * **Sync con TS**: ogni modifica alle formule QUI richiede modifica
 * simmetrica in `useInvoiceTotals.ts` + `tests/Fixtures/invoice_calc_cases.json`.
 * Il test parity `InvoiceCalculatorTest` blocca le divergenze backend-vs-fixture.
 *
 * **Single source of truth costanti**: `BANK_WITHHOLDING_RATE` è referenziata
 * anche dal model accessor [[App\Models\Invoice::withholdingAmount]] per il
 * rendering JSON. Modificare l'aliquota qui propaga ovunque.
 *
 * **Regole RB3** (forfettario architetti Inarcassa):
 * - Bollo €2.00 se imponibile > €77,47, altrimenti 0.
 * - Cassa Inarcassa = (imponibile + bollo) × 4%.
 * - Totale = imponibile + bollo + cassa + art.15.
 * - Ritenuta bancaria = totale × 8% se flag attivo.
 * - Netto = totale − ritenuta.
 *
 * Override utente: il metodo `canonicalize()` confronta i valori inviati
 * con i default calcolati; se differiscono entro tolleranza, sono "auto"
 * e vengono ricalcolati; se differiscono di più, sono "override" e
 * vengono preservati. Strategia "heuristic server-side" — niente
 * protocollo dirty-flag esplicito FE↔BE.
 */
class InvoiceCalculator
{
    public const BANK_WITHHOLDING_RATE = 0.08;

    public const INARCASSA_RATE = 0.04;

    public const STAMP_THRESHOLD = 77.47;

    public const STAMP_DEFAULT = 2.00;

    /**
     * Tolleranza per il match auto-vs-override. €0.01 perché tutti gli
     * importi sono decimal(12,2): differenze sub-cent sono artefatti
     * di floating point, non override intenzionali.
     */
    public const HEURISTIC_TOLERANCE = 0.01;

    /**
     * Bollo di default in base all'imponibile (RB3).
     */
    public function defaultStamp(float $amount): float
    {
        return $amount > self::STAMP_THRESHOLD ? self::STAMP_DEFAULT : 0.0;
    }

    /**
     * Cassa Inarcassa di default in base a (imponibile + bollo) × 4%.
     */
    public function defaultInarcassa(float $amount, float $stamp): float
    {
        return $this->round2(($amount + $stamp) * self::INARCASSA_RATE);
    }

    /**
     * Totale + ritenuta + netto, partendo dai 4 importi e dal flag.
     * Non applica nessun override/heuristic: pura aritmetica.
     *
     * @return array{total: float, withholding_amount: float, net_amount: float}
     */
    public function totals(
        float $amount,
        float $stamp,
        float $inarcassa,
        float $art15,
        bool $bankWithholding,
    ): array {
        $total = $this->round2($amount + $stamp + $inarcassa + $art15);

        $withholdingAmount = $bankWithholding
            ? $this->round2($total * self::BANK_WITHHOLDING_RATE)
            : 0.0;

        $netAmount = $this->round2($total - $withholdingAmount);

        return [
            'total' => $total,
            'withholding_amount' => $withholdingAmount,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Canonicalizza l'input prima del salvataggio.
     *
     * Strategia "heuristic server-side":
     * - Per ogni campo derivabile (stamp, inarcassa), calcola il default.
     * - Se il valore inviato coincide col default entro tolleranza →
     *   è "auto" → usa il default canonico (evita drift sub-cent).
     * - Se differisce di più → è "override utente" → preserva il valore.
     *
     * Ordine importante: prima canonicalizza stamp (dipende solo da
     * amount), poi inarcassa (dipende da amount + stamp canonico).
     *
     * @param  array{
     *     amount: float|int|string,
     *     stamp_amount?: float|int|string|null,
     *     inarcassa_amount?: float|int|string|null,
     *     art_15_amount?: float|int|string|null,
     *     bank_withholding?: bool,
     * }  $input
     * @return array{
     *     amount: float,
     *     stamp_amount: float,
     *     inarcassa_amount: float,
     *     art_15_amount: float,
     *     bank_withholding: bool,
     *     total: float,
     *     withholding_amount: float,
     *     net_amount: float,
     * }
     */
    public function canonicalize(array $input): array
    {
        $amount = $this->toFloat($input['amount'] ?? 0);
        $art15 = $this->round2($this->toFloat($input['art_15_amount'] ?? 0));
        $bankWithholding = (bool) ($input['bank_withholding'] ?? false);

        $stamp = $this->canonicalizeStamp($amount, $this->toFloat($input['stamp_amount'] ?? null));
        $inarcassa = $this->canonicalizeInarcassa($amount, $stamp, $this->toFloat($input['inarcassa_amount'] ?? null));

        $totals = $this->totals($amount, $stamp, $inarcassa, $art15, $bankWithholding);

        return [
            'amount' => $this->round2($amount),
            'stamp_amount' => $stamp,
            'inarcassa_amount' => $inarcassa,
            'art_15_amount' => $art15,
            'bank_withholding' => $bankWithholding,
            ...$totals,
        ];
    }

    /** Canonicalize stamp: ≈ default → ricalcola; differisce → override. */
    private function canonicalizeStamp(float $amount, float $sent): float
    {
        $auto = $this->defaultStamp($amount);

        return $this->isAuto($sent, $auto) ? $auto : $this->round2($sent);
    }

    /** Canonicalize inarcassa: base = amount + stamp CANONICO (non sent). */
    private function canonicalizeInarcassa(float $amount, float $canonicalStamp, float $sent): float
    {
        $auto = $this->defaultInarcassa($amount, $canonicalStamp);

        return $this->isAuto($sent, $auto) ? $auto : $this->round2($sent);
    }

    /**
     * True se i due valori differiscono di meno della tolleranza.
     */
    private function isAuto(float $sent, float $computed): bool
    {
        return abs($sent - $computed) < self::HEURISTIC_TOLERANCE;
    }

    private function round2(float $n): float
    {
        return round($n, 2);
    }

    private function toFloat(mixed $v): float
    {
        if (is_float($v) || is_int($v)) {
            return (float) $v;
        }

        if (is_string($v)) {
            $normalized = str_replace(',', '.', trim($v));

            return is_numeric($normalized) ? (float) $normalized : 0.0;
        }

        return 0.0;
    }
}
