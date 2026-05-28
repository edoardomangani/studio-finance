<?php

use App\Enums\AnnoRiferimentoSpesa;
use App\Enums\TipoScadenza;
use App\Models\ProfessionalProfile;
use App\Models\ScadenzaTipo;
use App\Models\User;
use App\Models\VoceSpesa;

function onboardedScad(): User
{
    $user = User::factory()->create();
    ProfessionalProfile::factory()->for($user)->create();

    return $user;
}

it('mostra la pagina scadenze tipo con tabella + voci attive', function (): void {
    $user = onboardedScad();
    VoceSpesa::factory()->for($user)->create(['attiva' => true]);
    ScadenzaTipo::factory()->for($user)->count(2)->create();

    $this->actingAs($user)
        ->get('/settings/scadenze-tipo')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/ScadenzeTipo/Index')
            ->has('scadenzeTipo', 2)
            ->has('tipiScadenza')
            ->has('anniData')
            ->has('anniRiferimento')
            ->has('vociAttive', 1),
        );
});

it('reindirizza a /onboarding se non onboarded (index)', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/scadenze-tipo')
        ->assertRedirect('/onboarding');
});

it('crea una scadenza tipo adempimento', function (): void {
    $user = onboardedScad();

    $this->actingAs($user)->post('/settings/scadenze-tipo', [
        'nome' => 'Comunicazione X',
        'giorno' => 15,
        'mese' => 10,
        'tipo' => TipoScadenza::Adempimento->value,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Corrente->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente->value,
        'attiva' => true,
    ])->assertRedirect('/settings/scadenze-tipo');

    expect($user->scadenzeTipo()->where('nome', 'Comunicazione X')->exists())->toBeTrue();
});

it('persiste anno_data_scadenza=successivo per saldi e bolli Q4', function (): void {
    $user = onboardedScad();
    $voce = App\Models\VoceSpesa::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/scadenze-tipo', [
        'nome' => 'Saldo IS',
        'giorno' => 30,
        'mese' => 6,
        'tipo' => TipoScadenza::Pagamento->value,
        'voce_spesa_id' => $voce->id,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Successivo->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente->value,
        'attiva' => true,
    ])->assertRedirect('/settings/scadenze-tipo');

    $saldo = $user->scadenzeTipo()->where('nome', 'Saldo IS')->first();
    expect($saldo->anno_data_scadenza)
        ->toBe(App\Enums\AnnoDataScadenza::Successivo);
    expect($saldo->anno_riferimento_spesa)
        ->toBe(AnnoRiferimentoSpesa::Corrente);
});

it('crea una scadenza tipo pagamento con voce collegata', function (): void {
    $user = onboardedScad();
    $voce = VoceSpesa::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/scadenze-tipo', [
        'nome' => 'Saldo IS',
        'giorno' => 30,
        'mese' => 6,
        'tipo' => TipoScadenza::Pagamento->value,
        'voce_spesa_id' => $voce->id,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Corrente->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente->value,
        'attiva' => true,
    ])->assertRedirect('/settings/scadenze-tipo');

    expect($user->scadenzeTipo()->where('nome', 'Saldo IS')->first()->voce_spesa_id)
        ->toBe($voce->id);
});

it('richiede voce_spesa_id per pagamento', function (): void {
    $user = onboardedScad();

    $this->actingAs($user)->post('/settings/scadenze-tipo', [
        'nome' => 'X',
        'giorno' => 30,
        'mese' => 6,
        'tipo' => TipoScadenza::Pagamento->value,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Corrente->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente->value,
        'attiva' => true,
    ])->assertSessionHasErrors(['voce_spesa_id']);
});

it('vieta voce_spesa_id su adempimento', function (): void {
    $user = onboardedScad();
    $voce = VoceSpesa::factory()->for($user)->create();

    $this->actingAs($user)->post('/settings/scadenze-tipo', [
        'nome' => 'X',
        'giorno' => 15,
        'mese' => 10,
        'tipo' => TipoScadenza::Adempimento->value,
        'voce_spesa_id' => $voce->id,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Corrente->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente->value,
        'attiva' => true,
    ])->assertSessionHasErrors(['voce_spesa_id']);
});

it('valida giorno entro 1-31', function (): void {
    $user = onboardedScad();

    $this->actingAs($user)->post('/settings/scadenze-tipo', [
        'nome' => 'X',
        'giorno' => 32,
        'mese' => 6,
        'tipo' => TipoScadenza::Adempimento->value,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Corrente->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Corrente->value,
        'attiva' => true,
    ])->assertSessionHasErrors(['giorno']);
});

it('aggiorna una scadenza esistente', function (): void {
    $user = onboardedScad();
    $voce = VoceSpesa::factory()->for($user)->create();
    $scadenza = ScadenzaTipo::factory()->for($user)->pagamento()->create([
        'voce_spesa_id' => $voce->id,
    ]);

    $this->actingAs($user)->patch("/settings/scadenze-tipo/{$scadenza->id}", [
        'nome' => 'Aggiornata',
        'giorno' => 15,
        'mese' => 11,
        'tipo' => TipoScadenza::Pagamento->value,
        'voce_spesa_id' => $voce->id,
        'anno_data_scadenza' => App\Enums\AnnoDataScadenza::Corrente->value,
        'anno_riferimento_spesa' => AnnoRiferimentoSpesa::Successivo->value,
        'attiva' => true,
    ])->assertRedirect('/settings/scadenze-tipo');

    expect($scadenza->fresh()->nome)->toBe('Aggiornata');
});

it('archivia (soft delete) una scadenza', function (): void {
    $user = onboardedScad();
    $scadenza = ScadenzaTipo::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/settings/scadenze-tipo/{$scadenza->id}")
        ->assertRedirect('/settings/scadenze-tipo');

    expect($scadenza->fresh()->trashed())->toBeTrue();
});

it('blocca cross-user via tenancy scope', function (): void {
    $altro = onboardedScad();
    $scadenzaAltro = ScadenzaTipo::factory()->for($altro)->create();

    $user = onboardedScad();

    $this->actingAs($user)
        ->delete("/settings/scadenze-tipo/{$scadenzaAltro->id}")
        ->assertNotFound();
});
