<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\AnnoDataScadenza;
use App\Enums\AnnoRiferimentoSpesa;
use App\Enums\TipoScadenza;
use Database\Factories\ScadenzaTipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScadenzaTipo extends Model
{
    /** @use HasFactory<ScadenzaTipoFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $table = 'scadenze_tipo';

    protected $fillable = [
        'user_id',
        'nome',
        'giorno',
        'mese',
        'tipo',
        'voce_spesa_id',
        'anno_data_scadenza',
        'anno_riferimento_spesa',
        'attiva',
    ];

    protected function casts(): array
    {
        return [
            'giorno' => 'integer',
            'mese' => 'integer',
            'tipo' => TipoScadenza::class,
            'anno_data_scadenza' => AnnoDataScadenza::class,
            'anno_riferimento_spesa' => AnnoRiferimentoSpesa::class,
            'attiva' => 'boolean',
        ];
    }

    public function voceSpesa(): BelongsTo
    {
        return $this->belongsTo(VoceSpesa::class);
    }
}
