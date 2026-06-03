<?php

use App\Actions\Studiofinance\CreateDeadline;
use App\Actions\Studiofinance\OpenYear;
use App\Actions\Studiofinance\RegisterPayment;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Payment;
use App\Models\User;
use App\Models\Year;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/** Utente onboarded con un anno aperto (spese + scadenze generate). */
function userWithOpenYearForDeadlines(int $year = 2026): User
{
    $user = onboardedUserWithTemplates();
    test()->actingAs($user);
    $plan = app(YearOpeningPlanner::class)->plan($user, $year);
    app(OpenYear::class)($user, $plan);

    return $user;
}

it('crea una scadenza ad-hoc di pagamento con pagamento pianificato 1:1', function () {
    userWithOpenYearForDeadlines();
    $expense = AnnualExpense::query()->firstOrFail();

    $this->post(route('deadlines.store'), [
        'kind' => 'payment',
        'name' => 'Imposta di bollo straordinaria',
        'due_at' => '2026-09-30',
        'annual_expense_id' => $expense->id,
    ])->assertRedirect();

    $deadline = Deadline::query()->where('name', 'Imposta di bollo straordinaria')->firstOrFail();
    expect($deadline->kind)->toBe(DeadlineKind::Payment)
        ->and($deadline->status)->toBe(DeadlineStatus::Open)
        ->and($deadline->annual_expense_id)->toBe($expense->id)
        ->and($deadline->year_id)->toBe($expense->year_id)
        ->and($deadline->quota_type)->toBeNull();

    $payment = $deadline->payment;
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Planned)
        ->and($payment->annual_expense_id)->toBe($expense->id);
});

it('crea una scadenza ad-hoc di adempimento senza pagamento', function () {
    userWithOpenYearForDeadlines();
    $year = Year::query()->firstOrFail();

    $this->post(route('deadlines.store'), [
        'kind' => 'fulfillment',
        'name' => 'Comunicazione straordinaria',
        'due_at' => '2026-10-15',
        'year_id' => $year->id,
    ])->assertRedirect();

    $deadline = Deadline::query()->where('name', 'Comunicazione straordinaria')->firstOrFail();
    expect($deadline->kind)->toBe(DeadlineKind::Fulfillment)
        ->and($deadline->status)->toBe(DeadlineStatus::Open)
        ->and($deadline->annual_expense_id)->toBeNull()
        ->and($deadline->year_id)->toBe($year->id)
        ->and($deadline->payment)->toBeNull();
});

it('richiede la spesa per il pagamento e l anno per l adempimento', function () {
    userWithOpenYearForDeadlines();

    // Pagamento senza spesa.
    $this->post(route('deadlines.store'), [
        'kind' => 'payment',
        'name' => 'X',
        'due_at' => '2026-09-30',
    ])->assertSessionHasErrors('annual_expense_id');

    // Adempimento senza anno.
    $this->post(route('deadlines.store'), [
        'kind' => 'fulfillment',
        'name' => 'X',
        'due_at' => '2026-09-30',
    ])->assertSessionHasErrors('year_id');

    // Nome mancante.
    $year = Year::query()->firstOrFail();
    $this->post(route('deadlines.store'), [
        'kind' => 'fulfillment',
        'due_at' => '2026-09-30',
        'year_id' => $year->id,
    ])->assertSessionHasErrors('name');
});

it('non permette di collegare spesa o anno di un altro utente', function () {
    $user = userWithOpenYearForDeadlines();

    $other = User::factory()->create();
    $this->actingAs($other);
    $otherExpense = AnnualExpense::factory()->for($other)->create();
    $otherYear = $otherExpense->year;

    $this->actingAs($user);

    $this->post(route('deadlines.store'), [
        'kind' => 'payment',
        'name' => 'X',
        'due_at' => '2026-09-30',
        'annual_expense_id' => $otherExpense->id,
    ])->assertSessionHasErrors('annual_expense_id');

    $this->post(route('deadlines.store'), [
        'kind' => 'fulfillment',
        'name' => 'X',
        'due_at' => '2026-09-30',
        'year_id' => $otherYear->id,
    ])->assertSessionHasErrors('year_id');

    expect(Deadline::query()->where('name', 'X')->count())->toBe(0);
});

