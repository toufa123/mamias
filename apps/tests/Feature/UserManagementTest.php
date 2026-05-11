<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login as LoginPage;
use App\Filament\Pages\Auth\Register as RegisterPage;
use App\Models\User;
use App\Support\FilamentAuthRedirect;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    auth()->logout();
    session()->flush();

    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('scientist', 'web');
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('panel_user', 'web');
    Role::findOrCreate('user', 'web');
});

it('registers a user with the required FR-USER profile fields and assigns the default role', function () {
    $page = new class extends RegisterPage
    {
        /**
         * @param  array<string, mixed>  $data
         */
        public function registerUser(array $data): User
        {
            $user = $this->handleRegistration($data);

            assert($user instanceof User);

            return $user;
        }
    };

    $user = $page->registerUser([
        'title' => 'Dr',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'country' => 'TN',
        'password' => 'password',
    ]);

    expect($user->title)->toBe('Dr')
        ->and($user->first_name)->toBe('Jane')
        ->and($user->last_name)->toBe('Doe')
        ->and($user->country)->toBe('TN')
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->hasRole('user'))->toBeTrue()
        ->and(User::query()->whereKey($user->getKey())->exists())->toBeTrue()
        ->and(FilamentAuthRedirect::for($user))->toBe(route('filament.mamias.auth.email-verification.prompt'));
});

it('keeps the full name in sync and exposes a generated avatar url', function () {
    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
    ]);

    $user->update([
        'first_name' => 'New',
        'last_name' => 'Surname',
    ]);

    $user->refresh();

    expect($user->name)->toBe('New Surname')
        ->and($user->getFilamentName())->toBe('New Surname')
        ->and($user->avatar_url)->toContain('ui-avatars.com/api/')
        ->and($user->avatar_url)->toContain(urlencode('New Surname'));
});

it('redirects a super_admin to the mamias panel after login', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Livewire::test(LoginPage::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(filament()->getPanel('mamias')->getUrl());

    $this->assertAuthenticatedAs($user);
});

it('redirects a scientist to the mamias panel after login', function () {
    $user = User::factory()->create();
    $user->assignRole('scientist');

    Livewire::test(LoginPage::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(filament()->getPanel('mamias')->getUrl());

    $this->assertAuthenticatedAs($user);
});

it('redirects an admin to the public home page after login', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::test(LoginPage::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(url('/'));

    $this->assertAuthenticatedAs($user);
});

it('redirects a panel user to the public home page after login', function () {
    $user = User::factory()->create();
    $user->assignRole('panel_user');

    Livewire::test(LoginPage::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(url('/'));

    $this->assertAuthenticatedAs($user);
});

it('resolves the public home page as the post-login target for a regular user', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    expect(FilamentAuthRedirect::for($user))->toBe(url('/'));
});

it('forbids a regular user from accessing the mamias panel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(filament()->getPanel('mamias')->getUrl())
        ->assertForbidden();
});

it('forbids an admin from accessing the mamias panel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(filament()->getPanel('mamias')->getUrl())
        ->assertForbidden();
});

it('forbids a panel user from accessing the mamias panel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('panel_user');

    $this->actingAs($user)
        ->get(filament()->getPanel('mamias')->getUrl())
        ->assertForbidden();
});

it('allows a scientist to access the mamias panel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('scientist');

    $this->actingAs($user)
        ->get(filament()->getPanel('mamias')->getUrl())
        ->assertOk();
});

it('allows a super_admin to access the mamias panel', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(filament()->getPanel('mamias')->getUrl())
        ->assertOk();
});
