<?php

use App\Models\User;
use App\Notifications\TestPush;
use Illuminate\Support\Facades\Notification;

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

function subscriptionPayload(string $endpoint = 'https://push.example.com/device'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        'contentEncoding' => 'aes128gcm',
    ];
}

it('registra la subscription del dispositivo senza duplicarla', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.notifications.subscribe'), subscriptionPayload())
        ->assertNoContent();
    $this->actingAs($user)
        ->postJson(route('settings.notifications.subscribe'), subscriptionPayload())
        ->assertNoContent();

    expect($user->pushSubscriptions()->count())->toBe(1);
});

it('riassegna all\'utente corrente un dispositivo già registrato da un altro', function () {
    $previous = User::factory()->create();
    $current = User::factory()->create();
    subscribeDevice($previous, 'https://push.example.com/device');

    $this->actingAs($current)
        ->postJson(route('settings.notifications.subscribe'), subscriptionPayload())
        ->assertNoContent();

    expect($previous->pushSubscriptions()->count())->toBe(0)
        ->and($current->pushSubscriptions()->count())->toBe(1);
});

it('valida il corpo della subscription', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('settings.notifications.subscribe'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'endpoint non https' => [['endpoint' => 'http://push.example.com/x', 'keys' => ['p256dh' => 'a', 'auth' => 'b']], 'endpoint'],
    'chiavi mancanti' => [['endpoint' => 'https://push.example.com/x'], 'keys.p256dh'],
    'encoding sconosciuto' => [[...subscriptionPayload(), 'contentEncoding' => 'gzip'], 'contentEncoding'],
]);

it('rimuove solo le proprie subscription', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    subscribeDevice($user, 'https://push.example.com/mine');
    subscribeDevice($other, 'https://push.example.com/theirs');

    $this->actingAs($user)
        ->deleteJson(route('settings.notifications.unsubscribe'), ['endpoint' => 'https://push.example.com/theirs'])
        ->assertNoContent();
    $this->actingAs($user)
        ->deleteJson(route('settings.notifications.unsubscribe'), ['endpoint' => 'https://push.example.com/mine'])
        ->assertNoContent();

    expect($user->pushSubscriptions()->count())->toBe(0)
        ->and($other->pushSubscriptions()->count())->toBe(1);
});

it('invia la notifica di prova all\'utente', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.notifications.test'))
        ->assertNoContent();

    Notification::assertSentTo($user, TestPush::class);
});

it('richiede autenticazione', function () {
    $this->postJson(route('settings.notifications.subscribe'), subscriptionPayload())->assertUnauthorized();
    $this->postJson(route('settings.notifications.test'))->assertUnauthorized();
});