it('richiede onboarding e autenticazione', function () {
    $this->post(route('deadlines.store'), [])->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->post(route('deadlines.store'), [])
        ->assertRedirect(route('onboarding.show'));
});

/** Crea una scadenza ad-hoc di pagamento sulla spesa data. */
function adHocPaymentDeadline(AnnualExpense $expense, string $name = 'Ad-hoc'): Deadline
{
    return app(CreateDeadline::class)([
        'kind' => 'payment',
        'name' => $name,
        'due_at' => '2026-09-30',
        'annual_expense_id' => $expense->id,
    ]);
}

it('modifica nome e data di una scadenza (sempre consentito)', function () {
    userWithOpenYearForDeadlines();
    $expense = AnnualExpense::query()->firstOrFail();
    $deadline = adHocPaymentDeadline($expense);

    $this->patch(route('deadlines.update', $deadline), [
        'name' => 'Nome corretto',
        'due_at' => '2026-11-15',
    ])->assertRedirect();

    $deadline->refresh();
    expect($deadline->name)->toBe('Nome corretto')
        ->and($deadline->due_at->toDateString())->toBe('2026-11-15');
});

it('cambia la spesa di una ad-hoc di pagamento aperta e sincronizza il pagamento', function () {
    userWithOpenYearForDeadlines();
    [$a, $b] = AnnualExpense::query()->take(2)->get()->all();
    $deadline = adHocPaymentDeadline($a);

    $this->patch(route('deadlines.update', $deadline), [
        'name' => $deadline->name,
        'due_at' => $deadline->due_at->toDateString(),
        'annual_expense_id' => $b->id,
    ])->assertRedirect();

    $deadline->refresh();
    expect($deadline->annual_expense_id)->toBe($b->id)
        ->and($deadline->payment->annual_expense_id)->toBe($b->id);
});

it('su una scadenza standard cambia la data ma non la spesa', function () {
    userWithOpenYearForDeadlines();
    $standard = Deadline::query()
        ->whereNotNull('recurring_deadline_id')
        ->where('kind', DeadlineKind::Payment)
        ->whereNotNull('annual_expense_id')
        ->firstOrFail();
    $originalExpenseId = $standard->annual_expense_id;
    $otherExpense = AnnualExpense::query()->where('id', '!=', $originalExpenseId)->firstOrFail();

    $this->patch(route('deadlines.update', $standard), [
        'name' => 'Rinominata',
        'due_at' => '2026-12-01',
        'annual_expense_id' => $otherExpense->id,
    ])->assertRedirect();

    $standard->refresh();
    expect($standard->due_at->toDateString())->toBe('2026-12-01')
        ->and($standard->name)->toBe('Rinominata')
        ->and($standard->annual_expense_id)->toBe($originalExpenseId); // immutata
});

it('non cambia la spesa di una ad-hoc gia pagata', function () {
    userWithOpenYearForDeadlines();
    [$a, $b] = AnnualExpense::query()->take(2)->get()->all();
    $deadline = adHocPaymentDeadline($a);
    app(RegisterPayment::class)($deadline, ['amount' => 100, 'paid_at' => '2026-03-15']);

    $this->patch(route('deadlines.update', $deadline), [
        'name' => $deadline->name,
        'due_at' => $deadline->due_at->toDateString(),
        'annual_expense_id' => $b->id,
    ])->assertRedirect();

    expect($deadline->refresh()->annual_expense_id)->toBe($a->id); // immutata
});

it('archivia una scadenza ad-hoc non pagata, con il suo pagamento pianificato', function () {
    userWithOpenYearForDeadlines();
    $expense = AnnualExpense::query()->firstOrFail();
    $deadline = adHocPaymentDeadline($expense);
    $paymentId = $deadline->payment->id;

    $this->delete(route('deadlines.destroy', $deadline))->assertRedirect();

    expect(Deadline::query()->find($deadline->id))->toBeNull()
        ->and(Deadline::withTrashed()->find($deadline->id))->not->toBeNull()
        ->and(Payment::query()->find($paymentId))->toBeNull();
});

