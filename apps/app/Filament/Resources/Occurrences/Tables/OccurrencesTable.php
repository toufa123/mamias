<?php

namespace App\Filament\Resources\Occurrences\Tables;

use App\Enums\Habitat;
use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Actions\OccurrenceActions;
use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Tables\MapColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures the Filament table for occurrence records.
 * Displays species, status, location map, depth, ACFOR scale,
 * habitats, observed-at, submitter, and submitted-at columns
 * with approve/reject actions.
 */
class OccurrencesTable
{
    /**
     * @param  Table  $table  The table to configure.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'taxon', 'introEventRecord.taxon']))
            ->columns([
                self::getSpeciesColumn(),
                self::getStatusColumn(),
                self::getMapColumn(),
                self::getDepthColumn(),
                self::getAcforScaleColumn(),
                self::getHabitatsColumn(),
                self::getObservedAtColumn(),
                self::getSubmitterColumn(),
                self::getSubmittedAtColumn(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OccurrenceStatus::class),
                Filter::make('date_from')
                    ->form([
                        DatePicker::make('date_from')->label('Observed from'),
                        DatePicker::make('date_until')->label('Observed until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $v) => $q->whereDate('observed_at', '>=', $v))
                            ->when($data['date_until'], fn ($q, $v) => $q->whereDate('observed_at', '<=', $v));
                    }),
            ])
            ->recordActions([
                OccurrenceActions::makeApproveAction(),
                OccurrenceActions::makeRejectAction(),
                ViewAction::make(),
            ])
            ->recordAction(ViewAction::class)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return TextColumn The species column, searchable, sortable, rendered in italics.
     */
    public static function getSpeciesColumn(): TextColumn
    {
        return TextColumn::make('introEventRecord.taxon.scientificname')
            ->label('Species')
            ->searchable()
            ->sortable()
            ->html()
            ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>');
    }

    /**
     * @return TextColumn The status column as a badge with moderation notes tooltip.
     */
    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->badge()
            ->sortable()
            ->tooltip(fn (Occurrence $record): ?string => match (true) {
                $record->status === OccurrenceStatus::REJECTED && $record->moderation_notes => $record->moderation_notes,
                default => null,
            });
    }

    /**
     * @return MapColumn The location map column, toggleable with a red pick marker.
     */
    public static function getMapColumn(): MapColumn
    {
        return MapColumn::make('location')
            ->label('Location')
            ->state(fn (?Occurrence $record): ?array => match (true) {
                $record?->location === null => null,
                is_array($record->location) && isset($record->location[0]) => $record->location[0],
                default => $record->location,
            })
            ->height(72)
            ->width(108)
            ->zoom(5)
            ->static()
            ->pickMarker(fn (Marker $marker) => $marker->red())
            ->placeholder('—')
            ->hidden(fn (?Occurrence $record): bool => $record?->getRawOriginal('location') === null)
            ->toggleable();
    }

    /**
     * @return TextColumn The depth column with "m" suffix, numeric and sortable.
     */
    public static function getDepthColumn(): TextColumn
    {
        return TextColumn::make('depth')
            ->label('Depth')
            ->placeholder('—')
            ->suffix(' m')
            ->numeric()
            ->sortable();
    }

    /**
     * @return TextColumn The ACFOR scale column as a badge, sortable.
     */
    public static function getAcforScaleColumn(): TextColumn
    {
        return TextColumn::make('acfor_scale')
            ->label('Abundance (ACFOR)')
            ->badge()
            ->sortable()
            ->placeholder('—');
    }

    /**
     * @return TextColumn The habitats column, toggleable with human-readable labels.
     */
    public static function getHabitatsColumn(): TextColumn
    {
        return TextColumn::make('habitats')
            ->label('Habitats')
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true)
            ->formatStateUsing(fn (Occurrence $record): ?string => $record->habitats
                ? collect($record->habitats)
                    ->map(fn (string $h) => Habitat::tryFrom($h)?->getLabel() ?? $h)
                    ->implode(', ')
                : null);
    }

    /**
     * @return TextColumn The observed-at date column, sortable.
     */
    public static function getObservedAtColumn(): TextColumn
    {
        return TextColumn::make('observed_at')
            ->label('Observed')
            ->dateTime()
            ->sortable();
    }

    /**
     * @return TextColumn The submitter name column, sortable and searchable.
     */
    public static function getSubmitterColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Reported By')
            ->sortable()
            ->searchable();
    }

    /**
     * @return TextColumn The submitted-at date column, sortable.
     */
    public static function getSubmittedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Submitted')
            ->dateTime()
            ->sortable();
    }
}
