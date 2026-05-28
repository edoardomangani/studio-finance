<?php

namespace App\Concerns;

use App\Enums\AnnoDataScadenza;
use App\Enums\AnnoRiferimentoSpesa;
use App\Enums\TipoScadenza;
use App\Models\VoceSpesa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Regole condivise tra StoreScadenzaTipoRequest e UpdateScadenzaTipoRequest.
 *
 * Validazione cross-field:
 * - se tipo == pagamento → voce_spesa_id è required, deve appartenere all'utente
 *   (il global scope sul model fa già il lavoro: exists() vede solo le voci
 *   dell'utente autenticato)
 * - se tipo == adempimento → voce_spesa_id deve essere NULL.
 * - giorno: 1-31 (validità per mese non controllata server-side, l'UI usa
 *   un picker giorno+mese visuale).
 *
 * @mixin FormRequest
 */
trait ScadenzaTipoValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function scadenzaTipoRules(): array
    {
        $isPagamento = $this->input('tipo') === TipoScadenza::Pagamento->value;

        return [
            'nome' => ['required', 'string', 'max:255'],
            'giorno' => ['required', 'integer', 'between:1,31'],
            'mese' => ['required', 'integer', 'between:1,12'],
            'tipo' => ['required', Rule::enum(TipoScadenza::class)],
            'voce_spesa_id' => $isPagamento
                ? ['required', Rule::exists((new VoceSpesa)->getTable(), 'id')]
                : ['nullable', 'prohibited'],
            'anno_data_scadenza' => [
                'required',
                Rule::enum(AnnoDataScadenza::class),
            ],
            'anno_riferimento_spesa' => [
                'required',
                Rule::enum(AnnoRiferimentoSpesa::class),
            ],
            'attiva' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function scadenzaTipoMessages(): array
    {
        return [
            'nome.required' => 'Il nome è obbligatorio.',
            'giorno.between' => 'Il giorno deve essere tra 1 e 31.',
            'mese.between' => 'Il mese deve essere tra 1 e 12.',
            'tipo.required' => 'Il tipo è obbligatorio.',
            'tipo.enum' => 'Tipo non valido.',
            'voce_spesa_id.required' => 'Le scadenze di pagamento richiedono una voce di spesa.',
            'voce_spesa_id.prohibited' => 'Le scadenze di adempimento non possono avere una voce di spesa collegata.',
            'voce_spesa_id.exists' => 'Voce di spesa non valida.',
            'anno_data_scadenza.enum' => 'Anno della data scadenza non valido.',
            'anno_riferimento_spesa.enum' => 'Anno di riferimento non valido.',
        ];
    }
}
