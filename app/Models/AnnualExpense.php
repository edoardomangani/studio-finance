<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ExpenseCalculationType;
use Database\Factories\AnnualExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualExpense extends Model
{
    /** @use HasFactory<AnnualExpenseFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'year_id',
        'expense_item_id',
        'name',
        'calculation_type',
        'is_pension_contribution',
        'rate',
        'minimum',
        'maximum',
        'amount',
        'effective_amount',
        'previous_year_credit',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'calculation_type' => ExpenseCalculationType::class,
            'is_pension_contribution' => 'boolean',
            'rate' => 'decimal:2',
            'minimum' => 'decimal:2',
            'maximum' => 'decimal:2',
            'amount' => 'decimal:2',
            'effective_amount' => 'decimal:2',
            'previous_year_credit' => 'decimal:2',
        ];
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function expenseItem(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }

    /**
     * Spese annuali (tutti gli anni) per gli autocomplete: pagamento manuale
     * (F8) e scadenza ad-hoc di pagamento. Solo attive (no archiviate, via
     * SoftDeletes); tenancy dal global scope [[App\Concerns\BelongsToUser]].
     *
     * @return array<int, array{id: int, name: string, year: int}>
     */
    public static function pickerOptions(): array
    {
        return self::query()
            ->with('year:id,year')
            ->orderByDesc('year_id')
            ->orderBy('name')
            ->get(['id', 'year_id', 'name'])
            ->map(fn (self $e): array => [
                'id' => $e->id,
                'name' => $e->name,
                'year' => $e->year->year,
            ])
            ->all();
    }
}
