<?php

namespace App\Livewire;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Filament\Resources\Literatures\Tables\LiteraturesTable;
use App\Models\Literature;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MyReferences extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Literature::forUser(auth()->user()))
            ->columns([
                LiteraturesTable::getCodeColumn(),
                LiteraturesTable::getShortRefColumn(),
                LiteraturesTable::getDoiColumn(),
                LiteraturesTable::getTypeColumn(),
                LiteraturesTable::getStatusColumn(),
                LiteraturesTable::getFileColumn(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LiteratureStatus::class),
                SelectFilter::make('type')
                    ->options(LiteratureType::class),
            ])
            ->actions([
                ViewAction::make()
                    ->form([
                        LiteratureForm::getBibliographicReferenceSection(),
                    ]),
            ])
            ->recordAction(ViewAction::class)
            ->headerActions([
                $this->createAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Add New Reference')
            ->icon('tabler-file-plus')
            ->modalHeading('Submit a New Reference')
            ->modalDescription('Enter a DOI to auto-fill fields, or fill them manually. Submissions are reviewed before publication.')
            ->button()
            ->color('primary')
            ->size('lg')
            ->form([
                LiteratureForm::getBibliographicReferenceSection(),
            ])
            ->action(function (array $data) {
                $data['status'] = LiteratureStatus::PENDING;

                Literature::create($data);

                Notification::make()
                    ->title('Reference submitted')
                    ->body('Your reference has been submitted for review.')
                    ->success()
                    ->send();
            });
    }

    public function render(): View
    {
        return view('livewire.my-references')
            ->extends('app')
            ->section('content');
    }
}
