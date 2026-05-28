<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseYearOffset;
use Database\Factories\RecurringDeadlineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringDeadline extends Model
{
    /** @use HasFactory<RecurringDeadlineFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'day',
        'month',
        'kind',
        'expense_item_id',
        'due_year_offset',
        'expense_year_offset',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'month' => 'integer',
            'kind' => DeadlineKind::class,
            'due_year_offset' => DueYearOffset::class,
            'expense_year_offset' => ExpenseYearOffset::class,
            'active' => 'boolean',
        ];
    }

    public function expenseItem(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class);
    }
}
