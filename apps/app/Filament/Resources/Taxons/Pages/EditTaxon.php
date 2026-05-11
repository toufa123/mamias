<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Filament\Resources\Taxons\TaxonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

use App\Enums\Catalogue_Status;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class EditTaxon extends EditRecord
{
    protected static string $resource = TaxonResource::class;

    #[On('applyTaxonMatch')]
    public function applyTaxonMatch(string $matchedName, string $originalName): void
    {
        $currentNotes = $this->data['notes'] ?? '';
        $newNotes = trim(($currentNotes ? $currentNotes . "\n" : "") . "Original name before match: " . $originalName);

        $this->data['notes'] = $newNotes;
        $this->data['scientificname'] = $matchedName;
        $this->data['scientificname_editable'] = true;
        $this->data['catalogue_status'] = Catalogue_Status::checked_not_accepted->value;

        Notification::make()
            ->title('Match Applied')
            ->body("Scientific name updated to '{$matchedName}'. Original name saved to notes.")
            ->success()
            ->send();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
    
    
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
