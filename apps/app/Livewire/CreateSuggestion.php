<?php

namespace App\Livewire;

use App\Enums\LiteratureStatus;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionForm;
use App\Models\NisSuggestion;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateSuggestion extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(NisSuggestionForm::getComponents())
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

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

        $this->redirect(route('suggestions'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.create-suggestion')->extends('app')->section('content');
    }
}
