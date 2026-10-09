<?php

use App\Models\User;
use Baspa\FilamentCanary\FilamentCanaryServiceProvider;
use Filament\Panel;

// config for Baspa/FilamentCanary
// acting_as / tenant below were proposed by `php artisan canary:install` — review them.
return [

    'panels' => [
        'only' => [],
        'except' => [],
    ],

    'exclude' => [],

    'test_guests' => true,

    'strict_authorization' => false,

    // Canary is require-dev (a test tool). Production images do not have it, and
    // a closure here makes `config:cache` fail ("non-serializable"), which the
    // production container runs at every start — so only define it where the
    // package is installed.
    'acting_as' => class_exists(FilamentCanaryServiceProvider::class) ? [
        // mamias — Role-based access detected (assignRole('super_admin')). Ensure that role exists (seed it or your TestCase seeds roles). (confidence: high)
        'mamias' => fn (Panel $panel) => User::factory()->create()->assignRole('super_admin'),
    ] : [],

    'tenant' => null,

];
