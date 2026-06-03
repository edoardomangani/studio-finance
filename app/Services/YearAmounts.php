<?php

namespace App\Services;

use App\Models\AnnualExpense;
use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Dati d'anno caricati e calcolati una volta sola: fatture, figure (basi
 * lordo/netto, reddito, contributi), spese dell'anno e la mappa expense_id =>
 * famiglia importi. Prodotto da [[YearExpenseAmounts]]::compute e cacheato da
 * [[YearAmountsLoader]]; consumato sia dalla vista anno ([[YearService]]::forShow)
 * sia dall'importo previsto delle scadenze ([[DeadlineExpectation]]), senza
 * ricaricare.
 *
 * @phpstan-type ExpenseAmounts array<int, array<string, mixed>>
 */
final class YearAmounts
{
    /**
     * @param  Collection<int, Invoice>  $invoices  fatture dell'anno (ordinate per la vista)
     * @param  Collection<int, AnnualExpense>  $expenses  spese dell'anno
     * @param  ExpenseAmounts  $expenseAmounts  mappa expense_id => famiglia importi
     */
    public function __construct(
        public readonly Collection $invoices,
        public readonly YearFigures $figures,
        public readonly int $monthsElapsed,
        public readonly Collection $expenses,
        public readonly array $expenseAmounts,
    ) {}
}
