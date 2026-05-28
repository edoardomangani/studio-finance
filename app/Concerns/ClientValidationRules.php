<?php

namespace App\Concerns;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Regole condivise tra StoreClientRequest e UpdateClientRequest.
 *
 * Validazione cross-field critica: almeno uno tra `vat_number` (P.IVA) e
 * `tax_code` (CF) deve essere valorizzato. `required_without` asimmetrico
 * solo su vat_number: quando entrambi mancano l'errore appare una volta
 * sola sotto la P.IVA.
 *
 * Formati validati via regex (strutturale, NO checksum):
 * - P.IVA italiana: 11 cifre, opzionale prefisso ISO `IT`. Pattern accetta
 *   `12345678901` e `IT12345678901`.
 * - CF italiano: 16 caratteri alfanumerici per persone fisiche
 *   (`RSSMRA80A01H501Z`) o 11 cifre per enti (alias P.IVA).
 *   L'algoritmo di checksum (formula Luhn per P.IVA, lettera di controllo
 *   CF) NON è validato lato server: l'utente vede l'errore al
 *   commercialista, non a livello app. Un package di validation
 *   algoritmica può essere aggiunto in Fase 11 se necessario.
 *
 * @mixin FormRequest
 */
trait ClientValidationRules
{
    /**
     * Pattern P.IVA italiana: 11 cifre, prefisso ISO `IT` opzionale.
     */
    private const VAT_NUMBER_PATTERN = '/^(IT)?\d{11}$/i';

    /**
     * Pattern Codice Fiscale italiano:
     * - Persone fisiche: 16 caratteri `LLLLLL NN L NN L NNN L` (L=letter, N=digit)
     * - Enti: 11 cifre (formato P.IVA)
     */
    private const TAX_CODE_PATTERN = '/^([A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]|\d{11})$/i';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function clientRules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'vat_number' => [
                'nullable',
                'required_without:tax_code',
                'string',
                'max:32',
                'regex:'.self::VAT_NUMBER_PATTERN,
            ],
            'tax_code' => [
                'nullable',
                'string',
                'max:32',
                'regex:'.self::TAX_CODE_PATTERN,
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
            'name.min' => 'La denominazione deve avere almeno 2 caratteri.',
            'vat_number.required_without' => 'Devi inserire almeno la P.IVA o il Codice Fiscale.',
            'vat_number.regex' => 'P.IVA non valida: deve essere di 11 cifre (eventuale prefisso IT).',
            'tax_code.regex' => 'Codice Fiscale non valido: 16 caratteri (persone fisiche) o 11 cifre (enti).',
            'notes.max' => 'Le note non possono superare i 2000 caratteri.',
        ];
    }
}
