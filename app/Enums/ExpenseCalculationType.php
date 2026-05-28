<?php

namespace App\Enums;

/**
 * Modalità di calcolo per un expense item template.
 *
 * - percentage_of_irpef_income: % sul reddito IRPEF (forfettario × coefficiente).
 *   Es. Imposta sostitutiva, Inarcassa Soggettivo.
 * - percentage_of_iva_revenue: % sul volume d'affari IVA (imponibile fatture).
 *   Es. Inarcassa Integrativo.
 * - fixed_annual: importo annuale fisso. Es. Maternità, Commercialista,
 *   Assicurazione, OATO.
 * - sum_of_bolli: somma dei bolli applicati alle fatture dell'anno.
 *
 * I valori conservano i termini fiscali italiani (IRPEF, IVA, bolli)
 * perché sono acronimi/proper-nouns senza equivalente inglese.
 */
enum ExpenseCalculationType: string
{
    case PercentageOfIrpefIncome = 'percentage_of_irpef_income';
    case PercentageOfIvaRevenue = 'percentage_of_iva_revenue';
    case FixedAnnual = 'fixed_annual';
    case SumOfBolli = 'sum_of_bolli';

    public function label(): string
    {
        return match ($this) {
            self::PercentageOfIrpefIncome => '% reddito IRPEF',
            self::PercentageOfIvaRevenue => '% volume affari IVA',
            self::FixedAnnual => 'Importo fisso annuale',
            self::SumOfBolli => 'Somma bolli fatture',
        };
    }
}
