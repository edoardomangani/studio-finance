/**
 * Format helpers — single source of truth per la formattazione di valori
 * fiscali e date. Usato da pagine Inertia (clients, invoices, future
 * scadenze/pagamenti) per evitare duplicazione e drift di locale.
 */

const EUR_FORMATTER = new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
});

/** Formatta un numero come valuta EUR locale italiana (`€ 1.234,56`). */
export function formatEUR(n: number): string {
    return EUR_FORMATTER.format(n);
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
