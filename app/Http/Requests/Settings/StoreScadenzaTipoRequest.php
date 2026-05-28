<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ScadenzaTipoValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreScadenzaTipoRequest extends FormRequest
{
    use ScadenzaTipoValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->scadenzaTipoRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->scadenzaTipoMessages();
    }
}
