<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Models\NisSuggestion;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
            ->columns(3)
            ->schema([
                TextEntry::make('suggested_scientific_name')
                    ->label('Scientific Name')
                    ->html()
                    ->formatStateUsing(fn (string $state): string => "<span class='italic font-serif'>".e($state).'</span>')
                    ->columnSpan(2),
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
                TextEntry::make('suggested_common_name')
                    ->label('Common Name')
                    ->placeholder('—'),
                TextEntry::make('depth')
                    ->label('Depth')
                    ->placeholder('—')
                    ->suffix(' m')
                    ->numeric(),
                TextEntry::make('bibliography')
                    ->label('Bibliography')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('doi')
                    ->label('DOI')
                    ->placeholder('—')
                    ->url(fn (NisSuggestion $record): ?string => $record->doi
                        ? "https://doi.org/{$record->doi}"
                        : null)
                    ->openUrlInNewTab()
                    ->columnSpanFull(),
            ]);
    }

    protected static function getLocationSection(): Section
    {
        return Section::make('Location')
            ->icon('tabler-map-pin')
            ->columns(2)
            ->hidden(fn (NisSuggestion $record): bool => $record->getRawOriginal('location') === null)
            ->schema([
                TextEntry::make('latitude')
                    ->label('Latitude')
                    ->state(fn (NisSuggestion $record): ?string => $record->getRawOriginal('location') !== null
                        ? number_format(json_decode($record->getRawOriginal('location'), true)['lat'] ?? 0, 7)
                        : null),
                TextEntry::make('longitude')
                    ->label('Longitude')
                    ->state(fn (NisSuggestion $record): ?string => $record->getRawOriginal('location') !== null
                        ? number_format(json_decode($record->getRawOriginal('location'), true)['lng'] ?? 0, 7)
                        : null),
            ]);
    }

    protected static function getPhotosSection(): Section
    {
        return Section::make('Photos')
            ->icon('tabler-photo')
            ->hidden(fn (NisSuggestion $record): bool => empty($record->photo_paths))
            ->schema([
                ImageEntry::make('photo_paths')
                    ->label('')
                    ->disk('public')
                    ->stacked()
                    ->columnSpanFull(),
            ]);
    }

    protected static function getDocumentsSection(): Section
    {
        return Section::make('Supporting Documents')
            ->icon('tabler-file-description')
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
            ->columns(3)
            ->schema([
                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),
                TextEntry::make('user.name')
                    ->label('Submitted By'),
                TextEntry::make('created_at')
                    ->label('Submitted')
                    ->dateTime(),
                TextEntry::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->placeholder('—')
                    ->hidden(fn (NisSuggestion $record): bool => $record->rejection_reason === null)
                    ->columnSpanFull(),
            ]);
    }
}
