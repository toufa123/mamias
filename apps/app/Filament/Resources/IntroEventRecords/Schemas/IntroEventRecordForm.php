<?php

namespace App\Filament\Resources\IntroEventRecords\Schemas;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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
                                    ->label(fn (Get $get) => 'Year of 1st Introduction: '.($get('first_introduction_year') ?? now()->year))
                                    ->minValue(1800)
                                    ->maxValue(now()->year)
                                    ->step(1)
                                    ->default(now()->year)
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
                                    ->required()
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
                                            ->options(Subregion::class)
                                            ->required(),
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
                                            ->options(PathwayType::class)
                                            ->required(),
                                        Select::make('category')
                                            ->label('CBD Category')
                                            ->options(CbdPathwayCategory::class)
                                            ->required()
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
                                            })
                                            ->required(),
                                        Select::make('uncertainty')
                                            ->label('Uncertainty')
                                            ->options(DataQuality::class),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
