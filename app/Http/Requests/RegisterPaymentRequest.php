<?php

namespace App\Http\Requests;

use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Registrazione del pagamento di una scadenza (F7). L'importo è precompilato
 * lato UI con il previsto (suggerimento, RB8) ma resta libero: l'utente
 * conferma o corregge la cifra reale dall'F24.
 */
class RegisterPaymentRequest extends FormRequest
{
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
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            // Date passate ammesse (ricostruzione storico); future no.
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Deadline $deadline */
            $deadline = $this->route('deadline');

            if ($deadline->kind !== DeadlineKind::Payment) {
                $validator->errors()->add('amount', 'Questa scadenza non prevede un pagamento.');

                return;
            }

            if ($deadline->status !== DeadlineStatus::Open) {
                $validator->errors()->add('amount', 'La scadenza non è più aperta.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Inserisci un importo.',
            'amount.gt' => 'Inserisci un importo maggiore di zero.',
            'paid_at.required' => 'Inserisci la data del pagamento.',
            'paid_at.before_or_equal' => 'La data del pagamento non può essere futura.',
        ];
    }
}
