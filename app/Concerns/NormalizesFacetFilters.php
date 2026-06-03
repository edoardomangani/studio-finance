<?php

namespace App\Concerns;

/**
 * Normalizzazione dei parametri faccetta multi-select dalla query string.
 * Condiviso dai controller con liste filtrabili (Deadline, Invoice, Payment):
 * la UI manda `?year[]=2026&year[]=2027` ma tollera anche lo scalare
 * `?year=2026`. Estratto qui per evitare la copia in ogni controller.
 */
trait NormalizesFacetFilters
{
    /**
     * Faccetta → lista di int. Accetta sia l'array sia lo scalare; scarta i
     * valori non numerici e i duplicati.
     *
     * @return list<int>
     */
    protected function intArray(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->filter(fn ($v): bool => is_numeric($v))
            ->map(fn ($v): int => (int) $v)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Come [[intArray]] ma per valori stringa (es. enum kind/status): trim,
     * scarta le stringhe vuote e i duplicati.
     *
     * @return list<string>
     */
    protected function stringArray(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->map(fn ($v): string => is_string($v) ? trim($v) : '')
            ->filter(fn (string $v): bool => $v !== '')
            ->unique()
            ->values()
            ->all();
    }
}