it('non archivia una scadenza standard (usa non dovuta)', function () {
    userWithOpenYearForDeadlines();
    $standard = Deadline::query()->whereNotNull('recurring_deadline_id')->firstOrFail();

    $this->delete(route('deadlines.destroy', $standard))->assertStatus(422);

    expect(Deadline::query()->find($standard->id))->not->toBeNull();
});

it('non archivia una ad-hoc gia pagata', function () {
    userWithOpenYearForDeadlines();
    $expense = AnnualExpense::query()->firstOrFail();
    $deadline = adHocPaymentDeadline($expense);
    app(RegisterPayment::class)($deadline, ['amount' => 100, 'paid_at' => '2026-03-15']);

    $this->delete(route('deadlines.destroy', $deadline))->assertStatus(422);

    expect(Deadline::query()->find($deadline->id))->not->toBeNull();
});

it('non modifica ne archivia scadenze di un altro utente', function () {
    userWithOpenYearForDeadlines();
    $expense = AnnualExpense::query()->firstOrFail();
    $deadline = adHocPaymentDeadline($expense);

    // Altro utente onboarded (così supera il middleware): il route-model
    // binding sotto global scope non trova la scadenza → 404.
    onboardedUserWithTemplates();

    $this->patch(route('deadlines.update', $deadline), [
        'name' => 'Hack', 'due_at' => '2026-10-10',
    ])->assertNotFound();
    $this->delete(route('deadlines.destroy', $deadline))->assertNotFound();
});

it('crea una ad-hoc col previsto manuale e lo espone come previsto in lista', function () {
    userWithOpenYearForDeadlines();
    $expense = AnnualExpense::query()->firstOrFail();

    $this->post(route('deadlines.store'), [
        'kind' => 'payment',
        'name' => 'Con previsto',
        'due_at' => '2026-09-30',
        'annual_expense_id' => $expense->id,
        'manual_expected_amount' => '320.50',
    ])->assertRedirect();

    $deadline = Deadline::query()->where('name', 'Con previsto')->firstOrFail();
    expect((float) $deadline->manual_expected_amount)->toBe(320.50);

    // Il previsto manuale è esposto come expected_amount (vista derivata).
    $this->get(route('deadlines.index', ['search' => 'Con previsto']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines.data.0.expected_amount', 320.5)
            ->etc());
});

it('modifica il previsto manuale di una ad-hoc aperta', function () {
    userWithOpenYearForDeadlines();
    $deadline = adHocPaymentDeadline(AnnualExpense::query()->firstOrFail());

    $this->patch(route('deadlines.update', $deadline), [
        'name' => $deadline->name,
        'due_at' => $deadline->due_at->toDateString(),
        'manual_expected_amount' => '500',
    ])->assertRedirect();

    expect((float) $deadline->refresh()->manual_expected_amount)->toBe(500.0);
});

it('ignora il previsto manuale su una scadenza standard', function () {
    userWithOpenYearForDeadlines();
    $standard = Deadline::query()
        ->whereNotNull('recurring_deadline_id')
        ->where('kind', DeadlineKind::Payment)
        ->firstOrFail();

    $this->patch(route('deadlines.update', $standard), [
        'name' => $standard->name,
        'due_at' => $standard->due_at->toDateString(),
        'manual_expected_amount' => '999',
    ])->assertRedirect();

    expect($standard->refresh()->manual_expected_amount)->toBeNull();
});

it('ignora il previsto manuale su una ad-hoc gia pagata', function () {
    userWithOpenYearForDeadlines();
    $deadline = adHocPaymentDeadline(AnnualExpense::query()->firstOrFail());
    app(RegisterPayment::class)($deadline, ['amount' => 100, 'paid_at' => '2026-03-15']);

    $this->patch(route('deadlines.update', $deadline), [
        'name' => $deadline->name,
        'due_at' => $deadline->due_at->toDateString(),
        'manual_expected_amount' => '777',
    ])->assertRedirect();

    expect($deadline->refresh()->manual_expected_amount)->toBeNull();
});
