<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'annual_expense_id',
        'deadline_id',
        'description',
        'amount',
        'paid_at',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'status' => PaymentStatus::class,
        ];
    }

    public function annualExpense(): BelongsTo
    {
        return $this->belongsTo(AnnualExpense::class);
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }
}
