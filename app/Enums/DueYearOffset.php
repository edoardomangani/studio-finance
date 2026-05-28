<?php

namespace App\Enums;

/**
 * Anno calendariale in cui cade la data della scadenza, relativo all'anno
 * del wizard (N).
 *
 * - current: la data cade in N (default). Es. assicurazione 31/03/N,
 *   1° acconto IS 30/06/N.
 * - next: la data cade in N+1. Es. saldo IS 30/06/N+1, bolli Q4 28/02/N+1.
 *
 * Ortogonale a [[ExpenseYearOffset]]: la prima dice QUANDO si paga, la
 * seconda QUALE spesa si paga.
 */
enum DueYearOffset: string
{
    case Current = 'current';
    case Next = 'next';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Stesso anno',
            self::Next => 'Anno successivo',
        };
    }
}
