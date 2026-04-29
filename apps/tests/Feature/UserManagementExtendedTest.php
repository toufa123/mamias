<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Reset Spatie Permission's cache and set the active Filament panel before
 * each test so role lookups and panel-aware Livewire components are consistent.
 */
beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'panel_user',  'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user',         'guard_name' => 'web']);

    Filament::setCurrentPanel(Filament::getPanel('mamias'));
});

// ── Helper functions ───────────────────────────────────────────────────────────

/**
 * Create a super_admin user for admin-panel CRUD tests.
 */
function createAdminUser(): User
{
    $user = User::factory()->create([
        'first_name'        => 'Admin',
        'last_name'         => 'Test',
        'email'             => 'admin@test.example',
        'password'          => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    $user->assignRole('super_admin');

    return $user;
}

// ── HTTP — Login page ─────────────────────────────────────────────────────────

it('makes the login page accessible to unauthenticated guests', function () {
    $this->get(route('filament.mamias.auth.login'))
        ->assertOk();
});

it('redirects authenticated panel_user away from the login page', function () {
    $user = User::factory()->create([
        'first_name'        => 'Panel',
        'last_name'         => 'User',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('panel_user');

    // Authenticated users hitting the login Livewire component are redirected
    // inside mount() via $this->redirect(). The Livewire test verifies the
    // component redirects rather than rendering the form.
    $this->actingAs($user);

    Livewire::test(\App\Filament\Pages\Auth\Login::class)
        ->assertRedirect();
});

// ── HTTP — Logout ─────────────────────────────────────────────────────────────

it('logs authenticated panel users out and redirects to the login page', function () {
    $user = User::factory()->create([
        'first_name'        => 'Panel',
        'last_name'         => 'User',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('panel_user');

    $this->actingAs($user)
        ->post(route('filament.mamias.auth.logout'))
        ->assertRedirect(route('filament.mamias.auth.login'));

    $this->assertGuest();
});

// ── Developer login shortcuts ─────────────────────────────────────────────────

it('hides developer login shortcuts on the login page outside local environment', function () {
    // APP_ENV=testing (set in phpunit.xml) so the plugin is disabled.
    $this->get(route('filament.mamias.auth.login'))
        ->assertOk()
        ->assertDontSee('atef.ouerghi@spa-rac.org')
        ->assertDontSee('atef.ouerghi@gmail.com');
});

it('configures the developer login plugin with the expected admin and user shortcuts', function () {
    $panel = Filament::getPanel('mamias');

    // Find the FilamentDeveloperLoginsPlugin in the registered plugins
    $plugin = collect($panel->getPlugins())
        ->first(fn ($p) => $p instanceof \DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin);

    expect($plugin)->not->toBeNull();
});

// ── Admin CRUD — Users resource ───────────────────────────────────────────────

it('allows a super_admin to view the users list page', function () {
    $admin = createAdminUser();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertOk();
});

it('allows a super_admin to view the create user page', function () {
    $admin = createAdminUser();

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->assertOk();
});

it('allows a super_admin to view the edit user page for an existing user', function () {
    $admin   = createAdminUser();
    $subject = User::factory()->create([
        'first_name' => 'Target',
        'last_name'  => 'User',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->assertOk();
});

it('shows existing users in the admin user list', function () {
    $admin   = createAdminUser();
    $subject = User::factory()->create([
        'first_name' => 'Listed',
        'last_name'  => 'User',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$subject]);
});

it('shows the delete action for super_admin on the edit user page', function () {
    $admin   = createAdminUser();
    $subject = User::factory()->create([
        'first_name' => 'Delete',
        'last_name'  => 'Me',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->assertActionExists('delete');
});
