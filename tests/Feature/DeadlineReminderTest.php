<?php

use App\Actions\Studiofinance\OpenYear;
use App\Enums\DeadlineKind;
use App\Models\Deadline;
use App\Models\DeadlineNotification;
use App\Models\User;
use App\Models\Year;
use App\Notifications\DeadlineReminder;
use App\Services\DeadlineReminderService;
use App\Services\YearOpeningPlanner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->today = CarbonImmutable::parse('2026-11-02', 'Europe/Rome');
});

function reminderUser(bool $subscribed = true): User
{
    $user = User::factory()->create();

    if ($subscribed) {
        $user->updatePushSubscription('https://push.example.com/'.$user->id, 'p256dh', 'auth');
    }

    return $user;
}

function reminderDeadline(User $user, CarbonImmutable $dueAt, array $attributes = []): Deadline
{
    return Deadline::factory()->for($user)->create([
        'year_id' => Year::factory()->for($user)->create(['year' => 2026])->id,
        'name' => 'Dichiarazione redditi',
        'due_at' => $dueAt->toDateString(),
        ...$attributes,
    ]);
}

function reminders(): DeadlineReminderService
{
    return app(DeadlineReminderService::class);
}

it('avvisa a 14, 7, 3, 1, 0 giorni e ogni giorno dopo la scadenza', function (int $days) {
    $user = reminderUser();
    $deadline = reminderDeadline($user, $this->today->addDays($days));

    expect(reminders()->send($this->today))->toBe(1);

    Notification::assertSentTo($user, DeadlineReminder::class, fn (DeadlineReminder $n) => $n->deadlineId === $deadline->id);
    $this->assertDatabaseHas('deadline_notifications', ['deadline_id' => $deadline->id, 'days_until_due' => $days]);
})->with([14, 7, 3, 1, 0, -1, -5]);

it('non avvisa negli altri giorni', function (int $days) {
    reminderDeadline(reminderUser(), $this->today->addDays($days));

    expect(reminders()->send($this->today))->toBe(0);
    Notification::assertNothingSent();
})->with([2, 4, 8, 13, 15, 30]);

it('ignora le scadenze completate, non dovute o archiviate', function (string $state) {
    $user = reminderUser();
    $deadline = reminderDeadline($user, $this->today);

    match ($state) {
        'completed' => $deadline->update(['status' => 'completed']),
        'not_due' => $deadline->update(['status' => 'not_due']),
        'archived' => $deadline->delete(),
    };

    expect(reminders()->send($this->today))->toBe(0);
})->with(['completed', 'not_due', 'archived']);

it('salta gli utenti senza subscription senza scrivere log', function () {
    reminderDeadline(reminderUser(subscribed: false), $this->today);

    expect(reminders()->send($this->today))->toBe(0);
    expect(DeadlineNotification::query()->count())->toBe(0);
});

it('notifica ogni scadenza una sola volta al giorno', function () {
    reminderDeadline(reminderUser(), $this->today->addDays(7));

    expect(reminders()->send($this->today))->toBe(1)
        ->and(reminders()->send($this->today))->toBe(0)
        ->and(reminders()->send($this->today->addDay()))->toBe(0);
});

it('sollecita di nuovo la scadenza aperta il giorno dopo', function () {
    reminderDeadline(reminderUser(), $this->today->subDay());

    expect(reminders()->send($this->today))->toBe(1)
        ->and(reminders()->send($this->today->addDay()))->toBe(1);
});

it('manda a ogni utente solo le proprie scadenze', function () {
    $alice = reminderUser();
    $bob = reminderUser();
    $aliceDeadline = reminderDeadline($alice, $this->today);
    $bobDeadline = reminderDeadline($bob, $this->today->addDays(3));

    expect(reminders()->send($this->today))->toBe(2);

    Notification::assertSentTo($alice, DeadlineReminder::class, fn (DeadlineReminder $n) => $n->deadlineId === $aliceDeadline->id);
    Notification::assertNotSentTo($alice, DeadlineReminder::class, fn (DeadlineReminder $n) => $n->deadlineId === $bobDeadline->id);
    Notification::assertSentTo($bob, DeadlineReminder::class, fn (DeadlineReminder $n) => $n->deadlineId === $bobDeadline->id);
});

it('include l\'importo previsto nelle scadenze di pagamento', function () {
    $user = onboardedUserWithTemplates();
    app(OpenYear::class)($user, app(YearOpeningPlanner::class)->plan($user, 2026));
    $user->updatePushSubscription('https://push.example.com/owner', 'p256dh', 'auth');
    // Il cron gira senza utente autenticato.
    auth()->forgetUser();

    // Assicurazione: voce fissa €350, rata unica.
    Deadline::query()->withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->where('name', 'Assicurazione professionale')
        ->update(['due_at' => $this->today->addDays(3)->toDateString()]);

    reminders()->send($this->today);

    Notification::assertSentTo($user, DeadlineReminder::class, fn (DeadlineReminder $n) => $n->title === 'Assicurazione professionale'
        && $n->body === 'Tra 3 giorni (05/11) · € 350,00');
});

it('compone il testo della notifica', function (int $days, ?float $amount, string $expected) {
    $deadline = new Deadline([
        'due_at' => '2026-11-16',
        'kind' => $amount === null ? DeadlineKind::Fulfillment : DeadlineKind::Payment,
    ]);

    expect(reminders()->body($deadline, $days, $amount))->toBe($expected);
})->with([
    [14, null, 'Tra 14 giorni (16/11)'],
    [3, 2340.0, 'Tra 3 giorni (16/11) · € 2.340,00'],
    [1, null, 'Domani (16/11)'],
    [0, null, 'Oggi (16/11)'],
    [-1, null, 'Scaduta ieri (16/11)'],
    [-4, 12.5, 'Scaduta da 4 giorni (16/11) · € 12,50'],
]);

it('il comando invia i promemoria del giorno', function () {
    $this->travelTo($this->today->setTime(8, 0));
    reminderDeadline(reminderUser(), $this->today);

    $this->artisan('deadlines:send-reminders')
        ->expectsOutputToContain('Promemoria inviati: 1')
        ->assertSuccessful();
});
