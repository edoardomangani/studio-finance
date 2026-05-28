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
        'coefficiente_redditivita',
        'anno_inizio_attivita',
    ];

    protected function casts(): array
    {
        return [
            'coefficiente_redditivita' => 'decimal:2',
            'anno_inizio_attivita' => 'integer',
        ];
    }
}
