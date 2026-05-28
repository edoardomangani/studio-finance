<?php

namespace App\Concerns;

use App\Enums\TipoCalcoloVoceSpesa;
use Illuminate\Validation\Rule;

/**
 * Regole condivise tra StoreVoceSpesaRequest e UpdateVoceSpesaRequest.
 *
 * Note di dominio:
 * - aliquota_default ha senso solo per tipi `perc_*`, ma non la blocchiamo
 *   server-side: l'utente potrebbe voler salvare un valore "in stand-by" e
 *   cambiare tipo poi. La UI guida il flusso, la validazione tiene larga.
 */
trait VoceSpesaValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function voceSpesaRules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo_calcolo' => ['required', Rule::enum(TipoCalcoloVoceSpesa::class)],
            'aliquota_default' => ['nullable', 'numeric', 'between:0,100'],
            'minimale_default' => ['nullable', 'numeric', 'min:0'],
            'massimale_default' => ['nullable', 'numeric', 'min:0', 'gte:minimale_default'],
            'quota_default' => ['nullable', 'numeric', 'min:0'],
            'attiva' => ['required', 'boolean'],
            'ordine' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function voceSpesaMessages(): array
    {
        return [
            'nome.required' => 'Il nome è obbligatorio.',
            'tipo_calcolo.required' => 'Il tipo di calcolo è obbligatorio.',
            'tipo_calcolo.enum' => 'Tipo di calcolo non valido.',
            'aliquota_default.between' => "L'aliquota deve essere tra 0 e 100.",
            'massimale_default.gte' => 'Il massimale deve essere ≥ del minimale.',
        ];
    }
}
