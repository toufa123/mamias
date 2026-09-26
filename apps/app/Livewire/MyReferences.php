<?php

namespace App\Livewire;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Filament\Resources\Literatures\Tables\LiteraturesTable;
use App\Models\Literature;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class MyReferences extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Literature::forUser(auth()->user())
                ->withCount(['comments', 'comments as unanswered_comments_count' => Literature::unansweredComments(fromSubmitter: false)]))
            ->columns($this->getTableColumns())
            ->filters($this->getTableFilters())
            ->persistFiltersInSession(false)
            ->persistColumnSearchesInSession(false)
            ->persistSortInSession(false)
            ->recordActions([
                ViewAction::make('view')
                    ->form([
                        LiteratureForm::getBibliographicReferenceSection(),
                        $this->reviewTimelineSection(),
                    ]),
            ])
            ->recordAction('view')
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Where the reference stands: submitted, then reviewed by whom and when,
     * with the reviewer's comment.
     */
    private function reviewTimelineSection(): Section
    {
        return Section::make('Review')
            ->icon('tabler-timeline')
            ->compact()
            ->columns(3)
            ->schema([
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->label('Submitted')
                    ->dateTime(),
                TextEntry::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->placeholder('Waiting for a reviewer')
                    // The reviewer's role, not their name.
                    ->helperText(fn (Literature $record): ?string => $record->reviewer ? "by {$record->reviewer->primaryRoleLabel()}" : null),
                TextEntry::make('review_comment')
                    ->label('Reviewer comment')
                    ->columnSpanFull()
                    ->visible(fn (Literature $record): bool => filled($record->review_comment)),
            ]);
    }

    /**
     * The submitter's references with moderator comments awaiting their reply.
     *
     * @return Collection<int, Literature>
     */
    public function unansweredReferences(): Collection
    {
        return Literature::forUser(auth()->user())->withUnansweredComments(fromSubmitter: false)->get(['id', 'code', 'short_ref']);
    }

    /**
     * The submitter's rejected references. Rejection is final for a
     * reference — a metadata slip is fixed by the reviewer, who approves it
     * — so this only points the submitter at the reviewer's reason.
     */
    public function rejectedCount(): int
    {
        return Literature::forUser(auth()->user())->where('status', LiteratureStatus::REJECTED)->count();
    }

    private function getTableColumns(): array
    {
        return [
            LiteraturesTable::getCodeColumn(),
            LiteraturesTable::getShortRefColumn(),
            LiteraturesTable::getDoiColumn()
                ->hiddenFrom('sm'),
            LiteraturesTable::getTypeColumn(),
            LiteraturesTable::getYearColumn(),
            LiteraturesTable::getStatusColumn(),
            LiteraturesTable::getCommentsColumn(),
            LiteraturesTable::getFileColumn()
                ->hiddenFrom('sm'),
            TextColumn::make('created_at')
                ->label('Submitted')
                ->dateTime()
                ->fontFamily(FontFamily::Mono)
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    private function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')->options(LiteratureStatus::class),
            SelectFilter::make('type')->options(LiteratureType::class),
            LiteraturesTable::getYearFilter(Literature::forUser(auth()->user())),
        ];
    }

    /**
     * How-to for contributors, in a scrollable popup.
     */
    public function guideAction(): Action
    {
        return LiteratureGuide::action('references', 'How My Bibliographic References works')
            ->size('lg');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Add New Reference')
            ->icon('tabler-file-plus')
            ->modalHeading('Submit a New Reference')
            ->modalDescription('Submissions are reviewed before publication.')
            ->button()
            ->color('primary')
            ->size('lg')
            ->steps(LiteratureForm::getSubmissionSteps())
            ->action(function (array $data) {
                Literature::create([
                    ...$data,
                    'status' => LiteratureStatus::PENDING,
                ]);

                // Filament's own notification, not the notify() helper: this runs
                // inside a Filament modal action, so the feedback belongs in the
                // panel's notification channel (and is what assertNotified sees).
                Notification::make()
                    ->success()
                    ->title('Reference submitted')
                    ->body('Your reference has been submitted for review.')
                    ->send();
            });
    }

    public function render(): View
    {
        return view('livewire.my-references')->extends('app')->section('content');
    }
}
