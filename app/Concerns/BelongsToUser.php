<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Global tenancy trait: scopes queries to the authenticated user and forces
 * `user_id` on creating. Apply on every domain model owned by a single user.
 *
 * Behaviour:
 * - Adds a global scope `user_id = Auth::id()` quando l'utente è autenticato.
 *   Contesti senza auth (artisan, queue worker, seeder) saltano lo scope; le
 *   query in quei contesti devono filtrare manualmente per user_id oppure
 *   usare `Model::withoutGlobalScopes()`.
 * - On `creating`, forza `user_id` con `Auth::id()` (anche se il payload
 *   passato include un valore diverso). Previene la classica IDOR-via-mass-
 *   assignment: nessun caller può creare risorse intestate a un altro utente,
 *   nemmeno con `$model->fill($request->all())` + `user_id` in input.
 * - Se non c'è auth e `user_id` non è impostato esplicitamente, lancia
 *   `LogicException` per evitare INSERT con FK violation criptici.
 * - Expose a `user()` BelongsTo relation.
 */
trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new class implements Scope
        {
            public function apply(Builder $builder, $model): void
            {
                if (Auth::check()) {
                    $builder->where($model->qualifyColumn('user_id'), Auth::id());
                }
            }
        });

        static::creating(function ($model) {
            if (Auth::check()) {
                // Forza sempre l'utente loggato: niente IDOR-via-mass-assignment.
                $model->user_id = Auth::id();

                return;
            }

            if (! $model->user_id) {
                throw new LogicException(sprintf(
                    'Cannot create %s without user_id in unauthenticated context. '.
                    'Either authenticate the user, or set user_id explicitly on the model '.
                    '(e.g. seeders, jobs, console commands).',
                    static::class,
                ));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
