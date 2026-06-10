<?php

use App\Actions\Studiofinance\OpenYear;
use App\Actions\Studiofinance\RegisterPayment;
use App\Enums\DeadlineKind;
use App\Models\Deadline;
use App\Models\User;
use App\Models\Year;
use App\Services\YearOpeningPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/** Utente onboarded con un anno aperto (spese + scadenze generate). */
function userWithOpenYear(int $year = 2026): User
{
    $user = onboardedUserWithTemplates();
    test()->actingAs($user);
    $plan = app(YearOpeningPlanner::class)->plan($user, $year);
    app(OpenYear::class)($user, $plan);

    return $user;
}

it('mostra la lista scadenze con il previsto calcolato', function () {
    userWithOpenYear();

    // Assicurazione: voce fissa €350, scadenza unica → previsto pieno.
    $this->get(route('deadlines.index', ['search' => 'Assicurazione', 'state' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('deadlines/Index')
            ->has('deadlines.data', 1)
            ->where('deadlines.data.0.name', 'Assicurazione professionale')
            ->where('deadlines.data.0.quota_type', 'full_amount')
            ->where('deadlines.data.0.expected_amount', 350)
            ->where('deadlines.data.0.status', 'open')
            ->where('deadlines.data.0.kind', 'payment')
            ->etc());
});

it('lascia il previsto nullo quando mancano i dati (acconto senza anno precedente)', function () {
    userWithOpenYear();

    // Acconti imposta del primo anno: serve l'IS netta di N-1, che non esiste.
    $this->get(route('deadlines.index', ['search' => 'acconto imposta', 'state' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 2)
            ->where('deadlines.data.0.quota_type', 'tax_advance')
            ->where('deadlines.data.0.expected_amount', null)
            ->etc());
});

it('filtra per tipo adempimento', function () {
    userWithOpenYear();

    // Adempimenti seedati: Dichiarazione redditi + Comunicazione Inarcassa.
    $this->get(route('deadlines.index', ['kind' => 'fulfillment', 'state' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 2)
            ->where('deadlines.data.0.kind', 'fulfillment')
            ->where('deadlines.data.0.expected_amount', null)
            ->etc());
});

it('filtra per voce di spesa', function () {
    $user = userWithOpenYear();
    $bolli = $user->expenseItems()->where('name', 'Bolli')->firstOrFail();

    $this->get(route('deadlines.index', ['expense_item_id' => $bolli->id, 'state' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 4) // 4 rate trimestrali bolli
            ->where('deadlines.data', fn ($rows) => collect($rows)->every(fn ($r) => $r['annual_expense_name'] === 'Bolli'))
            ->etc());
});

it('filtra per anno scadenza (due_at), distinto dall anno di riferimento', function () {
    userWithOpenYear(); // anno 2026; i saldi/bolli Q4/commercialista cadono nel 2027

    $this->get(route('deadlines.index', ['due_year' => 2027, 'state' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines.data', fn ($rows) => collect($rows)->isNotEmpty()
                && collect($rows)->every(fn ($r) => str_starts_with($r['due_at'], '2027')))
            ->etc());
});

it('espone gli anni e le opzioni di filtro', function () {
    userWithOpenYear();

    $this->get(route('deadlines.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('availableYears')
            ->has('kindOptions', 2)
            ->etc());
});

it('il toggle stato "closed" mostra completate e non dovute, non le aperte', function () {
    $user = userWithOpenYear();
    // Una scadenza di pagamento la registro (→ completata), un'altra resta aperta.
    $paid = Deadline::query()
        ->where('kind', DeadlineKind::Payment)
        ->whereNotNull('annual_expense_id')
        ->firstOrFail();
    app(RegisterPayment::class)($paid, ['amount' => 100, 'paid_at' => '2026-03-15']);

    $this->get(route('deadlines.index', ['state' => 'closed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 1)
            ->where('deadlines.data.0.status', 'completed')
            ->etc());

    $this->get(route('deadlines.index', ['state' => 'open']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines.data', fn ($rows) => collect($rows)->every(fn ($r) => $r['status'] === 'open'))
            ->etc());
});

it('ordina le aperte per data crescente (to-do) e le altre decrescente (registro)', function () {
    userWithOpenYear();

    // Aperte: la più imminente prima (ASC).
    $this->get(route('deadlines.index', ['state' => 'open']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines.data', function ($rows) {
                $dates = collect($rows)->pluck('due_at')->all();

                return $dates === collect($dates)->sort()->values()->all();
            })
            ->etc());

    // Vista "tutte": l'ultima per data prima (DESC).
    $this->get(route('deadlines.index', ['state' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines.data', function ($rows) {
                $dates = collect($rows)->pluck('due_at')->all();

                return $dates === collect($dates)->sortDesc()->values()->all();
            })
            ->etc());
});

it('il default "prossime" mostra solo le aperte entro 3 mesi, scadute incluse', function () {
    $user = onboardedUserWithTemplates();
    $this->actingAs($user);
    $year = Year::factory()->forYear((int) now()->year)->create(['user_id' => $user->id]);

    Deadline::factory()->create(['user_id' => $user->id, 'year_id' => $year->id, 'name' => 'Scaduta', 'due_at' => now()->subMonth()]);
    Deadline::factory()->create(['user_id' => $user->id, 'year_id' => $year->id, 'name' => 'Tra un mese', 'due_at' => now()->addMonth()]);
    Deadline::factory()->create(['user_id' => $user->id, 'year_id' => $year->id, 'name' => 'Tra sei mesi', 'due_at' => now()->addMonths(6)]);
    Deadline::factory()->completed()->create(['user_id' => $user->id, 'year_id' => $year->id, 'name' => 'Completata vicina', 'due_at' => now()]);

    // Nessun filtro stato → default "prossime": scaduta + entro finestra; escluse
    // l'aperta oltre 3 mesi e la completata.
    $this->get(route('deadlines.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deadlines.data', 2)
            ->where('deadlines.data', fn ($rows) => collect($rows)->pluck('name')->sort()->values()->all() === ['Scaduta', 'Tra un mese'])
            ->etc());
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('deadlines.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(); // no profilo → non onboarded
    $this->actingAs($user)
        ->get(route('deadlines.index'))
        ->assertRedirect(route('onboarding.show'));
});
