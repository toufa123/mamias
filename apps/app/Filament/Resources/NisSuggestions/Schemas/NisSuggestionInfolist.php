<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Enums\LiteratureStatus;
use App\Models\NisSuggestion;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class NisSuggestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getSpeciesSection(),
                self::getLocationSection(),
                self::getPhotosSection(),
                self::getDocumentsSection(),
                self::getReviewSection(),
            ]);
    }

    public static function getComponents(): array
    {
        return [
            self::getSpeciesSection(),
            self::getLocationSection(),
            self::getPhotosSection(),
            self::getDocumentsSection(),
            self::getReviewSection(),
        ];
    }

    protected static function getSpeciesSection(): Section
    {
        return Section::make('Species Information')
            ->icon('tabler-fish')
            ->compact()
            ->schema([
                Grid::make(5)->schema([
                    TextEntry::make('suggested_scientific_name')
                        ->label('Scientific Name')
                        ->html()
                        ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>'),
                    TextEntry::make('authority')
                        ->label('Authority')
                        ->placeholder('—'),
                    TextEntry::make('aphia_id')
                        ->label('Aphia ID')
                        ->placeholder('—')
                        ->url(fn (NisSuggestion $record): ?string => $record->aphia_id
                            ? "https://www.marinespecies.org/aphia.php?p=taxdetails&id={$record->aphia_id}"
                            : null)
                        ->openUrlInNewTab(),
                    TextEntry::make('worms_status')
                        ->label('WoRMS Status')
                        ->badge()
                        ->placeholder('—'),
                    TextEntry::make('depth')
                        ->label('Depth')
                        ->placeholder('—')
                        ->suffix(' m')
                        ->numeric(),
                ]),
                TextEntry::make('literatures')
                    ->label('References')
                    ->placeholder('—')
                    ->listWithLineBreaks()
                    ->badge(),
                TextEntry::make('taxon.scientificname')
                    ->label('Catalogue Taxon')
                    ->placeholder('—')
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->url(fn (NisSuggestion $record): ?string => $record->taxon_id
                        ? route('filament.mamias.resources.taxons.edit', ['record' => $record->taxon_id])
                        : null)
                    ->openUrlInNewTab()
                    ->icon('tabler-fish')
                    ->hidden(fn (NisSuggestion $record): bool => $record->taxon_id === null),
            ]);
    }

    protected static function getLocationSection(): Section
    {
        return Section::make('Location')
            ->icon('tabler-map-pin')
            ->compact()
            ->hidden(fn (NisSuggestion $record): bool => $record->getRawOriginal('location') === null)
            ->schema([
                SpeciesLocationsMapEntry::make('location')
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
            ->hidden(fn (NisSuggestion $record): bool => empty($record->photo_paths))
            ->schema([
                ImageEntry::make('photo_paths')
                    ->hiddenLabel()
                    ->disk('public')
                    ->imageGallery()
                    ->columnSpanFull(),
            ]);
    }

    protected static function getDocumentsSection(): Section
    {
        return Section::make('Supporting Documents')
            ->icon('tabler-file-description')
            ->compact()
            ->hidden(fn (NisSuggestion $record): bool => empty($record->document_paths))
            ->schema([
                TextEntry::make('document_paths')
                    ->label('Documents')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state)) {
                            return '—';
                        }

                        return implode(', ', array_map(
                            fn ($path) => e(basename($path)),
                            $state
                        ));
                    })
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
                        ->label('Submitted By'),
                    TextEntry::make('created_at')
                        ->label('Submitted')
                        ->dateTime(),
                    TextEntry::make('updated_at')
                        ->label('Reviewed At')
                        ->dateTime()
                        ->hidden(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::PENDING)
                        ->placeholder('—'),
                ]),
                TextEntry::make('resubmittedFrom.suggested_scientific_name')
                    ->label('Resubmitted From')
                    ->html()
                    ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>')
                    ->placeholder('—')
                    ->hidden(fn (NisSuggestion $record): bool => $record->resubmitted_from_id === null)
                    ->columnSpanFull(),
                TextEntry::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->placeholder('—')
                    ->hidden(fn (NisSuggestion $record): bool => $record->rejection_reason === null)
                    ->columnSpanFull(),
            ]);
    }
}
