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
        'nome' => 'Mario Rossi',
        'coefficiente_redditivita' => 78,
        'anno_inizio_attivita' => 2020,
    ]);

    $response->assertRedirect('/dashboard');

    $user = $user->fresh();

    expect($user->isOnboarded())->toBeTrue();
    // Nome professionista = User.name (single source of truth).
    expect($user->name)->toBe('Mario Rossi');
    expect($user->professionalProfile)->not->toBeNull();
    expect((float) $user->professionalProfile->coefficiente_redditivita)->toBe(78.0);
    expect($user->professionalProfile->anno_inizio_attivita)->toBe(2020);
});

it('valida i campi obbligatori', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', [])
        ->assertSessionHasErrors(['nome', 'coefficiente_redditivita', 'anno_inizio_attivita']);
});

it('blocca coefficiente fuori range 0-100', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', [
            'nome' => 'Test',
            'coefficiente_redditivita' => 150,
            'anno_inizio_attivita' => 2020,
        ])
        ->assertSessionHasErrors(['coefficiente_redditivita']);
});

it('blocca anno fuori range 1990-corrente', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', [
            'nome' => 'Test',
            'coefficiente_redditivita' => 78,
            'anno_inizio_attivita' => 1980,
        ])
        ->assertSessionHasErrors(['anno_inizio_attivita']);
});

it('rifiuta una seconda submit se l\'utente è già onboarded', function (): void {
    $user = User::factory()->create(['name' => 'Originale']);
    ProfessionalProfile::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/onboarding', [
            'nome' => 'Altro nome',
            'coefficiente_redditivita' => 50,
            'anno_inizio_attivita' => 2010,
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
