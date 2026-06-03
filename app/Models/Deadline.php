<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
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
        'manual_expected_amount',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'kind' => DeadlineKind::class,
            'quota_type' => QuotaType::class,
            'manual_expected_amount' => 'decimal:2',
            'status' => DeadlineStatus::class,
        ];
    }

    /**
     * Scadenza ad-hoc (creata a mano, non da template): editabile e
     * archiviabile; le standard usano "non dovuta". Discrimina su
     * `recurring_deadline_id`.
     */
    public function isAdHoc(): bool
    {
        return $this->recurring_deadline_id === null;
    }

    /**
     * Il pagamento collegato è già stato registrato: blocca modifica spesa e
     * archiviazione (non si sposta/nasconde un fatto di cassa).
     */
    public function isPaid(): bool
    {
        return $this->payment?->status === PaymentStatus::Paid;
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
