<?php

namespace App\Enums;

/**
 * Anno della spesa cui una scadenza tipo si riferisce.
 *
 * - corrente: l'istanza di scadenza punterà alla SpesaAnnuale dello stesso
 *   anno della scadenza (caso comune: rate IS, acconti Inarcassa).
 * - successivo: l'istanza punterà alla SpesaAnnuale dell'anno N+1
 *   (caso classico: Commercialista a dicembre per la pratica dell'anno
 *   successivo). Trigger della creazione "Anno pre-aperto" se N+1 non esiste.
 */
enum AnnoRiferimentoSpesa: string
{
    case Corrente = 'corrente';
    case Successivo = 'successivo';

    public function label(): string
    {
        return match ($this) {
            self::Corrente => 'Anno corrente',
            self::Successivo => 'Anno successivo',
        };
    }
}
