<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Enums\Habitat;
use App\Enums\OccurrenceStatus;
use App\Models\Occurrence;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class OccurrenceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    public static function getComponents(): array
    {
        return [
            self::getSpeciesSection(),
            self::getLocationSection(),
            self::getPhotosSection(),
            self::getReviewSection(),
        ];
    }

    protected static function getSpeciesSection(): Section
    {
        return Section::make('Species Information')
            ->icon('tabler-fish')
            ->compact()
            ->schema([
                Grid::make(4)->schema([
                    TextEntry::make('introEventRecord.taxon.scientificname')
                        ->label('Scientific Name')
                        ->html()
                        ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>'),
                    TextEntry::make('introEventRecord.taxon.authority')
                        ->label('Authority')
                        ->placeholder('—'),
                    TextEntry::make('depth')
                        ->label('Depth')
                        ->placeholder('—')
                        ->suffix(' m')
                        ->numeric(),
                    TextEntry::make('acfor_scale')
                        ->label('Abundance (ACFOR)')
                        ->badge()
                        ->placeholder('—'),
                    TextEntry::make('introEventRecord.first_introduction_year')
                        ->label('First Introduction Year')
                        ->placeholder('—'),
                    TextEntry::make('habitats')
                        ->label('Habitats')
                        ->placeholder('—')
                        ->columnSpan(2)
                        ->formatStateUsing(fn (Occurrence $record): ?string => $record->habitats
                            ? collect($record->habitats)
                                ->map(fn (string $h) => Habitat::tryFrom($h)?->getLabel() ?? $h)
                                ->implode(', ')
                            : null),
                ]),
                TextEntry::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('observed_at')
                    ->label('Observed At')
                    ->dateTime()
                    ->weight(FontWeight::Bold),
            ]);
    }

    protected static function getLocationSection(): Section
    {
        return Section::make('Location')
            ->icon('tabler-map-pin')
            ->compact()
            ->hidden(fn (Occurrence $record): bool => $record->getRawOriginal('location') === null)
            ->schema([
                OccurrenceLocationsMapEntry::make('location')
                    ->hiddenLabel()
                    ->height(284)
                    ->zoom(10)
                    ->pickMarker(fn (Marker $marker) => $marker->red())
                    ->static()
                    ->extraAttributes(['x-on:x-modal-opened.window' => 'setTimeout(() => mapCore?.map?.invalidateSize(), 50); setTimeout(() => mapCore?.map?.invalidateSize(), 300);'])
                    ->columnSpanFull(),
            ]);
    }

    protected static function getPhotosSection(): Section
    {
        return Section::make('Photos')
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

    protected static function getReviewSection(): Section
    {
        return Section::make('Review')
            ->icon('tabler-clipboard-check')
            ->compact()
            ->schema([
                Grid::make(4)->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                    TextEntry::make('user.name')
                        ->label('Reported By'),
                    TextEntry::make('created_at')
                        ->label('Submitted')
                        ->dateTime(),
                    TextEntry::make('updated_at')
                        ->label('Reviewed At')
                        ->dateTime()
                        ->hidden(fn (Occurrence $record): bool => $record->status === OccurrenceStatus::PENDING)
                        ->placeholder('—'),
                ]),
                TextEntry::make('moderation_notes')
                    ->label('Moderation Notes')
                    ->placeholder('—')
                    ->hidden(fn (Occurrence $record): bool => $record->moderation_notes === null)
                    ->columnSpanFull(),
            ]);
    }
}
