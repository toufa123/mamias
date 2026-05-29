<?php

namespace App\Filament\Pages;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults as BaseHealthCheckResults;

class HealthCheckResults extends BaseHealthCheckResults
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-cpu';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Health Check Results';
    }
}
