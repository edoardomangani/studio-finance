<?php

use App\Actions\Studiofinance\OpenYear;
use App\Enums\DeadlineKind;
use App\Enums\DeadlineStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\YearAlreadyOpenException;
use App\Models\AnnualExpense;
use App\Models\Deadline;
use App\Models\Payment;
use App\Models\User;
use App\Models\Year;
use App\Services\YearOpeningPlanner;
use Database\Seeders\StudiofinanceTemplatesSeeder;

/**
 * Utente onboarded con i template standard (8 voci, 20 scadenze tipo) e
 * autenticato — così il global scope e la forzatura user_id agiscono come in
 * un vero request.
 */
function onboardedUserWithTemplates(float $coefficient = 78.00): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    $user->professionalProfile()->create([
        'profitability_coefficient' => $coefficient,
        'business_start_year' => 2020,
    ]);

    (new StudiofinanceTemplatesSeeder)->seedForUser($user);

    return $user;
}

function openYear(User $user, int $year, ?callable $editPlan = null): Year
{
    $plan = app(YearOpeningPlanner::class)->plan($user, $year);

    if ($editPlan !== null) {
        $plan = $editPlan($plan);
    }

    return app(OpenYear::class)($user, $plan);
}

it('apre un anno creando spese, scadenze e pagamenti pianificati', function () {
    $user = onboardedUserWithTemplates();

    $year = openYear($user, 2026);

    expect($year->year)->toBe(2026)
        ->and($year->pre_opened)->toBeFalse()
        ->and((float) $year->profitability_coefficient)->toBe(78.00);

    // 8 voci attive → 8 spese annuali su 2026.
    expect(AnnualExpense::where('year_id', $year->id)->count())->toBe(8);

    // 20 scadenze tipo attive → 20 scadenze (18 pagamento + 2 adempimento).
    expect(Deadline::where('year_id', $year->id)->count())->toBe(20);

    // Un pagamento pianificato per ogni scadenza di pagamento (18).
    expect(Payment::where('status', PaymentStatus::Planned)->count())->toBe(18);

    // Ogni scadenza di pagamento ha esattamente un pagamento collegato (1:1).
    $paymentDeadlines = Deadline::where('year_id', $year->id)
        ->where('kind', DeadlineKind::Payment)
        ->get();
    expect($paymentDeadlines)->toHaveCount(18);
    foreach ($paymentDeadlines as $deadline) {
        expect($deadline->payment()->exists())->toBeTrue()
            ->and($deadline->annual_expense_id)->not->toBeNull()
            ->and($deadline->status)->toBe(DeadlineStatus::Open);
    }

    // Gli adempimenti non hanno pagamento né spesa collegata.
    $fulfillments = Deadline::where('year_id', $year->id)
        ->where('kind', DeadlineKind::Fulfillment)
        ->get();
    expect($fulfillments)->toHaveCount(2);
    foreach ($fulfillments as $deadline) {
        expect($deadline->payment()->exists())->toBeFalse()
            ->and($deadline->annual_expense_id)->toBeNull();
    }
});

it('crea un anno N+1 pre-aperto con la sola spesa Commercialista (cross-year)', function () {
    $user = onboardedUserWithTemplates();

    openYear($user, 2026);

    $next = Year::where('year', 2027)->first();
    expect($next)->not->toBeNull()
        ->and($next->pre_opened)->toBeTrue();

    // Solo la spesa cross-year (Commercialista) sul pre-aperto.
    $nextExpenses = AnnualExpense::where('year_id', $next->id)->get();
    expect($nextExpenses)->toHaveCount(1)
        ->and($nextExpenses->first()->name)->toBe('Commercialista');

    // La scadenza Commercialista vive su 2026 (data 31/12/2026) ma il suo
    // pagamento punta alla spesa di 2027.
    $commercialista = Deadline::where('name', 'Commercialista (parcella anno successivo)')->first();
    expect($commercialista->year_id)->toBe(Year::where('year', 2026)->value('id'))
        ->and($commercialista->due_at->format('Y-m-d'))->toBe('2026-12-31')
        ->and($commercialista->annual_expense_id)->toBe($nextExpenses->first()->id);

    expect($commercialista->payment->annual_expense_id)->toBe($nextExpenses->first()->id);

    // Nessun adempimento e nessun'altra spesa sul pre-aperto.
    expect(Deadline::where('year_id', $next->id)->count())->toBe(0);
});

