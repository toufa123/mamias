<?php

use App\Models\User;
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

    'acting_as' => [
        // mamias — Role-based access detected (assignRole('super_admin')). Ensure that role exists (seed it or your TestCase seeds roles). (confidence: high)
        'mamias' => fn (Panel $panel) => User::factory()->create()->assignRole('super_admin'),
    ],

    'tenant' => null,

];
