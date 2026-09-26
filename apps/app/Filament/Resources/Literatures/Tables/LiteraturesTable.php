<?php

namespace App\Filament\Resources\Literatures\Tables;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Filament\Actions\DiscussionParticipantsAction;
use App\Filament\Resources\Literatures\LiteratureResource;
use App\Livewire\MyReferences;
use App\Models\Literature;
use App\Notifications\LiteratureReviewed;
use App\Services\DoiMetadataService;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use JeffersonGoncalves\FilamentExportAction\Actions\FilamentExportHeaderAction;
use JeffersonGoncalves\FilamentExportAction\Enums\ExportFormat;
use Kirschbaum\Commentions\Filament\Actions\CommentsTableAction;

/**
 * Configures the Filament table for literature records.
 * Displays code, short reference, DOI (with retraction and suggested-DOI
 * notes), year, type, status, submitter, usage, full reference, file, and
 * link columns; row actions are grouped in one menu. References still cited
 * by records cannot be deleted, only merged into another one.
 */
class LiteraturesTable
{
    /**
     * @param  Table  $table  The table to configure.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            // The submitter column reads each creator's roles; the usage column the counts.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('creator.roles')
                ->withCount(['introEvents', 'nisSuggestions', 'describedTaxa', 'comments', 'comments as unanswered_comments_count' => Literature::unansweredComments(fromSubmitter: true)]))
            // Fluid: columns drop out by priority as the screen narrows (breakpoints
            // are set here, not in the getters, which "My references" reuses with
            // its own). Reference and status always stay.
            ->columns([
                self::getCodeColumn()->visibleFrom('lg'),
                self::getShortRefColumn(),
                self::getDoiColumn()->wrap()->visibleFrom('xl'),
                self::getYearColumn()->visibleFrom('md'),
                self::getTypeColumn()->visibleFrom('lg'),
                self::getStatusColumn(),
                self::getCommentsColumn(),
                self::getSubmitterColumn()->visibleFrom('md'),
                self::getCitationsColumn()->visibleFrom('lg'),
                self::getFullRefColumn()->visibleFrom('2xl'),
                self::getFileColumn()->visibleFrom('xl'),
                self::getLinkColumn()->visibleFrom('2xl'),
            ])
            ->filters([
                SelectFilter::make('type')->options(LiteratureType::class),
                self::getYearFilter(Literature::query()),
                SelectFilter::make('created_by')
                    ->label('Submitted by')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('unanswered_comments')
                    ->label('Comments awaiting a reply')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->withUnansweredComments(fromSubmitter: true)),
                Filter::make('is_retracted')
                    ->label('Retracted')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('is_retracted', true)),
                Filter::make('suggested_doi')
                    ->label('DOI suggestion to review')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNotNull('suggested_doi')),
                Filter::make('uncited')
                    ->label('Not cited by any record')
                    ->toggle()
                    ->query(fn (Builder $query) => $query
                        ->whereDoesntHave('introEvents')
                        ->whereDoesntHave('nisSuggestions')
                        ->whereDoesntHave('describedTaxa')),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::getViewAction(),
                    EditAction::make(),
                    DiscussionParticipantsAction::make(),
                    self::getBibtexAction(),
                    ActionGroup::make([
                        self::getApproveAction(),
                        self::getRejectAction(),
                    ])->dropdown(false),
                    ActionGroup::make([
                        self::getAcceptSuggestedDoiAction(),
                        self::getDismissSuggestedDoiAction(),
                    ])->dropdown(false),
                    self::getMergeAction(),
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
                    self::getBulkReviewAction(LiteratureStatus::APPROVED),
                    self::getBulkReviewAction(LiteratureStatus::REJECTED),
                    self::getDeleteBulkAction(),
                ]),
            ]);
    }

    /**
     * Read-only view of a reference with its review trail.
     */
    public static function getViewAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading(fn (Literature $record): string => "{$record->code} — {$record->short_ref}")
            ->modalWidth('3xl')
            ->schema([
                Section::make('Reference')
                    ->icon('tabler-book')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('full_ref')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                        TextEntry::make('doi')
                            ->label('DOI')
                            ->url(fn (?string $state): ?string => $state ? "https://doi.org/{$state}" : null)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('year')
                            ->placeholder('—'),
                        TextEntry::make('type')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('link')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->limit(60)
                            ->placeholder('—')
                            ->columnSpan(2),
                        TextEntry::make('file_path')
                            ->label('PDF')
                            ->formatStateUsing(fn (): string => 'Open PDF')
                            ->url(fn (?string $state): ?string => $state ? Storage::disk('public')->url($state) : null)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('is_retracted')
                            ->label('Retraction')
                            ->badge()
                            ->color('danger')
                            ->formatStateUsing(fn (): string => 'Retracted')
                            ->visible(fn (Literature $record): bool => $record->is_retracted),
                        TextEntry::make('suggested_doi')
                            ->label('Suggested DOI')
                            ->visible(fn (Literature $record): bool => filled($record->suggested_doi)),
                    ]),
                Section::make('Review')
                    ->icon('tabler-shield-check')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('creator.name')
                            ->label('Submitted by')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('—'),
                        TextEntry::make('reviewed_at')
                            ->label('Reviewed')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('review_comment')
                            ->label('Review comment')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Approves a reference, with an optional comment for the submitter. On a
     * rejected reference it becomes "Re-approve", to reverse the decision.
     */
    public static function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label(fn (Literature $record): string => $record->status === LiteratureStatus::REJECTED ? 'Re-approve' : 'Approve')
            ->icon(TablerIcon::Check)
            ->color('success')
            ->visible(fn (Literature $record): bool => $record->status !== LiteratureStatus::APPROVED)
            ->modalHeading(fn (Literature $record): string => $record->status === LiteratureStatus::REJECTED ? 'Re-approve reference' : 'Approve reference')
            ->modalDescription(fn (Literature $record): string => "{$record->code} — {$record->short_ref}")
            ->modalSubmitActionLabel('Approve')
            ->extraModalFooterActions(fn (Action $action, Literature $record, $livewire): array => self::nextFooterAction($action, $record, $livewire, 'Approve & next'))
            ->schema([
                Textarea::make('review_comment')
                    ->label('Comment for the submitter')
                    ->rows(3)
                    ->placeholder('Optional'),
            ])
            ->action(function (Literature $record, array $data, array $arguments, $livewire): void {
                self::review($record, LiteratureStatus::APPROVED, $data['review_comment'] ?? null);
                self::openNextPending($livewire, 'approve', $record, $arguments);
            });
    }

    /**
     * Rejects a reference. The reason is required: it is what the submitter
     * gets back. On an approved reference it becomes "Re-reject".
     */
    public static function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label(fn (Literature $record): string => $record->status === LiteratureStatus::APPROVED ? 'Re-reject' : 'Reject')
            ->icon(TablerIcon::X)
            ->color('danger')
            ->visible(fn (Literature $record): bool => $record->status !== LiteratureStatus::REJECTED)
            ->modalHeading(fn (Literature $record): string => $record->status === LiteratureStatus::APPROVED ? 'Re-reject reference' : 'Reject reference')
            ->modalDescription(fn (Literature $record): string => "{$record->code} — {$record->short_ref}")
            ->modalSubmitActionLabel('Reject')
            ->extraModalFooterActions(fn (Action $action, Literature $record, $livewire): array => self::nextFooterAction($action, $record, $livewire, 'Reject & next'))
            ->schema([
                Textarea::make('review_comment')
                    ->label('Reason for the submitter')
                    ->required()
                    ->rows(3)
                    ->placeholder('Explain why the reference is being rejected…')
                    // Rejection is final: the submitter cannot resubmit a reference.
                    ->helperText('Rejection is final. For a metadata error (year, DOI, typo), edit the reference and approve it instead.'),
            ])
            ->action(function (Literature $record, array $data, array $arguments, $livewire): void {
                self::review($record, LiteratureStatus::REJECTED, $data['review_comment']);
                self::openNextPending($livewire, 'reject', $record, $arguments);
            });
    }

    /**
     * "… & next" submit button, offered from the list while reviewing a
     * pending reference: the same action then opens on the next pending one.
     *
     * @return array<Action>
     */
    private static function nextFooterAction(Action $action, Literature $record, mixed $livewire, string $label): array
    {
        if (! $livewire instanceof HasTable || $record->status !== LiteratureStatus::PENDING) {
            return [];
        }

        return [
            $action->makeModalSubmitAction('submitAndNext', arguments: ['next' => true])
                ->label($label)
                ->color('gray'),
        ];
    }

    /**
     * After an "… & next" submit, swaps the open modal for the same action
     * on the oldest other pending reference. Closes normally when none is left.
     *
     * @param  array<string, mixed>  $arguments
     */
    private static function openNextPending(mixed $livewire, string $actionName, Literature $record, array $arguments): void
    {
        if (! ($arguments['next'] ?? false) || ! $livewire instanceof HasTable) {
            return;
        }

        $next = Literature::where('status', LiteratureStatus::PENDING)
            ->whereKeyNot($record->getKey())
            ->oldest()
            ->first();

        if ($next) {
            $livewire->replaceMountedAction($actionName, context: ['table' => true, 'recordKey' => $next->getKey()]);
        }
    }

    /**
     * Approves or rejects every selected reference that is not already in
     * that state, with one comment for all submitters.
     */
    public static function getBulkReviewAction(LiteratureStatus $status): BulkAction
    {
        $approve = $status === LiteratureStatus::APPROVED;

        return BulkAction::make($approve ? 'bulkApprove' : 'bulkReject')
            ->label($approve ? 'Approve selected' : 'Reject selected')
            ->icon($approve ? TablerIcon::Check : TablerIcon::X)
            ->color($approve ? 'success' : 'danger')
            ->modalHeading($approve ? 'Approve selected references' : 'Reject selected references')
            ->modalSubmitActionLabel($approve ? 'Approve' : 'Reject')
            ->schema([
                Textarea::make('review_comment')
                    ->label($approve ? 'Comment for the submitters' : 'Reason for the submitters')
                    ->required(! $approve)
                    ->rows(3)
                    ->placeholder($approve ? 'Optional' : 'Explain why the references are being rejected…'),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data) use ($status, $approve): void {
                $toReview = $records->filter(fn (Literature $record): bool => $record->status !== $status);

                $toReview->each(fn (Literature $record) => self::review($record, $status, $data['review_comment'] ?? null, notifyReviewer: false));

                Notification::make()
                    ->title(trans_choice(($approve ? ':count reference approved' : ':count reference rejected').'|'.($approve ? ':count references approved' : ':count references rejected'), $toReview->count()))
                    ->{$approve ? 'success' : 'warning'}()
                    ->send();
            });
    }

    /**
     * Deletes the selected references that no record cites. Deleting a cited
     * one would cascade to its introduction events, so those are kept and
     * reported: merge them into the reference that stays instead.
     */
    public static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->action(function (Collection $records): void {
                [$cited, $unused] = $records->partition(fn (Literature $record): bool => $record->citationCount() > 0);

                $unused->each->delete();

                Notification::make()
                    ->title(trans_choice(':count reference deleted|:count references deleted', $unused->count()))
                    ->body($cited->isEmpty() ? null : 'Kept, still cited by records: '.$cited->pluck('code')->join(', ').'. Use "Merge into…" to replace them.')
                    ->{$cited->isEmpty() ? 'success' : 'warning'}()
                    ->persistent($cited->isNotEmpty())
                    ->send();
            });
    }

    /**
     * Folds a duplicate into the reference that stays: its introduction
     * events, species suggestions and original descriptions move over, then
     * it is deleted. Near-duplicates are offered first.
     */
    public static function getMergeAction(): Action
    {
        $label = fn (Literature $literature): string => "{$literature->code} — {$literature->short_ref}";

        return Action::make('merge')
            ->label('Merge into…')
            ->icon('tabler-arrow-merge')
            ->color('gray')
            ->modalHeading(fn (Literature $record): string => "Merge {$record->code} into another reference")
            ->modalDescription(fn (Literature $record): string => "The {$record->citationCount()} record(s) citing {$record->code} will cite the chosen reference instead, then {$record->code} is deleted. This cannot be undone.")
            ->modalSubmitActionLabel('Merge')
            ->schema([
                Select::make('target_id')
                    ->label('Reference to keep')
                    ->required()
                    ->searchable()
                    ->options(fn (Literature $record): array => Literature::similarTo($record->full_ref, 0.4)
                        ->whereKeyNot($record->getKey())
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(fn (Literature $literature): array => [$literature->getKey() => $label($literature)])
                        ->all())
                    ->getSearchResultsUsing(fn (string $search, Literature $record): array => Literature::query()
                        ->whereKeyNot($record->getKey())
                        ->where(fn (Builder $query) => $query
                            ->where('code', 'ilike', "%{$search}%")
                            ->orWhere('short_ref', 'ilike', "%{$search}%")
                            ->orWhere('doi', 'ilike', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Literature $literature): array => [$literature->getKey() => $label($literature)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => ($literature = Literature::find($value)) ? $label($literature) : null)
                    ->helperText('Near-duplicates are listed first; type to search by code, reference or DOI.'),
            ])
            ->action(function (Literature $record, array $data, $livewire): void {
                $target = Literature::findOrFail($data['target_id']);

                $record->mergeInto($target);

                Notification::make()
                    ->title("Merged into {$target->code}")
                    ->success()
                    ->send();

                if ($livewire instanceof EditRecord) {
                    $livewire->redirect(LiteratureResource::getUrl('edit', ['record' => $target]));
                }
            });
    }

    /**
     * BibTeX entry, ready to copy into a reference manager.
     */
    public static function getBibtexAction(): Action
    {
        return Action::make('bibtex')
            ->label('Cite (BibTeX)')
            ->icon('tabler-quote')
            ->color('gray')
            ->modalHeading(fn (Literature $record): string => "Cite {$record->short_ref}")
            ->modalWidth('2xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->schema([
                TextEntry::make('bibtex')
                    ->hiddenLabel()
                    ->state(fn (Literature $record): string => self::formatBibtex($record->toBibtex()))
                    // In a <pre> of its own: pre-wrap on the entry also kept the
                    // template's indentation before the first line.
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString('<pre style="white-space: pre-wrap; margin: 0">'.e($state).'</pre>'))
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->copyMessage('BibTeX copied'),
            ]);
    }

    /**
     * Discussion size as a badge, amber while comments await a reply. Needs
     * `comments_count` and `unanswered_comments_count` on the query.
     */
    public static function getCommentsColumn(): TextColumn
    {
        return TextColumn::make('comments_count')
            ->label('Discussion')
            ->badge()
            ->icon('tabler-message-circle')
            ->color(fn (Literature $record): string => $record->unanswered_comments_count ? 'warning' : 'gray')
            ->tooltip(fn (Literature $record): string => $record->unanswered_comments_count
                ? trans_choice(':count comment awaiting a reply|:count comments awaiting a reply', $record->unanswered_comments_count)
                : 'Open the discussion')
            ->action(self::getDiscussionAction());
    }

    /**
     * The moderator/submitter thread on a reference. Also used on "My
     * references"; replies are routed by NotifyLiteratureDiscussion, so the
     * package's subscribe sidebar is hidden.
     */
    public static function getDiscussionAction(): CommentsTableAction
    {
        return CommentsTableAction::make()
            ->label('Discussion')
            ->color('gray')
            ->modalDescription(fn (Literature $record): HtmlString => DiscussionParticipantsAction::summary($record))
            ->disableSidebar();
    }

    /**
     * Records the decision, logs it and tells the submitter. A moderator
     * reviewing their own submission is not notified of it.
     */
    private static function review(Literature $record, LiteratureStatus $status, ?string $comment, bool $notifyReviewer = true): void
    {
        $record->update([
            'status' => $status,
            'review_comment' => filled($comment) ? trim($comment) : null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($record)
            ->withProperties(['review_comment' => $record->review_comment])
            ->event($status->value)
            ->log($status->value);

        if ($record->creator && $record->creator->isNot(auth()->user())) {
            $record->creator->notify(new LiteratureReviewed($record));
        }

        if (! $notifyReviewer) {
            return;
        }

        Notification::make()
            ->title($status === LiteratureStatus::APPROVED ? 'Reference approved' : 'Reference rejected')
            ->{$status === LiteratureStatus::APPROVED ? 'success' : 'warning'}()
            ->send();
    }

    /**
     * Adopts the DOI `literature:enrich --search` found. Year, link and the
     * retraction flag come from Crossref; the curated reference text is kept.
     */
    public static function getAcceptSuggestedDoiAction(): Action
    {
        return Action::make('acceptSuggestedDoi')
            ->label('Accept DOI')
            ->icon('tabler-link-plus')
            ->color('info')
            ->visible(fn (Literature $record): bool => filled($record->suggested_doi))
            ->requiresConfirmation()
            ->modalDescription(fn (Literature $record): string => "Set the DOI of {$record->code} to {$record->suggested_doi}? Check it at https://doi.org/{$record->suggested_doi} first.")
            ->action(function (Literature $record): void {
                if (Literature::where('doi', $record->suggested_doi)->exists()) {
                    Notification::make()
                        ->title('Another reference already has this DOI')
                        ->danger()
                        ->send();

                    return;
                }

                $metadata = app(DoiMetadataService::class)->fetchFromCrossref($record->suggested_doi);

                $record->doi = $record->suggested_doi;
                $record->suggested_doi = null;
                $record->crossref_checked_at = now();

                if ($metadata) {
                    $record->year ??= $metadata['year'];
                    $record->link ??= $metadata['link'];
                    $record->is_retracted = $metadata['is_retracted'];
                }

                $record->save();

                Notification::make()->title('DOI accepted')->success()->send();
            });
    }

    /**
     * Discards a wrong suggestion. The record stays marked as searched, so
     * the same guess is not suggested again.
     */
    public static function getDismissSuggestedDoiAction(): Action
    {
        return Action::make('dismissSuggestedDoi')
            ->label('Dismiss DOI')
            ->icon('tabler-link-off')
            ->color('gray')
            ->visible(fn (Literature $record): bool => filled($record->suggested_doi))
            ->action(fn (Literature $record) => $record->update(['suggested_doi' => null]));
    }

    /**
     * @return TextColumn The code column, sortable and searchable.
     */
    public static function getCodeColumn(): TextColumn
    {
        return TextColumn::make('code')
            ->label('Code')
            ->fontFamily(FontFamily::Mono)
            ->sortable()
            ->searchable();
    }

    /**
     * @return TextColumn The short reference column, sortable, searchable, and wrapping.
     */
    public static function getShortRefColumn(): TextColumn
    {
        return TextColumn::make('short_ref')
            ->label('Short Reference')
            ->sortable()
            ->searchable()
            ->wrap();
    }

    /**
     * @return TextColumn The DOI column with an external link icon, sortable and searchable.
     */
    public static function getDoiColumn(): TextColumn
    {
        return TextColumn::make('doi')
            ->label('DOI')
            ->fontFamily(FontFamily::Mono)
            ->sortable()
            ->searchable()
            ->icon(TablerIcon::Link)
            ->iconPosition('before')
            ->url(fn ($state) => $state ? 'https://doi.org/'.$state : null)
            ->openUrlInNewTab()
            ->color(fn (Literature $record): ?string => $record->is_retracted ? 'danger' : null)
            ->description(fn (Literature $record): ?string => $record->is_retracted ? 'Retracted' : null)
            // A suggestion only exists when there is no DOI, and Filament skips
            // the description of an empty cell — so it goes in the placeholder.
            ->placeholder(fn (Literature $record): ?string => filled($record->suggested_doi) ? "Suggested: {$record->suggested_doi}" : null)
            ->toggleable();
    }

    /**
     * @return TextColumn The submitter name as a badge coloured by role, the role in its tooltip.
     */
    public static function getSubmitterColumn(): TextColumn
    {
        return TextColumn::make('creator.name')
            ->label('Submitted By')
            ->badge()
            ->color(fn (Literature $record): string => match ($record->creator?->primaryRoleName()) {
                'super_admin' => 'danger',
                'scientist' => 'info',
                default => 'gray',
            })
            ->tooltip(fn (Literature $record): ?string => $record->creator?->primaryRoleLabel())
            ->sortable()
            ->searchable()
            ->placeholder('—')
            ->toggleable();
    }

    /**
     * One field per line. Crossref answers with the whole entry on a single
     * line (and a leading space), which reads as a ragged block when shown.
     */
    public static function formatBibtex(string $bibtex): string
    {
        $bibtex = trim($bibtex);

        if (str_contains($bibtex, "\n")) {
            return $bibtex;
        }

        $bibtex = preg_replace('/,\s*(\w+)\s*=/', ",\n  $1 = ", $bibtex);

        return preg_replace('/\s*}\s*$/', "\n}", $bibtex);
    }

    /**
     * @return TextColumn How many records cite the reference, the breakdown in its tooltip.
     */
    public static function getCitationsColumn(): TextColumn
    {
        return TextColumn::make('citations')
            ->label('Used in')
            ->state(fn (Literature $record): int => $record->citationCount())
            ->badge()
            ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
            ->tooltip(fn (Literature $record): string => "{$record->intro_events_count} introduction event(s) · {$record->nis_suggestions_count} species suggestion(s) · {$record->described_taxa_count} original description(s)")
            ->toggleable();
    }

    /**
     * @return TextColumn The publication year column, sortable.
     */
    /**
     * Publication years present in $scope, newest first.
     *
     * @param  Builder<Literature>  $scope
     */
    public static function getYearFilter(Builder $scope): SelectFilter
    {
        return SelectFilter::make('year')
            ->options(fn (): array => $scope->clone()->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year', 'year')->all())
            ->searchable();
    }

    public static function getYearColumn(): TextColumn
    {
        return TextColumn::make('year')
            ->label('Year')
            ->fontFamily(FontFamily::Mono)
            ->sortable()
            ->placeholder('—')
            ->toggleable();
    }

    /**
     * @return TextColumn The type column as a neutral badge (a category, not a
     *                    status — its icon names it), sortable and toggleable.
     */
    public static function getTypeColumn(): TextColumn
    {
        return TextColumn::make('type')
            ->label('Type')
            ->badge()
            ->color('gray')
            ->sortable()
            ->toggleable();
    }

    /**
     * @return TextColumn The full reference column, limited to 100 characters and toggleable.
     */
    public static function getFullRefColumn(): TextColumn
    {
        return TextColumn::make('full_ref')
            ->label('Full Reference')
            ->limit(100)
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return TextColumn The file column with a document icon, toggleable.
     */
    public static function getFileColumn(): TextColumn
    {
        return TextColumn::make('file_path')
            ->label('File')
            ->icon(Heroicon::OutlinedDocument)
            ->toggleable();
    }

    /**
     * @return TextColumn The status column as a badge, sortable; click opens the review comment.
     */
    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->badge()
            ->sortable()
            // Shared with "My references": clicking a reviewed status opens the
            // moderator's comment, so the submitter can read why.
            ->icon(fn (Literature $record): ?string => $record->review_comment ? 'tabler-message-circle' : null)
            ->iconPosition('after')
            ->tooltip(fn (Literature $record): ?string => $record->review_comment ? 'Click to read the reviewer comment' : null)
            ->disabledClick(fn (Literature $record): bool => blank($record->review_comment))
            ->action(
                Action::make('showReviewComment')
                    ->visible(fn (Literature $record): bool => filled($record->review_comment))
                    // The body carries the label, reference and reviewer (see the view);
                    // no modal icon, as Filament draws it in a round badge (rule 1).
                    ->modalHeading(fn (Literature $record): string => $record->status === LiteratureStatus::REJECTED ? 'Reference rejected' : 'Reference approved')
                    ->modalWidth('lg')
                    // Submitters see the reviewer's role, not their name.
                    ->modalContent(fn (Literature $record, $livewire) => view('filament.literatures.review-comment', [
                        'record' => $record,
                        'reviewerLabel' => $livewire instanceof MyReferences ? $record->reviewer?->primaryRoleLabel() : $record->reviewer?->name,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            );
    }

    /**
     * @return TextColumn The link column with an external URL, limited to 50 characters and toggleable.
     */
    public static function getLinkColumn(): TextColumn
    {
        return TextColumn::make('link')
            ->label('Link')
            ->url(fn ($record) => $record->link)
            ->openUrlInNewTab()
            ->limit(50)
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
