<?php

namespace App\Filament\Pages;

use UniFileManager\FilamentFileManager\Filament\Pages\FileManager as BaseFileManager;

/**
 * The package's own page never checks the `manageFileManager` gate for
 * *viewing* the page — only individual upload/delete/rename/move actions do.
 * Only accessible to super_admin users.
 */
class FileManager extends BaseFileManager
{
    /**
     * {@inheritDoc}
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
