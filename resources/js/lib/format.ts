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

/**
 * Normalizza il testo digitato in un input numerico verso la forma canonica
 * col PUNTO decimale, pronta per il submit: il backend valida `numeric` /
 * `decimal:0,2`, che accettano SOLO il punto e nessun separatore di migliaia.
 * Pensata per girare a ogni keystroke (vedi [[DecimalInput]]): l'utente può
 * digitare la virgola "all'italiana" e la vede diventare punto. Niente
 * migliaia in edit — scelta di progetto "edit grezzo": [[formatEUR]] /
 * [[formatNumber]] formattano solo in lettura.
 */
export function sanitizeDecimalInput(raw: string, allowNegative = true): string {
    const negative = allowNegative && raw.trimStart().startsWith('-');
    // virgola → punto, poi via tutto ciò che non è cifra o punto
    let body = raw.replace(',', '.').replace(/[^0-9.]/g, '');

    // collassa più punti tenendo solo il primo (un solo separatore decimale)
    const firstDot = body.indexOf('.');

    if (firstDot !== -1) {
        body =
            body.slice(0, firstDot + 1) +
            body.slice(firstDot + 1).replace(/\./g, '');
    }

    return negative ? `-${body}` : body;
}

/**
 * Converte in numero JS una stringa importo/percentuale, accettando virgola O
 * punto come separatore decimale e tollerando i punti di migliaia
 * all'italiana (`1.234,56` → `1234.56`). Ritorna `fallback` (default 0) per
 * input vuoti o non numerici. È l'inverso di [[formatNumber]] e la fonte unica
 * di parsing lato client (es. preview live dei totali fattura).
 */
export function parseDecimal(
    value: string | number | null | undefined,
    fallback = 0,
): number {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : fallback;
    }

    if (typeof value !== 'string' || value.trim() === '') {
        return fallback;
    }

    // Se compaiono sia "." sia ",", il punto è separatore di migliaia e la
    // virgola è il decimale (formato it-IT): togli i punti, virgola → punto.
    // Con la sola virgola, è il decimale. Con il solo punto, è già decimale.
    const hasComma = value.includes(',');
    const normalized = hasComma
        ? value.replace(/\./g, '').replace(',', '.')
        : value;

    const n = parseFloat(normalized);

    return Number.isFinite(n) ? n : fallback;
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
