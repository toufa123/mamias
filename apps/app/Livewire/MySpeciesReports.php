<?php

namespace App\Livewire;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceForm;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceInfolist;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
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
                TextColumn::make('observed_at')->label('Observed')->dateTime()->sortable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Occurrence Details')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->schema(OccurrenceInfolist::getComponents()),
                $this->editAction(),
            ])
            ->headerActions([$this->createAction()])
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
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(OccurrenceForm::getComponents())
            ->action(function (array $data): void {
                Occurrence::create([
                    ...$data,
                    'user_id' => auth()->id(),
                    'status' => OccurrenceStatus::PENDING,
                ]);

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
            ->label('Edit')
            ->icon('tabler-pencil')
            ->color('gray')
            ->visible(fn (Occurrence $record): bool => $record->status === OccurrenceStatus::PENDING)
            ->modalHeading('Edit Occurrence')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(OccurrenceForm::getComponents())
            ->fillForm(fn (Occurrence $record): array => $record->toArray())
            ->action(function (Occurrence $record, array $data): void {
                $record->update($data);

                Notification::make()
                    ->title('Occurrence updated')
                    ->success()
                    ->send();
            });
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
