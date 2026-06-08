<?php

namespace App\Http\Requests;

use App\Enums\DeadlineKind;
use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use App\Enums\ExpenseYearOffset;
use App\Enums\QuotaType;
use App\Models\ExpenseItem;
use App\Models\Year;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validazione del wizard di apertura anno (F6). Il payload è il "piano"
 * editato dall'utente: anno, coefficiente, voci di spesa (con flag di
 * inclusione) e scadenze con date.
 *
 * L'unicità dell'anno per utente è verificata qui per dare un errore inline
 * sullo step 1; l'action [[App\Actions\Studiofinance\OpenYear]] resta
 * comunque il guardiano transazionale (un anno pre-aperto NON è un errore:
 * viene completato).
 */
class OpenYearRequest extends FormRequest
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
        $currentYear = (int) now()->year;

        return [
            'year' => ['required', 'integer', 'min:1990', 'max:'.($currentYear + 5)],
            'profitability_coefficient' => ['required', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            // Gate UX puro: il pre-open di N+1 è comportamento legittimo di
            // sistema (RB8), quindi non viene enforced lato server.
            'cross_year_confirmed' => ['boolean'],

            'expenses' => ['present', 'array'],
            'expenses.*.expense_item_id' => [
                'nullable', 'integer',
                Rule::exists((new ExpenseItem)->getTable(), 'id')->where('user_id', Auth::id()),
            ],
            'expenses.*.name' => ['required', 'string', 'max:255'],
            'expenses.*.calculation_type' => ['required', Rule::enum(ExpenseCalculationType::class)],
            'expenses.*.kind' => ['required', Rule::enum(ExpenseKind::class)],
            'expenses.*.rate' => ['nullable', 'numeric', 'between:0,100'],
            'expenses.*.minimum' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'expenses.*.maximum' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'expenses.*.included' => ['required', 'boolean'],

            'deadlines' => ['present', 'array'],
            'deadlines.*.recurring_deadline_id' => ['nullable', 'integer'],
            'deadlines.*.name' => ['required', 'string', 'max:255'],
            'deadlines.*.due_at' => ['required', 'date'],
            'deadlines.*.kind' => ['required', Rule::enum(DeadlineKind::class)],
            'deadlines.*.expense_item_id' => [
                'nullable', 'integer',
                Rule::exists((new ExpenseItem)->getTable(), 'id')->where('user_id', Auth::id()),
            ],
            'deadlines.*.expense_year_offset' => ['required', Rule::enum(ExpenseYearOffset::class)],
            'deadlines.*.quota_type' => ['nullable', Rule::enum(QuotaType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Seleziona un anno.',
            'year.integer' => 'Anno non valido.',
            'profitability_coefficient.required' => 'Il coefficiente di redditività è obbligatorio.',
            'profitability_coefficient.min' => 'Il coefficiente deve essere tra 0 e 100.',
            'profitability_coefficient.max' => 'Il coefficiente deve essere tra 0 e 100.',
            'expenses.*.rate.between' => "L'aliquota deve essere tra 0 e 100.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $year = $this->integer('year');

            if ($year === 0) {
                return;
            }

            // Anno già aperto formalmente (non pre-aperto) → blocco con
            // messaggio. Lo scope per user_id è automatico (BelongsToUser).
            $existing = Year::query()->where('year', $year)->first();

            if ($existing !== null && ! $existing->pre_opened) {
                $validator->errors()->add(
                    'year',
                    sprintf('Anno %d già aperto. Vai alla vista anno per consultarlo.', $year),
                );
            }
        });
    }
}
