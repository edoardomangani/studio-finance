<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Tentativo di aprire formalmente un anno che esiste già come anno aperto
 * (NON pre-aperto). Un anno pre-aperto non solleva questa eccezione: il
 * wizard lo completa riusando le istanze esistenti (RB10).
 *
 * Lanciata da [[App\Actions\Studiofinance\OpenYear]]; il controller la
 * traduce in un errore di validazione sullo step "Scelta anno".
 */
class YearAlreadyOpenException extends RuntimeException
{
    public function __construct(public readonly int $year)
    {
        parent::__construct("Anno {$year} già aperto.");
    }
}
