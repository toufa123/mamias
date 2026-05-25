<?php

namespace App\Filament\Pages;

use Daljo25\FilamentDependencyManager\Pages\NpmDependencyManagerPage;

class NpmDependencies extends NpmDependencyManagerPage
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
