<?php

use App\Actions\Studiofinance\OpenYear;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\Deadline;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Prima scadenza di pagamento aperta dell'anno aperto per l'utente. */
function anOpenPaymentDeadline(int $year = 2026): Deadline
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

it('registra il pagamento: scadenza completata e pagamento pagato', function () {
    $deadline = anOpenPaymentDeadline();

    $this->post(route('deadlines.payment', $deadline), [
        'description' => $deadline->name,
        'amount' => 1217.50,
        'paid_at' => '2026-03-15',
    ])->assertRedirect();

    $deadline->refresh();
    expect($deadline->status)->toBe(DeadlineStatus::Completed)
        ->and($deadline->payment->status)->toBe(PaymentStatus::Paid)
        ->and((float) $deadline->payment->amount)->toBe(1217.50)
        ->and($deadline->payment->paid_at->toDateString())->toBe('2026-03-15');
});

it('rifiuta importo non positivo', function () {
    $deadline = anOpenPaymentDeadline();

    $this->post(route('deadlines.payment', $deadline), [
        'amount' => 0,
        'paid_at' => '2026-03-15',
    ])->assertSessionHasErrors('amount');

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::Open);
});

it('rifiuta data futura', function () {
    $deadline = anOpenPaymentDeadline();

    $this->post(route('deadlines.payment', $deadline), [
        'amount' => 100,
        'paid_at' => now()->addDay()->toDateString(),
    ])->assertSessionHasErrors('paid_at');
});

it('non registra due volte la stessa scadenza', function () {
    $deadline = anOpenPaymentDeadline();

    $this->post(route('deadlines.payment', $deadline), [
        'amount' => 100,
        'paid_at' => '2026-03-15',
    ])->assertRedirect();

    // Ora è completata: un secondo tentativo viene rifiutato.
    $this->post(route('deadlines.payment', $deadline), [
        'amount' => 200,
        'paid_at' => '2026-03-15',
    ])->assertSessionHasErrors('amount');

    expect((float) $deadline->fresh()->payment->amount)->toBe(100.0);
});

it('non lascia registrare il pagamento di un altro utente (tenancy)', function () {
    $deadline = anOpenPaymentDeadline();

    $other = onboardedUserWithTemplates();
    $this->actingAs($other);

    $this->post(route('deadlines.payment', $deadline), [
        'amount' => 100,
        'paid_at' => '2026-03-15',
    ])->assertNotFound();
});
