<?php

namespace App\Filament\Resources\IntroEventRecords;

use App\Filament\Resources\IntroEventRecords\Pages\CreateIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Filament\Resources\IntroEventRecords\RelationManagers\OccurrencesRelationManager;
use App\Filament\Resources\IntroEventRecords\Schemas\IntroEventRecordForm;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\IntroEventRecord;
use BackedEnum;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Filament resource for managing introduction event records (NIS introductions).
 *
 * @extends \Filament\Resources\Resource
 *
 * @model App\Models\IntroEventRecord
 */
class IntroEventRecordResource extends Resource
{
    protected static ?string $model = IntroEventRecord::class;

    /* MapPin plutôt que CalendarEvent : un événement d'introduction est
       d'abord un lieu (sous-région, coordonnées), la date n'étant qu'un de
       ses attributs. L'icône calendrier le rangeait mentalement avec les
       agendas. */
    protected static string|BackedEnum|null $navigationIcon = TablerIcon::MapPin;

    protected static ?string $modelLabel = 'Intro Event';

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralModelLabel = 'Intro Events';

    protected static ?string $navigationLabel = 'Introduction Events ';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    protected static ?string $recordTitleAttribute = 'NIS Data';

    /**
     * Introduction events, trashed ones excluded — the same figure as the
     * list's All tab. Cast because the badge is ?string and count() is an int.
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Introduction events';
    }

    /**
     * Configure the form schema for the resource.
     */
    public static function form(Schema $schema): Schema
    {
        return IntroEventRecordForm::configure($schema);
    }

    /**
     * Configure the table for the resource.
     */
    public static function table(Table $table): Table
    {
        return IntroEventRecordsTable::configure($table);
    }

    /**
     * Get the list of relation managers for the resource.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            OccurrencesRelationManager::class,
        ];
    }

    /**
     * Get the route binding query, including soft-deleted records, so a
     * trashed event can still be opened and restored from its edit page.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Get the page routes for the resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListIntroEventRecords::route('/'),
            'create' => CreateIntroEventRecord::route('/create'),
            'edit' => EditIntroEventRecord::route('/{record}/edit'),
        ];
    }
}
