<?php

namespace App\Concerns;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Regole condivise tra StoreInvoiceRequest e UpdateInvoiceRequest.
 *
 * Vincoli salienti (vedi RB3):
 * - `number` univoco per `(user_id, anno_di_emissione)`. L'anno deriva da
 *   `issued_at` e PostgreSQL/SQLite gestiscono diversamente expression
 *   unique index, quindi il check vive in `afterValidator()` con messaggio
 *   specifico ("«2026/001» già usato nel 2026.").
 * - `client_id` scopato all'utente loggato via `Rule::exists()` filtrato
 *   per `user_id`: impedisce IDOR via FK guessing.
 * - Importi: numerici tra ±9.999.999,99 (€10M, soglia sentinel:
 *   forfettario ha limite di ricavi €85k/anno, oltre i 10M c'è
 *   certamente un errore di digitazione). I negativi sono ammessi per
 *   modellare le note di credito come fatture a importo negativo: gli
 *   storni scalano linearmente in tutte le aggregazioni.
 *
 * @mixin FormRequest
 */
trait InvoiceValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function invoiceRules(): array
    {
        return [
            'number' => ['required', 'string', 'max:64'],
            'issued_at' => ['required', 'date'],
            'client_id' => [
                'required',
                Rule::exists((new Client)->getTable(), 'id')
                    ->where('user_id', Auth::id()),
            ],
            'amount' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99', 'decimal:0,2'],
            'inarcassa_amount' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99', 'decimal:0,2'],
            'stamp_amount' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99', 'decimal:0,2'],
            'art_15_amount' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99', 'decimal:0,2'],
            // Default true (a carico cliente) se assente: comportamento
            // storico, applicato in InvoiceCalculator::canonicalize.
            'stamp_charged_to_client' => ['sometimes', 'boolean'],
            'bank_withholding' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function invoiceMessages(): array
    {
        return [
            'number.required' => 'Il numero fattura è obbligatorio.',
            'issued_at.required' => 'La data di emissione è obbligatoria.',
            'issued_at.date' => 'Inserisci una data valida.',
            'client_id.required' => 'Seleziona un cliente.',
            'client_id.exists' => 'Cliente non valido.',
            'amount.min' => 'L\'imponibile è fuori dai limiti consentiti.',
            'amount.max' => 'L\'imponibile è fuori dai limiti consentiti.',
            'inarcassa_amount.min' => 'La cassa Inarcassa è fuori dai limiti consentiti.',
            'inarcassa_amount.max' => 'La cassa Inarcassa è fuori dai limiti consentiti.',
            'stamp_amount.min' => 'Il bollo è fuori dai limiti consentiti.',
            'stamp_amount.max' => 'Il bollo è fuori dai limiti consentiti.',
            'art_15_amount.min' => 'L\'art.15 è fuori dai limiti consentiti.',
            'art_15_amount.max' => 'L\'art.15 è fuori dai limiti consentiti.',
        ];
    }

    /**
     * Hook `after()` del validator: vincolo cross-field di unicità numero
     * fattura per anno. Esce silenziosamente se `number` o `issued_at` non
     * passano la validazione base (evita falsi positivi sui campi vuoti).
     *
     * Lo scope per `user_id` è automatico via global scope `BelongsToUser`
     * su Invoice.
     */
    protected function invoiceAfterValidation(
        Validator $validator,
        ?Invoice $ignoreInvoice = null,
    ): void {
        $validator->after(function (Validator $validator) use ($ignoreInvoice): void {
            $number = $this->input('number');
            $issuedAt = $this->input('issued_at');

            if (! is_string($number) || ! is_string($issuedAt) || $number === '') {
                return;
            }

            try {
                $year = Carbon::parse($issuedAt)->year;
            } catch (\Throwable) {
                return;
            }

            $query = Invoice::query()
                ->where('number', $number)
                ->whereYear('issued_at', $year);

            if ($ignoreInvoice !== null) {
                $query->where('id', '!=', $ignoreInvoice->id);
            }

            if ($query->exists()) {
                $validator->errors()->add(
                    'number',
                    sprintf('«%s» è già usato per il %d.', $number, $year),
                );
            }
        });
    }
}
