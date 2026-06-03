<?php

use App\Enums\DeadlineKind;
use App\Enums\DueYearOffset;
use App\Enums\ExpenseYearOffset;
use App\Enums\QuotaType;
use App\Models\ExpenseItem;
use App\Models\ProfessionalProfile;
use App\Models\RecurringDeadline;
use App\Models\User;

function onboardedRD(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

it('mostra la pagina recurring deadlines con tabella + active expense items', function (): void {
    $user = onboardedRD();
    ExpenseItem::factory()->for($user)->create(['active' => true]);
    RecurringDeadline::factory()->for($user)->count(2)->create();

    $this->actingAs($user)
        ->get('/settings/recurring-deadlines')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/RecurringDeadlines/Index')
            ->has('recurringDeadlines', 2)
            ->has('kinds')
            ->has('dueYearOffsets')
            ->has('expenseYearOffsets')
            ->has('activeExpenseItems', 1),
        );
});

it('reindirizza a /onboarding se non onboarded (index)', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/recurring-deadlines')
        ->assertRedirect('/onboarding');
});

it('crea un recurring deadline fulfillment', function (): void {
    $user = onboardedRD();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'Comunicazione X',
        'day' => 15,
        'month' => 10,
        'kind' => DeadlineKind::Fulfillment->value,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'active' => true,
    ])->assertRedirect('/settings/recurring-deadlines');

    expect($user->recurringDeadlines()->where('name', 'Comunicazione X')->exists())->toBeTrue();
});

it('persiste due_year_offset=next per saldi e bolli Q4', function (): void {
    $user = onboardedRD();
    $item = ExpenseItem::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'Saldo IS',
        'day' => 30,
        'month' => 6,
        'kind' => DeadlineKind::Payment->value,
        'expense_item_id' => $item->id,
        'due_year_offset' => DueYearOffset::Next->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'active' => true,
    ])->assertRedirect('/settings/recurring-deadlines');

    $saldo = $user->recurringDeadlines()->where('name', 'Saldo IS')->first();
    expect($saldo->due_year_offset)
        ->toBe(DueYearOffset::Next);
    expect($saldo->expense_year_offset)
        ->toBe(ExpenseYearOffset::Current);
});

it('crea un recurring deadline payment con expense item collegato', function (): void {
    $user = onboardedRD();
    $item = ExpenseItem::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'Saldo IS',
        'day' => 30,
        'month' => 6,
        'kind' => DeadlineKind::Payment->value,
        'expense_item_id' => $item->id,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'active' => true,
    ])->assertRedirect('/settings/recurring-deadlines');

    expect($user->recurringDeadlines()->where('name', 'Saldo IS')->first()->expense_item_id)
        ->toBe($item->id);
});

it('persiste il quota_type su una scadenza di pagamento', function (): void {
    $user = onboardedRD();
    $item = ExpenseItem::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => '1° acconto IS',
        'day' => 30,
        'month' => 6,
        'kind' => DeadlineKind::Payment->value,
        'expense_item_id' => $item->id,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'quota_type' => QuotaType::TaxAdvance->value,
        'active' => true,
    ])->assertRedirect('/settings/recurring-deadlines');

    expect($user->recurringDeadlines()->where('name', '1° acconto IS')->first()->quota_type)
        ->toBe(QuotaType::TaxAdvance);
});

it('vieta il quota_type su fulfillment', function (): void {
    $user = onboardedRD();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'Dichiarazione',
        'day' => 30,
        'month' => 11,
        'kind' => DeadlineKind::Fulfillment->value,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'quota_type' => QuotaType::FullAmount->value,
        'active' => true,
    ])->assertSessionHasErrors(['quota_type']);
});

it('richiede expense_item_id per payment', function (): void {
    $user = onboardedRD();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'X',
        'day' => 30,
        'month' => 6,
        'kind' => DeadlineKind::Payment->value,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'active' => true,
    ])->assertSessionHasErrors(['expense_item_id']);
});

it('vieta expense_item_id su fulfillment', function (): void {
    $user = onboardedRD();
    $item = ExpenseItem::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'X',
        'day' => 15,
        'month' => 10,
        'kind' => DeadlineKind::Fulfillment->value,
        'expense_item_id' => $item->id,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'active' => true,
    ])->assertSessionHasErrors(['expense_item_id']);
});

it('valida day entro 1-31', function (): void {
    $user = onboardedRD();

    $this->actingAs($user)->post('/settings/recurring-deadlines', [
        'name' => 'X',
        'day' => 32,
        'month' => 6,
        'kind' => DeadlineKind::Fulfillment->value,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Current->value,
        'active' => true,
    ])->assertSessionHasErrors(['day']);
});

it('aggiorna una scadenza esistente', function (): void {
    $user = onboardedRD();
    $item = ExpenseItem::factory()->for($user)->create();
    $deadline = RecurringDeadline::factory()->for($user)->payment()->create([
        'expense_item_id' => $item->id,
    ]);

    $this->actingAs($user)->patch("/settings/recurring-deadlines/{$deadline->id}", [
        'name' => 'Aggiornata',
        'day' => 15,
        'month' => 11,
        'kind' => DeadlineKind::Payment->value,
        'expense_item_id' => $item->id,
        'due_year_offset' => DueYearOffset::Current->value,
        'expense_year_offset' => ExpenseYearOffset::Next->value,
        'active' => true,
    ])->assertRedirect('/settings/recurring-deadlines');

    expect($deadline->fresh()->name)->toBe('Aggiornata');
});

it('archivia (soft delete) una scadenza', function (): void {
    $user = onboardedRD();
    $deadline = RecurringDeadline::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/settings/recurring-deadlines/{$deadline->id}")
        ->assertRedirect('/settings/recurring-deadlines');

    expect($deadline->fresh()->trashed())->toBeTrue();
});

it('blocca cross-user via tenancy scope', function (): void {
    $altro = onboardedRD();
    $deadlineAltro = RecurringDeadline::factory()->for($altro)->create();

    $user = onboardedRD();

    $this->actingAs($user)
        ->delete("/settings/recurring-deadlines/{$deadlineAltro->id}")
        ->assertNotFound();
});
