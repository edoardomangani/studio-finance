<?php

namespace App\Enums;

/**
 * Tipo fiscale ("famiglia") di una voce di spesa: insieme chiuso, definito dal
 * dominio forfettario. È la categoria autoritativa che guida calcolo e
 * raggruppamento — sostituisce il vecchio flag `is_pension_contribution` e
 * l'euristica "imposta sostitutiva". Ogni voce ha un solo `kind`; le famiglie
 * mostrate all'utente sono questi quattro tipi, rinominabili ma non creabili
 * (vedi [[App\Models\ExpenseFamily]]).
 *
 * Aggancio ai calcoli (solo due tipi hanno semantica fiscale, gli altri sono
 * solo raggruppamento):
 *  - Pension → contributo previdenziale: reddito lordo, minimale, conguaglio,
 *    e concorre ai "contributi pagati nell'anno" dedotti dall'imposta sostitutiva.
 *  - Tax → imposta sostitutiva: scala ritenute bancarie + credito anno precedente.
 *
 * Il valore conserva i termini fiscali (Bolli) dove non c'è equivalente inglese.
 */
enum ExpenseKind: string
{
    case Tax = 'tax';
    case Pension = 'pension';
    case StampDuty = 'stamp_duty';
    case Fixed = 'fixed';

    /**
     * Nome di default della famiglia (rinominabile dall'utente).
     */
    public function label(): string
    {
        return match ($this) {
            self::Tax => 'Imposte',
            self::Pension => 'Previdenza',
            self::StampDuty => 'Bolli',
            self::Fixed => 'Costi fissi',
        };
    }

    /**
     * Spiegazione per la pagina Impostazioni: aiuta a rinominare con cognizione.
     */
    public function description(): string
    {
        return match ($this) {
            self::Tax => 'Imposta sostitutiva e tributi sul reddito.',
            self::Pension => 'Contributi previdenziali (es. Inarcassa): reddito lordo, minimale, conguaglio.',
            self::StampDuty => 'Imposta di bollo sulle fatture (marche da bollo).',
            self::Fixed => 'Costi fissi annuali: commercialista, assicurazione, quota Ordine…',
        };
    }
}
