<?php

namespace App\Http\Requests\Settings;

use App\Concerns\RecurringDeadlineValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringDeadlineRequest extends FormRequest
{
    use RecurringDeadlineValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->recurringDeadlineRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->recurringDeadlineMessages();
    }
}
