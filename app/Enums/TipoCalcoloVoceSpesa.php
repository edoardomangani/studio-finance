<?php

namespace App\Enums;

/**
 * Tipo di calcolo della voce di spesa template.
 *
 * - perc_reddito_irpef: % sul reddito IRPEF (forfettario × coefficiente).
 *   Es. Imposta sostitutiva, Inarcassa Soggettivo.
 * - perc_volume_affari_iva: % sul volume d'affari IVA (imponibile fatture).
 *   Es. Inarcassa Integrativo.
 * - fissa_annuale: importo annuale fisso. Es. Maternità, Commercialista,
 *   Assicurazione, OATO.
 * - somma_bolli: somma dei bolli applicati alle fatture dell'anno.
 *   Es. voce "Bolli" che rimborsa al cliente i bolli applicati.
 */
enum TipoCalcoloVoceSpesa: string
{
    case PercRedditoIrpef = 'perc_reddito_irpef';
    case PercVolumeAffariIva = 'perc_volume_affari_iva';
    case FissaAnnuale = 'fissa_annuale';
    case SommaBolli = 'somma_bolli';

    public function label(): string
    {
        return match ($this) {
            self::PercRedditoIrpef => '% reddito IRPEF',
            self::PercVolumeAffariIva => '% volume affari IVA',
            self::FissaAnnuale => 'Importo fisso annuale',
            self::SommaBolli => 'Somma bolli fatture',
        };
    }
}
