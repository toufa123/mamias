<?php

namespace App\Filament\Pages;

use Daljo25\FilamentDependencyManager\Pages\NpmDependencyManagerPage;

class NpmDependencies extends NpmDependencyManagerPage
{
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
