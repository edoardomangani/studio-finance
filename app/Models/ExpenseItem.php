<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ExpenseCalculationType;
use App\Enums\ExpenseKind;
use Database\Factories\ExpenseItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseItem extends Model
{
    /** @use HasFactory<ExpenseItemFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'calculation_type',
        'kind',
        'default_rate',
        'default_minimum',
        'default_maximum',
        'default_amount',
        'active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'calculation_type' => ExpenseCalculationType::class,
            'kind' => ExpenseKind::class,
            'default_rate' => 'decimal:2',
            'default_minimum' => 'decimal:2',
            'default_maximum' => 'decimal:2',
            'default_amount' => 'decimal:2',
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function recurringDeadlines(): HasMany
    {
        return $this->hasMany(RecurringDeadline::class);
    }
}
