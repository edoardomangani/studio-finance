<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocca tutte le rotte di dominio per utenti che non hanno ancora completato
 * l'onboarding (no ProfessionalProfile). Da applicare DOPO il middleware
 * `auth`/`verified` di Fortify.
 *
 * La rotta `/onboarding` stessa NON deve essere protetta da questo middleware
 * (loop): vive sotto auth+verified soltanto.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isOnboarded()) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
