<?php

namespace App\Livewire;

use App\Enums\LiteratureStatus;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionForm;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionInfolist;
use App\Filament\Resources\NisSuggestions\Tables\NisSuggestionsTable;
use App\Models\NisSuggestion;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MySuggestions extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(NisSuggestion::where('user_id', auth()->id()))
            ->columns([
                NisSuggestionsTable::getScientificNameColumn(),
                NisSuggestionsTable::getAuthorityColumn(),
                NisSuggestionsTable::getStatusColumn(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Suggestion Details')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->schema(NisSuggestionInfolist::getComponents()),
            ])
            ->recordAction(ViewAction::class)
            ->headerActions([$this->createAction()])
            ->defaultSort('created_at', 'desc');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Suggest New NIS')
            ->icon('tabler-bulb')
            ->button()
            ->color('primary')
            ->size('lg')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading('Suggest a New NIS Species')
            ->modalDescription('Search for the species in the WoRMS database, provide additional details, and optionally mark the location on the map.')
            ->schema(NisSuggestionForm::getComponents())
            ->action(fn (array $data) => $this->handleSuggestionCreation($data));
    }

    private function handleSuggestionCreation(array $data): void
    {
        NisSuggestion::create([
            ...$data,
            'user_id' => auth()->id(),
            'status' => LiteratureStatus::PENDING,
        ]);

        notify()
            ->success()
            ->title('Suggestion submitted')
            ->message('Thank you! Your suggestion will be reviewed by our team.')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.my-suggestions')->extends('app')->section('content');
    }
}
