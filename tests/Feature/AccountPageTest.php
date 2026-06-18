<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('mostra la pagina account all utente onboarded', function () {
    onboardedUserWithTemplates();

    $this->get(route('account'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('account/Index'));
});

it('richiede onboarding e autenticazione', function () {
    $this->get(route('account'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('account'))
        ->assertRedirect(route('onboarding.show'));
});

it('serve il service worker a scope root con header dedicato', function () {
    $path = public_path('build/sw.js');
    $created = false;

    if (! file_exists($path)) {
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, '// stub service worker');
        $created = true;
    }

    try {
        $response = $this->get('/build/serviceworker.js');

        $response->assertOk();
        expect($response->headers->get('Service-Worker-Allowed'))->toBe('/');
    } finally {
        if ($created) {
            unlink($path);
        }
    }
});
