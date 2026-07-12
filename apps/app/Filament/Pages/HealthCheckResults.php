<?php

namespace App\Filament\Pages;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults as BaseHealthCheckResults;

/**
 * Health check results page placed under the "System" navigation group
 * with a custom heading.
 */
class HealthCheckResults extends BaseHealthCheckResults
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-cpu';

    protected static ?int $navigationSort = 2;

    /**
     * {@inheritDoc}
     */
    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    /**
     * {@inheritDoc}
     */
    public function getHeading(): string|Htmlable
    {
        return 'Health Check Results';
    }
}
