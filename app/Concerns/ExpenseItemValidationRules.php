<?php

namespace App\Concerns;

use App\Enums\ExpenseCalculationType;
use Illuminate\Validation\Rule;

/**
 * Regole condivise tra StoreExpenseItemRequest e UpdateExpenseItemRequest.
 *
 * Note di dominio:
 * - default_rate ha senso solo per i tipi percentuali (IRPEF / IVA), ma
 *   non la blocchiamo server-side: l'utente potrebbe voler salvare un
 *   valore "in stand-by" e cambiare tipo poi. La UI guida il flusso,
 *   la validazione tiene larga.
 */
trait ExpenseItemValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function expenseItemRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'calculation_type' => ['required', Rule::enum(ExpenseCalculationType::class)],
            'default_rate' => ['nullable', 'numeric', 'between:0,100'],
            'default_minimum' => ['nullable', 'numeric', 'min:0'],
            'default_maximum' => ['nullable', 'numeric', 'min:0', 'gte:default_minimum'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'active' => ['required', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function expenseItemMessages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'calculation_type.required' => 'Il tipo di calcolo è obbligatorio.',
            'calculation_type.enum' => 'Tipo di calcolo non valido.',
            'default_rate.between' => "L'aliquota deve essere tra 0 e 100.",
            'default_maximum.gte' => 'Il massimale deve essere ≥ del minimale.',
        ];
    }
}
