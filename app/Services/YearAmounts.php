<?php

namespace App\Services;

use App\Models\AnnualExpense;
use App\Models\Invoice;
use App\Models\Payment;
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
     * @param  Collection<int, Invoice>  $invoices  fatture dell'anno (ordinate per la vista, con `client`)
     * @param  Collection<int, AnnualExpense>  $expenses  spese dell'anno
     * @param  ExpenseAmounts  $expenseAmounts  mappa expense_id => famiglia importi
     * @param  Collection<int, Payment>  $payments  pagamenti `paid` rilevanti per l'anno
     *                                              (per cassa ∪ per competenza, RB9), con
     *                                              `annualExpense.year` e `deadline` caricati.
     *                                              È l'union della singola query pagamenti
     *                                              del loader: riusata da vista, scadenze e tab.
     */
    public function __construct(
        public readonly Collection $invoices,
        public readonly YearFigures $figures,
        public readonly int $monthsElapsed,
        public readonly Collection $expenses,
        public readonly array $expenseAmounts,
        public readonly Collection $payments,
    ) {}
}
