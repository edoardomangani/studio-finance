<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TestPush;

/**
 * Subscription Web Push dei dispositivi dell'utente.
 */
class PushSubscriptionService
{
    /**
     * Upsert per endpoint: se il dispositivo era di un altro utente passa a questo.
     *
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}, contentEncoding?: string|null}  $data
     */
    public function subscribe(User $user, array $data): void
    {
        $user->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm',
        );
    }

    public function unsubscribe(User $user, string $endpoint): void
    {
        $user->deletePushSubscription($endpoint);
    }

    public function sendTest(User $user): void
    {
        $user->notify(new TestPush);
    }
}
