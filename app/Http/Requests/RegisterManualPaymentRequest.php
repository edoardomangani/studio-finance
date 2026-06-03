<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registrazione di un pagamento manuale extra-scadenza (F8).
 *
 * La regola `exists` su `annual_expense_id` è scoping di tenancy esplicito
 * (`where('user_id', ...)` + `whereNull('deleted_at')`): `Rule::exists` gira
 * una query raw che bypassa il global scope [[App\Concerns\BelongsToUser]],
 * quindi va vincolata a mano — un id di altro utente o archiviato deve fallire.
 */
class RegisterManualPaymentRequest extends FormRequest
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
            'annual_expense_id' => [
                'required',
                'integer',
                Rule::exists('annual_expenses', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            // Date passate ammesse (ricostruzione storico); future no.
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'annual_expense_id.required' => 'Scegli la spesa a cui imputare il pagamento.',
            'annual_expense_id.exists' => 'Spesa non valida.',
            'amount.required' => 'Inserisci un importo.',
            'amount.gt' => 'Inserisci un importo maggiore di zero.',
            'paid_at.required' => 'Inserisci la data del pagamento.',
            'paid_at.before_or_equal' => 'La data del pagamento non può essere futura.',
        ];
    }
}
