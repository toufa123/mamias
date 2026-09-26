<?php

namespace App\Filament\Resources\Taxons\Tables;

use App\Enums\Catalogue_Status;
use App\Enums\Environment;
use App\Filament\Actions\DiscussionParticipantsAction;
use App\Filament\Resources\Taxons\TaxonResource;
use App\Jobs\FetchEasinIdsJob;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\Taxon;
use App\Models\User;
use App\Services\AcceptedNameConfidence;
use App\Services\TaxonService;
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
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Html;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use JeffersonGoncalves\FilamentExportAction\Actions\FilamentExportHeaderAction;
use JeffersonGoncalves\FilamentExportAction\Enums\ExportFormat;
use Kirschbaum\Commentions\Filament\Actions\CommentsTableAction;
use RuntimeException;
use Zvizvi\UserFields\Components\UserColumn;
use Zvizvi\UserFields\Components\UserSelect;

/**
 * Configures the Filament table for taxon records.
 * Displays ID, Aphia ID, EASIN ID, scientific name, WoRMS status,
 * catalogue status, rank, kingdom, phylum, LSID, environments,
 * fetch/creation/update timestamps, and creator/editor columns
 * with WoRMS sync, duplicate detection, and bulk fetch actions.
 *
 * Columns drop out by breakpoint so the row fits without sideways scrolling:
 * ID, scientific name and both statuses always show; rank, Aphia ID and
 * environment join from `lg` (tablet landscape); EASIN ID, kingdom and phylum
 * only from `xl` (desktop).
 */
