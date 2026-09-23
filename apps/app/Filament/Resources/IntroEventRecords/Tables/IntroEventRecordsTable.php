<?php

namespace App\Filament\Resources\IntroEventRecords\Tables;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EstablishmentStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Models\IntroEventRecord;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\Slider;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentExportAction\Actions\FilamentExportHeaderAction;
use JeffersonGoncalves\FilamentExportAction\Enums\ExportFormat;

/**
 * Configures the Filament table for intro event records.
 * Displays taxon, introduction year, country, NIS status and
 * establishment status, plus the review reason on the Needs review tab.
 */
class IntroEventRecordsTable
{
    /**
     * @param  Table  $table  The table to configure.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            // Trashed species included: deleting a species from the catalogue
            // leaves its events here, and they must still say which species.
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['taxon' => fn ($taxonQuery) => $taxonQuery->withTrashed()])
                ->withCount('occurrences'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('taxon.scientificname')
                    ->label('NIS Scientific Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->html()
                    // The name is italic, the authority is not — that is the
                    // nomenclatural convention, and it is why the two are
                    // wrapped separately rather than the whole string slanted.
                    ->formatStateUsing(fn ($state, $record): string => "<span class='italic'>".e((string) $state).'</span>'
                        .($record?->taxon?->authority ? ' ('.e((string) $record->taxon->authority).')' : '')
                        .($record?->taxon?->trashed() ? ' — species deleted from catalogue' : '')),
                TextColumn::make('first_introduction_year')
                    ->label('1st Year of Introduction')
                    ->wrapHeader()
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->visibleFrom('md'),
                // One badge per country rather than a joined string: a record can
                // have several co-first countries, and each is a value in its own
                // right — the same values the country filter offers.
                TextColumn::make('first_country')
                    ->label('1st Country of Introduction')
                    ->wrapHeader()
                    ->badge()
                    ->placeholder('-')
                    ->searchable()
                    ->visibleFrom('lg'),
                TextColumn::make('nis_status')
                    ->label('NIS Status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('establishment_status')
                    ->label('Establishment Status')
                    ->wrapHeader()
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('lg'),
                // Only on the Needs review tab, where it is the point of the tab;
                // anywhere else it would be empty for nearly every row.
                TextColumn::make('review_reason')
                    ->label('Review Reason')
                    ->state(fn (IntroEventRecord $record): array => self::getReviewReasons($record))
                    ->listWithLineBreaks()
                    ->placeholder('No reason recorded')
                    ->visible(fn ($livewire): bool => $livewire->activeTab === 'needs_review'),
                //                TextColumn::make('data_source_type')
                //                    ->badge()
                //                    ->searchable(),
                TextColumn::make('created_by')
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_by')
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // 12-column grid so the two rows divide evenly: four record-level
            // filters at 3 columns each, then the four pathway filters at 3.
            // An int column count applies from the `lg` breakpoint up, so both
            // rows still stack on narrow screens.
            ->filters([
                self::getYearFilter()
                    ->columnSpan(3),
                self::getCountryFilter()
                    ->columnSpan(3),
                SelectFilter::make('establishment_status')
                    ->label('Establishment Status')
                    ->multiple()
                    ->options(EstablishmentStatus::class)
                    ->columnSpan(3),
                self::getRelatedEnumFilter('subregion', 'EcAp Subregion', 'subregionRecords', 'subregion', Subregion::class)
                    ->columnSpan(3),
                self::getRelatedEnumFilter('pathway_category', 'CBD Pathway Category', 'pathwayRecords', 'category', CbdPathwayCategory::class)
                    // Live so the subcategory options narrow as soon as a
                    // category is picked, rather than on the next round trip.
                    ->modifyFormFieldUsing(fn (FormSelect $field): FormSelect => $field->live())
                    ->columnSpan(3),
                self::getRelatedEnumFilter('pathway_subcategory', 'Pathway Subcategory', 'pathwayRecords', 'subcategory', CbdPathwaySubcategory::class)
                    ->options(fn ($livewire): array => self::getPathwaySubcategoryOptions($livewire))
                    ->columnSpan(3),
                self::getRelatedEnumFilter('pathway_type', 'Pathway Type', 'pathwayRecords', 'pathway_type', PathwayType::class)
                    ->columnSpan(3),
                self::getRelatedEnumFilter('pathway_uncertainty', 'Uncertainty', 'pathwayRecords', 'uncertainty', DataQuality::class)
                    ->columnSpan(3),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(12)
            // Filters have no extraAttributes() of their own in Filament v5, so
            // the alignment is set on the filters grid from the table wrapper.
            // The child combinator matters: every filter contains its own
            // .fi-grid, and centring those would collapse the taller fields.
            ->extraAttributes(['class' => '[&_.fi-ta-filters>.fi-grid]:items-center [&_.fi-ta-record]:py-1 [&_.fi-ta-cell]:py-1'])
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->columnManagerTriggerAction(fn (Action $action) => $action->slideOver())
            ->recordActions([
                ActionGroup::make([
                    // No view page: the modal renders the edit form read-only,
                    // subregion and pathway repeaters included. Wide because
                    // that form lays out five fields per row.
                    ViewAction::make()
                        ->modalWidth('6xl'),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    // Only a permanent delete can hit the database: subregion
                    // and pathway rows cascade, but occurrences restrict it —
                    // field observations are not collateral. Disabled rather
                    // than left to fail with a foreign-key error.
                    ForceDeleteAction::make()
                        ->disabled(fn (IntroEventRecord $record): bool => $record->hasOccurrences())
                        ->tooltip(fn (IntroEventRecord $record): ?string => $record->hasOccurrences()
                            ? 'Has occurrences. Delete or reassign them first.'
                            : null),
                ]),
            ])
            ->toolbarActions([
                FilamentExportHeaderAction::make()
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx, ExportFormat::Pdf])
                    ->defaultFormat(ExportFormat::Xlsx)
                    ->withFilters()
                    ->withSearch()
                    ->withSort(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('taxon.scientificname');
    }

    /**
     * Two-handle slider spanning the years actually present in the data.
     *
     * A slider can never be empty — it always reports a value — so "unset" has
     * to be expressed as the handles sitting at both ends of the track, and the
     * query is skipped in that case. Without that the filter would be
     * permanently active and would drop every record whose year is unknown.
     */
    protected static function getYearFilter(): Filter
    {
        [$earliest, $latest] = self::getYearBounds();

        return Filter::make('first_introduction_year')
            ->form([
                Slider::make('years')
                    ->label('1st Year of Introduction')
                    ->range(minValue: $earliest, maxValue: $latest)
                    ->default([$earliest, $latest])
                    ->step(1)
                    ->fillTrack([false, true, false])
                    ->tooltips()
                    ->pips(),
            ])
            ->query(function (Builder $query, array $data) use ($earliest, $latest): Builder {
                [$from, $until] = self::getSelectedYears($data, $earliest, $latest);

                if ($from <= $earliest && $until >= $latest) {
                    return $query;
                }

                return $query->whereBetween('first_introduction_year', [$from, $until]);
            })
            ->indicateUsing(function (array $data) use ($earliest, $latest): ?string {
                [$from, $until] = self::getSelectedYears($data, $earliest, $latest);

                if ($from <= $earliest && $until >= $latest) {
                    return null;
                }

                return "1st Year of Introduction: {$from} – {$until}";
            });
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function getSelectedYears(array $data, int $earliest, int $latest): array
    {
        $years = array_map(intval(...), (array) ($data['years'] ?? []));

        return [$years[0] ?? $earliest, $years[1] ?? $latest];
    }

    /**
     * The track spans the recorded years rather than a fixed 1800–today, so the
     * handles stay useful instead of dragging across decades with no records.
     *
     * @return array{0: int, 1: int}
     */
    protected static function getYearBounds(): array
    {
        $bounds = IntroEventRecord::query()
            ->whereNotNull('first_introduction_year')
            ->selectRaw('MIN(first_introduction_year) AS earliest, MAX(first_introduction_year) AS latest')
            ->first();

        return [
            (int) ($bounds?->earliest ?? 1800),
            (int) ($bounds?->latest ?: now()->year),
        ];
    }

    /**
     * first_country is a JSON array (a species can arrive in more than one
     * country at once), so it is matched with whereJsonContains rather than a
     * plain where, and the options are unpacked from the stored arrays.
     */
    protected static function getCountryFilter(): SelectFilter
    {
        return SelectFilter::make('first_country')
            ->label('1st Country of Introduction')
            ->multiple()
            ->searchable()
            ->options(fn (): array => IntroEventRecord::query()
                ->whereNotNull('first_country')
                ->pluck('first_country')
                ->flatten()
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn (string $country): array => [$country => $country])
                ->all())
            ->query(function (Builder $query, array $data): Builder {
                $values = array_filter($data['values'] ?? []);

                if ($values === []) {
                    return $query;
                }

                return $query->where(function (Builder $subQuery) use ($values): void {
                    foreach ($values as $value) {
                        $subQuery->orWhereJsonContains('first_country', $value);
                    }
                });
            });
    }

