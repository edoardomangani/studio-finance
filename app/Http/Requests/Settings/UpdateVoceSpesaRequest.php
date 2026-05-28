<?php

namespace App\Http\Requests\Settings;

use App\Concerns\VoceSpesaValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVoceSpesaRequest extends FormRequest
{
    use VoceSpesaValidationRules;

    /**
     * Il global scope di BelongsToUser garantisce che la VoceSpesa risolta dal
     * route-model binding appartenga già all'utente (404 altrimenti). Qui basta
     * verificare auth + onboarding.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->voceSpesaRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->voceSpesaMessages();
    }
}
