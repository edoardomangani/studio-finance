<?php

use App\Models\Deadline;
use App\Models\User;
use App\Models\Year;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Il prop condiviso `nav` alimenta la sidebar: conteggio scadenze "prossime"
 * (badge: aperte entro 3 mesi, scadute incluse) e anno corrente (etichetta
 * "Anni"). Vedi HandleInertiaRequests.
 */
it('condivide conteggio scadenze prossime e anno corrente da autenticato', function () {
    $user = onboardedUserWithTemplates();
    $year = Year::factory()->forYear((int) now()->year)->create();

    // Prossime (contate): 1 scaduta + 1 entro la finestra. Escluse: 1 aperta
    // oltre 3 mesi e 1 completata.
    Deadline::factory()->create(['year_id' => $year->id, 'due_at' => now()->subMonth()]);
    Deadline::factory()->create(['year_id' => $year->id, 'due_at' => now()->addMonth()]);
    Deadline::factory()->create(['year_id' => $year->id, 'due_at' => now()->addMonths(6)]);
    Deadline::factory()->completed()->create(['year_id' => $year->id, 'due_at' => now()]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nav.upcomingDeadlines', 2)
            ->where('nav.currentYear', (int) now()->year)
        );
});

it('non condivide nav da ospite', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav', null));
});

it('currentYear è null quando nessun anno è aperto', function () {
    onboardedUserWithTemplates();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nav.upcomingDeadlines', 0)
            ->where('nav.currentYear', null)
        );
});

it('il conteggio è isolato per utente', function () {
    $other = User::factory()->create();
    $otherYear = Year::factory()->forYear((int) now()->year)->create(['user_id' => $other->id]);
    Deadline::factory()->count(3)->create(['user_id' => $other->id, 'year_id' => $otherYear->id]);

    onboardedUserWithTemplates();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav.upcomingDeadlines', 0));
});
