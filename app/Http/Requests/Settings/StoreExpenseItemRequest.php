<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ExpenseItemValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseItemRequest extends FormRequest
{
    use ExpenseItemValidationRules;

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
