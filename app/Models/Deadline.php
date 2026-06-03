<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\QuotaType;
use Database\Factories\DeadlineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deadline extends Model
{
    /** @use HasFactory<DeadlineFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'year_id',
        'recurring_deadline_id',
        'name',
        'due_at',
        'kind',
        'annual_expense_id',
        'quota_type',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'kind' => DeadlineKind::class,
            'quota_type' => QuotaType::class,
            'status' => DeadlineStatus::class,
        ];
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function recurringDeadline(): BelongsTo
    {
        return $this->belongsTo(RecurringDeadline::class);
    }

    public function annualExpense(): BelongsTo
    {
        return $this->belongsTo(AnnualExpense::class);
    }

    /**
     * Pagamento collegato (1:1 strict per le scadenze di tipo payment, RB8).
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
