<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
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
            'coefficiente_redditivita.required' => 'Il coefficiente di redditività è obbligatorio.',
            'coefficiente_redditivita.between' => 'Il coefficiente deve essere tra 0 e 100.',
            'anno_inizio_attivita.required' => 'L\'anno di inizio attività è obbligatorio.',
            'anno_inizio_attivita.between' => 'L\'anno deve essere tra 1990 e :max.',
        ];
    }
}
