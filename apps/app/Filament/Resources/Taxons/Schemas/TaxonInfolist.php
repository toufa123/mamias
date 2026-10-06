<?php

namespace App\Filament\Resources\Taxons\Schemas;

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use App\Models\Taxon;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The catalogue's view modal, compact: the classification on one line, the
 * identifiers and statuses in two rows of four, a warning only when WoRMS disagrees with the name, then tabs for synonyms,
 * references, and notes & audit, all of one fixed height that scrolls.
 */
class TaxonInfolist
{
    /** WoRMS record page for an Aphia ID. */
    private const WORMS_URL = 'https://www.marinespecies.org/aphia.php?p=taxdetails&id=';

    private const TAB_HEIGHT = '22rem';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::getClassificationEntry(),
                self::getDetailsGrid(),
                self::getNameWarning(),
                Tabs::make('Taxon Details')
                    ->tabs([
                        self::getSynonymsTab(),
                        self::getReferencesTab(),
                        self::getNotesTab(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * The public data page's version (App\Livewire\NisSpecies): no notes &
     * audit tab, which holds internal notes and editors' names.
     */
    public static function configurePublic(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::getClassificationEntry(),
                self::getDetailsGrid(),
                self::getNameWarning(),
                Tabs::make('Taxon Details')
                    ->tabs([
                        self::getSynonymsTab(),
                        self::getReferencesTab(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Kingdom › phylum › class › order › family › genus, the genus in italics,
     * led by the kingdom's icon.
     */
    protected static function getClassificationEntry(): TextEntry
    {
        return TextEntry::make('classification')
            ->hiddenLabel()
            ->state(function (Taxon $record): ?HtmlString {
                $ranks = array_filter([$record->kingdom, $record->phylum, $record->class, $record->order, $record->family]);
                $path = array_map(fn (string $rank): string => e($rank), $ranks);

                if (filled($record->genus)) {
                    $path[] = '<em>'.e($record->genus).'</em>';
                }

                return $path === [] ? null : new HtmlString(implode(' <span class="text-gray-400">›</span> ', $path));
            })
            ->color('gray')
            ->icon(fn (Taxon $record): string => Taxon::kingdomIcon($record->kingdom));
    }

    /**
     * Identifiers then statuses, four per row: Aphia ID, EASIN ID, LSID, rank,
     * environment, WoRMS status, catalogue status and the last WoRMS sync.
     */
    protected static function getDetailsGrid(): Grid
    {
        return Grid::make(['default' => 2, 'md' => 4])
            ->schema([
                TextEntry::make('aphia_id')
                    ->label('Aphia ID')
                    ->icon('tabler-external-link')
                    ->color('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->numeric(thousandsSeparator: '')
                    ->url(fn ($state): ?string => $state ? self::WORMS_URL.$state : null)
                    ->openUrlInNewTab()
                    ->placeholder('Not in WoRMS'),
                TextEntry::make('Easin_id')
                    ->label('EASIN ID')
                    ->icon('tabler-external-link')
                    ->color('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn ($state): ?string => $state ? "https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/{$state}" : null)
                    ->openUrlInNewTab()
                    ->placeholder('Not in EASIN'),
                TextEntry::make('lsid')
                    ->label('LSID')
                    ->icon('tabler-copy')
                    ->copyable()
                    ->copyableState(fn ($state): ?string => $state)
                    ->formatStateUsing(fn (): string => 'Copy LSID')
                    ->tooltip(fn ($state): ?string => $state)
                    ->placeholder('—'),
                TextEntry::make('rank')
                    ->label('Rank')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                // A category, not a status: neutral (DESIGN-SYSTEM.md, "Scales and categories").
                TextEntry::make('environments')
                    ->label('Environment')
                    ->badge()
                    ->color('gray')
                    ->separator(',')
                    ->placeholder('—'),
                TextEntry::make('worms_status')
                    ->label('WoRMS status')
                    ->badge()
                    ->icon(fn ($state) => $state instanceof Worms_Status ? $state->getIcon() : null)
                    ->placeholder('—'),
                TextEntry::make('catalogue_status')
                    ->label('Catalogue status')
                    ->badge()
                    ->icon(fn ($state) => $state instanceof Catalogue_Status ? $state->getIcon() : null)
                    ->placeholder('—'),
                TextEntry::make('fetched_at')
                    ->label('Last WoRMS sync')
                    ->fontFamily(FontFamily::Mono)
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('Never'),
            ]);
    }

    /**
     * Shown only when WoRMS points away from the catalogue name.
     */
    protected static function getNameWarning(): Callout
    {
        return Callout::make(fn (Taxon $record): string => filled($record->proposed_accepted_name)
            ? 'WoRMS proposes another accepted name'
            : 'WoRMS does not accept this name')
            ->description(fn (Taxon $record): HtmlString => new HtmlString(implode(' · ', array_filter([
                filled($record->proposed_accepted_name) ? 'Accepted name: <em>'.e($record->proposed_accepted_name).'</em>' : null,
                filled($record->unacceptreason) ? 'Reason: '.e($record->unacceptreason) : null,
            ]))))
            ->key('name_warning')
            // Unresolved, not amber: amber is Established, and `warning` is for
            // actions and notifications only (DESIGN-SYSTEM.md). The icon keeps
            // the meaning off colour alone.
            ->color('gray')
            ->icon('tabler-alert-triangle')
            ->visible(fn (Taxon $record): bool => filled($record->proposed_accepted_name) || filled($record->unacceptreason));
    }

    /**
     * WoRMS synonyms as a table: name, authority, status and reason.
     */
    protected static function getSynonymsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Synonyms')
            ->icon('tabler-list-details')
            ->badge(fn (Taxon $record): int => count((array) $record->synonyms_data))
            ->schema([
                self::scrolling([
                    RepeatableEntry::make('synonyms')
                        ->hiddenLabel()
                        ->state(fn (Taxon $record): array => array_map(fn (array $synonym): array => [
                            'name' => filled($synonym['AphiaID'] ?? null)
                                ? '<a href="'.self::WORMS_URL.(int) $synonym['AphiaID'].'" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400"><em>'.e((string) ($synonym['scientificname'] ?? '')).'</em></a>'
                                : '<em>'.e((string) ($synonym['scientificname'] ?? '')).'</em>',
                            'authority' => $synonym['authority'] ?? null,
                            // The WoRMS status enum, so its colour, icon and label match everywhere else.
                            'status' => Worms_Status::tryFrom((string) ($synonym['status'] ?? '')) ?? ($synonym['status'] ?? null),
                            'unacceptreason' => $synonym['unacceptreason'] ?? null,
                        ], array_values(array_filter((array) $record->synonyms_data, 'is_array'))))
                        ->placeholder('No synonyms found in WoRMS.')
                        ->table([
                            TableColumn::make('Name'),
                            TableColumn::make('Authority'),
                            TableColumn::make('Status'),
                            TableColumn::make('Reason'),
                        ])
                        ->schema([
                            TextEntry::make('name')->html(),
                            TextEntry::make('authority')->color('gray')->placeholder('—'),
                            TextEntry::make('status')->badge()->placeholder('—'),
                            TextEntry::make('unacceptreason')->placeholder('—'),
                        ]),
                ]),
            ]);
    }

    /**
     * Approved literature for the taxon — original description, first records,
     * supporting references — oldest first, with a BibTeX download of the list.
     *
     * @return Tabs\Tab The references tab.
     */
    protected static function getReferencesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('References')
            ->icon('tabler-books')
            ->badge(fn (Taxon $record): int => $record->literatureReferences()->count())
            ->schema([
                self::scrolling([
                    Actions::make([
                        Action::make('downloadBibtex')
                            ->label('Download BibTeX')
                            ->icon('tabler-download')
                            ->color('gray')
                            ->size('sm')
                            ->visible(fn (Taxon $record): bool => $record->literatureReferences()->isNotEmpty())
                            ->action(function (Taxon $record) {
                                $bibtex = $record->literatureReferences()
                                    ->map(fn (array $row) => $row['literature']->toBibtex())
                                    ->implode("\n\n");

                                return response()->streamDownload(
                                    fn () => print ($bibtex."\n"),
                                    Str::slug($record->scientificname ?: 'taxon').'-references.bib',
                                    ['Content-Type' => 'application/x-bibtex'],
                                );
                            }),
                    ])->alignEnd(),
                    RepeatableEntry::make('literature_references')
                        ->hiddenLabel()
                        ->state(fn (Taxon $record): array => $record->literatureReferences()
                            ->map(fn (array $row) => [
                                'role' => $row['role'],
                                'short_ref' => $row['literature']->short_ref,
                                'year' => $row['literature']->year,
                                'full_ref' => $row['literature']->full_ref,
                                'doi' => $row['literature']->doi,
                                // The DOI link already points to the publisher.
                                'link' => $row['literature']->doi ? null : $row['literature']->link,
                                'pdf' => $row['literature']->file_path ? Storage::disk('public')->url($row['literature']->file_path) : null,
                                'retracted' => $row['literature']->is_retracted ? 'Retracted' : null,
                            ])
                            ->all())
                        ->placeholder('No approved references are linked to this taxon yet.')
                        ->contained(false)
                        ->schema([
                            Grid::make(12)->schema([
                                TextEntry::make('short_ref')
                                    ->hiddenLabel()
                                    ->weight('bold')
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextEntry::make('role')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Original description' => 'info',
                                        'First record' => 'success',
                                        default => 'gray',
                                    })
                                    ->columnSpan(['default' => 6, 'md' => 3]),
                                TextEntry::make('retracted')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('danger')
                                    ->icon('tabler-alert-octagon')
                                    ->hidden(fn ($state): bool => blank($state))
                                    ->columnSpan(['default' => 6, 'md' => 2]),
                                TextEntry::make('full_ref')
                                    ->hiddenLabel()
                                    ->color('gray')
                                    ->columnSpanFull(),
                                TextEntry::make('doi')
                                    ->hiddenLabel()
                                    ->icon('tabler-link')
                                    ->url(fn ($state): ?string => $state ? "https://doi.org/{$state}" : null)
                                    ->openUrlInNewTab()
                                    ->hidden(fn ($state): bool => blank($state))
                                    ->columnSpan(['default' => 12, 'md' => 6]),
                                TextEntry::make('link')
                                    ->hiddenLabel()
                                    ->icon('tabler-external-link')
                                    ->formatStateUsing(fn (): string => 'Source')
                                    ->url(fn ($state): ?string => $state)
                                    ->openUrlInNewTab()
                                    ->hidden(fn ($state): bool => blank($state))
                                    ->columnSpan(['default' => 6, 'md' => 3]),
                                TextEntry::make('pdf')
                                    ->hiddenLabel()
                                    ->icon('tabler-file-type-pdf')
                                    ->formatStateUsing(fn (): string => 'PDF')
                                    ->url(fn ($state): ?string => $state)
                                    ->openUrlInNewTab()
                                    ->hidden(fn ($state): bool => blank($state))
                                    ->columnSpan(['default' => 6, 'md' => 3]),
                            ]),
                        ]),
                ]),
            ]);
    }

    /**
     * One fixed height for every tab's content, scrolling inside it, so the
     * modal keeps its size whichever tab is open.
     *
     * @param  array<int, mixed>  $components
     */
    protected static function scrolling(array $components): Group
    {
        return Group::make($components)->extraAttributes(['style' => 'height: '.self::TAB_HEIGHT.'; overflow-y: auto;']);
    }

    /**
     * Free-text notes, then who created and last changed the record.
     */
    protected static function getNotesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Notes & audit')
            ->icon('tabler-history')
            ->schema([
                self::scrolling([
                    TextEntry::make('notes')
                        ->hiddenLabel()
                        ->placeholder('No notes.'),
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime()
                                ->suffix(fn ($record): string => $record->creator ? ' · '.$record->creator->getFormattedNameWithRoles() : '')
                                ->placeholder('—'),
                            TextEntry::make('updated_at')
                                ->label('Last updated')
                                ->dateTime()
                                ->suffix(fn ($record): string => $record->editor ? ' · '.$record->editor->getFormattedNameWithRoles() : '')
                                ->placeholder('—'),
                        ]),
                ]),
            ]);
    }
}
