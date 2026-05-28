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
            'name' => ['required', 'string', 'max:120'],
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
            'profitability_coefficient.required' => 'Il coefficiente di redditività è obbligatorio.',
            'profitability_coefficient.between' => 'Il coefficiente deve essere tra 0 e 100.',
            'business_start_year.required' => "L'anno di inizio attività è obbligatorio.",
            'business_start_year.between' => "L'anno deve essere tra 1990 e :max.",
        ];
    }
}
