<?php

use App\Actions\Studiofinance\OpenYear;
use App\Actions\Studiofinance\RegisterManualPayment;
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

    $this->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/Index')
            ->has('payments.data', 1)
            ->where('payments.data.0.amount', 250)
            ->where('payments.data.0.paid_at', '2026-03-15')
            ->where('payments.data.0.is_manual', false)
            ->etc());
});

it('il registro mostra solo i pagamenti effettuati, non i pianificati', function () {
    userWithOpenYearForPayments();
    // Il wizard ha generato molti pagamenti pianificati; nessuno deve comparire.
    expect(Payment::query()->where('status', PaymentStatus::Planned)->count())->toBeGreaterThan(0);

    $this->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('payments.data', 0)->etc());

    // Registrato uno → compare solo quello.
    registerFirstPayment(100, '2026-03-15');
    $this->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('payments.data', 1)->etc());
});

it('filtra per anno della data di cassa (paid_year)', function () {
    userWithOpenYearForPayments();
    registerFirstPayment(100, '2026-06-30');

    $this->get(route('payments.index', ['paid_year' => 2026]))
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

it('espone gli anni di filtro e le spese per l autocomplete', function () {
    userWithOpenYearForPayments();

    $this->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
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

it('modifica un pagamento manuale', function () {
    userWithOpenYearForPayments();
    $expense = AnnualExpense::query()->firstOrFail();
    $manual = app(RegisterManualPayment::class)($expense, ['amount' => '100', 'paid_at' => '2026-04-10', 'description' => 'Bozza']);

    $this->from(route('payments.index'))
        ->patch(route('payments.update', $manual), [
            'annual_expense_id' => $expense->id,
            'amount' => '250.00',
            'paid_at' => '2026-05-01',
            'description' => 'Saldo corretto',
        ])->assertRedirect(route('payments.index'));

    $manual->refresh();
    expect((float) $manual->amount)->toBe(250.0)
        ->and($manual->description)->toBe('Saldo corretto')
        ->and($manual->paid_at->toDateString())->toBe('2026-05-01');
});

it('elimina (soft delete) un pagamento manuale', function () {
    userWithOpenYearForPayments();
    $expense = AnnualExpense::query()->firstOrFail();
    $manual = app(RegisterManualPayment::class)($expense, ['amount' => '100', 'paid_at' => '2026-04-10', 'description' => null]);

    $this->from(route('payments.index'))
        ->delete(route('payments.destroy', $manual))
        ->assertRedirect(route('payments.index'));

    expect(Payment::find($manual->id))->toBeNull()
        ->and(Payment::withTrashed()->find($manual->id))->not->toBeNull();
});

it('vieta modifica ed eliminazione di un pagamento da scadenza', function () {
    userWithOpenYearForPayments();
    registerFirstPayment(250, '2026-03-15');
    $fromDeadline = Payment::query()->where('status', PaymentStatus::Paid)->whereNotNull('deadline_id')->firstOrFail();

    $this->patch(route('payments.update', $fromDeadline), [
        'annual_expense_id' => $fromDeadline->annual_expense_id,
        'amount' => '999',
        'paid_at' => '2026-03-15',
    ])->assertForbidden();

    $this->delete(route('payments.destroy', $fromDeadline))->assertForbidden();

    expect((float) $fromDeadline->fresh()->amount)->toBe(250.0);
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
    $this->get(route('payments.index'))
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
