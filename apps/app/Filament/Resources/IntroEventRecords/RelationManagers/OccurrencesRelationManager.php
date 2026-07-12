<?php

namespace App\Filament\Resources\IntroEventRecords\RelationManagers;

use App\Filament\Resources\Occurrences\Actions\OccurrenceActions;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceInfolist;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Relation manager for managing occurrences of an intro event record.
 */
class OccurrencesRelationManager extends RelationManager
{
    protected static string $relationship = 'occurrences';

    protected static ?string $title = 'Occurrences';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return OccurrencesTable::configure($table)
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (Occurrence $record): string => "Occurrence #{$record->id}")
                    ->modalWidth('6xl'),
                OccurrenceActions::makeApproveAction(),
                OccurrenceActions::makeRejectAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public function infolist(Schema $schema): Schema
    {
        return OccurrenceInfolist::configure($schema);
    }
}
