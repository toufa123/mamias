<?php

namespace App\Filament\Resources;

use BackedEnum;
use BinaryBuilds\CommandRunner\Resources\CommandRuns\CommandRunResource as BaseCommandRunResource;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Filament resource for running Artisan commands from the panel.
 *
 * @extends BaseCommandRunResource
 */
class CommandRunResource extends BaseCommandRunResource
{
    /**
     * Determine whether the current user can access this resource.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * Get the navigation label for the resource.
     */
    public static function getNavigationLabel(): string
    {
        return __('Command Runner');
    }

    /**
     * Get the navigation icon for the resource.
     */
    public static function getNavigationIcon(): string|BackedEnum|Heroicon|null
    {
        return Heroicon::OutlinedCommandLine;
    }

    /**
     * Get the navigation group for the resource.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'System';
    }

    /**
     * Get the navigation sort order for the resource.
     */
    public static function getNavigationSort(): ?int
    {
        return null;
    }

    /**
     * Get the slug for the resource.
     */
    public static function getSlug(?Panel $panel = null): string
    {
        return 'command-runner';
    }
}
