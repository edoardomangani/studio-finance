<?php

use App\Models\User;

function subscribeDevice(User $user, string $endpoint = 'https://push.example.com/abc'): void
{
    $user->updatePushSubscription($endpoint, 'p256dh-key', 'auth-token', 'aes128gcm');
}

it('elimina le subscription push quando si cancella l\'utente', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    subscribeDevice($user);
    subscribeDevice($other, 'https://push.example.com/other');

    $user->delete();

    $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://push.example.com/abc']);
    $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://push.example.com/other']);
});
