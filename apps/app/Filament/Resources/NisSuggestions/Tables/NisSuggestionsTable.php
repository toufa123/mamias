<?php

namespace App\Filament\Resources\NisSuggestions\Tables;

use App\Enums\Habitat;
use App\Enums\LiteratureStatus;
use App\Filament\Resources\NisSuggestions\Actions\NisSuggestionActions;
use App\Models\NisSuggestion;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Tables\MapColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NisSuggestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['user', 'taxon'])
            )
            ->columns([
                self::getScientificNameColumn(),
                self::getAuthorityColumn(),
                self::getAphiaIdColumn(),
                self::getStatusColumn(),
                self::getMapColumn(),
                self::getTaxonColumn(),
                self::getSubmitterColumn(),
                self::getSubmittedAtColumn(),
                self::getAcforScaleColumn(),
                self::getHabitatsColumn(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options(LiteratureStatus::class),
                Filter::make('date_from')
                    ->form([
                        DatePicker::make('date_from')->label('Submitted from'),
                        DatePicker::make('date_until')->label('Submitted until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['date_until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->recordActions([
                NisSuggestionActions::makeApproveAction(),
                NisSuggestionActions::makeRejectAction(),
                ViewAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->recordAction(ViewAction::class)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getScientificNameColumn(): TextColumn
    {
        return TextColumn::make('suggested_scientific_name')
            ->label('Scientific Name')
            ->searchable()
            ->sortable()
            ->html()
            ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>');
    }

    public static function getAuthorityColumn(): TextColumn
    {
        return TextColumn::make('authority')
            ->label('Authority')
            ->placeholder('—')
            ->toggleable();
    }

    public static function getAphiaIdColumn(): TextColumn
    {
        return TextColumn::make('aphia_id')
            ->label('Aphia ID')
            ->placeholder('—')
            ->url(fn (NisSuggestion $record): ?string => $record->aphia_id
                ? "https://www.marinespecies.org/aphia.php?p=taxdetails&id={$record->aphia_id}"
                : null)
            ->openUrlInNewTab()
            ->icon('tabler-external-link')
            ->toggleable();
    }

    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->badge()
            ->sortable()
            ->tooltip(fn (NisSuggestion $record): ?string => match (true) {
                $record->status === LiteratureStatus::REJECTED && $record->rejection_reason => $record->rejection_reason,
                default => null,
            });
    }

    public static function getSubmitterColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Submitted By')
            ->sortable()
            ->searchable();
    }

    public static function getSubmittedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Submitted')
            ->dateTime()
            ->sortable();
    }

    public static function getTaxonColumn(): TextColumn
    {
        return TextColumn::make('taxon')
            ->label('Taxon')
            ->placeholder('—')
            ->state(fn (NisSuggestion $record): ?string => $record->taxon?->scientificname)
            ->url(fn (NisSuggestion $record): ?string => $record->taxon_id
                ? route('filament.mamias.resources.taxons.taxa.edit', ['record' => $record->taxon_id])
                : null)
            ->openUrlInNewTab()
            ->icon('tabler-fish')
            ->toggleable();
    }

    public static function getMapColumn(): MapColumn
    {
        return MapColumn::make('location')
            ->label('Location')
            ->state(fn (?NisSuggestion $record): ?array => match (true) {
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
            ->hidden(fn (?NisSuggestion $record): bool => $record?->getRawOriginal('location') === null)
            ->toggleable();
    }

    public static function getAcforScaleColumn(): TextColumn
    {
        return TextColumn::make('acfor_scale')
            ->label('Abundance (ACFOR)')
            ->badge()
            ->sortable()
            ->placeholder('—');
    }

    public static function getHabitatsColumn(): TextColumn
    {
        return TextColumn::make('habitats')
            ->label('Habitats')
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true)
            ->formatStateUsing(fn (NisSuggestion $record): ?string => $record->habitats
                ? collect($record->habitats)
                    ->map(fn (string $h) => Habitat::tryFrom($h)?->getLabel() ?? $h)
                    ->implode(', ')
                : null);
    }
}
