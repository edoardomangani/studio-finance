<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ExpenseItemValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseItemRequest extends FormRequest
{
    use ExpenseItemValidationRules;

    /**
     * Il global scope di BelongsToUser garantisce che l'ExpenseItem risolto dal
     * route-model binding appartenga già all'utente (404 altrimenti). Qui basta
     * verificare auth + onboarding.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->expenseItemRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->expenseItemMessages();
    }
}
