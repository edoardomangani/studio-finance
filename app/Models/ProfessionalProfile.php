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

    /**
     * Aliquota imposta sostitutiva per un anno: 5% nei primi 5 periodi d'imposta
     * dall'inizio attività (regime forfettario start-up), 15% dal sesto in poi.
     */
    public function impostaSostitutivaRateFor(int $year): float
    {
        $startup = $year >= $this->business_start_year
            && $year <= $this->business_start_year + 4;

        return $startup ? 5.0 : 15.0;
    }
}
