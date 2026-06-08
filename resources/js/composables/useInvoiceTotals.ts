/**
 * **Sync col backend**: la fonte di verità autoritativa è
 * `app/Services/InvoiceCalculator.php`. Questo composable è il mirror
 * frontend per UX live (preview reattiva); il backend canonicalizza ogni
 * valore al salvataggio. Le formule QUI devono restare in parità con
 * quelle PHP — il file `tests/Fixtures/invoice_calc_cases.json` è il
 * "contratto" condiviso (testato in `tests/Unit/InvoiceCalculatorTest.php`).
 * Modificare una formula qui senza aggiornare PHP + fixture causerà
 * divergenza visibile tra UI preview e salvato.
 *
 * useInvoiceTotals — calcoli live + default dirty-aware per il form fattura.
 *
 * Espone:
 * - `total`           Imponibile + cassa + bollo + art.15 (computed reattivo)
 * - `withholdingAmount` Ritenuta bancaria (scorporo IVA 22% + aliquota per data), 0 se off
 * - `netAmount`       Totale meno ritenuta (computed)
 * - `markInarcassaDirty()` / `markStampDirty()` invocati dai field on `input`
 *   per fermare l'auto-update dal momento in cui l'utente tocca esplicitamente
 *   il campo. I default si ricalcolano automaticamente *solo* se non toccati.
 *
 * Vedi RB3 per i default:
 * - Cassa Inarcassa = **(imponibile + bollo) × 4%**. Il bollo è parte
 *   della base contributiva (decisione di progetto, allineata alla
 *   prassi più conservativa per architetti Inarcassa).
 * - Bollo = €2 se imponibile > €77,47, altrimenti 0 (ma sempre editabile)
 * - Ritenuta = (totale / 1,22) × aliquota per data: 8% fino al 29/2/2024, 11% dal 1/3/2024
 *
 * **Sync col backend**: le costanti ritenuta qui devono restare allineate a
 * `App\Services\InvoiceCalculator` (`withholding()` è la formula unica;
 * `Invoice::withholdingAmount()` accessor delega lì). Aggiornare entrambi.
 */
import { computed, ref, watch } from 'vue';

const WITHHOLDING_RATE_LEGACY = 0.08; // bonifici fino al 29/2/2024
const WITHHOLDING_RATE = 0.11; // bonifici dal 1/3/2024
const WITHHOLDING_RATE_CHANGE = '2024-03-01';
const ORDINARY_VAT_DIVISOR = 1.22; // scorporo IVA ordinaria 22%
const INARCASSA_RATE = 0.04;
const STAMP_THRESHOLD = 77.47;
const STAMP_VALUE = 2.0;

export type InvoiceTotalsForm = {
    issued_at: string;
    amount: number | string;
    inarcassa_amount: number | string;
    stamp_amount: number | string;
    stamp_charged_to_client: boolean;
    art_15_amount: number | string;
    bank_withholding: boolean;
};

function toFloat(v: number | string | null | undefined): number {
    if (typeof v === 'number') {
        return Number.isFinite(v) ? v : 0;
    }

    if (typeof v !== 'string' || v === '') {
        return 0;
    }

    const n = parseFloat(v.replace(',', '.'));

    return Number.isFinite(n) ? n : 0;
}

export function useInvoiceTotals(form: InvoiceTotalsForm) {
    const inarcassaDirty = ref(false);
    const stampDirty = ref(false);

    const total = computed(() => {
        const a = toFloat(form.amount);
        const inarcassa = toFloat(form.inarcassa_amount);
        // Il bollo entra nel totale solo se a carico del cliente (rivalsa).
        const stamp = form.stamp_charged_to_client
            ? toFloat(form.stamp_amount)
            : 0;
        const art15 = toFloat(form.art_15_amount);

        return Math.round((a + inarcassa + stamp + art15) * 100) / 100;
    });

    const withholdingAmount = computed(() => {
        if (!form.bank_withholding) {
            return 0;
        }

        // Aliquota per data del bonifico (issued_at); base scorporata dell'IVA 22%.
        const rate =
            form.issued_at && form.issued_at < WITHHOLDING_RATE_CHANGE
                ? WITHHOLDING_RATE_LEGACY
                : WITHHOLDING_RATE;

        return (
            Math.round((total.value / ORDINARY_VAT_DIVISOR) * rate * 100) / 100
        );
    });

    const netAmount = computed(
        () => Math.round((total.value - withholdingAmount.value) * 100) / 100,
    );

    function markInarcassaDirty(): void {
        inarcassaDirty.value = true;
    }

    function markStampDirty(): void {
        stampDirty.value = true;
    }

    /** Forza la cassa/bollo a "non toccati". Utile in edit quando i valori
     *  storati coincidono col default (per evitare di considerarli dirty). */
    function resetDirty(): void {
        inarcassaDirty.value = false;
        stampDirty.value = false;
    }

    // Watch combinata su amount + stamp_amount + rivalsa bollo. Evita race
    // condition tra watch separati: l'auto-stamp arriva PRIMA dell'auto-cassa
    // così la base per Inarcassa include il bollo aggiornato. Dipende anche
    // dal flag rivalsa: se il bollo non è a carico del cliente esce dalla base.
    watch(
        () =>
            [
                toFloat(form.amount),
                toFloat(form.stamp_amount),
                form.stamp_charged_to_client,
            ] as const,
        ([amount, , chargedToClient]) => {
            // Auto-bollo dipende solo da amount.
            let stampEffective = toFloat(form.stamp_amount);

            if (!stampDirty.value) {
                const newStamp = amount > STAMP_THRESHOLD ? STAMP_VALUE : 0;
                const newStampStr = newStamp.toFixed(2);

                if (form.stamp_amount !== newStampStr) {
                    form.stamp_amount = newStampStr;
                    stampEffective = newStamp;
                }
            }

            // Auto-cassa: base = imponibile + bollo (RB3), ma il bollo entra
            // nella base solo se a carico del cliente (coerente col totale).
            if (!inarcassaDirty.value) {
                const base = amount + (chargedToClient ? stampEffective : 0);
                form.inarcassa_amount = (
                    Math.round(base * INARCASSA_RATE * 100) / 100
                ).toFixed(2);
            }
        },
    );

    return {
        total,
        withholdingAmount,
        netAmount,
        markInarcassaDirty,
        markStampDirty,
        resetDirty,
    };
}
