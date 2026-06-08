<?php

use App\Enums\ExpenseCalculationType;
use App\Models\ExpenseItem;
use App\Models\ProfessionalProfile;
use App\Models\User;

function onboarded(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

it('mostra la pagina expense items con tabella + opzioni enum', function (): void {
    $user = onboarded();
    ExpenseItem::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get('/settings/expense-items')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/ExpenseItems/Index')
            ->has('expenseItems', 3)
            ->has('calculationTypes'),
        );
});

it('reindirizza a /onboarding se non onboarded (index)', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/expense-items')
        ->assertRedirect('/onboarding');
});

it('crea una nuova voce di spesa', function (): void {
    $user = onboarded();

    $this->actingAs($user)->post('/settings/expense-items', [
        'name' => 'Tasse comunali',
        'calculation_type' => ExpenseCalculationType::FixedAnnual->value,
        'kind' => 'fixed',
        'default_amount' => 150,
        'active' => true,
        'position' => 100,
    ])->assertRedirect('/settings/expense-items');

    expect($user->expenseItems()->where('name', 'Tasse comunali')->exists())->toBeTrue();
});

it('valida name required', function (): void {
    $user = onboarded();

    $this->actingAs($user)->post('/settings/expense-items', [
        'calculation_type' => ExpenseCalculationType::FixedAnnual->value,
        'kind' => 'fixed',
        'active' => true,
    ])->assertSessionHasErrors(['name']);
});

it('valida default_maximum >= default_minimum', function (): void {
    $user = onboarded();

    $this->actingAs($user)->post('/settings/expense-items', [
        'name' => 'X',
        'calculation_type' => ExpenseCalculationType::PercentageOfIrpefIncome->value,
        'kind' => 'fixed',
        'default_rate' => 10,
        'default_minimum' => 500,
        'default_maximum' => 100,
        'active' => true,
    ])->assertSessionHasErrors(['default_maximum']);
});

it('aggiorna una voce esistente', function (): void {
    $user = onboarded();
    $item = ExpenseItem::factory()->for($user)->create([
        'name' => 'Vecchio nome',
    ]);

    $this->actingAs($user)->patch("/settings/expense-items/{$item->id}", [
        'name' => 'Nuovo nome',
        'calculation_type' => ExpenseCalculationType::FixedAnnual->value,
        'kind' => 'fixed',
        'default_amount' => 200,
        'active' => true,
    ])->assertRedirect('/settings/expense-items');

    expect($item->fresh()->name)->toBe('Nuovo nome');
});

it('archivia (soft delete) una voce', function (): void {
    $user = onboarded();
    $item = ExpenseItem::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/settings/expense-items/{$item->id}")
        ->assertRedirect('/settings/expense-items');

    expect($item->fresh()->trashed())->toBeTrue();
});

it('blocca utenti non onboarded', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/settings/expense-items', [
        'name' => 'X',
        'calculation_type' => ExpenseCalculationType::FixedAnnual->value,
        'kind' => 'fixed',
        'active' => true,
    ])->assertRedirect('/onboarding');
});

it('blocca cross-user via tenancy scope (route binding 404)', function (): void {
    $altro = onboarded();
    $itemAltro = ExpenseItem::factory()->for($altro)->create();

    $user = onboarded();

    $this->actingAs($user)
        ->patch("/settings/expense-items/{$itemAltro->id}", [
            'name' => 'Hijack',
            'calculation_type' => ExpenseCalculationType::FixedAnnual->value,
            'kind' => 'fixed',
            'active' => true,
        ])
        ->assertNotFound();
});
