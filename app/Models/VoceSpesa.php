<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\TipoCalcoloVoceSpesa;
use Database\Factories\VoceSpesaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoceSpesa extends Model
{
    /** @use HasFactory<VoceSpesaFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $table = 'voci_spesa';

    protected $fillable = [
        'user_id',
        'nome',
        'tipo_calcolo',
        'aliquota_default',
        'minimale_default',
        'massimale_default',
        'quota_default',
        'attiva',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'tipo_calcolo' => TipoCalcoloVoceSpesa::class,
            'aliquota_default' => 'decimal:2',
            'minimale_default' => 'decimal:2',
            'massimale_default' => 'decimal:2',
            'quota_default' => 'decimal:2',
            'attiva' => 'boolean',
            'ordine' => 'integer',
        ];
    }

    public function scadenzeTipo(): HasMany
    {
        return $this->hasMany(ScadenzaTipo::class);
    }
}
