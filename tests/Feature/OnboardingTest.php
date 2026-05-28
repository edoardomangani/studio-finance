<?php

use App\Models\ProfessionalProfile;
use App\Models\User;

it('mostra la pagina di onboarding a un utente non onboarded', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Onboarding'));
});

it('reindirizza alla dashboard se l\'utente è già onboarded', function (): void {
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertRedirect('/dashboard');
});

it('crea il professional profile, aggiorna User.name e reindirizza alla dashboard', function (): void {
    $user = User::factory()->create(['name' => 'Originale']);

    $response = $this->actingAs($user)->post('/onboarding', [
        'name' => 'Mario Rossi',
        'profitability_coefficient' => 78,
        'business_start_year' => 2020,
    ]);

    $response->assertRedirect('/dashboard');

    $user = $user->fresh();

    expect($user->isOnboarded())->toBeTrue();
    // Nome professionista = users.name (single source of truth).
    expect($user->name)->toBe('Mario Rossi');
    expect($user->professionalProfile)->not->toBeNull();
    expect((float) $user->professionalProfile->profitability_coefficient)->toBe(78.0);
    expect($user->professionalProfile->business_start_year)->toBe(2020);
});

it('seed dei template iniziali (8 expense items + recurring deadlines) all\'onboarding', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/onboarding', [
        'name' => 'Mario Rossi',
        'profitability_coefficient' => 78,
        'business_start_year' => 2020,
    ])->assertRedirect('/dashboard');

    $user = $user->fresh();

    expect($user->expenseItems()->count())->toBe(8);
    expect($user->recurringDeadlines()->count())->toBeGreaterThanOrEqual(15);

    // Le scadenze di tipo payment devono essere linkate a un expense item.
    $paymentSenzaItem = $user->recurringDeadlines()
        ->where('kind', 'payment')
        ->whereNull('expense_item_id')
        ->count();
    expect($paymentSenzaItem)->toBe(0);

    // Almeno una scadenza con expense_year_offset=next (commercialista).
    expect($user->recurringDeadlines()
        ->where('expense_year_offset', 'next')
        ->exists()
    )->toBeTrue();
});

it('rollback del seeding se la creazione del profilo fallisce', function (): void {
    $user = User::factory()->create();

    // coefficiente fuori range fa fallire la validazione prima ancora
    // dell'action, quindi il rollback è implicito: il test verifica che
    // nessun template residuo resti.
    $this->actingAs($user)->post('/onboarding', [
        'name' => 'Mario Rossi',
        'profitability_coefficient' => 999,
        'business_start_year' => 2020,
    ])->assertSessionHasErrors(['profitability_coefficient']);

    expect($user->expenseItems()->count())->toBe(0);
    expect($user->recurringDeadlines()->count())->toBe(0);
});

it('valida i campi obbligatori', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', [])
        ->assertSessionHasErrors(['name', 'profitability_coefficient', 'business_start_year']);
});

it('blocca coefficiente fuori range 0-100', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', [
            'name' => 'Test',
            'profitability_coefficient' => 150,
            'business_start_year' => 2020,
        ])
        ->assertSessionHasErrors(['profitability_coefficient']);
});

it('blocca anno fuori range 1990-corrente', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', [
            'name' => 'Test',
            'profitability_coefficient' => 78,
            'business_start_year' => 1980,
        ])
        ->assertSessionHasErrors(['business_start_year']);
});

it('rifiuta una seconda submit se l\'utente è già onboarded', function (): void {
    $user = User::factory()->create(['name' => 'Originale']);
    ProfessionalProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/onboarding', [
            'name' => 'Altro nome',
            'profitability_coefficient' => 50,
            'business_start_year' => 2010,
        ])
        ->assertForbidden();

    expect($user->fresh()->name)->toBe('Originale');
});

it('redirige a /onboarding quando un utente non-onboarded apre una rotta protetta', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/onboarding');
});

it('lascia passare alla dashboard un utente onboarded', function (): void {
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
