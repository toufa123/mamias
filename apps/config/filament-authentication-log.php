<?php

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource;

return [
    // 'user-resource' => \App\Filament\Resources\UserResource::class,
    'user-resource' => UserResource::class,

    'resources' => [
        'AutenticationLogResource' => AuthenticationLogResource::class,
    ],

    'authenticable-resources' => [
        User::class,
    ],

    'authenticatable' => [
        'field-to-display' => null,
        'resource-page' => 'edit',
    ],

    'navigation' => [
        'authentication-log' => [
            'register' => true,
            'sort' => 1,
            'icon' => 'heroicon-o-shield-check',
            'group' => 'System',
        ],
    ],

    'sort' => [
        'column' => 'login_at',
        'direction' => 'desc',
    ],
];
