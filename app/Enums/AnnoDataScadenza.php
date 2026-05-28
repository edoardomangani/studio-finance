<?php

namespace App\Enums;

/**
 * Anno calendariale in cui cade la data della scadenza, relativa all'anno
 * del wizard (N).
 *
 * - corrente: la data della scadenza cade in N (default).
 *   Es. assicurazione 31/03/N, 1° acconto IS 30/06/N.
 * - successivo: la data cade in N+1.
 *   Es. saldo IS 30/06/N+1, bolli Q4 28/02/N+1.
 *
 * Ortogonale a [[AnnoRiferimentoSpesa]]: la prima dice QUANDO si paga, la
 * seconda QUALE spesa si paga.
 */
enum AnnoDataScadenza: string
{
    case Corrente = 'corrente';
    case Successivo = 'successivo';

    public function label(): string
    {
        return match ($this) {
            self::Corrente => 'Stesso anno',
            self::Successivo => 'Anno successivo',
        };
    }
}
