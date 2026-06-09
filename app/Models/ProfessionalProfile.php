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

    /** Regime forfettario: aliquota start-up e durata (periodi d'imposta), poi standard. */
    private const STARTUP_YEARS = 5;

    private const STARTUP_RATE = 5.0;

    private const STANDARD_RATE = 15.0;

    /**
     * Aliquota imposta sostitutiva per un anno: agevolata nei primi periodi
     * d'imposta dall'inizio attività (start-up), standard dal successivo.
     */
    public function impostaSostitutivaRateFor(int $year): float
    {
        $startup = $year >= $this->business_start_year
            && $year < $this->business_start_year + self::STARTUP_YEARS;

        return $startup ? self::STARTUP_RATE : self::STANDARD_RATE;
    }
}
