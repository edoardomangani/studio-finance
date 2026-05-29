<?php

namespace App\Enums;

/**
 * Stato di una scadenza (istanza). Reversibili tutti con conferma (F9):
 * - open: appena generata dal wizard, in attesa.
 * - completed: pagamento registrato (per kind=payment) o adempimento svolto.
 * - not_due: marcata come non dovuta dall'utente; il pagamento collegato
 *   diventa anch'esso not_due.
 */
enum DeadlineStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case NotDue = 'not_due';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aperta',
            self::Completed => 'Completata',
            self::NotDue => 'Non dovuta',
        };
    }
}
