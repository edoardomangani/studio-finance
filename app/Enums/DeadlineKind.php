<?php

namespace App\Enums;

/**
 * Tipo di scadenza:
 * - payment: collegata a un expense item, genera un pagamento pianificato
 *   all'apertura anno (1:1 con AnnualExpense).
 * - fulfillment: scadenza informativa (es. dichiarazione redditi, Dich.RED
 *   Inarcassa), nessun pagamento collegato.
 */
enum DeadlineKind: string
{
    case Payment = 'payment';
    case Fulfillment = 'fulfillment';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Pagamento',
            self::Fulfillment => 'Adempimento',
        };
    }
}
