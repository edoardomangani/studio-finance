<?php

namespace App\Enums;

/**
 * Anno della spesa cui una recurring deadline si riferisce.
 *
 * - current: la scadenza punterà all'AnnualExpense dello stesso anno
 *   (caso comune: rate IS, acconti Inarcassa).
 * - next: punterà all'AnnualExpense di N+1 (caso classico: commercialista
 *   31/12/N paga la parcella per N+1). Trigger della creazione "Anno
 *   pre-aperto" N+1 se non esiste.
 */
enum ExpenseYearOffset: string
{
    case Current = 'current';
    case Next = 'next';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Anno corrente',
            self::Next => 'Anno successivo',
        };
    }
}
