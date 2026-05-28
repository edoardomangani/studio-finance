<?php

namespace App\Http\Requests;

use App\Concerns\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
{
    use InvoiceValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->invoiceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->invoiceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $this->invoiceAfterValidation($validator);
    }
}
