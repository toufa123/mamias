<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Models\Literature;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddLiterature extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return LiteratureForm::configure($schema)
            ->statePath('data')
            ->model(Literature::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $data['code'] = Literature::generateNextCode();
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();

        Literature::create($data);

        $this->form->fill();

        notify()
            ->success()
            ->title('Bibliographic reference added successfully.')
            ->send();

        $this->redirect(route('profile'));
    }

    public function render(): View
    {
        return view('livewire.add-literature')
            ->extends('app')
            ->section('content');
    }
}
