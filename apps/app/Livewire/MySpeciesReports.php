<?php

namespace App\Livewire;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceForm;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceInfolist;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Literature;
use App\Models\Occurrence;
use App\Notifications\OccurrenceSubmitted;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Component;

class MySpeciesReports extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Occurrence::where('user_id', auth()->id())->with('introEventRecord.taxon'))
            ->columns([
                OccurrencesTable::getSpeciesColumn(),
                OccurrencesTable::getStatusColumn(),
                OccurrencesTable::getMapColumn(),
                OccurrencesTable::getDepthColumn(),
                OccurrencesTable::getObservedAtColumn(),
                OccurrencesTable::getSubmittedAtColumn(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Occurrence Details')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->schema(OccurrenceInfolist::getComponents()),
                $this->editAction(),
                $this->withdrawAction(),
            ])
            ->headerActions([LiteratureGuide::action('species-reports', 'How My Species Reports works')->size('lg'), $this->createAction()])
            ->defaultSort('created_at', 'desc');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Report New Occurrence')
            ->icon('tabler-binoculars')
            ->button()
            ->color('primary')
            ->size('lg')
            ->modalHeading('Report a New Species Occurrence')
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(OccurrenceForm::getComponents())
            ->action(function (array $data): void {
                $occurrence = Occurrence::create([
                    ...$data,
                    'user_id' => auth()->id(),
                    'status' => OccurrenceStatus::PENDING,
                ]);

                $this->notifyModerators($occurrence);

                Notification::make()
                    ->title('Occurrence reported')
                    ->body('Thank you! Your occurrence report will be reviewed by our team.')
                    ->success()
                    ->send();
            });
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'Revise & resubmit' : 'Edit')
            ->icon(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'tabler-refresh' : 'tabler-pencil')
            ->color(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'warning' : 'gray')
            ->visible(fn (Occurrence $record): bool => $record->status !== OccurrenceStatus::APPROVED)
            ->modalHeading(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'Revise & resubmit occurrence' : 'Edit Occurrence')
            ->modalDescription(fn (Occurrence $record): ?string => $record->status === OccurrenceStatus::REJECTED && $record->moderation_notes
                ? 'Reviewer feedback: '.$record->moderation_notes
                : null)
            ->modalSubmitActionLabel(fn (Occurrence $record): string => $record->status === OccurrenceStatus::REJECTED ? 'Resubmit for review' : 'Save changes')
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(OccurrenceForm::getComponents())
            ->fillForm(fn (Occurrence $record): array => $record->toArray())
            ->action(function (Occurrence $record, array $data): void {
                $isResubmission = $record->status === OccurrenceStatus::REJECTED;

                // The rejection reason stays on the record so the reviewer sees what was asked for.
                $record->update([...$data, 'status' => OccurrenceStatus::PENDING]);

                if ($isResubmission) {
                    $this->notifyModerators($record, isResubmission: true);
                }

                Notification::make()
                    ->title($isResubmission ? 'Occurrence resubmitted' : 'Occurrence updated')
                    ->body($isResubmission ? 'Your revised report is back in the review queue.' : null)
                    ->success()
                    ->send();
            });
    }

    public function withdrawAction(): DeleteAction
    {
        return DeleteAction::make('withdraw')
            ->label('Withdraw')
            ->icon('tabler-trash')
            ->visible(fn (Occurrence $record): bool => $record->status === OccurrenceStatus::PENDING)
            ->modalHeading('Withdraw this report?')
            ->modalDescription('It will be removed from the review queue. This cannot be undone.')
            ->successNotificationTitle('Report withdrawn');
    }

    private function notifyModerators(Occurrence $occurrence, bool $isResubmission = false): void
    {
        $moderators = Literature::moderators()->reject(fn ($moderator): bool => $moderator->is(auth()->user()));

        NotificationFacade::send($moderators, new OccurrenceSubmitted($occurrence, $isResubmission));
    }

    public function getStats(): array
    {
        $row = Occurrence::where('user_id', auth()->id())
            ->selectRaw('count(*) as total, count(*) filter (where status = ?) as pending, count(*) filter (where status = ?) as approved, count(*) filter (where status = ?) as rejected', [
                OccurrenceStatus::PENDING->value,
                OccurrenceStatus::APPROVED->value,
                OccurrenceStatus::REJECTED->value,
            ])
            ->first();

        return [
            'total' => $row->total,
            'pending' => $row->pending,
            'approved' => $row->approved,
            'rejected' => $row->rejected,
        ];
    }

    public function render(): View
    {
        return view('livewire.my-species-reports', [
            'stats' => $this->getStats(),
        ])->extends('app')->section('content');
    }
}
