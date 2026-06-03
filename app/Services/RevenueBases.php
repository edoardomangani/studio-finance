<?php

namespace App\Services;

/**
 * Basi fiscali di un periodo (mese o anno), calcolate una volta da RevenueCalculator
 * e consumate da ExpenseCalculator.
 */
final class RevenueBases
{
    public function __construct(
        public readonly float $taxableAmount,
        public readonly float $irpefIncome,
        public readonly float $vatTurnover,
        public readonly float $stampDuty,
    ) {}
}
