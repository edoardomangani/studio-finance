<?php

use App\Enums\TipoCalcoloVoceSpesa;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Models\VoceSpesa;

function onboarded(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

it('mostra la pagina voci di spesa con tabella + opzioni enum', function (): void {
    $user = onboarded();
    VoceSpesa::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get('/settings/voci-spesa')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/VociSpesa/Index')
            ->has('vociSpesa', 3)
            ->has('tipiCalcolo'),
        );
});

it('reindirizza a /onboarding se non onboarded (index)', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/voci-spesa')
        ->assertRedirect('/onboarding');
});

it('crea una nuova voce di spesa', function (): void {
    $user = onboarded();

    $this->actingAs($user)->post('/settings/voci-spesa', [
        'nome' => 'Tasse comunali',
        'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale->value,
        'quota_default' => 150,
        'attiva' => true,
        'ordine' => 100,
    ])->assertRedirect('/settings/voci-spesa');

    expect($user->vociSpesa()->where('nome', 'Tasse comunali')->exists())->toBeTrue();
});

it('valida nome required', function (): void {
    $user = onboarded();

    $this->actingAs($user)->post('/settings/voci-spesa', [
        'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale->value,
        'attiva' => true,
    ])->assertSessionHasErrors(['nome']);
});

it('valida massimale >= minimale', function (): void {
    $user = onboarded();

    $this->actingAs($user)->post('/settings/voci-spesa', [
        'nome' => 'X',
        'tipo_calcolo' => TipoCalcoloVoceSpesa::PercRedditoIrpef->value,
        'aliquota_default' => 10,
        'minimale_default' => 500,
        'massimale_default' => 100,
        'attiva' => true,
    ])->assertSessionHasErrors(['massimale_default']);
});

it('aggiorna una voce esistente', function (): void {
    $user = onboarded();
    $voce = VoceSpesa::factory()->for($user)->create([
        'nome' => 'Vecchio nome',
    ]);

    $this->actingAs($user)->patch("/settings/voci-spesa/{$voce->id}", [
        'nome' => 'Nuovo nome',
        'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale->value,
        'quota_default' => 200,
        'attiva' => true,
    ])->assertRedirect('/settings/voci-spesa');

    expect($voce->fresh()->nome)->toBe('Nuovo nome');
});

it('archivia (soft delete) una voce', function (): void {
    $user = onboarded();
    $voce = VoceSpesa::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/settings/voci-spesa/{$voce->id}")
        ->assertRedirect('/settings/voci-spesa');

    expect($voce->fresh()->trashed())->toBeTrue();
});

it('blocca utenti non onboarded', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/settings/voci-spesa', [
        'nome' => 'X',
        'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale->value,
        'attiva' => true,
    ])->assertRedirect('/onboarding');
});

it('blocca cross-user via tenancy scope (route binding 404)', function (): void {
    $altro = onboarded();
    $voceAltro = VoceSpesa::factory()->for($altro)->create();

    $user = onboarded();

    $this->actingAs($user)
        ->patch("/settings/voci-spesa/{$voceAltro->id}", [
            'nome' => 'Hijack',
            'tipo_calcolo' => TipoCalcoloVoceSpesa::FissaAnnuale->value,
            'attiva' => true,
        ])
        ->assertNotFound();
});
