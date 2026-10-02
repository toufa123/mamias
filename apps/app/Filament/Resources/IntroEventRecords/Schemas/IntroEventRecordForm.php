<?php

namespace App\Filament\Resources\IntroEventRecords\Schemas;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\IntroEventRecord;
use App\Models\Taxon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Icetalker\FilamentStepper\Forms\Components\Stepper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures the Filament form schema for intro event records.
 * Organises species identification, references, subregion records,
 * pathways and EICAT impact assessments into sections and tabs. Occurrences
 * are moderated on their own page (OccurrenceResource), not here.
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
                                ->label('NIS Scientific Name')
                                // How many species are left to choose from on a new event.
                                ->afterLabel(fn (string $operation): array => $operation === 'create' ? [
                                    Text::make((string) Taxon::whereDoesntHave('introEvents')->count())
                                        ->badge()
                                        ->color('gray')
                                        ->tooltip('Species without an introduction event')
                                        // A badge is taller than the label line; without this the input
                                        // sits a few pixels below its neighbours in the row.
                                        ->extraAttributes(['style' => 'margin-block: -0.25rem;']),
                                ] : [])
                                // Only species without an introduction event yet, so a species
                                // is never entered twice; an edited event keeps its own species.
                                ->relationship(
                                    'taxon',
                                    'scientificname',
                                    modifyQueryUsing: fn (Builder $query, ?IntroEventRecord $record): Builder => $query->where(
                                        fn (Builder $query): Builder => $query
                                            ->whereDoesntHave('introEvents')
                                            ->when($record?->taxon_id, fn (Builder $query, int $taxonId): Builder => $query->orWhereKey($taxonId)),
                                    ),
                                )
                                // The name alone from lg up, where this field shrinks to a fifth
                                // of the row; the authority returns wherever the field is wide,
                                // and is always in the tooltip. WoRMS authorities carry their own
                                // parentheses: none added.
                                ->getOptionLabelFromRecordUsing(fn ($record): string => '<span title="'.e(trim($record->scientificname.' '.$record->authority)).'"><i>'.e($record->scientificname).'</i>'.($record->authority ? '<span class="lg:hidden"> '.e($record->authority).'</span>' : '').'</span>')
                                ->allowHtml()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(fn (?IntroEventRecord $record): ?string => IntroEventRecordsTable::recordedAs($record))
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
                            // Stored as names (as the importers write them), picked by code.
                            CountrySelectWithMedPriority::make('first_country')
                                ->storeNames()
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
                        Textarea::make('pathway_check')
                            ->label('Pathway check (EASIN)')
                            ->helperText('Clear once the pathways have been checked.')
                            ->rows(3)
                            ->visible(fn (?string $state): bool => filled($state))
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
                                        TableColumn::make('NIS Status'),
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
                                        // May differ from the event's: a species validated in one
                                        // sub-region can be questionable or debatable in another.
                                        Select::make('nis_status')
                                            ->label('NIS Status')
                                            ->options(NisStatus::class)
                                            ->placeholder('Select status')
                                            ->columnSpan(2),
                                        Select::make('establishment_status')
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
                                            ->extraAttributes(['style' => 'display:flex; justify-content:center;'])
                                            ->extraInputAttributes(['style' => 'width:5rem; min-width:0; flex:none; text-align:center;']),
                                    ])
                                    ->columns(7),
                            ]),
                        Tab::make('Countries')
                            ->icon('tabler-flag')
                            ->schema([
                                Text::make('Every Mediterranean country the species has been recorded in, including the first one.')
                                    ->color('gray'),
                                Repeater::make('countryRecords')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Country'),
                                        TableColumn::make('Establishment Success'),
                                        TableColumn::make('Year of 1st Record'),
                                        TableColumn::make('Reference'),
                                    ])
                                    ->addActionLabel('Add Country Record')
                                    ->compact()
                                    ->minItems(0)
                                    ->relationship()
                                    ->schema([
                                        // Stored as names, like first_country, so the two can be compared.
                                        CountrySelectWithMedPriority::make('country')
                                            ->storeNames()
                                            ->displayFlags(true)
                                            ->imageFlags()
                                            ->required()
                                            ->distinct()
                                            ->label('Country'),
                                        Select::make('establishment_status')
                                            ->label('Establishment Success')
                                            ->options(EstablishmentStatus::class),
                                        Stepper::make('first_record_year')
                                            ->label('Year of 1st Record')
                                            ->minValue(1800)
                                            ->maxValue(now()->year)
                                            ->step(1)
                                            ->default(now()->year)
                                            ->extraAttributes(['style' => 'display:flex; justify-content:center;'])
                                            ->extraInputAttributes(['style' => 'width:5rem; min-width:0; flex:none; text-align:center;']),
                                        Select::make('literature_id')
                                            ->relationship('literature', 'short_ref')
                                            ->searchable()
                                            ->label('Reference')
                                            ->placeholder('Search literature...'),
                                    ]),
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
                                            ->placeholder('Select type')
                                            ->required(),
                                        Select::make('category')
                                            ->label('CBD Category')
                                            ->options(CbdPathwayCategory::class)
                                            ->placeholder('Select category')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn ($set) => $set('subcategory', null)),
                                        // Optional: a pathway is often known only at category level.
                                        Select::make('subcategory')
                                            ->label('Subcategory')
                                            ->placeholder('Optional')
                                            ->disabled(fn ($get): bool => blank($get('category')))
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

                    ]),
            ]);
    }
}
