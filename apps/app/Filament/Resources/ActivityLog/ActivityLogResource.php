<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLog;

use App\Filament\Resources\ActivityLog\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLog\Tables\ActivityLogTable;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/**
 * Filament resource for viewing the Spatie activity log.
 *
 * @extends \Filament\Resources\Resource
 *
 * @model Spatie\Activitylog\Models\Activity
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-history';

    protected static ?string $modelLabel = 'Activity Log';

    protected static ?string $pluralModelLabel = 'Activity Logs';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    protected static ?string $slug = 'activity-log';

    /**
     * Configure the table for the resource.
     */
    public static function table(Table $table): Table
    {
        return ActivityLogTable::configure($table);
    }

    /**
     * Get the page routes for the resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

    /**
     * Determine whether the current user can access this resource.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
