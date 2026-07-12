<?php

namespace App\Filament\Pages;

use Daljo25\FilamentDependencyManager\Pages\NpmDependencyManagerPage;

/**
 * Page that displays and allows management of NPM package dependencies.
 * Only accessible to super_admin users.
 */
class NpmDependencies extends NpmDependencyManagerPage
{
    protected static ?int $navigationSort = 4;

    /**
     * {@inheritDoc}
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
