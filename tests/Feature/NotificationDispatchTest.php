<?php

use App\Models\Deadline;
use App\Models\User;
use App\Models\Year;
use App\Notifications\DeadlineReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    config(['services.notifications.dispatch_token' => 'secret-token']);
});

it('risponde 404 se il token non è configurato', function () {
    config(['services.notifications.dispatch_token' => null]);

    $this->postJson(route('notifications.dispatch'))->assertNotFound();
});

it('rifiuta richieste senza token o con token errato', function (?string $token) {
    $this->withToken((string) $token)
        ->postJson(route('notifications.dispatch'))
        ->assertUnauthorized();

    Notification::assertNothingSent();
})->with([null, 'wrong-token']);

it('invia i promemoria del giorno col token corretto', function () {
    $this->travelTo(now('Europe/Rome')->setTime(8, 0));
    $user = User::factory()->create();
    $user->updatePushSubscription('https://push.example.com/a', 'p256dh', 'auth');
    Deadline::factory()->for($user)->create([
        'year_id' => Year::factory()->for($user)->create()->id,
        'due_at' => now('Europe/Rome')->toDateString(),
    ]);

    $this->withToken('secret-token')
        ->postJson(route('notifications.dispatch'))
        ->assertOk()
        ->assertExactJson(['sent' => 1]);

    $this->withToken('secret-token')
        ->postJson(route('notifications.dispatch'))
        ->assertExactJson(['sent' => 0]);

    Notification::assertSentTo($user, DeadlineReminder::class);
});
