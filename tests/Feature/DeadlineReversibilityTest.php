<?php

use App\Actions\Studiofinance\OpenYear;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\Deadline;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Una scadenza di pagamento aperta dell'anno aperto, recante l'utente attivo. */
function openPaymentDeadline(int $year = 2026): Deadline
{
    $user = onboardedUserWithTemplates();
    test()->actingAs($user);
    app(OpenYear::class)($user, app(YearOpeningPlanner::class)->plan($user, $year));

    return Deadline::query()
        ->where('kind', DeadlineKind::Payment)
        ->where('status', DeadlineStatus::Open)
        ->whereNotNull('annual_expense_id')
        ->firstOrFail();
}

it('annulla il completamento: torna aperta e azzera il pagamento', function () {
    $deadline = openPaymentDeadline();
    $this->post(route('deadlines.payment', $deadline), ['amount' => 500, 'paid_at' => '2026-03-15']);

    $this->post(route('deadlines.reopen', $deadline))->assertRedirect();

    $deadline->refresh();
    expect($deadline->status)->toBe(DeadlineStatus::Open)
        ->and($deadline->payment->status)->toBe(PaymentStatus::Planned)
        ->and($deadline->payment->amount)->toBeNull()
        ->and($deadline->payment->paid_at)->toBeNull();
});

it('marca non dovuta: scadenza e pagamento not_due', function () {
    $deadline = openPaymentDeadline();

    $this->post(route('deadlines.mark-not-due', $deadline))->assertRedirect();

    $deadline->refresh();
    expect($deadline->status)->toBe(DeadlineStatus::NotDue)
        ->and($deadline->payment->status)->toBe(PaymentStatus::NotDue);
});

it('riapre una scadenza non dovuta', function () {
    $deadline = openPaymentDeadline();
    $this->post(route('deadlines.mark-not-due', $deadline));

    $this->post(route('deadlines.reopen', $deadline))->assertRedirect();

    $deadline->refresh();
    expect($deadline->status)->toBe(DeadlineStatus::Open)
        ->and($deadline->payment->status)->toBe(PaymentStatus::Planned);
});

it('non riapre una scadenza già aperta', function () {
    $deadline = openPaymentDeadline();

    $this->post(route('deadlines.reopen', $deadline))->assertStatus(422);

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::Open);
});

it('non marca non dovuta una scadenza già completata', function () {
    $deadline = openPaymentDeadline();
    $this->post(route('deadlines.payment', $deadline), ['amount' => 500, 'paid_at' => '2026-03-15']);

    $this->post(route('deadlines.mark-not-due', $deadline))->assertStatus(422);

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::Completed);
});

it('non consente reversibilità sulle scadenze di un altro utente (tenancy)', function () {
    $deadline = openPaymentDeadline();

    $other = onboardedUserWithTemplates();
    $this->actingAs($other);

    $this->post(route('deadlines.reopen', $deadline))->assertNotFound();
    $this->post(route('deadlines.mark-not-due', $deadline))->assertNotFound();
});
