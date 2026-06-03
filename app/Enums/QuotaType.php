<?php

namespace App\Enums;

/**
 * Tipo di quota di una scadenza di pagamento (RB7/RB8). Determina come si
 * calcola l'importo previsto (suggerimento) della scadenza — vista derivata,
 * mai a DB, vedi [[App\Models\Deadline]]::expectedAmount().
 *
 * Nullable: valorizzato solo per le scadenze di tipo payment.
 *
 * - tax_advance: acconto imposta. IS netta dell'anno N-1 ÷ n° acconti.
 * - tax_balance: saldo imposta. definitive della spesa − acconti già pagati.
 * - contribution_minimum: rata del minimale contributivo. minimale ÷ n° rate.
 * - contribution_adjustment: conguaglio. definitive − minimi già pagati.
 * - full_amount: quota intera. definitive ÷ n° rate (= definitive per pagamento
 *   unico). Le rate Bolli sono un caso speciale gestito a parte (saldo bolli
 *   maturati − pagati), vedi expectedAmount().
 */
enum QuotaType: string
{
    case TaxAdvance = 'tax_advance';
    case TaxBalance = 'tax_balance';
    case ContributionMinimum = 'contribution_minimum';
    case ContributionAdjustment = 'contribution_adjustment';
    case FullAmount = 'full_amount';

    public function label(): string
    {
        return match ($this) {
            self::TaxAdvance => 'Acconto imposta',
            self::TaxBalance => 'Saldo imposta',
            self::ContributionMinimum => 'Minimo contributivo',
            self::ContributionAdjustment => 'Conguaglio contributivo',
            self::FullAmount => 'Quota intera',
        };
    }
}
