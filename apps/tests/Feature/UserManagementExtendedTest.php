<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Services\WormsService;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    auth()->logout();
    session()->flush();

    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('panel_user', 'web');
    Role::findOrCreate('user', 'web');
});

function createAdminWithUserResourcePermissions(): User
{
    $role = Role::findOrCreate('super_admin', 'web');

    foreach (['ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'DeleteAny:User'] as $permissionName) {
        $role->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function fakeWormsService(): void
{
    app()->instance(WormsService::class, new class extends WormsService
    {
        public function getPhyla(): array
        {
            return [
                'Animalia (1)' => [1 => 'Chordata'],
            ];
        }
    });
}

it('serves the mamias login page over http to guests', function () {
    $this->get(route('filament.mamias.auth.login'))
        ->assertOk();
});

it('redirects authenticated panel users away from the login page over http', function () {
    $user = User::factory()->create();
    $user->assignRole('panel_user');

    $this->actingAs($user)
        ->get(route('filament.mamias.auth.login'))
        ->assertRedirect(filament()->getPanel('mamias')->getUrl());
});

it('logs authenticated panel users out over http', function () {
    $user = User::factory()->create();
    $user->assignRole('panel_user');

    $this->actingAs($user)
        ->withSession(['_token' => 'test-token'])
        ->post(route('filament.mamias.auth.logout'), ['_token' => 'test-token'])
        ->assertRedirect(route('filament.mamias.auth.login'));

    $this->assertGuest();
});

it('shows developer login shortcuts on the login page in local environment only', function () {
    $originalEnv = env('APP_ENV', 'testing');

    try {
        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = 'local';
        $_SERVER['APP_ENV'] = 'local';

        $this->refreshApplication();
        Filament::setCurrentPanel(Filament::getPanel('mamias'));

        $this->get(route('filament.mamias.auth.login'))
            ->assertOk()
            ->assertSee('Login as')
            ->assertSee('Admin')
            ->assertSee('User');
    } finally {
        putenv("APP_ENV={$originalEnv}");
        $_ENV['APP_ENV'] = $originalEnv;
        $_SERVER['APP_ENV'] = $originalEnv;

        $this->refreshApplication();
        Filament::setCurrentPanel(Filament::getPanel('mamias'));
    }
});

it('hides developer login shortcuts on the login page outside local environment', function () {
    putenv('APP_ENV=testing');
    $_ENV['APP_ENV'] = 'testing';
    $_SERVER['APP_ENV'] = 'testing';

    $this->refreshApplication();
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    $this->get(route('filament.mamias.auth.login'))
        ->assertOk()
        ->assertDontSee('Admin')
        ->assertDontSee('atef.ouerghi@spa-rac.org');
});

it('allows an admin to list users in the Filament resource', function () {
    $admin = createAdminWithUserResourcePermissions();
    $listedUser = User::factory()->create([
        'email' => 'listed@example.com',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertSee($listedUser->email);
});

it('allows an admin to create users in the Filament resource', function () {
    fakeWormsService();

    $admin = createAdminWithUserResourcePermissions();
    $userRole = Role::findByName('user', 'web');

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->set('data.title', 'Prof')
        ->set('data.first_name', 'Created')
        ->set('data.last_name', 'Scientist')
        ->set('data.email', 'created@example.com')
        ->set('data.password', 'password')
        ->set('data.country', 'TN')
        ->set('data.roles', [$userRole->getKey()])
        ->call('create')
        ->assertHasNoErrors();

    $createdUser = User::query()->where('email', 'created@example.com')->first();

    expect($createdUser)->not->toBeNull()
        ->and($createdUser?->name)->toBe('Created Scientist')
        ->and($createdUser?->hasRole('user'))->toBeTrue();
});

it('allows an admin to edit and delete users in the Filament resource', function () {
    fakeWormsService();

    $admin = createAdminWithUserResourcePermissions();
    $managedUser = User::factory()->create([
        'first_name' => 'Managed',
        'last_name' => 'User',
        'email' => 'managed@example.com',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $managedUser->getKey()])
        ->set('data.taxonomic_area', [1])
        ->set('data.first_name', 'Updated')
        ->set('data.last_name', 'Member')
        ->call('save')
        ->assertHasNoErrors();

    expect($managedUser->fresh()?->name)->toBe('Updated Member');

    Livewire::test(EditUser::class, ['record' => $managedUser->getKey()])
        ->call('mountAction', 'delete')
        ->call('callMountedAction');

    expect(User::query()->whereKey($managedUser->getKey())->exists())->toBeFalse();
});

it('restores developer-login accounts idempotently via database seeding', function () {
    User::query()
        ->whereIn('email', ['atef.ouerghi@spa-rac.org', 'atef.ouerghi@gmail.com'])
        ->delete();

    Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
    Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);

    $admin = User::query()->where('email', 'atef.ouerghi@spa-rac.org')->first();
    $panelUser = User::query()->where('email', 'atef.ouerghi@gmail.com')->first();

    expect($admin)->not->toBeNull()
        ->and($panelUser)->not->toBeNull()
        ->and(User::query()->where('email', 'atef.ouerghi@spa-rac.org')->count())->toBe(1)
        ->and(User::query()->where('email', 'atef.ouerghi@gmail.com')->count())->toBe(1)
        ->and($admin?->hasRole('super_admin'))->toBeTrue()
        ->and($panelUser?->hasRole('panel_user'))->toBeTrue();
});

