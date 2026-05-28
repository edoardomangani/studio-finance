<?php

namespace App\Enums;

/**
 * Tipo di scadenza:
 * - pagamento: scadenza collegata a una voce di spesa, genera un pagamento
 *   pianificato all'apertura anno (1:1 con SpesaAnnuale).
 * - adempimento: scadenza informativa (es. dichiarazione redditi),
 *   nessun pagamento collegato.
 */
enum TipoScadenza: string
{
    case Pagamento = 'pagamento';
    case Adempimento = 'adempimento';

    public function label(): string
    {
        return match ($this) {
            self::Pagamento => 'Pagamento',
            self::Adempimento => 'Adempimento',
        };
    }
}
