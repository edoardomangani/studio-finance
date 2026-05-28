<?php

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('global scope nasconde i record di altri utenti quando autenticato', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    ProfessionalProfile::factory()->for($userA)->create();
    ProfessionalProfile::factory()->for($userB)->create();

    Auth::login($userA);

    // ProfessionalProfile::query() vede solo il record di A.
    expect(ProfessionalProfile::count())->toBe(1);
    expect(ProfessionalProfile::first()->user_id)->toBe($userA->id);
});

it('withoutGlobalScopes() permette di vedere tutti i record (admin/seeders)', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    ProfessionalProfile::factory()->for($userA)->create();
    ProfessionalProfile::factory()->for($userB)->create();

    Auth::login($userA);

    expect(ProfessionalProfile::withoutGlobalScopes()->count())->toBe(2);
});

it('forza user_id sempre = Auth::id() quando autenticato (no IDOR via mass-assignment)', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Auth::login($userA);

    // Tentativo malicious: passare user_id di B nel payload.
    $profile = ProfessionalProfile::create([
        'user_id' => $userB->id,
        'coefficiente_redditivita' => 78,
        'anno_inizio_attivita' => 2024,
    ]);

    // Il trait sovrascrive: user_id = A (mai B).
    expect($profile->user_id)->toBe($userA->id);
});

it('lancia LogicException se si crea senza user_id in contesto non autenticato', function (): void {
    expect(fn () => ProfessionalProfile::create([
        'coefficiente_redditivita' => 78,
        'anno_inizio_attivita' => 2024,
    ]))->toThrow(LogicException::class, 'without user_id');
});

it('permette create con user_id esplicito in contesto non autenticato (seeders, jobs)', function (): void {
    $user = User::factory()->create();

    // Esplicitamente non autenticati.
    Auth::logout();

    $profile = ProfessionalProfile::create([
        'user_id' => $user->id,
        'coefficiente_redditivita' => 78,
        'anno_inizio_attivita' => 2024,
    ]);

    expect($profile->user_id)->toBe($user->id);
});

it('utente B non può aggiornare il profilo professionale di utente A via endpoint', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    ProfessionalProfile::factory()->for($userA)->create([
        'coefficiente_redditivita' => 78,
        'anno_inizio_attivita' => 2020,
    ]);
    ProfessionalProfile::factory()->for($userB)->create([
        'coefficiente_redditivita' => 78,
        'anno_inizio_attivita' => 2020,
    ]);

    // B fa PATCH: il controller opera su $request->user()->professionalProfile,
    // quindi tocca solo il proprio profilo.
    $this->actingAs($userB)
        ->patch('/settings/professional', [
            'nome' => 'Hacker',
            'email' => $userB->email,
            'coefficiente_redditivita' => 50,
            'anno_inizio_attivita' => 2010,
        ])
        ->assertRedirect();

    // Il profilo di A è intatto.
    Auth::login($userA);
    $profileA = $userA->fresh()->professionalProfile;
    expect((float) $profileA->coefficiente_redditivita)->toBe(78.0);
    expect($profileA->anno_inizio_attivita)->toBe(2020);
    expect($userA->fresh()->name)->not->toBe('Hacker');
});
