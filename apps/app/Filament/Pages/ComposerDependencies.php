<?php

namespace App\Filament\Pages;

use Daljo25\FilamentDependencyManager\Pages\DependencyManagerPage;

class ComposerDependencies extends DependencyManagerPage
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
