<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Occurrences\Schemas\OccurrenceInfolist;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Relation manager for viewing occurrences associated with a user.
 */
class OccurrencesRelationManager extends RelationManager
{
    protected static string $relationship = 'occurrences';

    protected static ?string $title = 'Occurrences';

    protected static ?string $recordTitleAttribute = 'id';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return OccurrencesTable::configure($table)
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (Occurrence $record): string => "Occurrence #{$record->id}")
                    ->modalWidth('6xl'),
            ])
            ->bulkActions([]);
    }

    public function infolist(Schema $schema): Schema
    {
        return OccurrenceInfolist::configure($schema);
    }
}
