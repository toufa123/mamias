<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Enums\AcforScale;
use App\Enums\Habitat;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Forms\MultipleMarkersMapPicker;
use App\Models\IntroEventRecord;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;

/**
 * Configures the Filament form schema for occurrence records.
 * Provides species/country search, depth, abundance, habitat,
 * location map, photos, and notes fields.
 */
class OccurrenceForm
{
    /**
     * @param  Schema  $schema  The form schema to configure.
     * @return Schema The configured schema instance.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    public static function getComponents(): array
    {
        return [
            Hidden::make('kingdom'),
            Grid::make(3)->schema([
                CountrySelectWithMedPriority::make('country')
                    ->label('Country')
                    ->dehydrated(false)
                    ->placeholder('Filter species by country…')
                    ->live(),
                Select::make('intro_event_record_id')
                    ->label('Species')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search, Get $get): array => self::searchSpecies($search, $get))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => self::getSpeciesLabel($value))
                    ->live()
                    ->afterStateUpdated(fn (Set $set, mixed $state) => self::populateKingdom($set, $state))
                    ->hintIcon('tabler-fish')
                    ->placeholder('Type at least 3 characters to search…'),
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
                DateTimePicker::make('observed_at')
                    ->label('Date & Time of Observation')
                    ->required()
                    ->default(now())
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i'),
            ])->columnSpanFull(),
            Tabs::make('Details')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Location')
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
                        ]),
                    Tab::make('Photos')
                        ->schema([
                            FileUpload::make('photo_paths')
                                ->label('Photos')
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
                                ->directory('occurrences/photos')
                                ->visibility('public')
                                ->maxSize(5120)
                                ->imagePreviewHeight('40')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Notes')
                        ->schema([
                            Textarea::make('notes')
                                ->label('Notes')
                                ->rows(5)
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /**
     * @param  string  $search  The search query (minimum 3 characters).
     * @param  Get  $get  The reactive form state getter.
     * @return array<int, string> Map of intro event record IDs to formatted species labels.
     */
    public static function searchSpecies(string $search, Get $get): array
    {
        if (strlen($search) < 3) {
            return [];
        }

        $country = $get('country');

        $query = IntroEventRecord::with('taxon')
            ->whereHas('taxon', fn ($q) => $q->where('scientificname', 'ilike', "%{$search}%"));

        if ($country) {
            $query->where('first_country', $country);
        }

        return $query
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (IntroEventRecord $ie): array => [
                $ie->id => ($ie->taxon?->scientificname ?? 'Unknown species').' — '.($ie->first_introduction_year ?? '?').', '.($ie->first_country ?? '?'),
            ])
            ->toArray();
    }

    /**
     * @param  mixed  $value  The intro event record ID.
     * @return string|null The formatted species label or null.
     */
    public static function getSpeciesLabel(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        $ie = IntroEventRecord::with('taxon')->find($value);

        if (! $ie) {
            return (string) $value;
        }

        return ($ie->taxon?->scientificname ?? 'Unknown species').' — '.($ie->first_introduction_year ?? '?').', '.($ie->first_country ?? '?');
    }

    /**
     * @param  Set  $set  The form state setter utility.
     * @param  mixed  $state  The selected intro event record ID.
     */
    public static function populateKingdom(Set $set, mixed $state): void
    {
        if (! $state) {
            $set('kingdom', null);

            return;
        }

        $ie = IntroEventRecord::with('taxon')->find($state);

        if ($ie && $ie->taxon) {
            $set('kingdom', $ie->taxon->kingdom);
        } else {
            $set('kingdom', null);
        }
    }

    /**
     * @param  string|null  $kingdom  The kingdom to filter ACFOR descriptions for (e.g. "Plantae").
     * @return array<string, string> Map of ACFOR scale values to labels with kingdom-specific descriptions.
     */
    public static function getAcforOptions(?string $kingdom): array
    {
        return collect(AcforScale::cases())->mapWithKeys(fn (AcforScale $scale) => [
            $scale->value => $scale->getLabel().self::getAcforDescriptionSuffix($kingdom, $scale),
        ])->toArray();
    }

    /**
     * @param  string|null  $kingdom  The kingdom (e.g. "Plantae", "Chromista") or null.
     * @param  AcforScale  $scale  The ACFOR scale value.
     * @return string The appropriate description suffix for the given kingdom.
     */
    public static function getAcforDescriptionSuffix(?string $kingdom, AcforScale $scale): string
    {
        if (! $kingdom) {
            return '';
        }

        $isPlant = in_array($kingdom, ['Plantae', 'Chromista'], true);

        return ' — '.($isPlant ? $scale->getPlantDescription() : $scale->getAnimalDescription());
    }
}
