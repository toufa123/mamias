<?php

namespace App\Filament\Pages;

use Daljo25\FilamentDependencyManager\Pages\DependencyManagerPage;

class ComposerDependencies extends DependencyManagerPage
{
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
