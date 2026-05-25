<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Models\NisSuggestion;
use App\Services\WormsService;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;
use Illuminate\Validation\Rule;

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
                ->dehydrated(true)
                ->rules([Rule::unique('taxas', 'scientificname')])
                ->validationMessages(['unique' => 'This species already exists in the MAMIAS catalogue. Please contribute to the existing record instead.']),
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
            Textarea::make('bibliography')
                ->label('Bibliography (optional)')
                ->maxLength(1000)
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('doi')
                ->label('DOI (optional)')
                ->nullable()
                ->regex('/^10\.\d{4,9}\/[-._;()\/: A-Z0-9]+$/i')
                ->validationMessages(['regex' => 'Enter a valid DOI (e.g. 10.1000/182).'])
                ->columnSpanFull(),
            FileUpload::make('document_paths')
                ->label('Supporting Documents (optional)')
                ->multiple()
                ->acceptedFileTypes(['application/pdf', 'text/plain'])
                ->disk('local')
                ->directory('suggestions/documents')
                ->visibility('private')
                ->maxSize(10240)
                ->columnSpanFull(),
        ];
    }

    public static function getLocationAndMediaFields(): array
    {
        return [
            MapPicker::make('location')
                ->label('Location (optional — click the map to set coordinates)')
                ->height(400)
                ->center(36, 14)
                ->zoom(5)
                ->tileLayersUrl(TileLayer::OpenStreetMap)
                ->extraAttributes(['style' => 'min-height:400px'])
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
