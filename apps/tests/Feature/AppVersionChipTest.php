<?php

use App\Models\User;
use Vaslv\FilamentAppVersion\Facades\AppVersion;

use function Pest\Laravel\get;

it('resolves the version from APP_VERSION through the config', function () {
    config()->set('filament-app-version.version', '9.9.9');

    expect(AppVersion::get())->toBe('9.9.9');
});

it('renders the version chip in the panel', function () {
    config()->set('filament-app-version.version', '9.9.9');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    get('/mamias')
        ->assertOk()
        ->assertSee('v9.9.9');
});
