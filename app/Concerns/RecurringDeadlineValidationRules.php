<?php

namespace App\Concerns;

use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseYearOffset;
use App\Enums\QuotaType;
use App\Models\ExpenseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Regole condivise tra StoreRecurringDeadlineRequest e
 * UpdateRecurringDeadlineRequest.
 *
 * Validazione cross-field:
 * - se kind == payment → expense_item_id è required e deve appartenere
 *   all'utente autenticato. `Rule::exists` esegue SQL diretto, non passa
 *   per Eloquent → il global scope di [[App\Concerns\BelongsToUser]] NON
 *   si applica. Scoping esplicito via `->where('user_id', Auth::id())`
 *   è obbligatorio per evitare IDOR (un utente potrebbe linkare un
 *   expense item di un altro tenant).
 * - se kind == fulfillment → expense_item_id deve essere NULL.
 * - day: 1-31 (la validità del giorno per il mese specifico non è
 *   enforced server-side; l'UI usa selettore giorno + mese visuale e
 *   le istanze concrete vengono validate al wizard).
 *
 * @mixin FormRequest
 */
trait RecurringDeadlineValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function recurringDeadlineRules(): array
    {
        $isPayment = $this->input('kind') === DeadlineKind::Payment->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'day' => ['required', 'integer', 'between:1,31'],
            'month' => ['required', 'integer', 'between:1,12'],
            'kind' => ['required', Rule::enum(DeadlineKind::class)],
            'expense_item_id' => $isPayment
                ? [
                    'required',
                    Rule::exists((new ExpenseItem)->getTable(), 'id')
                        ->where('user_id', Auth::id()),
                ]
                : ['nullable', 'prohibited'],
            'due_year_offset' => [
                'required',
                Rule::enum(DueYearOffset::class),
            ],
            'expense_year_offset' => [
                'required',
                Rule::enum(ExpenseYearOffset::class),
            ],
            // tipo quota: solo per le scadenze di pagamento; determina come si
            // calcola l'importo previsto (RB8). Può restare null (nessun
            // suggerimento). Per gli adempimenti è vietato.
            'quota_type' => $isPayment
                ? ['nullable', Rule::enum(QuotaType::class)]
                : ['nullable', 'prohibited'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function recurringDeadlineMessages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'day.between' => 'Il giorno deve essere tra 1 e 31.',
            'month.between' => 'Il mese deve essere tra 1 e 12.',
            'kind.required' => 'Il tipo è obbligatorio.',
            'kind.enum' => 'Tipo non valido.',
            'expense_item_id.required' => 'Le scadenze di pagamento richiedono una voce di spesa.',
            'expense_item_id.prohibited' => 'Le scadenze di adempimento non possono avere una voce di spesa collegata.',
            'expense_item_id.exists' => 'Voce di spesa non valida.',
            'quota_type.prohibited' => 'Gli adempimenti non hanno un tipo quota.',
            'quota_type.enum' => 'Tipo quota non valido.',
            'due_year_offset.enum' => 'Anno della data scadenza non valido.',
            'expense_year_offset.enum' => 'Anno di riferimento non valido.',
        ];
    }
}
