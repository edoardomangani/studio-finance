/**
 * Origine di navigazione — breadcrumb che ricordano il punto d'accesso.
 *
 * Una pagina di dettaglio (es. fattura) può essere raggiunta da più superfici
 * (lista Fatture, vista anno, …). Invece di breadcrumb statiche, chi linka
 * allega il proprio "trail" via `?from=…` con [[withOrigin]]; la destinazione
 * lo rilegge con [[originTrail]] e costruisce le breadcrumb di conseguenza,
 * con un fallback statico quando si arriva da deep-link.
 */
import { usePage } from '@inertiajs/vue3';

export type OriginCrumb = { label: string; href: string };

/** Appende il trail di origine a un url come `?from=<json>`. */
export function withOrigin(url: string, trail: OriginCrumb[]): string {
    if (trail.length === 0) {
        return url;
    }

    const separator = url.includes('?') ? '&' : '?';

    return `${url}${separator}from=${encodeURIComponent(JSON.stringify(trail))}`;
}

/** Legge il trail di origine dall'URL corrente (SSR-safe via usePage). */
export function originTrail(): OriginCrumb[] {
    const query = usePage().url.split('?')[1];

    if (!query) {
        return [];
    }

    const raw = new URLSearchParams(query).get('from');

    if (!raw) {
        return [];
    }

    try {
        const parsed: unknown = JSON.parse(raw);

        if (
            Array.isArray(parsed) &&
            parsed.every(
                (c): c is OriginCrumb =>
                    typeof c?.label === 'string' && typeof c?.href === 'string',
            )
        ) {
            return parsed;
        }
    } catch {
        // `from` malformato → fallback statico.
    }

    return [];
}
