<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCalculationType;
use App\Models\AnnualExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Modifica dei valori di una spesa annuale (correzione post-apertura anno).
 * Si toccano solo i valori, non la struttura: `calculation_type` e
 * `is_pension_contribution` definiscono il tipo di voce e restano immutabili
 * (cambiarli = altra spesa → si riapre l'anno). I campi richiesti dipendono dal
 * tipo: aliquota per le percentuali, importo per le fisse; i bolli non hanno
 * parametri di calcolo editabili (derivano dalle fatture). `effective_amount` è
 * l'override manuale (scavalca il calcolo) ed è ammesso per ogni tipo.
 */
class UpdateAnnualExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var AnnualExpense $expense */
        $expense = $this->route('annualExpense');
        $type = $expense->calculation_type;

        $isPercentage = in_array($type, [
            ExpenseCalculationType::PercentageOfIrpefIncome,
            ExpenseCalculationType::PercentageOfIvaRevenue,
        ], true);
        $isFixed = $type === ExpenseCalculationType::FixedAnnual;

        return [
            'name' => ['required', 'string', 'max:255'],
            'rate' => [Rule::requiredIf($isPercentage), 'nullable', 'numeric', 'min:0', 'max:100'],
            'minimum' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'maximum' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'amount' => [Rule::requiredIf($isFixed), 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'previous_year_credit' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'effective_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci un nome per la voce.',
            'rate.required' => 'Inserisci l’aliquota.',
            'amount.required' => 'Inserisci l’importo annuale.',
        ];
    }
}
