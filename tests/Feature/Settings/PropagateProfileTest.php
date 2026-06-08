<?php

use App\Models\AnnualExpense;
use App\Models\User;
use App\Models\Year;

function makeYear(User $user, int $year): Year
{
    return $user->years()->create([
        'year' => $year,
        'profitability_coefficient' => 78,
        'pre_opened' => false,
    ]);
}

it('propaga il coefficiente agli anni selezionati', function () {
    $user = onboardedUserWithTemplates();
    $a = makeYear($user, 2024);
    $b = makeYear($user, 2025);

    // Il nuovo coefficiente è già sul profilo (lo salva l'update prima del dialog).
    $user->professionalProfile->update(['profitability_coefficient' => 80]);

    $this->post(route('professional.propagate'), [
        'year_ids' => [$a->id, $b->id],
        'coefficient' => true,
        'start_year' => false,
    ])->assertRedirect(route('profile.edit'));

    expect((float) $a->fresh()->profitability_coefficient)->toBe(80.0)
        ->and((float) $b->fresh()->profitability_coefficient)->toBe(80.0);
});

it('propaga l anno inizio aggiornando l aliquota IS (5 in start-up, 15 dopo)', function () {
    $user = onboardedUserWithTemplates(); // business_start_year = 2020 → start-up 2020..2024
    $startup = makeYear($user, 2024);
    $regular = makeYear($user, 2026);

    $isStartup = AnnualExpense::factory()->impostaSostitutiva()->create([
        'user_id' => $user->id, 'year_id' => $startup->id, 'rate' => 99,
    ]);
    $isRegular = AnnualExpense::factory()->impostaSostitutiva()->create([
        'user_id' => $user->id, 'year_id' => $regular->id, 'rate' => 99,
    ]);

    $this->post(route('professional.propagate'), [
        'year_ids' => [$startup->id, $regular->id],
        'coefficient' => false,
        'start_year' => true,
    ])->assertRedirect(route('profile.edit'));

    expect((float) $isStartup->fresh()->rate)->toBe(5.0)
        ->and((float) $isRegular->fresh()->rate)->toBe(15.0);
});

it('non propaga ad anni di altri utenti', function () {
    $owner = User::factory()->create();
    $ownerYear = $owner->years()->create([
        'year' => 2024, 'profitability_coefficient' => 78, 'pre_opened' => false,
    ]);

    $attacker = onboardedUserWithTemplates();
    $attacker->professionalProfile->update(['profitability_coefficient' => 80]);

    $this->post(route('professional.propagate'), [
        'year_ids' => [$ownerYear->id],
        'coefficient' => true,
        'start_year' => false,
    ])->assertRedirect();

    // L'anno del proprietario resta invariato (global scope esclude l'id estraneo).
    $fresh = Year::withoutGlobalScopes()->find($ownerYear->id);
    expect((float) $fresh->profitability_coefficient)->toBe(78.0);
});

it('richiede almeno un campo da propagare', function () {
    $user = onboardedUserWithTemplates();
    $year = makeYear($user, 2024);

    $this->post(route('professional.propagate'), [
        'year_ids' => [$year->id],
        'coefficient' => false,
        'start_year' => false,
    ])->assertSessionHasErrors('coefficient');
});
