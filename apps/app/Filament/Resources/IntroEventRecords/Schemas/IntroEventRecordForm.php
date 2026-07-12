<?php

namespace App\Filament\Resources\IntroEventRecords\Schemas;

use App\Enums\AcforScale;
use App\Enums\AssessmentScale;
use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EicatCategory;
use App\Enums\EicatMechanism;
use App\Enums\EstablishmentStatus;
use App\Enums\Habitat;
use App\Enums\NisStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Forms\MultipleMarkersMapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;

/**
 * Configures the Filament form schema for intro event records.
 * Organises species identification, references, subregion records,
 * pathways, occurrences, and EICAT impact assessments into sections and tabs.
 */
class IntroEventRecordForm
{
    /**
     * @param  Schema  $schema  The form schema to configure.
     * @return Schema The configured schema instance.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Species Identification')
                    ->icon('tabler-fish')
                    ->description('Select the non-indigenous species and classify its introduction status.')
                    ->compact()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3, 'lg' => 5])->schema([
                            Select::make('taxon_id')
                                ->label('NIS Taxon')
                                ->relationship('taxon', 'scientificname')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "<i>{$record->scientificname}</i>".($record->authority ? " ({$record->authority})" : ''))
                                ->allowHtml()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(['default' => 1, 'md' => 3, 'lg' => 1]),
                            Select::make('nis_status')
                                ->options(NisStatus::class)
                                ->label('NIS Status')
                                ->native(false)
                                ->placeholder('Select status')
                                ->columnSpan(1),
                            Select::make('establishment_status')
                                ->options(EstablishmentStatus::class)
                                ->label('Establishment Status')
                                ->native(false)
                                ->placeholder('Select status')
                                ->columnSpan(1),
                            Stepper::make('first_introduction_year')
                                ->label('Year of 1st Introduction')
                                ->minValue(1800)
                                ->maxValue(now()->year)
                                ->step(1)
                                ->default(now()->year)
                                ->columnSpan(1),
                            CountrySelectWithMedPriority::make('first_country')
                                ->displayFlags(true)
                                ->imageFlags()
                                ->multiple()
                                ->label('1st Country of Introduction')
                                ->columnSpan(['default' => 1, 'md' => 3, 'lg' => 1]),
                        ]),
                    ]),

                Section::make('References & Notes')
                    ->icon('tabler-notes')
                    ->compact()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('literature_id')
                            ->relationship('literature', 'short_ref')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Citations / Literature')
                            ->placeholder('Search literature...')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional observations or context...')
                            ->columnSpanFull(),
                    ]),

                Tabs::make('Details')
                    ->persistTabInQueryString()
                    ->contained(false)
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('EcAp Subregions')
                            ->icon('tabler-map')
                            ->schema([
                                Repeater::make('subregionRecords')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('EcAp Sub-region'),
                                        TableColumn::make('Establishment Success'),
                                        TableColumn::make('Year of 1st Introduction'),
                                    ])
                                    ->addActionLabel('Add Subregion Record')
                                    ->compact()
                                    ->minItems(0)
                                    ->maxItems(4)
                                    ->relationship()
                                    ->schema([
                                        Select::make('subregion')
                                            ->label('EcAp Sub-region')
                                            ->placeholder('Select subregion')
                                            ->searchable()
                                            ->preload()
                                            ->options(Subregion::class)
                                            ->columnSpan(2),
                                        Select::make('nis_status')
                                            ->label('Establishment Success')
                                            ->options(EstablishmentStatus::class)
                                            ->columnSpan(2),
                                        Stepper::make('first_arrival_year')
                                            ->label('Year of 1st Introduction')
                                            ->minValue(1800)
                                            ->maxValue(now()->year)
                                            ->step(1)
                                            ->default(now()->year)
                                            ->columnSpan(1)
                                            ->extraAttributes(['class' => 'max-w-28']),
                                    ])
                                    ->columns(5),
                            ]),
                        Tab::make('Pathways')
                            ->icon('tabler-route')
                            ->schema([
                                Repeater::make('pathwayRecords')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Pathway Type'),
                                        TableColumn::make('CBD Category'),
                                        TableColumn::make('Subcategory'),
                                        TableColumn::make('Uncertainty'),
                                    ])
                                    ->addActionLabel('Add Pathway')
                                    ->compact()
                                    ->minItems(0)
                                    ->maxItems(4)
                                    ->relationship()
                                    ->schema([
                                        Select::make('pathway_type')
                                            ->label('Pathway Type')
                                            ->options(PathwayType::class)
                                            ->placeholder('Select type'),
                                        Select::make('category')
                                            ->label('CBD Category')
                                            ->options(CbdPathwayCategory::class)
                                            ->placeholder('Select category')
                                            ->live()
                                            ->afterStateUpdated(fn ($set) => $set('subcategory', null)),
                                        Select::make('subcategory')
                                            ->label('Subcategory')
                                            ->placeholder('Select subcategory')
                                            ->options(function ($get) {
                                                $category = $get('category');

                                                if (! $category) {
                                                    return [];
                                                }

                                                $categoryValue = $category instanceof CbdPathwayCategory ? $category->value : $category;

                                                return collect(CbdPathwaySubcategory::cases())
                                                    ->filter(fn (CbdPathwaySubcategory $case) => str_starts_with($case->value, (string) $categoryValue.'.'))
                                                    ->mapWithKeys(fn (CbdPathwaySubcategory $case) => [$case->value => $case->getLabel()]);
                                            }),
                                        Select::make('uncertainty')
                                            ->label('Uncertainty')
                                            ->options(DataQuality::class)
                                            ->placeholder('Select level'),
                                    ]),
                            ]),
                        Tab::make('Occurrences')
                            ->icon('tabler-map-pin')
                            ->schema(static::occurrenceSchema()),
                        Tab::make('EICAT Impact')
                            ->icon('tabler-alert-triangle')
                            ->schema([
                                Repeater::make('eicatAssessments')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Category'),
                                        TableColumn::make('Mechanism'),
                                        TableColumn::make('Confidence'),
                                        TableColumn::make('Literature'),
                                    ])
                                    ->addActionLabel('Add EICAT Assessment')
                                    ->compact()
                                    ->minItems(0)
                                    ->maxItems(10)
                                    ->relationship()
                                    ->schema([
                                        Select::make('category')
                                            ->label('Impact Category')
                                            ->options(EicatCategory::class)
                                            ->placeholder('Select category')
                                            ->required()
                                            ->columnSpan(1),
                                        Select::make('mechanism')
                                            ->label('Impact Mechanism')
                                            ->options(EicatMechanism::class)
                                            ->placeholder('Select mechanism')
                                            ->required()
                                            ->columnSpan(1),
                                        Select::make('confidence')
                                            ->label('Confidence')
                                            ->options(DataQuality::class)
                                            ->default('medium')
                                            ->columnSpan(1),
                                        Select::make('assessment_scale')
                                            ->label('Assessment Scale')
                                            ->options(AssessmentScale::class)
                                            ->placeholder('Select scale')
                                            ->helperText('Geographic scope of this assessment')
                                            ->columnSpan(1),
                                        Select::make('literature_id')
                                            ->label('Supporting Literature')
                                            ->relationship('literature', 'short_ref')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(1),
                                        Textarea::make('rationale')
                                            ->label('Rationale')
                                            ->placeholder('Describe the evidence and justification for this assessment...')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        DatePicker::make('assessed_at')
                                            ->label('Assessment Date')
                                            ->default(now())
                                            ->columnSpan(1),
                                    ])
                                    ->columns(5),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<Component>
     */
    public static function occurrenceSchema(): array
    {
        return [
            Repeater::make('occurrences')
                ->hiddenLabel()
                ->addActionLabel('Add Occurrence')
                ->compact()
                ->minItems(0)
                ->maxItems(4)
                ->relationship()
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn (): int => auth()->id()),
                    Grid::make(['default' => 1, 'md' => 2, 'lg' => 4])->schema([
                        Stepper::make('depth')
                            ->label('Depth (m)')
                            ->minValue(0)
                            ->maxValue(11000)
                            ->step(1),
                        Select::make('acfor_scale')
                            ->label('Abundance (ACFOR)')
                            ->options(AcforScale::class)
                            ->native(false)
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
                    MultipleMarkersMapPicker::make('location')
                        ->hiddenLabel()
                        ->height(250)
                        ->center([36, 14])
                        ->zoom(5)
                        ->tileLayersUrl(TileLayer::OpenStreetMap)
                        ->pickMarker(fn (Marker $marker) => $marker->red())
                        ->extraAttributes(['x-on:x-modal-opened.window' => 'setTimeout(() => mapCore?.map?.invalidateSize(), 50); setTimeout(() => mapCore?.map?.invalidateSize(), 300);'])
                        ->columnSpanFull(),
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
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
