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
            // 'nome' è il campo form, mappato su User.name dal controller.
            // Riusiamo le regole del trait per coerenza con ProfileUpdateRequest.
            'nome' => $this->nameRules(),
            'email' => $this->emailRules($this->user()->id),
            'coefficiente_redditivita' => ['required', 'numeric', 'between:0,100'],
            'anno_inizio_attivita' => ['required', 'integer', 'between:1990,'.date('Y')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Il nome è obbligatorio.',
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
            'email.unique' => 'Questa email è già utilizzata.',
            'coefficiente_redditivita.required' => 'Il coefficiente di redditività è obbligatorio.',
            'coefficiente_redditivita.between' => 'Il coefficiente deve essere tra 0 e 100.',
            'anno_inizio_attivita.required' => "L'anno di inizio attività è obbligatorio.",
            'anno_inizio_attivita.between' => "L'anno deve essere tra 1990 e :max.",
        ];
    }
}
