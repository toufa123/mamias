<?php

namespace App\Filament\Resources\Occurrences\Schemas;

use App\Enums\AcforScale;
use App\Enums\CoverageMethod;
use App\Enums\CoverageUnit;
use App\Enums\Habitat;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Forms\SinglePointMapPicker;
use App\Models\IntroEventRecord;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                    ->afterStateUpdated(fn (Get $get, Set $set, mixed $state) => self::populateKingdom($get, $set, $state))
                    ->hintIcon('tabler-fish')
                    ->placeholder('Type at least 3 characters to search…'),
                Stepper::make('depth')
                    ->label('Depth (m)')
                    ->minValue(0)
                    ->maxValue(11000)
                    ->step(1),

                // Second row: what was seen.
                //
                // Density and extent answer two different questions, so the
                // labels have to keep them apart: ACFOR is how tightly packed
                // the species is where it was found, extent is how much of it
                // there is in total. They also sit on separate rows for that
                // reason — the whole extent group is the third row.
                Select::make('acfor_scale')
                    ->label('Abundance (density)')
                    ->options(fn (Get $get): array => self::getAcforOptions($get('kingdom')))
                    ->native(false)
                    ->live()
                    ->hintIcon(
                        Heroicon::OutlinedQuestionMarkCircle,
                        tooltip: 'ACFOR scale — how tightly packed the species is where you found it.',
                    )
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

                // Third row: the extent figure, its unit, and how it was taken.
                TextInput::make('coverage_value')
                    ->label('Extent (area or count)')
                    ->numeric()
                    ->minValue(0)
                    ->step('any')
                    ->live(onBlur: true)
                    ->suffix(fn (Get $get): ?string => self::getCoverageSuffix($get('coverage_unit')))
                    ->hintIcon(
                        Heroicon::OutlinedQuestionMarkCircle,
                        tooltip: 'How much there is in total: area covered in m², or number of individuals.',
                    ),
                Select::make('coverage_unit')
                    ->label('Extent Unit')
                    ->options(CoverageUnit::class)
                    ->native(false)
                    ->live()
                    ->requiredWith('coverage_value')
                    ->placeholder('Select unit'),
                Select::make('coverage_method')
                    ->label('Estimated or Measured')
                    ->options(CoverageMethod::class)
                    ->native(false)
                    ->requiredWith('coverage_value')
                    ->placeholder('How was it obtained?'),
            ])->columnSpanFull(),
            Tabs::make('Details')
                ->columnSpanFull()
                // The three panels have very different natural heights — the
                // map, a photo grid, a textarea — so the modal resized on every
                // tab switch. A floor on the container keeps the box steady;
                // the tallest panel (Location) sets it, so this tracks the map
                // height above it.
                //
                // On the container, not on each Tab: Filament merges a Tab's
                // extraAttributes into its strip button as well as its panel, so
                // a height there stretches the tab strip itself.
                ->extraAttributes(['style' => 'min-height: 26rem;'])
                ->tabs([
                    Tab::make('Location')
                        ->schema([
                            // One observation, one point: click the map to place
                            // the pin, click again to move it.
                            SinglePointMapPicker::make('location')
                                ->hiddenLabel()
                                ->helperText('Click the map to place the observation point. Clicking again moves it.')
                                ->height(280)
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
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /**
     * Suffix shown inside the coverage input, following the selected unit.
     *
     * The state arrives as a raw string on first render and as a CoverageUnit
     * once the Select has hydrated its enum options, so both are accepted.
     *
     * @param  mixed  $unit  The current `coverage_unit` form state.
     * @return string|null The unit suffix (m² / ind.), or null when no unit is picked.
     */
    public static function getCoverageSuffix(mixed $unit): ?string
    {
        if ($unit instanceof CoverageUnit) {
            return $unit->getSuffix();
        }

        return is_string($unit) ? CoverageUnit::tryFrom($unit)?->getSuffix() : null;
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
     * Resolves the selected species' kingdom and, from it, the unit the extent
     * is most likely to be recorded in.
     *
     * @param  Get  $get  The reactive form state getter.
     * @param  Set  $set  The form state setter utility.
     * @param  mixed  $state  The selected intro event record ID.
     */
    public static function populateKingdom(Get $get, Set $set, mixed $state): void
    {
        if (! $state) {
            $set('kingdom', null);

            return;
        }

        $ie = IntroEventRecord::with('taxon')->find($state);
        $kingdom = $ie?->taxon?->kingdom;

        $set('kingdom', $kingdom);
        self::defaultCoverageUnit($get, $set, $kingdom);
    }

    /**
     * Pre-selects the extent unit that matches the kingdom — area for plants
     * and algae, a head count for everything else.
     *
     * Only fills an empty unit: once a reporter has picked one it is their
     * call, and re-picking the species must not quietly overwrite it.
     *
     * @param  Get  $get  The reactive form state getter.
     * @param  Set  $set  The form state setter utility.
     * @param  string|null  $kingdom  The selected species' kingdom.
     */
    public static function defaultCoverageUnit(Get $get, Set $set, ?string $kingdom): void
    {
        if ($kingdom === null || filled($get('coverage_unit'))) {
            return;
        }

        $set('coverage_unit', self::isPlantKingdom($kingdom)
            ? CoverageUnit::SQUARE_METRES->value
            : CoverageUnit::INDIVIDUALS->value);
    }

    /**
     * Whether the kingdom is recorded as cover rather than as a head count.
     *
     * @param  string|null  $kingdom  The kingdom (e.g. "Plantae", "Chromista") or null.
     */
    public static function isPlantKingdom(?string $kingdom): bool
    {
        return in_array($kingdom, ['Plantae', 'Chromista'], true);
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

        return ' — '.(self::isPlantKingdom($kingdom)
            ? $scale->getPlantDescription()
            : $scale->getAnimalDescription());
    }
}
