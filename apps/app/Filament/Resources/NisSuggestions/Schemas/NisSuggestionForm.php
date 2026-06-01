<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Enums\AcforScale;
use App\Enums\Habitat;
use App\Filament\Forms\MultipleMarkersMapPicker;
use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Services\WormsService;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
            Grid::make(3)->schema(self::getTaxonFields())->columnSpanFull(),
            Hidden::make('worms_status'),
            Hidden::make('kingdom'),
            ...self::getLocationAndMediaFields(),
            ...self::getDetailFields(),
        ];
    }

    public static function getTaxonFields(): array
    {
        $wormsService = app(WormsService::class);

        return [
            Select::make('suggested_scientific_name')
                ->label('Scientific Name (search WoRMS)')
                ->searchable()
                ->required()
                ->getSearchResultsUsing(fn (string $search) => self::searchWoRMS($search, $wormsService))
                ->getOptionLabelUsing(fn (mixed $value) => self::getWormsLabel($value, $wormsService))
                ->live()
                ->afterStateUpdated(fn (Set $set, mixed $state) => self::populateTaxonData($set, $state, $wormsService))
                ->hintIcon('tabler-info-circle', tooltip: 'Type at least 4 characters to search the WoRMS database'),
            TextInput::make('authority')
                ->label('Authority')
                ->readOnly()
                ->dehydrated(true),
            TextInput::make('aphia_id')
                ->label('Aphia ID')
                ->readOnly()
                ->dehydrated(true),
            Stepper::make('depth')
                ->label('Depth (m)')
                ->minValue(0)
                ->maxValue(11000)
                ->step(1),
            Select::make('acfor_scale')
                ->label('Abundance (ACFOR Scale)')
                ->options(fn (Get $get): array => self::getAcforOptions($get('kingdom')))
                ->native(false)
                ->live()
                ->placeholder('Select ACFOR scale'),
            Select::make('habitats')
                ->label('Habitats')
                ->multiple()
                ->options(Habitat::class)
                ->native(false)
                ->placeholder('Select habitats'),
        ];
    }

    public static function getDetailFields(): array
    {
        return [
            Section::make('Supporting documents')
                ->schema([
                    FileUpload::make('photo_paths')
                        ->label('Photos')
                        ->required()
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
                    Select::make('literatures')
                        ->label('Bibliographic References')
                        ->relationship('literatures', 'short_ref')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([LiteratureForm::getBibliographicReferenceSection()])
                        ->hintIcon('tabler-info-circle', tooltip: 'Select existing references or create a new one with DOI auto-fill.')
                        ->columnSpanFull(),
                ])
                ->compact()
                ->columnSpanFull(),
        ];
    }

    public static function getLocationAndMediaFields(): array
    {
        return [
            Section::make('Location')
                ->schema([
                    MultipleMarkersMapPicker::make('location')
                        ->hiddenLabel()
                        ->height(400)
                        ->center([36, 14])
                        ->zoom(5)
                        ->tileLayersUrl(TileLayer::OpenStreetMap)
                        ->pickMarker(fn (Marker $marker) => $marker->red())
                        ->extraAttributes(['x-on:x-modal-opened.window' => 'setTimeout(() => mapCore?.map?.invalidateSize(), 50); setTimeout(() => mapCore?.map?.invalidateSize(), 300);'])
                        ->columnSpanFull(),
                ])
                ->compact()
                ->columnSpanFull(),
        ];
    }

    public static function getAcforOptions(?string $kingdom): array
    {
        return collect(AcforScale::cases())->mapWithKeys(fn (AcforScale $scale) => [
            $scale->value => $scale->getLabel().self::getAcforDescriptionSuffix($kingdom, $scale),
        ])->toArray();
    }

    public static function getAcforDescriptionSuffix(?string $kingdom, AcforScale $scale): string
    {
        if (! $kingdom) {
            return '';
        }

        $isPlant = in_array($kingdom, ['Plantae', 'Chromista'], true);

        return ' — '.($isPlant ? $scale->getPlantDescription() : $scale->getAnimalDescription());
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

        if (is_numeric($value)) {
            $record = $wormsService->getRecordByAphiaID((int) $value);

            return $record
                ? $record['scientificname'].' ['.($record['status'] === 'accepted' ? 'verified' : $record['status']).']'
                : (string) $value;
        }

        return (string) $value;
    }

    public static function populateTaxonData(Set $set, mixed $state, WormsService $wormsService): void
    {
        if (! $state) {
            $set('aphia_id', null);
            $set('authority', null);
            $set('worms_status', null);
            $set('kingdom', null);

            return;
        }

        $record = $wormsService->getRecordByAphiaID((int) $state);
        if ($record) {
            $set('suggested_scientific_name', $record['scientificname']);
            $set('aphia_id', (int) $state);
            $set('authority', $record['authority'] ?? '');
            $set('worms_status', $record['status'] ?? '');
            $set('kingdom', $record['kingdom'] ?? '');
        }
    }
}
