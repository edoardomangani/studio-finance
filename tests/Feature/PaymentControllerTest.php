<?php

use App\Actions\Studiofinance\OpenYear;
use App\Actions\Studiofinance\RegisterPayment;
use App\Enums\DeadlineKind;
use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Payment;
use App\Models\User;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/** Utente onboarded con un anno aperto (spese + scadenze + pagamenti pianificati). */
function userWithOpenYearForPayments(int $year = 2026): User
{
    $user = onboardedUserWithTemplates();
    test()->actingAs($user);
    $plan = app(YearOpeningPlanner::class)->plan($user, $year);
    app(OpenYear::class)($user, $plan);

    return $user;
}

/** Registra il pagamento della prima scadenza di pagamento aperta. */
function registerFirstPayment(float $amount = 100, string $paidAt = '2026-03-15'): Deadline
{
    $deadline = Deadline::query()
        ->where('kind', DeadlineKind::Payment)
        ->whereNotNull('annual_expense_id')
        ->firstOrFail();

    app(RegisterPayment::class)($deadline, ['amount' => $amount, 'paid_at' => $paidAt]);

    return $deadline;
}

it('mostra i pagamenti pagati nella lista filtrata per stato', function () {
    userWithOpenYearForPayments();
    registerFirstPayment(250, '2026-03-15');

    $this->get(route('payments.index', ['status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/Index')
            ->has('payments.data', 1)
            ->where('payments.data.0.status', 'paid')
            ->where('payments.data.0.amount', 250)
            ->where('payments.data.0.paid_at', '2026-03-15')
            ->where('payments.data.0.is_manual', false)
            ->etc());
});

it('filtra per anno della data di cassa (paid_year)', function () {
    userWithOpenYearForPayments();
    registerFirstPayment(100, '2026-06-30');

    $this->get(route('payments.index', ['paid_year' => 2026, 'status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data', fn ($rows) => collect($rows)->isNotEmpty()
                && collect($rows)->every(fn ($r) => str_starts_with((string) $r['paid_at'], '2026')))
            ->etc());

    // Anno senza pagamenti → lista vuota.
    $this->get(route('payments.index', ['paid_year' => 2030]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('payments.data', 0)->etc());
});

it('espone le opzioni di filtro e le spese per l autocomplete', function () {
    userWithOpenYearForPayments();

    $this->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('statusOptions', 3)
            ->has('availableExpenseYears')
            ->where('annualExpenses', fn ($rows) => collect($rows)->isNotEmpty())
            ->etc());
});

it('registra un pagamento manuale extra-scadenza', function () {
    $user = userWithOpenYearForPayments();
    $expense = AnnualExpense::query()->firstOrFail();

    $this->post(route('payments.store'), [
        'annual_expense_id' => $expense->id,
        'amount' => '480.50',
        'paid_at' => '2026-04-10',
        'description' => 'Saldo IRPEF 2025',
    ])->assertRedirect();

    $payment = Payment::query()->where('description', 'Saldo IRPEF 2025')->firstOrFail();
    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->deadline_id)->toBeNull()
        ->and($payment->annual_expense_id)->toBe($expense->id)
        ->and((float) $payment->amount)->toBe(480.50)
        ->and($payment->user_id)->toBe($user->id);
});

it('rifiuta importo non positivo e data futura', function () {
    userWithOpenYearForPayments();
    $expense = AnnualExpense::query()->firstOrFail();

    $this->post(route('payments.store'), [
        'annual_expense_id' => $expense->id,
        'amount' => '0',
        'paid_at' => now()->addDay()->toDateString(),
    ])->assertSessionHasErrors(['amount', 'paid_at']);
});

it('non permette di imputare a una spesa di un altro utente', function () {
    $user = userWithOpenYearForPayments();
    $myPaymentCount = Payment::query()->count();

    // Il trait BelongsToUser forza user_id = Auth::id() in creazione: la spesa
    // dell'altro utente va creata mentre si è autenticati come lui.
    $other = User::factory()->create();
    $this->actingAs($other);
    $otherExpense = AnnualExpense::factory()->for($other)->create();

    $this->actingAs($user);
    $this->post(route('payments.store'), [
        'annual_expense_id' => $otherExpense->id,
        'amount' => '100',
        'paid_at' => '2026-04-10',
    ])->assertSessionHasErrors('annual_expense_id');

    expect(Payment::query()->count())->toBe($myPaymentCount);
});

it('non mostra i pagamenti di un altro utente', function () {
    $user = userWithOpenYearForPayments();
    registerFirstPayment(100, '2026-03-15');

    // Pagamento di un altro utente (creato sotto la sua auth): non deve
    // comparire nella mia lista.
    $other = User::factory()->create();
    $this->actingAs($other);
    Payment::factory()->for($other)->for(AnnualExpense::factory()->for($other))->paid(999)->create();

    $this->actingAs($user);
    $this->get(route('payments.index', ['status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data', fn ($rows) => collect($rows)->every(fn ($r) => (float) $r['amount'] !== 999.0))
            ->etc());
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('payments.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->get(route('payments.index'))
        ->assertRedirect(route('onboarding.show'));
});
