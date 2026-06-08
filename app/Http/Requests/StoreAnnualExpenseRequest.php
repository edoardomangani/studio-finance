<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creazione di una spesa una-tantum per un anno (costo specifico non ricorrente,
 * non da template). Solo importo fisso: una `% reddito IRPEF` non-previdenziale
 * verrebbe scambiata dal motore per una seconda imposta sostitutiva, e le voci
 * ricorrenti vanno create come template. `year_id` è vincolato alla tenancy
 * (la query raw di `Rule::exists` bypassa il global scope).
 */
class StoreAnnualExpenseRequest extends FormRequest
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
            'year_id' => [
                'required',
                'integer',
                Rule::exists('years', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci un nome per la voce.',
            'amount.required' => 'Inserisci l’importo annuale.',
        ];
    }
}
