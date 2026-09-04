<?php

use Database\Seeders\DeveloperLoginUsersSeeder;
use Database\Seeders\LayupAboutPageSeeder;
use Database\Seeders\LayupHomePageSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
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

/*
 * RefreshDatabase wraps every test in a transaction that is rolled back afterwards
 * (migrating only once per run). Without it nothing was undone between tests, so
 * anything writing a uniquely-constrained column — Literature codes, Taxon scientific
 * names — collided with rows left behind by earlier tests and the suite failed in
 * cascades that had nothing to do with the code under test.
 *
 * Applied to Unit as well: those files opt into TestCase individually, and several of
 * them touch the database despite the directory name.
 */
/**
 * Baseline every test starts from, restoring what RefreshDatabase truncated.
 *
 * Must be attached to the pest() chain below — a bare top-level beforeEach() in
 * this file is silently never executed, which is how the suite ended up running
 * with no roles and no Layup pages at all.
 */
$seedBaseline = function (): void {
    // Not every file under tests/Unit binds Tests\TestCase — the stock Pest
    // ExampleTest extends plain PHPUnit and has no seed()/database at all.
    if (! method_exists($this, 'seed')) {
        return;
    }

    // RolesSeeder first: DeveloperLoginUsersSeeder calls syncRoles(), which throws
    // "There is no role named `super_admin`" if the roles were rolled back with
    // everything else. Spatie caches its lookups, so the cache has to be dropped
    // afterwards or the freshly inserted rows stay invisible to this test.
    $this->seed(RolesSeeder::class);
    $this->seed(DeveloperLoginUsersSeeder::class);

    // "/" and "/about" are served by Layup out of the layup_pages table, which
    // RefreshDatabase truncates — without these the site root 404s in tests.
    $this->seed(LayupHomePageSeeder::class);
    $this->seed(LayupAboutPageSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
};

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach($seedBaseline)
    ->in('Feature');

// Unit files bind Tests\TestCase themselves and Pest rejects binding it twice,
// so attach only the trait here. Several of them touch the database despite the
// directory name, and the ones that don't are unaffected by an unused transaction.
pest()->use(RefreshDatabase::class)
    ->beforeEach($seedBaseline)
    ->in('Unit');

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

function something()
{
    // ..
}
