<?php

namespace App\Http\Requests;

use App\Enums\DeadlineKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creazione di una scadenza ad-hoc (non da template).
 *
 * Le regole `exists` su `annual_expense_id` / `year_id` sono scoping di tenancy
 * esplicito (`Rule::exists` gira raw, bypassa il global scope BelongsToUser):
 * un id di altro utente o archiviato deve fallire. `required_if` lega il campo
 * al tipo: la spesa serve solo per i pagamenti, l'anno solo per gli adempimenti.
 */
class StoreDeadlineRequest extends FormRequest
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
            'kind' => ['required', Rule::enum(DeadlineKind::class)],
            'name' => ['required', 'string', 'max:255'],
            // Le scadenze possono cadere in futuro: nessun limite sulla data.
            'due_at' => ['required', 'date'],
            'annual_expense_id' => [
                'required_if:kind,'.DeadlineKind::Payment->value,
                'nullable',
                'integer',
                Rule::exists('annual_expenses', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'year_id' => [
                'required_if:kind,'.DeadlineKind::Fulfillment->value,
                'nullable',
                'integer',
                Rule::exists('years', 'id')->where('user_id', $this->user()->id),
            ],
            // Previsto manuale opzionale (solo pagamento): suggerimento alla
            // registrazione. Lo applica il service solo su scadenze di pagamento.
            'manual_expected_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci un nome per la scadenza.',
            'due_at.required' => 'Inserisci la data di scadenza.',
            'annual_expense_id.required_if' => 'Scegli la spesa a cui collegare il pagamento.',
            'annual_expense_id.exists' => 'Spesa non valida.',
            'year_id.required_if' => 'Scegli l’anno della scadenza.',
            'year_id.exists' => 'Anno non valido.',
        ];
    }
}
