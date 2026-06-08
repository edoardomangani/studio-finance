<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rinomina di una famiglia di spesa: solo il `name` (il `kind` è immutabile).
 * Tenancy via global scope sul route-model binding (404 se non è dell'utente).
 */
class UpdateExpenseFamilyRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('expense_families', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('expenseFamily')->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'name.unique' => 'Esiste già una famiglia con questo nome.',
        ];
    }
}
