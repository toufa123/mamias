<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Services\WormsService;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;

class NisSuggestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    public static function getComponents(): array
    {
        return [
            Grid::make(4)->schema(self::getTaxonFields())->columnSpanFull(),
            Hidden::make('worms_status'),
            ...self::getLocationAndMediaFields(),
            ...self::getDetailFields(),
        ];
    }

    public static function getTaxonFields(): array
    {
        $wormsService = app(WormsService::class);

        return [
            Select::make('aphia_id')
                ->label('Scientific Name (search WoRMS)')
                ->searchable()
                ->required()
                ->columnSpan(2)
                ->getSearchResultsUsing(fn (string $search) => self::searchWoRMS($search, $wormsService))
                ->getOptionLabelUsing(fn (mixed $value) => self::getWormsLabel($value, $wormsService))
                ->live()
                ->afterStateUpdated(fn (Set $set, mixed $state) => self::populateTaxonData($set, $state, $wormsService))
                ->hintIcon('tabler-info-circle', tooltip: 'Type at least 4 characters to search the WoRMS database'),
            Hidden::make('suggested_scientific_name')
                ->dehydrated(true),
            TextInput::make('authority')->label('Authority')->readOnly()->dehydrated(true),
            Stepper::make('depth')
                ->label('Depth (m)')
                ->minValue(0)
                ->maxValue(11000)
                ->step(1),
        ];
    }

    public static function getDetailFields(): array
    {
        return [
            Section::make('Bibliographic References')
                ->schema([
                    Select::make('literatures')
                        ->relationship('literatures', 'short_ref')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([LiteratureForm::getBibliographicReferenceSection()])
                        ->hintIcon('tabler-info-circle', tooltip: 'Select existing references or create a new one with DOI auto-fill.'),
                ])
                ->compact()
                ->columnSpanFull(),

        ];
    }

    public static function getLocationAndMediaFields(): array
    {
        return [
            MapPicker::make('location')
                ->label('Location (optional — click the map to set coordinates)')
                ->height(400)
                ->center([36, 14])
                ->zoom(5)
                ->tileLayersUrl(TileLayer::OpenStreetMap)
                ->pickMarker(fn (Marker $marker) => $marker->red())
                ->extraAttributes(['x-on:x-modal-opened.window' => 'setTimeout(() => mapCore?.map?.invalidateSize(), 50); setTimeout(() => mapCore?.map?.invalidateSize(), 300);'])
                ->columnSpanFull(),
            FileUpload::make('photo_paths')
                ->label('Photos (optional)')

                ->panelLayout('grid')
                ->loadingIndicatorPosition('left')
                ->panelAspectRatio('8:1')
                ->removeUploadedFileButtonPosition('right')
                ->uploadButtonPosition('left')
                ->uploadProgressIndicatorPosition('left')
                ->multiple()
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('public')
                ->directory('suggestions/photos')
                ->visibility('public')
                ->maxSize(5120)
                ->imagePreviewHeight('40')

                ->columnSpanFull(),
        ];
    }

    public static function searchWoRMS(string $search, WormsService $wormsService): array
    {
        if (strlen($search) < 4) {
            return [];
        }

        return collect($wormsService->searchSpecies($search))
            ->mapWithKeys(fn (array $r) => [
                $r['AphiaID'] => $r['scientificname'].' ['.($r['status'] === 'accepted' ? 'verified' : $r['status']).']',
            ])
            ->toArray();
    }

    public static function getWormsLabel(mixed $value, WormsService $wormsService): ?string
    {
        if (! $value) {
            return null;
        }

        $record = $wormsService->getRecordByAphiaID((int) $value);

        return $record
            ? $record['scientificname'].' ['.($record['status'] === 'accepted' ? 'verified' : $record['status']).']'
            : (string) $value;
    }

    public static function populateTaxonData(Set $set, mixed $state, WormsService $wormsService): void
    {
        if (! $state) {
            return;
        }

        $record = $wormsService->getRecordByAphiaID((int) $state);
        if ($record) {
            $set('suggested_scientific_name', $record['scientificname']);
            $set('authority', $record['authority'] ?? '');
            $set('worms_status', $record['status'] ?? '');
        }
    }
}
