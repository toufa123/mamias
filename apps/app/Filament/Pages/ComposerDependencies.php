<?php

namespace App\Filament\Pages;

use Daljo25\FilamentDependencyManager\Pages\DependencyManagerPage;

/**
 * Page that displays and allows management of Composer package
 * dependencies. Only accessible to super_admin users.
 */
class ComposerDependencies extends DependencyManagerPage
{
    protected static ?int $navigationSort = 3;

    /**
     * {@inheritDoc}
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
