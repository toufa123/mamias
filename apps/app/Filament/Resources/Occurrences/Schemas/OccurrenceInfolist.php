<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

/**
 * Configures the Filament infolist schema for occurrence records.
 * Displays species information, location map, photos, and review status.
 */
class OccurrenceInfolist
{
    /** Six across on a wide screen, folding to three then two. */
    private const COLUMNS = ['default' => 2, 'md' => 3, 'lg' => 6];

    /**
     * @param  Schema  $schema  The infolist schema to configure.
     * @return Schema The configured schema instance.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    /**
     * @return array<int, mixed> The array of infolist sections.
     */
    public static function getComponents(): array
    {
        return [
            self::getSpeciesSection(),
            self::getLocationSection(),
            self::getPhotosSection(),
            self::getReviewSection(),
        ];
    }

    /**
     * @return Section The species information section with scientific name, authority, depth, ACFOR, etc.
     */
    protected static function getSpeciesSection(): Section
    {
        return Section::make('Species Information')
            ->columnSpanFull()
            ->icon('tabler-fish')
            ->compact()
            ->schema([
                // Two dense rows on a wide screen: who and when, then what was found and where.
                Grid::make(self::COLUMNS)->schema([
                    TextEntry::make('introEventRecord.taxon.scientificname')
                        ->label('Scientific Name')
                        ->html()
                        // Italic only — no font-serif. See DESIGN-SYSTEM.md.
                        ->formatStateUsing(fn (string $state): string => "<span class='italic'>".e($state).'</span>')
                        ->columnSpan(['default' => 2, 'lg' => 2]),
                    TextEntry::make('introEventRecord.taxon.authority')
                        ->label('Authority')
                        ->placeholder('—'),
                    TextEntry::make('introEventRecord.first_introduction_year')
                        ->label('First Introduction')
                        ->fontFamily(FontFamily::Mono)
                        ->placeholder('—'),
                    TextEntry::make('observed_at')
                        ->label('Observed At')
                        ->dateTime('d M Y H:i')
                        ->fontFamily(FontFamily::Mono),
                    TextEntry::make('depth')
                        ->label('Depth')
                        ->placeholder('—')
                        ->suffix(' m')
                        ->numeric(thousandsSeparator: '')
                        ->fontFamily(FontFamily::Mono),
                    TextEntry::make('acfor_scale')
                        ->label('Abundance (density)')
                        ->badge()
                        ->placeholder('—'),
                    TextEntry::make('coverage_value')
                        ->label('Extent')
                        ->placeholder('—')
                        ->fontFamily(FontFamily::Mono)
                        ->formatStateUsing(fn (Occurrence $record): ?string => $record->coverage_value === null
                            ? null
                            : rtrim(rtrim(number_format($record->coverage_value, 2, '.', ' '), '0'), '.')
                                .' '.($record->coverage_unit?->getSuffix() ?? '')),
                    TextEntry::make('coverage_method')
                        ->label('Estimated / Measured')
                        ->badge()
                        ->placeholder('—'),
                    TextEntry::make('habitats')
                        ->label('Habitats')
                        ->badge()
                        ->state(fn (Occurrence $record): array => OccurrencesTable::habitats($record))
                        ->placeholder('—'),
                    TextEntry::make('coordinates')
                        ->label('Coordinates')
                        ->state(fn (Occurrence $record): ?string => isset($record->location[0]['lat'], $record->location[0]['lng'])
                            ? sprintf('%.5f, %.5f', $record->location[0]['lat'], $record->location[0]['lng'])
                            : null)
                        ->fontFamily(FontFamily::Mono)
                        ->copyable()
                        ->copyMessage('Coordinates copied')
                        ->placeholder('—')
                        ->columnSpan(['default' => 2, 'lg' => 2]),
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->hidden(fn (Occurrence $record): bool => blank($record->notes))
                        ->columnSpanFull(),
                ]),
            ]);
    }

    /**
     * @return Section The location section with an interactive map entry.
     */
    protected static function getLocationSection(): Section
    {
        return Section::make('Location')
            // Stacked full width: the Mediterranean is a wide strip, and so is its map.
            ->columnSpanFull()
            ->icon('tabler-map-pin')
            ->compact()
            ->hidden(fn (Occurrence $record): bool => $record->getRawOriginal('location') === null)
            ->schema([
                OccurrenceLocationsMapEntry::make('location')
                    ->hiddenLabel()
                    // The cast returns a list of points; the map centres on one.
                    ->state(fn (Occurrence $record): ?array => $record->location[0] ?? null)
                    ->height(284)
                    ->zoom(9)
                    ->static()
                    ->extraAttributes(['x-on:x-modal-opened.window' => 'setTimeout(() => mapCore?.map?.invalidateSize(), 50); setTimeout(() => mapCore?.map?.invalidateSize(), 300);'])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Section The photos section with an image gallery entry.
     */
    protected static function getPhotosSection(): Section
    {
        return Section::make('Photos')
            ->columnSpanFull()
            ->icon('tabler-photo')
            ->compact()
            ->hidden(fn (Occurrence $record): bool => empty($record->photo_paths))
            ->schema([
                ImageEntry::make('photo_paths')
                    ->hiddenLabel()
                    ->disk('public')
                    ->imageGallery()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Section The review section with status, submitter, timestamps, and moderation notes.
     */
    protected static function getReviewSection(): Section
    {
        return Section::make('Review')
            ->columnSpanFull()
            ->icon('tabler-clipboard-check')
            ->compact()
            ->schema([
                Grid::make(self::COLUMNS)->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                    TextEntry::make('user.name')
                        ->label('Reported By'),
                    TextEntry::make('created_at')
                        ->label('Submitted')
                        ->dateTime('d M Y H:i')
                        ->fontFamily(FontFamily::Mono),
                    TextEntry::make('updated_at')
                        ->label('Reviewed At')
                        ->dateTime('d M Y H:i')
                        ->fontFamily(FontFamily::Mono)
                        ->hidden(fn (Occurrence $record): bool => $record->status === OccurrenceStatus::PENDING),
                    TextEntry::make('moderation_notes')
                        ->label('Moderation Notes')
                        ->hidden(fn (Occurrence $record): bool => $record->moderation_notes === null)
                        ->columnSpan(['default' => 2, 'lg' => 2]),
                ]),
            ]);
    }
}
