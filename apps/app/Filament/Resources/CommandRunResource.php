<?php

namespace App\Filament\Resources;

use BackedEnum;
use BinaryBuilds\CommandRunner\Resources\CommandRuns\CommandRunResource as BaseCommandRunResource;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CommandRunResource extends BaseCommandRunResource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Command Runner');
    }

    public static function getNavigationIcon(): string|BackedEnum|Heroicon|null
    {
        return Heroicon::OutlinedCommandLine;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'System';
    }

    public static function getNavigationSort(): ?int
    {
        return null;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'command-runner';
    }
}
