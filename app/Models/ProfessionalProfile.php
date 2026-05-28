<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\ProfessionalProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfessionalProfile extends Model
{
    /** @use HasFactory<ProfessionalProfileFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'profitability_coefficient',
        'business_start_year',
    ];

    protected function casts(): array
    {
        return [
            'profitability_coefficient' => 'decimal:2',
            'business_start_year' => 'integer',
        ];
    }
}
