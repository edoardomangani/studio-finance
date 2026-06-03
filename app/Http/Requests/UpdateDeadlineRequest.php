<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aggiornamento di una scadenza. Nome e data sono sempre modificabili (anche
 * sulle standard da template); `kind` è immutabile (non si trasforma un
 * pagamento in adempimento dopo la creazione). `annual_expense_id` è opzionale
 * e applicato dal service solo quando lecito (scadenza ad-hoc di pagamento non
 * ancora pagata); la regola `exists` resta scoping di tenancy esplicito.
 */
class UpdateDeadlineRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'annual_expense_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('annual_expenses', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            // Previsto manuale: applicato dal service solo quando lecito
            // (ad-hoc di pagamento non ancora pagata).
            'manual_expected_amount' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:9999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci un nome per la scadenza.',
            'due_at.required' => 'Inserisci la data di scadenza.',
            'annual_expense_id.exists' => 'Spesa non valida.',
        ];
    }
}
