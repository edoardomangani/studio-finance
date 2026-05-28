<?php

namespace App\Concerns;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Regole condivise tra StoreClientRequest e UpdateClientRequest.
 *
 * Validazione cross-field critica: almeno uno tra `vat_number` (P.IVA) e
 * `tax_code` (CF) deve essere valorizzato. Validato a livello FormRequest
 * tramite la rule `required_without` su entrambi i campi (simmetrica).
 *
 * Formati P.IVA / CF: vengono solo controllati come stringhe alphanumeriche
 * con range di lunghezza. NON viene validato l'algoritmo (checksum Luhn
 * per P.IVA, checksum CF). L'utente può correggere typo facilmente; il
 * controllo algoritmico arriverà in un polish successivo se necessario.
 *
 * @mixin FormRequest
 */
trait ClientValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function clientRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // P.IVA italiana: 11 cifre. Allarghiamo a 32 per tollerare
            // P.IVA estere o formati con prefisso (IT12345678901).
            'vat_number' => [
                'nullable',
                'required_without:tax_code',
                'string',
                'max:32',
                'alpha_num',
            ],
            // CF: 16 char alfanumerici (persone fisiche) o 11 cifre (enti).
            'tax_code' => [
                'nullable',
                'required_without:vat_number',
                'string',
                'max:32',
                'alpha_num',
            ],
            'bank_withholding' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function clientMessages(): array
    {
        return [
            'name.required' => 'La denominazione è obbligatoria.',
            'vat_number.required_without' => 'Devi inserire almeno la P.IVA o il Codice Fiscale.',
            'vat_number.alpha_num' => 'La P.IVA può contenere solo lettere e numeri.',
            'tax_code.required_without' => 'Devi inserire almeno la P.IVA o il Codice Fiscale.',
            'tax_code.alpha_num' => 'Il Codice Fiscale può contenere solo lettere e numeri.',
            'notes.max' => 'Le note non possono superare i 2000 caratteri.',
        ];
    }
}
