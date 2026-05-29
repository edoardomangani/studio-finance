<?php

use App\Models\User;
use Database\Seeders\StudiofinanceTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Utente onboarded con i template standard (8 voci di spesa + 20 scadenze
 * tipo) e autenticato, così il global scope di tenancy e la forzatura
 * `user_id` agiscono come in un vero request. Condiviso da OpenYearTest e
 * YearControllerTest.
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