    /**
     * The "Label: raw value" pairs the importer wrote when it flagged the row,
     * one per unresolved value. Empty when the record was flagged some other
     * way, which the column's placeholder then says.
     *
     * @return list<string>
     */
    protected static function getReviewReasons(IntroEventRecord $record): array
    {
        foreach (preg_split('/\R/', (string) $record->notes) as $line) {
            if (str_starts_with($line, IntroEventRecordImporter::REVIEW_NOTE_PREFIX)) {
                return explode('; ', substr($line, strlen(IntroEventRecordImporter::REVIEW_NOTE_PREFIX)));
            }
        }

        return [];
    }

    /**
     * Subcategories of the currently selected CBD categories, or all of them
     * while no category is selected.
     *
     * The CBD numbering already carries the hierarchy — subcategory "3.1"
     * belongs to category "3" — so the pairing is read off the values instead
     * of being duplicated in a map that could drift from the enums.
     *
     * @return array<string, string>
     */
    protected static function getPathwaySubcategoryOptions(mixed $livewire): array
    {
        $categories = array_filter((array) data_get(
            $livewire->getTableFilterFormState('pathway_category'),
            'values',
            [],
        ));

        $options = [];

        foreach (CbdPathwaySubcategory::cases() as $case) {
            if ($categories !== [] && ! in_array(explode('.', $case->value)[0], $categories, true)) {
                continue;
            }

            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Filter on an enum column of a has-many child (subregion or pathway rows).
     * Matching is existential: a record is kept when any of its children carries
     * one of the selected values.
     *
     * @param  class-string<\BackedEnum>  $enum
     */
    protected static function getRelatedEnumFilter(string $name, string $label, string $relation, string $column, string $enum): SelectFilter
    {
        return SelectFilter::make($name)
            ->label($label)
            ->multiple()
            ->options($enum)
            ->query(function (Builder $query, array $data) use ($relation, $column): Builder {
                $values = array_filter($data['values'] ?? []);

                if ($values === []) {
                    return $query;
                }

                return $query->whereHas(
                    $relation,
                    fn (Builder $related): Builder => $related->whereIn($column, $values),
                );
            });
    }
}
