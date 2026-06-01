/**
 * Clampa un valore numerico (di un form) nell'intervallo [min, max].
 *
 * Agganciato su `@blur` agli `<Input type="number">` percentuali: l'attributo
 * `max` HTML non impedisce di DIGITARE valori fuori range, e affidarsi alla
 * sola validazione backend rimanderebbe l'errore al submit (problematico nei
 * flussi multi-step come il wizard apertura anno). Sul blur il valore torna nei
 * limiti subito, nello step in cui è stato inserito. Il vuoto resta ''.
 */
export function clampNumber(value: number | string, min: number, max: number): number | string {
    if (value === '' || value === null || value === undefined) {
        return '';
    }

    const n = Number(value);

    if (Number.isNaN(n)) {
        return value;
    }

    return Math.min(max, Math.max(min, n));
}
