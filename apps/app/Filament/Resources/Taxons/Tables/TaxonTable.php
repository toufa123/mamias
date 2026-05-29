<?php

namespace App\Filament\Resources\Taxons\Tables;

use App\Enums\Environment;
use App\Jobs\FetchEasinIdsJob;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\Taxon;
use App\Models\User;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
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
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class TaxonTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['creator', 'editor'])
            )
            ->defaultSort('id', 'asc')
            ->extremePaginationLinks()
            ->deferLoading()
            ->searchable(false)
            ->striped()
            ->columns([
                self::getIdColumn(),
                self::getAphiaIdColumn(),
                self::getEasinIdColumn(),
                self::getScientificNameColumn(),
                self::getWormsStatusColumn(),
                self::getCatalogueStatusColumn(),
                self::getRankColumn(),
                self::getKingdomColumn(),
                self::getPhylumColumn(),
                self::getLsidColumn(),
                self::getEnvironmentsColumn(),
                self::getFetchedAtColumn(),
                self::getCreatedAtColumn(),
                self::getUpdatedAtColumn(),
                self::getCreatedByColumn(),
                self::getUpdatedByColumn(),
            ])
            ->extraAttributes(['class' => '[&_.fi-ta-record]:py-1 [&_.fi-ta-cell]:py-1'])
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->columnManagerTriggerAction(fn (Action $action) => $action->slideOver())
            ->filters([
                TrashedFilter::make(),
                self::getScientificNameFilter(),
                self::getKingdomFilter(),
                self::getPhylumFilter(),
                self::getRankFilter(),
                self::getEnvironmentsFilter(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordAction(ViewAction::class)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalWidth('7xl')
                        ->modalHeading(fn ($record) => trim(($record->scientificname ?? '').' '.($record->authority ?? '')) ?: 'Taxon'),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('fetch_from_worms')
                        ->label('Fetch from WoRMS')
                        ->icon(TablerIcon::CloudDownload)
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Fetch taxonomy from WoRMS')
                        ->modalDescription('This will update the classification data for the selected species from the WoRMS database.')
                        ->action(function (Collection $records, $livewire) {
                            FetchTaxaFromWormsJob::dispatch(
                                $records->pluck('id')->all(),
                                auth()->id()
                            );

                            $livewire->dispatch('worms-fetch-started');

                            Notification::make()
                                ->title('WoRMS Sync Started')
                                ->body('The taxonomy update for '.$records->count().' species is now running in the background.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('fetch_easin_ids')
                        ->label('Fetch EASIN IDs')
                        ->icon(TablerIcon::CloudDownload)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Fetch EASIN IDs')
                        ->modalDescription('This will search for and update EASIN IDs for the selected species using their scientific names.')
                        ->action(function (Collection $records, $livewire) {
                            FetchEasinIdsJob::dispatch(
                                $records->pluck('id')->all(),
                                auth()->id()
                            );

                            $livewire->dispatch('easin-fetch-started');

                            Notification::make()
                                ->title('EASIN Fetch Started')
                                ->body('The EASIN ID lookup for '.$records->count().' species is now running in the background.')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getIdColumn(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable();
    }

    protected static function getAphiaIdColumn(): TextColumn
    {
        return TextColumn::make('aphia_id')
            ->label('Aphia ID')
            ->icon(TablerIcon::Link)
            ->sortable()
            ->url(fn ($record) => $record->url)
            ->openUrlInNewTab();
    }

    protected static function getEasinIdColumn(): TextColumn
    {
        return TextColumn::make('Easin_id')
            ->label('EASIN ID')
            ->icon(TablerIcon::Link)
            ->sortable()
            ->url(fn ($record) => $record->Easin_id ? "https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/{$record->Easin_id}" : null)
            ->openUrlInNewTab();
    }

    protected static function getScientificNameColumn(): TextColumn
    {
        return TextColumn::make('scientificname')
            ->label('Scientific Name')
            ->sortable()
            ->html()
            ->formatStateUsing(fn ($state, $record) => self::formatScientificName($state, $record->rank))
            ->tooltip(fn ($record) => self::getScientificNameTooltip($record));
    }

    protected static function getWormsStatusColumn(): TextColumn
    {
        return TextColumn::make('worms_status')
            ->label('WoRMS Status')
            ->badge()
            ->sortable()
            ->searchable();
    }

    protected static function getCatalogueStatusColumn(): TextColumn
    {
        return TextColumn::make('catalogue_status')
            ->label('Catalogue Status')
            ->badge()
            ->sortable();
    }

    protected static function getRankColumn(): TextColumn
    {
        return TextColumn::make('rank')->label('Rank')->sortable();
    }

    protected static function getKingdomColumn(): TextColumn
    {
        return TextColumn::make('kingdom')->label('Kingdom')->sortable();
    }

    protected static function getPhylumColumn(): TextColumn
    {
        return TextColumn::make('phylum')->label('Phylum')->sortable();
    }

    protected static function getLsidColumn(): TextColumn
    {
        return TextColumn::make('lsid')
            ->label('LSID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getEnvironmentsColumn(): TextColumn
    {
        return TextColumn::make('environments')
            ->label('Environment')
            ->wrap()
            ->badge()
            ->color(function (mixed $state): string|array|null {
                if ($state instanceof Environment) {
                    $environment = $state;
                } elseif (is_string($state)) {
                    $environment = Environment::fromLabelOrValue($state);
                } else {
                    $environment = null;
                }
                $color = $environment?->getColor();
                if (is_string($color) && str_starts_with($color, '#')) {
                    return Color::hex($color);
                }

                return $color ?? 'gray';
            })
            ->formatStateUsing(function (mixed $state): string {
                if ($state instanceof Environment) {
                    $environment = $state;
                } elseif (is_string($state)) {
                    $environment = Environment::fromLabelOrValue($state);
                } else {
                    $environment = null;
                }

                return $environment?->getLabel() ?? (string) $state;
            })
            ->sortable();
    }

    protected static function getFetchedAtColumn(): TextColumn
    {
        return TextColumn::make('fetched_at')
            ->label('Fetched At')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Created At')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getUpdatedAtColumn(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->label('Updated At')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getCreatedByColumn(): TextColumn
    {
        return TextColumn::make('creator.first_name')
            ->label('Created By')
            ->icon(TablerIcon::User)
            ->placeholder('—')
            ->formatStateUsing(fn ($state, $record) => self::formatUserWithRole($record->creator))
            ->sortable()
            ->searchable(['users.first_name', 'users.last_name'])
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getUpdatedByColumn(): TextColumn
    {
        return TextColumn::make('editor.first_name')
            ->label('Updated By')
            ->icon(TablerIcon::UserEdit)
            ->placeholder('—')
            ->formatStateUsing(fn ($state, $record) => self::formatUserWithRole($record->editor))
            ->sortable()
            ->searchable(['users.first_name', 'users.last_name'])
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function formatUserWithRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($name === '') {
            return null;
        }

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->all() : [];
        if (! empty($roles)) {
            $name .= ' ('.implode(', ', $roles).')';
        }

        return $name;
    }

    protected static function formatScientificName(?string $state, ?string $rank): ?string
    {
        if (! $state) {
            return $state;
        }

        $isItalic = in_array(strtolower((string) $rank), ['genus', 'species', 'subspecies', 'variety', 'form'], true);
        if (! $isItalic) {
            return $state;
        }

        $formatted = preg_replace_callback('/(\'[^\']+\')/', function ($matches) {
            return '<span class="not-italic">'.$matches[1].'</span>';
        }, $state);

        return "<span class='italic font-serif'>{$formatted}</span>";
    }

    protected static function getScientificNameTooltip(Taxon $record): ?HtmlString
    {
        $name = $record->scientificname ?? '';
        $authority = $record->authority ?? '';
        $isItalic = in_array(strtolower((string) $record->rank), ['genus', 'species', 'subspecies', 'variety', 'form'], true);

        $fullName = trim("{$name} {$authority}");
        if ($fullName === '') {
            return null;
        }

        if ($isItalic) {
            $formattedName = preg_replace_callback('/(\'[^\']+\')/', function ($matches) {
                return '<span class="not-italic">'.$matches[1].'</span>';
            }, $name);

            return new HtmlString(
                "<span class='italic font-serif'>{$formattedName}</span>".
                ($authority !== '' ? " <span class='not-italic'> {$authority}</span>" : '')
            );
        }

        return new HtmlString($fullName);
    }

    protected static function getScientificNameFilter(): SelectFilter
    {
        return SelectFilter::make('scientificname')
            ->label('Scientific Name')
            ->searchable()
            ->options(fn () => self::getDistinctFilterOptions('scientificname'));
    }

    protected static function getKingdomFilter(): SelectFilter
    {
        return SelectFilter::make('kingdom')
            ->label('Kingdom')
            ->searchable()
            ->modifyFormFieldUsing(fn (FormSelect $field) => $field->live())
            ->options(fn () => self::getDistinctFilterOptions('kingdom'));
    }

    protected static function getPhylumFilter(): SelectFilter
    {
        return SelectFilter::make('phylum')
            ->label('Phylum')
            ->searchable()
            ->options(function ($livewire): array {
                $selectedKingdom = data_get($livewire->getTableFilterFormState('kingdom'), 'value');

                return self::getDistinctFilterOptions('phylum', filled($selectedKingdom) ? $selectedKingdom : null);
            });
    }

    protected static function getRankFilter(): SelectFilter
    {
        return SelectFilter::make('rank')
            ->label('Rank')
            ->searchable()
            ->options(fn () => self::getDistinctFilterOptions('rank'));
    }

    protected static function getDistinctFilterOptions(string $column, ?string $kingdom = null): array
    {
        static $options = [];
        $cacheKey = $column.'|'.($kingdom ?? '*');

        if (! array_key_exists($cacheKey, $options)) {
            $options[$cacheKey] = Taxon::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->when(filled($kingdom), fn (Builder $query) => $query->where('kingdom', $kingdom))
                ->select($column)
                ->distinct()
                ->orderBy($column)
                ->pluck($column, $column)
                ->all();
        }

        return $options[$cacheKey];
    }

    protected static function getEnvironmentsFilter(): SelectFilter
    {
        return SelectFilter::make('environments')
            ->label('Environment')
            ->multiple()
            ->searchable()
            ->options(collect(Environment::cases())
                ->mapWithKeys(fn (Environment $environment) => [$environment->value => $environment->getLabel()])
                ->all())
            ->query(function (Builder $query, array $data): Builder {
                $values = array_filter($data['values'] ?? []);
                if ($values === []) {
                    return $query;
                }

                return $query->where(function (Builder $subQuery) use ($values) {
                    foreach ($values as $value) {
                        $subQuery->orWhereJsonContains('environments', $value);
                    }
                });
            });
    }
}
