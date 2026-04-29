<?php

use App\Filament\Pages\Auth\Login;
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

// ── Registration ──────────────────────────────────────────────────────────────

it('assigns the default user role on registration', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
        'email'      => 'jane@example.com',
        'password'   => Hash::make('password'),
    ]);
    $user->assignRole('user');

    expect($user->hasRole('user'))->toBeTrue()
        ->and($user->hasRole('super_admin'))->toBeFalse()
        ->and($user->hasRole('panel_user'))->toBeFalse();
});

it('stores the required registration profile fields', function () {
    $user = User::factory()->create([
        'title'      => 'Dr',
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
        'country'    => 'TN',
    ]);

    expect($user->title)->toBe('Dr')
        ->and($user->first_name)->toBe('Jane')
        ->and($user->last_name)->toBe('Doe')
        ->and($user->country)->toBe('TN');
});

// ── Name sync ─────────────────────────────────────────────────────────────────

it('syncs the name attribute from first_name and last_name on save', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
    ]);

    expect($user->fresh()->name)->toBe('Jane Doe');
});

it('falls back to John Doe when both name parts are blank', function () {
    $user = User::factory()->create([
        'first_name' => '',
        'last_name'  => '',
    ]);

    expect($user->fresh()->name)->toBe('John Doe');
});

it('updates the name when first_name changes', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
    ]);

    $user->update(['first_name' => 'Alice']);

    expect($user->fresh()->name)->toBe('Alice Doe');
});

// ── Filament display ──────────────────────────────────────────────────────────

it('getFilamentName returns the full name', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
    ]);

    expect($user->getFilamentName())->toBe('Jane Doe');
});

it('getFilamentName falls back to John Doe when names are blank', function () {
    $user = User::factory()->make([
        'first_name' => null,
        'last_name'  => null,
    ]);

    expect($user->getFilamentName())->toBe('John Doe');
});

it('getFilamentAvatarUrl returns a UI-Avatars URL containing the user name', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
    ]);

    $url = $user->getFilamentAvatarUrl();

    expect($url)
        ->toContain('ui-avatars.com')
        ->toContain(urlencode('Jane Doe'));
});

// ── Login redirect logic ──────────────────────────────────────────────────────

it('redirects a super_admin away from the login page to the panel', function () {
    $user = User::factory()->create([
        'first_name'        => 'Admin',
        'last_name'         => 'User',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    Livewire::test(Login::class)
        ->assertRedirect(filament()->getPanel('mamias')->getUrl());
});

it('redirects a panel_user away from the login page to the panel', function () {
    $user = User::factory()->create([
        'first_name'        => 'Panel',
        'last_name'         => 'User',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('panel_user');

    $this->actingAs($user);

    Livewire::test(Login::class)
        ->assertRedirect(filament()->getPanel('mamias')->getUrl());
});

it('redirects a basic user away from the login page to the home page', function () {
    $user = User::factory()->create([
        'first_name'        => 'Regular',
        'last_name'         => 'User',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('user');

    $this->actingAs($user);

    Livewire::test(Login::class)
        ->assertRedirect(url('/'));
});

it('redirects a user with no role away from the login page to the home page', function () {
    $user = User::factory()->create([
        'first_name'        => 'No',
        'last_name'         => 'Role',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(Login::class)
        ->assertRedirect(url('/'));
});
