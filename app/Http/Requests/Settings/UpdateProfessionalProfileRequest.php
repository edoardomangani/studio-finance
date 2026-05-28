<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessionalProfileRequest extends FormRequest
{
    use ProfileValidationRules;

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
            // Le regole nome+email arrivano dal trait condiviso con
            // ProfileUpdateRequest. Il controller mappa 'name' su users.name.
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->user()->id),
            'profitability_coefficient' => ['required', 'numeric', 'between:0,100'],
            'business_start_year' => ['required', 'integer', 'between:1990,'.date('Y')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
            'email.unique' => 'Questa email è già utilizzata.',
            'profitability_coefficient.required' => 'Il coefficiente di redditività è obbligatorio.',
            'profitability_coefficient.between' => 'Il coefficiente deve essere tra 0 e 100.',
            'business_start_year.required' => "L'anno di inizio attività è obbligatorio.",
            'business_start_year.between' => "L'anno deve essere tra 1990 e :max.",
        ];
    }
}
