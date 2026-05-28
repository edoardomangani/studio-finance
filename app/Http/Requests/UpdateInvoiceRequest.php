<?php

namespace App\Http\Requests;

use App\Concerns\InvoiceValidationRules;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateInvoiceRequest extends FormRequest
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
        // L'invoice corrente è bound via route model binding ({invoice}).
        // Va escluso dal check di unicità per evitare il falso positivo
        // "in modifica del proprio record".
        $invoice = $this->route('invoice');

        $this->invoiceAfterValidation(
            $validator,
            $invoice instanceof Invoice ? $invoice : null,
        );
    }
}
