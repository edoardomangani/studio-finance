<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validazione del dialog di propagazione profilo (F11): gli anni selezionati e
 * quali campi propagare. La tenancy sugli id anno è garantita dal global scope
 * BelongsToUser nell'action; qui si valida solo struttura e presenza.
 */
class PropagateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year_ids' => ['required', 'array', 'min:1'],
            // exists scoped all'utente: un id estraneo è un errore esplicito, non
            // un no-op (la tenancy resta comunque garantita a valle dall'action).
            'year_ids.*' => [
                'integer',
                Rule::exists('years', 'id')->where('user_id', $this->user()->id),
            ],
            'coefficient' => ['required', 'boolean'],
            'start_year' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('coefficient') && ! $this->boolean('start_year')) {
                $validator->errors()->add('coefficient', 'Seleziona almeno un campo da propagare.');
            }
        });
    }
}