it('il planner segnala la pre-apertura necessaria solo se N+1 non esiste', function () {
    $user = onboardedUserWithTemplates();

    $plan = app(YearOpeningPlanner::class)->plan($user, 2026);
    expect($plan['cross_year_deadlines'])->toContain('Commercialista (parcella anno successivo)')
        ->and($plan['next_year_exists'])->toBeFalse()
        ->and($plan['next_year_needs_preopen'])->toBeTrue();

    openYear($user, 2026); // crea 2027 pre-aperto

    $plan2027neighbor = app(YearOpeningPlanner::class)->plan($user, 2026);
    // Ora 2027 esiste: il planner per 2026 non segnalerebbe più la pre-apertura.
    expect($plan2027neighbor['next_year_exists'])->toBeTrue()
        ->and($plan2027neighbor['next_year_needs_preopen'])->toBeFalse();
});

it('completa un anno pre-aperto riusando la spesa Commercialista invece di bloccare', function () {
    $user = onboardedUserWithTemplates();

    openYear($user, 2026); // crea 2027 pre-aperto con Commercialista
    $preOpened2027 = Year::where('year', 2027)->first();
    $reusedExpenseId = AnnualExpense::where('year_id', $preOpened2027->id)->value('id');

    // Apertura formale di 2027: NON deve bloccare.
    $year2027 = openYear($user, 2027);

    expect($year2027->id)->toBe($preOpened2027->id)
        ->and($year2027->fresh()->pre_opened)->toBeFalse();

    // 8 spese, nessun duplicato: la Commercialista pre-esistente è riusata.
    $expenses2027 = AnnualExpense::where('year_id', $year2027->id)->get();
    expect($expenses2027)->toHaveCount(8)
        ->and($expenses2027->where('name', 'Commercialista'))->toHaveCount(1)
        ->and($expenses2027->firstWhere('name', 'Commercialista')->id)->toBe($reusedExpenseId);

    // Set completo di scadenze ora presente su 2027.
    expect(Deadline::where('year_id', $year2027->id)->count())->toBe(20);

    // E a cascata 2028 è stato pre-aperto dal cross-year di 2027.
    expect(Year::where('year', 2028)->first()?->pre_opened)->toBeTrue();
});

it('blocca la riapertura di un anno già aperto formalmente', function () {
    $user = onboardedUserWithTemplates();

    openYear($user, 2026);

    expect(fn () => openYear($user, 2026))
        ->toThrow(YearAlreadyOpenException::class, 'Anno 2026 già aperto.');
});

it('salta le scadenze di pagamento di una voce esclusa dal wizard', function () {
    $user = onboardedUserWithTemplates();

    $year = openYear($user, 2026, function (array $plan): array {
        // Escludo la voce Assicurazione (1 sola scadenza di pagamento collegata).
        $plan['expenses'] = array_values(array_filter(
            $plan['expenses'],
            fn (array $e) => $e['name'] !== 'Assicurazione professionale',
        ));

        return $plan;
    });

    // 7 spese invece di 8.
    expect(AnnualExpense::where('year_id', $year->id)->count())->toBe(7);

    // La scadenza Assicurazione non è stata generata: 19 scadenze, 17 pagamenti.
    expect(Deadline::where('year_id', $year->id)->count())->toBe(19)
        ->and(Deadline::where('name', 'Assicurazione professionale')->exists())->toBeFalse()
        ->and(Payment::where('status', PaymentStatus::Planned)->count())->toBe(17);
});

it('calcola le date scadenza sull anno calendariale corretto e tronca i giorni invalidi', function () {
    $user = onboardedUserWithTemplates();

    // Aggiungo una scadenza tipo con giorno 31 in un mese corto (febbraio) per
    // verificare il troncamento all'ultimo giorno del mese.
    $user->recurringDeadlines()->create([
        'name' => 'Test 31 febbraio',
        'day' => 31,
        'month' => 2,
        'kind' => DeadlineKind::Fulfillment,
        'due_year_offset' => 'current',
        'expense_year_offset' => 'current',
        'active' => true,
    ]);

    $plan = app(YearOpeningPlanner::class)->plan($user, 2026);

    $byName = collect($plan['deadlines'])->keyBy('name');

    // Acconto IS (due current) cade nel 2026; saldo IS (due next) nel 2027.
    expect($byName['1° acconto imposta sostitutiva']['due_at'])->toBe('2026-06-30')
        ->and($byName['Saldo imposta sostitutiva']['due_at'])->toBe('2027-06-30');

    // 31 febbraio → troncato al 28 (2026 non bisestile).
    expect($byName['Test 31 febbraio']['due_at'])->toBe('2026-02-28');
});

it('isola gli anni per utente (tenancy)', function () {
    $userA = onboardedUserWithTemplates();
    openYear($userA, 2026);

    $userB = onboardedUserWithTemplates();
    openYear($userB, 2026);

    // Ciascun utente vede solo il proprio anno 2026 (+ il 2027 pre-aperto).
    expect(Year::where('year', 2026)->count())->toBe(1);

    test()->actingAs($userA);
    expect(Year::where('year', 2026)->count())->toBe(1)
        ->and(AnnualExpense::count())->toBe(9); // 8 su 2026 + 1 su 2027 pre-aperto
});
