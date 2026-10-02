<?php

namespace App\Filament\Resources\Occurrences;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Filament\Resources\Occurrences\Actions\OccurrenceActions;
use App\Filament\Resources\Occurrences\Pages\ListOccurrences;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceInfolist;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Field occurrences submitted from the public site, moderated here. Shown as
 * a sub-item of Introduction Events: an occurrence always belongs to one.
 *
 * There is no Occurrence policy: access follows the introduction-event
 * permissions, as it did when occurrences were a tab of the event.
 * Occurrences are submitted, never created or edited in the panel.
 */
class OccurrenceResource extends Resource
{
    protected static ?string $model = Occurrence::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-map-pins';

    protected static ?string $navigationLabel = 'Occurrences';

    protected static string|null|\UnitEnum $navigationGroup = 'MAMIAS database';

    /** Filament matches the parent by its exact label, trailing space included. */
    public static function getNavigationParentItem(): ?string
    {
        return IntroEventRecordResource::getNavigationLabel();
    }

    /** Occurrences awaiting moderation. */
    public static function getNavigationBadge(): ?string
    {
        $pending = Occurrence::where('status', OccurrenceStatus::PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Occurrences awaiting review';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', IntroEventRecord::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Occurrence && $record->introEventRecord !== null
            && (auth()->user()?->can('update', $record->introEventRecord) ?? false);
    }

    public static function table(Table $table): Table
    {
        return OccurrencesTable::configure($table)
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (Occurrence $record): string => "Occurrence #{$record->id}")
                    ->modalWidth('6xl')
                    // Moderate straight from the details (also where a map pin lands).
                    ->extraModalFooterActions([
                        OccurrenceActions::makeApproveAction()->cancelParentActions(),
                        OccurrenceActions::makeRejectAction()->cancelParentActions(),
                    ]),
                OccurrenceActions::makeApproveAction(),
                OccurrenceActions::makeRejectAction(),
                DeleteAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OccurrenceInfolist::configure($schema);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListOccurrences::route('/'),
        ];
    }
}
