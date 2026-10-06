<?php

namespace App\Filament\Resources\Occurrences\Tables;

use App\Enums\Habitat;
use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Actions\OccurrenceActions;
use App\Filament\Widgets\OccurrencesMap;
use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Tables\MapColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontFamily;
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
            // Italic only, no font-serif — a species name keeps the panel's own
            // face (Geist) and is distinguished by slant alone. See
            // DESIGN-SYSTEM.md.
            ->formatStateUsing(fn (string $state): string => "<span class='italic'>".e($state).'</span>');
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
                $record->status === OccurrenceStatus::PENDING && $record->moderation_notes => 'Resubmitted. Previously rejected: '.$record->moderation_notes,
                default => null,
            });
    }

    /**
     * @return MapColumn The location map column, toggleable, its pin coloured by review status.
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
            ->visibleFrom('xl')
            ->zoom(4)
            ->static()
            // Green approved, gray pending, red rejected, like every other occurrence map.
            ->pickMarker(fn (Marker $marker, ?Occurrence $record): Marker => $record ? OccurrencesMap::colourByStatus($marker, $record->status) : $marker)
            ->placeholder('—')
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
            ->numeric(thousandsSeparator: '')
            ->fontFamily(FontFamily::Mono)
            ->visibleFrom('md')
            ->sortable();
    }

    /**
     * @return TextColumn The ACFOR scale column as a badge, sortable.
     */
    public static function getAcforScaleColumn(): TextColumn
    {
        return TextColumn::make('acfor_scale')
            ->label('Abundance (density)')
            ->badge()
            ->sortable()
            ->visibleFrom('lg')
            ->placeholder('—');
    }

    /**
     * @return TextColumn The habitats column, toggleable, one neutral icon badge per habitat.
     */
    public static function getHabitatsColumn(): TextColumn
    {
        return TextColumn::make('habitats')
            ->label('Habitats')
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true)
            ->visibleFrom('xl')
            ->badge()
            ->state(fn (Occurrence $record): array => self::habitats($record));
    }

    /**
     * Habitats as enum cases, so badges take the enum's neutral colour and icon.
     *
     * @return list<Habitat>
     */
    public static function habitats(Occurrence $record): array
    {
        return collect($record->habitats ?? [])
            ->map(fn (string $habitat): ?Habitat => Habitat::tryFrom($habitat))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return TextColumn The observed-at date column, sortable.
     */
    public static function getObservedAtColumn(): TextColumn
    {
        return TextColumn::make('observed_at')
            ->label('Observed')
            ->dateTime('d M Y H:i')
            ->fontFamily(FontFamily::Mono)
            ->visibleFrom('sm')
            ->sortable();
    }

    /**
     * @return TextColumn The submitter name column, sortable and searchable.
     */
    public static function getSubmitterColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Reported By')
            ->visibleFrom('md')
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
            ->dateTime('d M Y H:i')
            ->fontFamily(FontFamily::Mono)
            ->visibleFrom('lg')
            ->sortable();
    }
}
