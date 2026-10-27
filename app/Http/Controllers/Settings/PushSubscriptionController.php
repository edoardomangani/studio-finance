<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DestroyPushSubscriptionRequest;
use App\Http\Requests\Settings\StorePushSubscriptionRequest;
use App\Services\PushSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Subscription push del dispositivo corrente. Risponde 204 (chiamate via
 * useHttp, la pagina non si ricarica).
 */
class PushSubscriptionController extends Controller
{
    public function __construct(private readonly PushSubscriptionService $subscriptions) {}

    public function store(StorePushSubscriptionRequest $request): Response
    {
        $this->subscriptions->subscribe($request->user(), $request->validated());

        return response()->noContent();
    }

    public function destroy(DestroyPushSubscriptionRequest $request): Response
    {
        $this->subscriptions->unsubscribe($request->user(), $request->validated('endpoint'));

        return response()->noContent();
    }

    public function test(Request $request): Response
    {
        $this->subscriptions->sendTest($request->user());

        return response()->noContent();
    }
}