class TaxonTable
{
    /**
     * @param  Table  $table  The table to configure.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['creator', 'editor', 'nameReviewer'])
            )
            ->defaultSort('id', 'asc')
            ->extremePaginationLinks()
            ->deferLoading()
            ->searchable(false)
            ->striped()
            ->columns([
                self::getIdColumn(),
                self::getScientificNameColumn(),
                self::getNameChangeConfidenceColumn(),
                UserColumn::make('nameReviewer')
                    ->label('Reviewer')
                    ->placeholder('—')
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'rename'),
                self::getAphiaIdColumn(),
                self::getEasinIdColumn(),
                self::getWormsStatusColumn(),
                self::getCatalogueStatusColumn(),
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
                    self::getMoveToAcceptedNameAction(),
                    self::getKeepCurrentNameAction(),
                    self::getSendForReviewAction(),
                    self::getDiscussionAction(),
                    DiscussionParticipantsAction::make(),
                    self::getUndoMoveAction(),
                    Action::make('sync_worms')
                        ->label('Sync WoRMS')
                        ->icon('tabler-cloud-download')
                        ->color('info')
                        ->visible(fn (Taxon $record): bool => $record->fetched_at === null || $record->fetched_at?->lt(now()->subDays(90)))
                        ->requiresConfirmation()
                        ->modalHeading('Sync with WoRMS')
                        ->modalDescription(fn (Taxon $record) => 'This will update classification data for '.($record->scientificname ?? 'this taxon').' from the WoRMS database.')
                        ->action(function (Taxon $record, $livewire) {
                            FetchTaxaFromWormsJob::dispatch([$record->id], auth()->id());
                            $livewire->dispatch('worms-fetch-started');
                            Notification::make()
                                ->title('WoRMS Sync Queued')
                                ->success()
                                ->send();
                        }),
                    Action::make('mark_accepted')
                        ->label('Mark as Accepted')
                        ->icon('tabler-circle-check')
                        ->color('success')
                        ->visible(fn (Taxon $record): bool => $record->catalogue_status !== Catalogue_Status::checked_accepted)
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Checked & Accepted')
                        ->modalDescription(fn (Taxon $record) => 'Set catalogue status to "Checked & accepted" for '.($record->scientificname ?? 'this taxon').'?')
                        ->action(function (Taxon $record) {
                            $record->update(['catalogue_status' => Catalogue_Status::checked_accepted]);
                            Notification::make()
                                ->title('Marked as accepted')
                                ->success()
                                ->send();
                        }),
                    Action::make('view_duplicates')
                        ->label('View Duplicates')
                        ->icon('tabler-copy')
                        ->color('warning')
                        ->visible(function (Taxon $record): bool {
                            return Taxon::where('scientificname', $record->scientificname)
                                ->where('id', '!=', $record->id)
                                ->exists();
                        })
                        ->modalWidth('2xl')
                        ->modalHeading(fn (Taxon $record) => 'Duplicates: '.($record->scientificname ?? 'unnamed'))
                        ->modalContent(function (Taxon $record): HtmlString {
                            $duplicates = Taxon::where('scientificname', $record->scientificname)
                                ->where('id', '!=', $record->id)
                                ->get();

                            $html = '<div class="space-y-2">';
                            $html .= '<p class="text-sm text-gray-500">The following records share the same scientific name:</p>';
                            $html .= '<ul class="list-disc pl-5 space-y-1">';
                            foreach ($duplicates as $dup) {
                                $label = $dup->catalogue_status?->getLabel() ?? '—';
                                $html .= '<li>#'.$dup->id.' — '.e($dup->scientificname ?? 'unnamed').' ('.e($label).')</li>';
                            }
                            $html .= '</ul></div>';

                            return new HtmlString($html);
                        }),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
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
                    self::getBulkMoveToAcceptedNameAction(),
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

    /**
     * Moves a taxon WoRMS no longer accepts to its accepted name (see
     * TaxonService::moveToAcceptedName()). The dialog shows the confidence
     * score with its reasons and warns when the move is a merge. A note is
     * required below "Safe to move". On the edit page it then opens the
     * taxon that holds the records, which is another one after a merge.
     */
    public static function getMoveToAcceptedNameAction(): Action
    {
        return Action::make('move_to_accepted_name')
            ->label('Move to accepted name')
            ->icon('tabler-arrow-right-circle')
            ->color('warning')
            ->visible(fn (Taxon $record): bool => filled($record->proposed_accepted_name) && ! $record->trashed())
            ->modalHeading(fn (Taxon $record): string => "Move {$record->scientificname} to {$record->proposed_accepted_name}")
            ->modalWidth('2xl')
            ->schema(fn (Taxon $record): array => [
                Html::make(fn (): HtmlString => self::moveSummary($record)),
                Textarea::make('note')
                    ->label('Note for the record')
                    ->placeholder('e.g. Authority checked against Galil 2009.')
                    ->rows(2)
                    ->required(fn (): bool => ($record->name_change_confidence ?? 0) < AcceptedNameConfidence::SAFE)
                    ->helperText(fn (): ?string => ($record->name_change_confidence ?? 0) < AcceptedNameConfidence::SAFE ? 'Required below "Safe to move": say what you checked.' : null),
            ])
            ->modalSubmitActionLabel('Move')
            ->action(function (Taxon $record, array $data, TaxonService $taxonService, $livewire): void {
                try {
                    $target = $taxonService->moveToAcceptedName($record, $data['note'] ?? null);
                } catch (RuntimeException $exception) {
                    Notification::make()->title('Not moved')->body($exception->getMessage())->danger()->send();

                    return;
                }

                $reconcile = $target->introEvents()->count() > 1;

                Notification::make()
                    ->title('Moved to the accepted name')
                    ->body("Now catalogued as {$target->scientificname}.".($reconcile ? ' It now has several first records to reconcile.' : ''))
                    ->status($reconcile ? 'warning' : 'success')
                    ->send();

                if ($livewire instanceof EditRecord) {
                    $livewire->redirect(TaxonResource::getUrl('edit', ['record' => $target]));
                }
            });
    }

