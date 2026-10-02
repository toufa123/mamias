<?php

namespace App\Filament\Resources\Taxons\Schemas;

use App\Enums\Catalogue_Status;
use App\Enums\Environment;
use App\Enums\Worms_Status;
use App\Services\EasinService;
use App\Services\TaxonNormalizer;
use App\Services\TaxonService;
use App\Services\WormsService;
use DefStudio\SearchableInput\DTO\SearchResult;
use DefStudio\SearchableInput\Forms\Components\SearchableInput;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;

/**
 * Configures the Filament form schema for taxon records.
 * Provides general information, taxonomic classification, status
 * and validation, and synonyms sections with WoRMS integration.
 */
class TaxonForm
{
    /**
     * Makes the two sections sharing the top row the same height. The form
     * grid stretches each column, but not the card inside: these attributes
     * land on the section's wrapper, which fills the column, and as a
     * one-cell grid it stretches the card to fill it in turn.
     */
    private const FILL_COLUMN = ['style' => 'height: 100%; display: grid;'];

    /**
     * @param  Schema  $schema  The form schema to configure.
     * @return Schema The configured schema instance.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getGeneralInformationSection(),
                self::getTaxonomicClassificationSection(),
                self::getStatusAndValidationSection(),
                self::getSynonymsSection(),
            ]);

    }

    /**
     * @return Section The general information section with scientific name, authority, Aphia ID, EASIN ID, etc.
     */
    protected static function getGeneralInformationSection(): Section
    {
        return Section::make('General Information')
            ->description('Scientific name, authority, and primary identifiers.')
            ->icon('tabler-tag')
            ->extraAttributes(self::FILL_COLUMN)
            ->schema([
                Grid::make(12)
                    ->schema([
                        ...self::getScientificNameFields(),
                        TextInput::make('authority')
                            ->label('Authority')
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('url')
                            ->label('WoRMS URL')
                            ->url()
                            ->maxLength(255)
                            ->columnSpan(6),

                        TextInput::make('lsid')
                            ->label('LSID')
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('aphia_id')
                            ->label('Aphia ID')
                            ->numeric()
                            ->columnSpan(6)
                            ->live(),
                        TextInput::make('Easin_id')
                            ->label('EASIN ID')
                            ->suffixActions([
                                // Next to the ID rather than as helper text under it, which
                                // made this field taller than Aphia ID on the same row.
                                Action::make('view_easin_factsheet')
                                    ->icon('tabler-external-link')
                                    ->tooltip('View EASIN factsheet')
                                    ->url(fn ($get): ?string => filled($get('Easin_id')) ? 'https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/'.rawurlencode((string) $get('Easin_id')) : null)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($get): bool => filled($get('Easin_id'))),
                                Action::make('fetch_easin_id')
                                    ->icon('tabler-refresh')
                                    ->tooltip('Fetch EASIN ID')
                                    ->action(function ($set, $get, EasinService $easinService) {
                                        $scientificName = $get('scientificname');
                                        if (! $scientificName) {
                                            Notification::make()
                                                ->title('Scientific Name Missing')
                                                ->warning()
                                                ->send();

                                            return;
                                        }
                                        $easinId = $easinService->fetchEasinId($scientificName);
                                        if ($easinId) {
                                            $set('Easin_id', $easinId);
                                            Notification::make()
                                                ->title('EASIN ID Fetched')
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('EASIN ID Not Found')
                                                ->danger()
                                                ->send();
                                        }
                                    }),
                            ])->columnSpan(6),
                        // WoRMS's verdict on the name, side by side: what it accepts
                        // instead and why. Read-only: every WoRMS sync overwrites both.
                        // Inputs aligned to the bottom, so a label that wraps on a narrow
                        // window does not drop its input below the other.
                        Grid::make(['default' => 1, 'md' => 2])
                            ->extraAttributes(['style' => 'align-items: end;'])
                            ->columnSpan(12)
                            ->schema([
                                TextInput::make('proposed_accepted_name')
                                    ->label('WoRMS accepted name')
                                    ->placeholder('Set by WoRMS if unaccepted')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('unacceptreason')
                                    ->label('Unaccept reason')
                                    ->placeholder('Given by WoRMS with it')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                        Toggle::make('is_extinct')
                            ->label('Extinct')
                            ->inline(false)
                            ->onColor('danger')
                            ->onIcon('tabler-skull')
                            ->live()
                            ->columnSpan(3)
                            ->visible(fn ($operation, $get) => $operation === 'create' || (bool) $get('is_extinct')),
                    ]),
            ]);
    }

    /** @return array<int, Component> */
    private static function getScientificNameFields(): array
    {
        return [
            Hidden::make('scientificname_editable')
                ->default(false)
                ->dehydrated(false)
                ->visible(fn ($operation) => $operation === 'edit'),
            SearchableInput::make('scientificname')
                ->label('Scientific Name')
                ->placeholder('Type at least 3 characters to search...')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->columnSpan(6)
                ->visible(fn ($operation) => $operation === 'create')
                ->searchUsing(function (string $search) {
                    if (strlen($search) < 3) {
                        return [];
                    }

                    return collect(app(WormsService::class)->searchSpecies($search))
                        ->map(fn ($record) => SearchResult::make($record['scientificname'], "{$record['scientificname']} {$record['authority']}")
                            ->withData($record, null))
                        ->toArray();
                })
                ->onItemSelected(function (SearchResult $item, Set $set, TaxonService $taxonService) {
                    $data = $taxonService->formatWormsDataForForm($item->toArray()['data']);
                    foreach ($data as $key => $value) {
                        if ($key === 'synonyms_data' && is_array($value)) {
                            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                        $set($key, $value);
                    }
                    $set('fetched_at', now());
                }),
            TextInput::make('scientificname')
                ->label('Scientific Name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->disabled(fn (Get $get): bool => ! (bool) $get('scientificname_editable'))
                ->columnSpan(6)
                ->visible(fn ($operation) => $operation === 'edit')
                ->suffixActions([
                    Action::make('toggle_scientificname_edit')
                        ->icon(fn (Get $get): string => (bool) $get('scientificname_editable')
                            ? 'tabler-lock-open'
                            : 'tabler-lock')
                        ->tooltip(fn (Get $get): string => (bool) $get('scientificname_editable')
                            ? 'Disable editing Scientific Name'
                            : 'Enable editing Scientific Name')
                        ->action(function (Set $set, Get $get): void {
                            $set('scientificname_editable', ! (bool) $get('scientificname_editable'));
                        }),
                    Action::make('try_taxon_match')
                        ->icon('tabler-wand')
                        ->color('info')
                        ->iconButton()
                        ->tooltip('Try WoRMS Taxon Match (fuzzy search)')
                        ->visible(fn ($operation, $get) => $operation === 'edit' && in_array($get('catalogue_status'), [Catalogue_Status::no_data_from_worms->value, Catalogue_Status::no_data_from_worms]))
                        ->action(function (Set $set, Get $get, TaxonService $taxonService) {
                            $taxonService->tryTaxonMatch($get, $set);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Try WoRMS Taxon Match')
                        ->modalDescription('This will search WoRMS using fuzzy matching for a potential match.')
                        ->modalSubmitActionLabel('Search'),
                    self::makeSyncWithAcceptedNameFromWormsAction(),
                ]),
        ];
    }

    /**
     * @return Section The taxonomic classification section with kingdom, phylum, class, order, family, genus, rank, and environment.
     */
    protected static function getTaxonomicClassificationSection(): Section
    {
        return Section::make('Taxonomic Classification')
            ->description('Full taxonomic hierarchy and rank.')
            ->icon('tabler-school')
            ->extraAttributes(self::FILL_COLUMN)
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('kingdom')->label('Kingdom')->maxLength(255),
                        TextInput::make('phylum')->label('Phylum')->maxLength(255),
                        TextInput::make('class')->label('Class')->maxLength(255),
                        TextInput::make('order')->label('Order')->maxLength(255),
                        TextInput::make('family')->label('Family')->maxLength(255),
                        TextInput::make('genus')->label('Genus')->maxLength(255),
                        TextInput::make('rank')
                            ->label('Taxonomic Rank')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Select::make('environments')
                            ->label('Environment')
                            ->columnSpan(2)
                            ->multiple()
                            ->options(Environment::class),

                    ]),
            ]);
    }

    /**
     * @return Section The status and validation section with WoRMS status, catalogue status, fetch timestamps, and notes. The WoRMS unaccept reason sits in General Information, with the accepted name.
     */
    protected static function getStatusAndValidationSection(): Section
    {
        return Section::make('Status & Validation')
            ->description('WoRMS/Catalogue status, fetch metadata, and notes.')
            ->icon('tabler-circle-check')
            ->afterHeader([
                self::makeSyncWithAcceptedNameFromWormsAction(
                    name: 'sync_with_proposed_accepted_name_from_worms',
                    requiresAcceptedName: true,
                    useAcceptedNameFromNotes: true,
                ),
            ])
            ->schema([
                // Two pairs: the two statuses, then the two dates.
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('worms_status')
                            ->label('WoRMS Status')
                            ->options(Worms_Status::class)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state === Worms_Status::accepted->value || $state === Worms_Status::alternative_representation->value) {
                                    $set('catalogue_status', Catalogue_Status::checked_accepted->value);
                                } elseif ($state) {
                                    $set('catalogue_status', Catalogue_Status::checked_not_accepted->value);
                                }
                            }),
                        Select::make('catalogue_status')
                            ->label('Catalogue Status')
                            ->options(Catalogue_Status::class)
                            ->live(),
                        DateTimePicker::make('fetched_at')
                            ->label('First Fetched At')
                            ->native(false),
                        DateTimePicker::make('updated_at')
                            ->label('Updated At')
                            ->native(false),
                    ]),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Section The synonyms section displaying WoRMS synonym data in a JSON viewer.
     */
    protected static function getSynonymsSection(): Section
    {
        return Section::make(fn ($get) => 'Synonyms ('.count(self::resolveSynonyms($get('synonyms_data'))).')')
            ->description('Synonyms fetched from WoRMS.')
            ->icon('tabler-circle-plus')
            ->compact()
            ->collapsible()
            ->schema([
                Html::make(function ($get) {
                    if (count(self::resolveSynonyms($get('synonyms_data'))) > 0) {
                        return '';
                    }

                    return $get('fetched_at')
                        ? '<span class="text-sm text-gray-500">No Synonyms found.</span>'
                        : '<span class="text-sm text-gray-500">No synonyms yet.</span>';
                }),
                PrettyJsonField::make('synonyms_data')
                    ->label('Synonymised names')
                    ->extraAttributes([
                        'style' => 'max-height: 250px;',
                    ])
                    ->copyable()
                    ->dehydrateStateUsing(fn ($state, TaxonNormalizer $taxonNormalizer) => $taxonNormalizer->normalizeSynonyms($state))
                    ->visible(fn ($get) => count(self::resolveSynonyms($get('synonyms_data'))) > 0),
            ]);
    }

    private static function resolveSynonyms(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private static function makeSyncWithAcceptedNameFromWormsAction(
        string $name = 'sync_with_accepted_name_from_worms',
        bool $showLabel = false,
        bool $requiresAcceptedName = false,
        bool $useAcceptedNameFromNotes = false,
    ): Action {
        return Action::make($name)
            ->icon($useAcceptedNameFromNotes ? 'tabler-arrow-back-up' : 'tabler-refresh')
            ->iconButton()
            ->tooltip($useAcceptedNameFromNotes ? 'Fetch data from WoRMS using the proposed accepted name' : 'Fetch data from WoRMS')
            ->visible(fn ($operation, $get): bool => $operation === 'edit' && (! $requiresAcceptedName || $get('proposed_accepted_name') !== null)
            )
            ->disabled(fn ($get): bool => $requiresAcceptedName && $get('proposed_accepted_name') === null
            )
            ->action(fn ($set, $get, TaxonService $taxonService) => $taxonService->syncWithWorms($get, $set, $useAcceptedNameFromNotes));
    }
}
