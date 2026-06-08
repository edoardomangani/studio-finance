<?php

namespace App\Services;

use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Models\AnnualExpense;
use App\Models\Year;

/**
 * Service per il dominio AnnualExpense. Le spese ricorrenti nascono dal wizard
 * di apertura ([[App\Actions\Studiofinance\OpenYear]]) dai template; qui vivono
 * la modifica dei valori post-apertura e la spesa una-tantum del singolo anno
 * (costo specifico non da template). Tenancy via global scope
 * [[App\Concerns\BelongsToUser]].
 */
class AnnualExpenseService
{
    /**
     * Crea una spesa una-tantum per l'anno: importo fisso, senza template
     * (`expense_item_id` null) né scadenze generate. Si paga con un pagamento
     * manuale o una scadenza ad-hoc.
     */
    public function create(Year $year, string $name, float $amount, ExpenseKind $kind): AnnualExpense
    {
        return $year->annualExpenses()->create([
            'name' => $name,
            'calculation_type' => ExpenseCalculationType::FixedAnnual,
            'kind' => $kind,
            'amount' => $amount,
            'expense_item_id' => null,
        ]);
    }

    /**
     * Elimina (soft delete) una spesa una-tantum. Presuppone il guard del
     * controller (solo `expense_item_id` null e senza pagamenti): le voci da
     * template non si cancellano post-apertura (lascerebbero scadenze orfane).
     */
    public function delete(AnnualExpense $expense): void
    {
        $expense->delete();
    }

    /**
     * Aggiorna i valori di una spesa annuale. I dati sono già validati e
     * filtrati per tipo da [[App\Http\Requests\UpdateAnnualExpenseRequest]]:
     * solo le chiavi pertinenti al `calculation_type` arrivano qui.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AnnualExpense $expense, array $data): void
    {
        $expense->update($data);
    }
}
