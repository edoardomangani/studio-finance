<?php

namespace App\Http\Requests\Settings;

use App\Concerns\VoceSpesaValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreVoceSpesaRequest extends FormRequest
{
    use VoceSpesaValidationRules;

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