    /**
     * Keeps the catalogued name instead of following WoRMS, with a required
     * reason; that WoRMS name is not proposed again.
     */
    public static function getKeepCurrentNameAction(): Action
    {
        return Action::make('keep_current_name')
            ->label('Keep current name')
            ->icon('tabler-lock')
            ->color('gray')
            ->visible(fn (Taxon $record): bool => filled($record->proposed_accepted_name) && ! $record->trashed())
            ->modalHeading(fn (Taxon $record): string => "Keep {$record->scientificname}")
            ->modalDescription(fn (Taxon $record): string => "WoRMS accepts {$record->proposed_accepted_name}. The catalogue keeps {$record->scientificname}, and this WoRMS name is not proposed again. A different accepted name later will be.")
            ->schema([
                Textarea::make('reason')
                    ->label('Why keep it')
                    ->placeholder('e.g. Follows the 2023 Mediterranean revision, not yet in WoRMS.')
                    ->required()
                    ->rows(3),
            ])
            ->modalSubmitActionLabel('Keep')
            ->action(function (Taxon $record, array $data, TaxonService $taxonService): void {
                $taxonService->keepCurrentName($record, $data['reason']);

                Notification::make()->title("Keeping {$record->scientificname}")->success()->send();
            });
    }

    /**
     * Asks a scientist to decide; they are notified and the question opens
     * the taxon's discussion.
     */
    public static function getSendForReviewAction(): Action
    {
        return Action::make('send_for_name_review')
            ->label('Send for expert review')
            ->icon('tabler-user-question')
            ->color('info')
            ->visible(fn (Taxon $record): bool => filled($record->proposed_accepted_name) && ! $record->trashed())
            ->modalHeading(fn (Taxon $record): string => "Ask a scientist about {$record->scientificname}")
            ->schema([
                UserSelect::make('reviewer_id')
                    ->label('Scientist')
                    ->options(fn (): array => DiscussionParticipantsAction::userOptions(
                        User::whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['super_admin', 'scientist']))
                            ->whereKeyNot(auth()->id())
                            ->get(),
                    ))
                    ->default(fn (Taxon $record): ?int => $record->name_reviewer_id)
                    ->searchable()
                    ->required(),
                Textarea::make('message')
                    ->label('Question (optional)')
                    ->placeholder('e.g. Does the Levantine population fit N. pinnicola?')
                    ->rows(3),
            ])
            ->modalSubmitActionLabel('Send')
            ->action(function (Taxon $record, array $data, TaxonService $taxonService): void {
                $reviewer = User::findOrFail($data['reviewer_id']);
                $taxonService->sendForNameReview($record, $reviewer, auth()->user(), $data['message'] ?? null);

                Notification::make()->title("Sent to {$reviewer->name}")->body('The question is in the Discussion.')->success()->send();
            });
    }

    /**
     * Reverses the latest move on this taxon (TaxonService::undoLastMove()).
     */
    public static function getUndoMoveAction(): Action
    {
        return Action::make('undo_name_move')
            ->label('Undo name move')
            ->icon('tabler-arrow-back-up')
            ->color('danger')
            ->visible(fn (Taxon $record): bool => ! $record->trashed() && app(TaxonService::class)->lastUndoableMove($record) !== null)
            ->requiresConfirmation()
            ->modalHeading('Undo the name move')
            ->modalDescription(function (Taxon $record): string {
                $move = app(TaxonService::class)->lastUndoableMove($record);
                $from = $move?->properties['from'] ?? 'the previous name';

                return isset($move?->properties['undo']['merged_taxon_id'])
                    ? "{$from} is restored and its records move back to it from {$record->scientificname}."
                    : "{$record->scientificname} goes back to {$from}, with its previous classification. Introduction events keep the name they were recorded under.";
            })
            ->action(function (Taxon $record, TaxonService $taxonService, $livewire): void {
                try {
                    $result = $taxonService->undoLastMove($record);
                } catch (RuntimeException $exception) {
                    Notification::make()->title('Not undone')->body($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Move undone')->body("Records are under {$result->scientificname} again.")->success()->send();

                if ($livewire instanceof EditRecord) {
                    $livewire->redirect(TaxonResource::getUrl('edit', ['record' => $result]));
                }
            });
    }

    public static function getDiscussionAction(): CommentsTableAction
    {
        return CommentsTableAction::make()
            ->label('Discussion')
            ->color('gray')
            ->modalDescription(fn (Taxon $record): HtmlString => DiscussionParticipantsAction::summary($record))
            ->disableSidebar();
    }

    /**
     * Moves every selected species scoring "Safe to move"; the others need
     * their own dialog (note, merge warning) and are listed as skipped.
     */
    private static function getBulkMoveToAcceptedNameAction(): BulkAction
    {
        return BulkAction::make('move_to_accepted_name')
            ->label('Move to accepted names')
            ->icon('tabler-arrow-right-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Each selected species scoring "Safe to move" ('.AcceptedNameConfidence::SAFE.'% or more) is moved to its accepted name. Introduction events keep the name they were recorded under. Lower scores are skipped: move those one by one.')
            // ponytail: runs in the request, about three WoRMS calls per species; queue it if selections grow past a few dozen.
            ->action(function (Collection $records, TaxonService $taxonService): void {
                $moved = 0;
                $skipped = [];

                foreach ($records->filter(fn (Taxon $taxon): bool => filled($taxon->proposed_accepted_name)) as $taxon) {
                    if (($taxon->name_change_confidence ?? 0) < AcceptedNameConfidence::SAFE) {
                        $skipped[] = $taxon->scientificname;

                        continue;
                    }

                    try {
                        $taxonService->moveToAcceptedName($taxon);
                        $moved++;
                    } catch (RuntimeException) {
                        $skipped[] = $taxon->scientificname;
                    }
                }

                Notification::make()
                    ->title(trans_choice(':count species moved|:count species moved', $moved))
                    ->body($skipped ? 'Move these one by one: '.implode(', ', $skipped).'.' : null)
                    ->status($skipped ? 'warning' : 'success')
                    ->send();
            });
    }

    /**
     * The confidence score as a badge with its band; reasons on hover.
     * Shown on the "Name to update" tab.
     */
    protected static function getNameChangeConfidenceColumn(): TextColumn
    {
        return TextColumn::make('name_change_confidence')
            ->label('Confidence')
            ->badge()
            ->sortable()
            ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : "{$state}% · ".AcceptedNameConfidence::band($state)['label'])
            ->color(fn (?int $state): string => AcceptedNameConfidence::band($state)['color'])
            ->placeholder('Not assessed')
            ->tooltip(fn (Taxon $record): ?string => collect($record->name_change_reasons)
                ->map(fn (array $reason): string => sprintf('%+d  %s', $reason['points'], $reason['label']))
                ->implode("\n") ?: null)
            ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'rename');
    }

    /**
     * Score, reasons and merge warning for the move dialog.
     */
    private static function moveSummary(Taxon $taxon): HtmlString
    {
        $band = AcceptedNameConfidence::band($taxon->name_change_confidence);
        $events = $taxon->introEvents()->count();
        $target = app(TaxonService::class)->acceptedInCatalogue($taxon);

        $reasons = collect($taxon->name_change_reasons)
            ->map(fn (array $reason): string => '<li><span class="font-mono">'.sprintf('%+d', $reason['points']).'</span> '.e($reason['label']).'</li>')
            ->implode('');

        $html = '<div class="space-y-3 text-sm">'
            .'<p><strong>Confidence: '.($taxon->name_change_confidence === null ? 'not assessed' : e($taxon->name_change_confidence.'% · '.$band['label'])).'</strong></p>'
            .($reasons ? '<ul class="space-y-1">'.$reasons.'</ul>' : '<p>Run "Fetch from WoRMS" to score this proposal.</p>')
            .'<p>'.e(trans_choice(':count introduction event follows|:count introduction events follow', $events)).', each keeping <em>'.e($taxon->scientificname).'</em> as the name it was recorded under.</p>';

        if ($target) {
            $targetEvents = $target->introEvents()->count();
            $html .= '<p class="rounded-md bg-warning-50 p-3 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">'
                .'<strong>This is a merge.</strong> <em>'.e($target->scientificname).'</em> is already in the catalogue'
                .($target->trashed() ? ' (in the trash, it will be restored)' : '')
                .' with '.e(trans_choice(':count introduction event|:count introduction events', $targetEvents)).'. After merging it will have '.($targetEvents + $events)
                .', and '.e($taxon->scientificname).' goes to the trash.'
                .($targetEvents + $events > 1 ? ' The first records will then need reconciling (earliest year, first country).' : '')
                .'</p>';
        }

        return new HtmlString($html.'</div>');
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
            ->visibleFrom('lg')
            ->url(fn ($record) => $record->url)
            ->openUrlInNewTab();
    }

    protected static function getEasinIdColumn(): TextColumn
    {
        return TextColumn::make('Easin_id')
            ->label('EASIN ID')
            ->icon(TablerIcon::Link)
            ->sortable()
            ->visibleFrom('xl')
            ->url(fn ($record) => $record->Easin_id ? "https://easin.jrc.ec.europa.eu/spexplorer/species/factsheet/{$record->Easin_id}" : null)
            ->openUrlInNewTab();
    }

    protected static function getScientificNameColumn(): TextColumn
    {
        return TextColumn::make('scientificname')
            ->label('Scientific Name')
            ->wrapHeader()
            ->sortable()
            ->wrap()
            ->html()
            ->formatStateUsing(fn ($state, $record) => self::formatScientificName($state, $record->rank))
            ->description(fn (Taxon $record): ?string => $record->proposed_accepted_name ? "WoRMS accepted: {$record->proposed_accepted_name}" : null)
            ->tooltip(fn ($record) => self::getScientificNameTooltip($record));
    }

    protected static function getWormsStatusColumn(): TextColumn
    {
        return TextColumn::make('worms_status')
            ->label('WoRMS Status')
            ->wrapHeader()
            ->badge()
            ->sortable()
            ->searchable();
    }

    protected static function getCatalogueStatusColumn(): TextColumn
    {
        return TextColumn::make('catalogue_status')
            ->label('Catalogue Status')
            ->wrapHeader()
            ->badge()
            ->sortable();
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
            ->visibleFrom('lg')
            ->badge()
            ->color(function (mixed $state): string|array|null {
                if (is_array($state)) {
                    return collect($state)
                        ->map(fn ($value) => Environment::fromLabelOrValue($value)?->getColor() ?? 'gray')
                        ->first();
                }
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
                if (is_array($state)) {
                    return collect($state)
                        ->map(fn ($value) => Environment::fromLabelOrValue($value)?->getLabel() ?? (string) $value)
                        ->implode(', ');
                }

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
            ->formatStateUsing(fn ($state, $record) => $record->creator?->getFormattedNameWithRoles())
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
            ->formatStateUsing(fn ($state, $record) => $record->editor?->getFormattedNameWithRoles())
            ->sortable()
            ->searchable(['users.first_name', 'users.last_name'])
            ->toggleable(isToggledHiddenByDefault: true);
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

        // Italic only — the name inherits the panel's own face (Geist). It used
        // to carry an inline Georgia/Times stack, which made species names the
        // one serif in an otherwise sans interface and pinned them to a font
        // the design system does not load. See DESIGN-SYSTEM.md: a species name
        // is italic at whatever size its context uses, nothing more.
        return "<span class='italic'>{$formatted}</span>";
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

            // Italic only, same as the column — a tooltip is not a different
            // typographic context. See DESIGN-SYSTEM.md.
            return new HtmlString(
                "<span class='italic'>{$formattedName}</span>".
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
