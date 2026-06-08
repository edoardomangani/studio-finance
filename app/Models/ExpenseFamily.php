<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ExpenseKind;
use Illuminate\Database\Eloquent\Model;

/**
 * Famiglia di spesa: i quattro tipi fiscali ([[ExpenseKind]]) come righe
 * per-utente, **non creabili né eliminabili** — solo rinominabili, per dare
 * all'utente una nomenclatura sua (es. "Inarcassa" al posto di "Previdenza").
 * Il `kind` è l'identità (immutabile), `name` l'etichetta editabile. Seedate a
 * onboarding ([[Database\Seeders\StudiofinanceTemplatesSeeder]]). Le voci di
 * spesa non hanno una FK qui: portano il `kind` e il nome si risolve per
 * (utente, kind).
 */
class ExpenseFamily extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'kind',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ExpenseKind::class,
        ];
    }

    /**
     * Mappa kind => nome per l'utente corrente (tenancy via global scope).
     * Usata dai service per etichettare le righe-spesa senza N+1.
     *
     * @return array<string, string>
     */
    public static function namesByKind(): array
    {
        return self::query()
            ->get(['kind', 'name'])
            ->mapWithKeys(fn (self $f): array => [$f->kind->value => $f->name])
            ->all();
    }
}
