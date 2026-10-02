<?php

namespace App\Filament\Resources\IntroEventRecords\Schemas;

use App\Filament\Resources\IntroEventRecords\Tables\IntroEventRecordsTable;
use App\Models\IntroEventRecord;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Illuminate\Support\HtmlString;

/**
 * An introduction event's view window, in the catalogue view's pattern
 * (TaxonInfolist): the event's facts in one row, its reference, a callout
 * only when something needs attention, then tabs of one fixed height for
 * its sub-regions, pathways, and notes & audit. Read-only entries rather
 * than the edit form disabled, so no form controls (clear buttons, year
 * steppers, placeholders) show in a view. Follows DESIGN-SYSTEM.md: years
 * in mono, statuses from their enums, attention callouts in the Unresolved
 * gray with an icon rather than amber.
 */
class IntroEventRecordInfolist
{
    private const TAB_HEIGHT = '18rem';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::getFactsGrid(),
                self::getReferenceEntry(),
                self::getReviewCallout(),
                self::getPathwayCheckCallout(),
                Tabs::make('Event details')
                    ->tabs([
                        self::getSubregionsTab(),
                        self::getCountriesTab(),
                        self::getPathwaysTab(),
                        self::getNotesTab(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * When and where the species was first recorded, and its two statuses.
     */
    protected static function getFactsGrid(): Grid
    {
        return Grid::make(['default' => 2, 'md' => 4])
            ->schema([
                TextEntry::make('first_introduction_year')
                    ->label('First Mediterranean record')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('Unknown'),
                TextEntry::make('first_country')
                    ->label('First country')
                    ->separator(', ')
                    ->placeholder('Unknown'),
                TextEntry::make('nis_status')
                    ->label('NIS status')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('establishment_status')
                    ->label('Establishment')
                    ->badge()
                    ->placeholder('—'),
            ]);
    }

    /**
     * The reference the event is recorded from, linking to its DOI or source.
     */
    protected static function getReferenceEntry(): TextEntry
    {
        return TextEntry::make('literature.short_ref')
            ->label('Reference')
            ->icon(fn (IntroEventRecord $record): string => self::referenceUrl($record) ? 'tabler-external-link' : 'tabler-book')
            ->color(fn (IntroEventRecord $record): string => self::referenceUrl($record) ? 'primary' : 'gray')
            ->url(fn (IntroEventRecord $record): ?string => self::referenceUrl($record))
            ->openUrlInNewTab()
            ->tooltip(fn (IntroEventRecord $record): ?string => $record->literature?->full_ref)
            ->placeholder('No reference linked');
    }

    /**
     * Shown only for an event the import flagged, with the reasons it gave.
     */
    protected static function getReviewCallout(): Callout
    {
        return Callout::make('Flagged for review')
            ->key('review_callout')
            ->description(fn (IntroEventRecord $record): string => implode(' · ', IntroEventRecordsTable::getReviewReasons($record)) ?: 'No reason recorded.')
            ->color('gray')
            ->icon('tabler-flag')
            ->visible(fn (IntroEventRecord $record): bool => (bool) $record->needs_review);
    }

    /**
     * Shown only while the EASIN pathway comparison is still open.
     */
    protected static function getPathwayCheckCallout(): Callout
    {
        return Callout::make('Pathway check (EASIN)')
            ->key('pathway_check_callout')
            ->description(fn (IntroEventRecord $record): HtmlString => new HtmlString(nl2br(e((string) $record->pathway_check))))
            ->color('gray')
            ->icon('tabler-route')
            ->visible(fn (IntroEventRecord $record): bool => filled($record->pathway_check));
    }

    protected static function getSubregionsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Sub-regions')
            ->icon('tabler-map')
            ->badge(fn (IntroEventRecord $record): int => $record->subregionRecords()->count())
            ->schema([
                self::scrolling([
                    RepeatableEntry::make('subregionRecords')
                        ->hiddenLabel()
                        ->placeholder('No EcAp sub-region recorded.')
                        ->table([
                            TableColumn::make('EcAp sub-region'),
                            TableColumn::make('NIS status'),
                            TableColumn::make('Establishment'),
                            TableColumn::make('First record'),
                        ])
                        ->schema([
                            // A category, not a status: neutral, and not teal, which reads as a link.
                            TextEntry::make('subregion')->color('gray'),
                            TextEntry::make('nis_status')->badge()->placeholder('—'),
                            TextEntry::make('establishment_status')->badge()->placeholder('—'),
                            TextEntry::make('first_arrival_year')->fontFamily(FontFamily::Mono)->placeholder('—'),
                        ]),
                ]),
            ]);
    }

    protected static function getCountriesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Countries')
            ->icon('tabler-flag')
            ->badge(fn (IntroEventRecord $record): int => $record->countryRecords()->count())
            ->schema([
                self::scrolling([
                    RepeatableEntry::make('countryRecords')
                        ->hiddenLabel()
                        ->placeholder('No country recorded.')
                        ->table([
                            TableColumn::make('Country'),
                            TableColumn::make('Establishment'),
                            TableColumn::make('First record'),
                            TableColumn::make('Reference'),
                        ])
                        ->schema([
                            TextEntry::make('country')->color('gray'),
                            TextEntry::make('establishment_status')->badge()->placeholder('—'),
                            TextEntry::make('first_record_year')->fontFamily(FontFamily::Mono)->placeholder('—'),
                            TextEntry::make('literature.short_ref')->placeholder('—'),
                        ]),
                ]),
            ]);
    }

    protected static function getPathwaysTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Pathways')
            ->icon('tabler-route')
            ->badge(fn (IntroEventRecord $record): int => $record->pathwayRecords()->count())
            ->schema([
                self::scrolling([
                    RepeatableEntry::make('pathwayRecords')
                        ->hiddenLabel()
                        ->placeholder('No pathway recorded.')
                        ->table([
                            TableColumn::make('CBD category'),
                            TableColumn::make('Subcategory'),
                            TableColumn::make('Type'),
                            TableColumn::make('Uncertainty'),
                        ])
                        ->schema([
                            TextEntry::make('category'),
                            TextEntry::make('subcategory')->placeholder('—'),
                            TextEntry::make('pathway_type')->placeholder('—'),
                            TextEntry::make('uncertainty')->badge()->placeholder('—'),
                        ]),
                ]),
            ]);
    }

    /**
     * Free-text notes, then who created and last changed the event.
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

    /**
     * One fixed height for every tab's content, scrolling inside it, so the
     * window keeps its size whichever tab is open.
     *
     * @param  array<int, mixed>  $components
     */
    protected static function scrolling(array $components): Group
    {
        return Group::make($components)->extraAttributes(['style' => 'height: '.self::TAB_HEIGHT.'; overflow-y: auto;']);
    }

    /** The reference's DOI, else its source link. */
    private static function referenceUrl(IntroEventRecord $record): ?string
    {
        $literature = $record->literature;

        return match (true) {
            $literature === null => null,
            filled($literature->doi) => 'https://doi.org/'.$literature->doi,
            filled($literature->link) => $literature->link,
            default => null,
        };
    }
}
