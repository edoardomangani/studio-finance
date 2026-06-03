<?php

namespace App\Services;

/**
 * Figure d'anno calcolate una volta sola dalle fatture: scalari da fatturazione,
 * reddito IRPEF lordo/netto, contributi e le basi (lordo/netto) per le spese.
 */
final class YearFigures
{
    public function __construct(
        public readonly float $taxableAmount,
        public readonly float $stampDuty,
        public readonly float $vatTurnover,
        public readonly float $invoiceTotal,
        public readonly float $withholdings,
        public readonly float $irpefIncomeGross,
        public readonly float $irpefIncomeNet,
        public readonly float $pensionContributionsPaid,
        public readonly RevenueBases $grossBases,
        public readonly RevenueBases $netBases,
    ) {}
}
