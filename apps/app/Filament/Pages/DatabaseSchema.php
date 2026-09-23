<?php

namespace App\Filament\Pages;

use AlbertoArena\FilamentTruss\Pages\SchemaPage;

/**
 * Live database ERD (Filament Truss), placed under "System".
 * Only accessible to super_admin users.
 */
class DatabaseSchema extends SchemaPage
{
    protected static string|null|\UnitEnum $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    /**
     * {@inheritDoc}
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
