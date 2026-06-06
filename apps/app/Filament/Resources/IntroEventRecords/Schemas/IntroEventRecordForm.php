<?php

namespace App\Filament\Resources\IntroEventRecords\Schemas;

use App\Enums\AcforScale;
use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EstablishmentStatus;
use App\Enums\Habitat;
use App\Enums\NisStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use App\Filament\Forms\MultipleMarkersMapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;
use Nakanakaii\FilamentCountries\Forms\Components\CountrySelect;

class IntroEventRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Introduction Event')
                    ->icon('tabler-fish')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                Select::make('taxon_id')
                                    ->label('NIS Taxon')
                                    ->relationship('taxon', 'scientificname')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->scientificname.($record->authority ? ' ('.$record->authority.')' : ''))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),
                                Slider::make('first_introduction_year')
                                    ->label('Year of 1st Introduction')
                                    ->minValue(1800)
                                    ->maxValue(now()->year)
                                    ->step(1)
                                    ->live()
                                    ->columnSpan(2),
                                CountrySelect::make('first_country')
                                    ->displayFlags(true)
                                    ->imageFlags()
                                    ->multiple()
                                    ->label('1st Country of Introduction')
                                    ->columnSpan(2),

                            ])->columnSpanFull(),
                        Grid::make(5)
                            ->schema([
                                Select::make('nis_status')
                                    ->options(NisStatus::class)
                                    ->label('NIS Status')
                                    ->columnSpan(1),
                                Select::make('establishment_status')
                                    ->options(EstablishmentStatus::class)
                                    ->label('Establishment Status')
                                    ->columnSpan(1),

                                Select::make('literature_id')
                                    ->relationship('literature', 'short_ref')
                                    ->multiple()
                                    ->preload()
                                    ->label('Citations/Literature')
                                    ->columnSpan(3),
                            ])->columnSpanFull(),

                        RichEditor::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),

                    ]),
                Tabs::make('Details')
                    ->tabs([
                        Tab::make('EcAp Subregions')
                            ->schema([
                                Repeater::make('subregionRecords')
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
                                            ->placeholder('Select Subregion')
                                            ->searchable()
                                            ->preload()
                                            ->options(Subregion::class),
                                        Select::make('nis_status')
                                            ->label('Establishment Success')
                                            ->options(NisStatus::class),
                                        Slider::make('first_arrival_year')
                                            ->label('Year of 1st Introduction')
                                            // ->hint(fn (?int $state) => $state ?? now()->year)
                                            // ->tooltips(true)
                                            ->minValue(1800)
                                            ->maxValue(now()->year)
                                            ->step(1)
                                            ->default(now()->year)
                                            ->live(),

                                    ])
                                    ->columns(4),
                            ]),
                        Tab::make('Pathways')
                            ->schema([
                                Repeater::make('pathwayRecords')
                                    ->table([
                                        TableColumn::make('Pathway Type'),
                                        TableColumn::make('CBD Category'),
                                        TableColumn::make('Subcategory'),
                                        TableColumn::make('Uncertainty'),
                                    ])
                                    ->addActionLabel('Add Pathways')
                                    ->compact()
                                    ->minItems(0)
                                    ->maxItems(4)
                                    ->relationship()
                                    ->schema([
                                        Select::make('pathway_type')
                                            ->label('Pathway Type')
                                            ->options(PathwayType::class),
                                        Select::make('category')
                                            ->label('CBD Category')
                                            ->options(CbdPathwayCategory::class)
                                            ->live()
                                            ->afterStateUpdated(fn ($set) => $set('subcategory', null)),
                                        Select::make('subcategory')
                                            ->label('Subcategory')
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
                                            ->options(DataQuality::class),
                                    ]),
                            ]),
                        Tab::make('Occurrences')
                            ->schema([
                                Repeater::make('occurrences')
                                    ->addActionLabel('Add Occurrence')
                                    ->compact()
                                    ->minItems(0)
                                    ->relationship()
                                    ->schema([
                                        Hidden::make('user_id')
                                            ->default(fn (): int => auth()->id()),
                                        Grid::make(3)->schema([
                                            Stepper::make('depth')
                                                ->label('Depth (m)')
                                                ->minValue(0)
                                                ->maxValue(11000)
                                                ->step(1),
                                            Select::make('acfor_scale')
                                                ->label('Abundance (ACFOR Scale)')
                                                ->options(AcforScale::class)
                                                ->native(false)
                                                ->placeholder('Select ACFOR scale'),
                                            Select::make('habitats')
                                                ->label('Habitats')
                                                ->multiple()
                                                ->options(Habitat::class)
                                                ->native(false)
                                                ->placeholder('Select habitats'),
                                        ])->columnSpanFull(),
                                        DateTimePicker::make('observed_at')
                                            ->label('Date & Time of Observation')
                                            ->required()
                                            ->default(now())
                                            ->seconds(false)
                                            ->displayFormat('Y-m-d H:i'),
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
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
