<?php

namespace App\Enums;

/**
 * Stato di un pagamento (RB9):
 * - planned: creato dal wizard insieme alla scadenza, importo e data vuoti.
 *   Non concorre ai totali della spesa.
 * - paid: pagamento registrato (F7) o manuale extra-scadenza (F8). È l'unico
 *   stato che concorre a `importo_pagato` della spesa.
 * - not_due: scadenza collegata marcata come non dovuta.
 */
enum PaymentStatus: string
{
    case Planned = 'planned';
    case Paid = 'paid';
    case NotDue = 'not_due';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Pianificato',
            self::Paid => 'Pagato',
            self::NotDue => 'Non dovuto',
        };
    }
}
