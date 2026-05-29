<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\YearFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Year extends Model
{
    /** @use HasFactory<YearFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'year',
        'profitability_coefficient',
        'pre_opened',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'profitability_coefficient' => 'decimal:2',
            'pre_opened' => 'boolean',
        ];
    }

    public function annualExpenses(): HasMany
    {
        return $this->hasMany(AnnualExpense::class);
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }
}
