<?php

declare(strict_types=1);

use Vaslv\FilamentAppVersion\Resolvers\ConfigVersionResolver;
use Vaslv\FilamentAppVersion\Resolvers\FileVersionResolver;
use Vaslv\FilamentAppVersion\Resolvers\GitVersionResolver;

return [

    /*
    |--------------------------------------------------------------------------
    | Application version
    |--------------------------------------------------------------------------
    |
    | env() is called HERE, inside the config file, and not at runtime: with
    | `php artisan config:cache` Laravel never loads .env, so a runtime env()
    | would return null silently — and only in production, where the config is
    | always cached.
    |
    */

    'version' => env('APP_VERSION'),

    /*
    |--------------------------------------------------------------------------
    | Fallback value
    |--------------------------------------------------------------------------
    |
    | Shown when no source produced a version. null renders no chip at all.
    |
    */

    'fallback' => 'dev',

    /*
    |--------------------------------------------------------------------------
    | Resolver chain
    |--------------------------------------------------------------------------
    |
    | First non-empty value wins. Entries must stay declarative ([class, ...args])
    | so the chain survives config:cache — closures belong in the panel provider.
    |
    |   1. APP_VERSION, frozen into the config above (CI / .env)
    |   2. a VERSION file written by the build into the image
    |   3. the short commit SHA — host-side dev only: .git lives at the repo
    |      root, one level above the Laravel app, and is never in the image
    |
    | MamiasPanelProvider declares the same chain for its chip; keep the two in
    | step so the AppVersion facade and the panel never disagree.
    |
    */

    'resolvers' => [
        [ConfigVersionResolver::class, 'filament-app-version.version'],
        [FileVersionResolver::class, base_path('VERSION')],
        [GitVersionResolver::class, base_path('../.git')],
    ],

];
