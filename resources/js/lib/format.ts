/**
 * Format helpers — single source of truth per la formattazione di valori
 * fiscali e date. Usato da pagine Inertia (clients, invoices, future
 * scadenze/pagamenti) per evitare duplicazione e drift di locale.
 */

// `useGrouping: 'always'`: in it-IT il default ('auto') NON raggruppa i numeri
// a 4 cifre (`minimumGroupingDigits`), quindi 1.200 verrebbe reso "1200".
// Forziamo il separatore di migliaia sempre, anche sulle 4 cifre.
const EUR_FORMATTER = new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
    useGrouping: 'always',
});

/** Formatta un numero come valuta EUR locale italiana (`1.234,56 €`). */
export function formatEUR(n: number): string {
    return EUR_FORMATTER.format(n);
}

const DECIMAL_FORMATTER = new Intl.NumberFormat('it-IT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    useGrouping: 'always',
});

/**
 * Regola generale: OGNI cifra mostrata in UI passa da qui (o da [[formatEUR]]
 * / [[formatPercent]]), mai da `toFixed`/`toLocaleString` inline. Locale it-IT
 * → separatore migliaia "." e decimali ",". Default 2 decimali per allineare
 * importi e percentuali in colonna.
 */
export function formatNumber(n: number, fractionDigits = 2): string {
    if (fractionDigits === 2) {
        return DECIMAL_FORMATTER.format(n);
    }

    return new Intl.NumberFormat('it-IT', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
        useGrouping: 'always',
    }).format(n);
}

/** Percentuale formattata locale italiana con suffisso `%` (`14,50%`). */
export function formatPercent(n: number, fractionDigits = 2): string {
    return `${formatNumber(n, fractionDigits)}%`;
}

/** Formatta una data ISO `YYYY-MM-DD` come `DD/MM/YYYY`. Fallback `—`
 *  per null/empty. */
export function formatDateIT(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    const [y, m, d] = iso.split('-');

    if (!y || !m || !d) {
        return '—';
    }

    return `${d}/${m}/${y}`;
}

/** Data odierna come ISO `YYYY-MM-DD` in fuso orario locale (no UTC).
 *  `new Date().toISOString()` userebbe UTC → bug intorno a mezzanotte
 *  locale (in Italia UTC+1/+2). `sv-SE` produce nativamente `YYYY-MM-DD`. */
export function todayISO(): string {
    return new Date().toLocaleDateString('sv-SE');
}
