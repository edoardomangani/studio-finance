<?php

namespace App\Http\Controllers;

use App\Services\DeadlineReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invio giornaliero dei promemoria, chiamato da cron-job.org (Render free non
 * ha scheduler). Niente sessione: autenticato solo dal Bearer token.
 */
class NotificationDispatchController extends Controller
{
    public function __invoke(Request $request, DeadlineReminderService $reminders): JsonResponse
    {
        $token = (string) config('services.notifications.dispatch_token');

        // Token non configurato = endpoint disattivato.
        abort_if($token === '', 404);
        abort_unless(hash_equals($token, (string) $request->bearerToken()), 401);

        return response()->json(['sent' => $reminders->send()]);
    }
}
