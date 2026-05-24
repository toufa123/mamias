<?php

namespace App\Filament\Resources\Taxons\Pages;

use App\Enums\Catalogue_Status;
use App\Filament\Resources\Taxons\TaxonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateTaxon extends CreateRecord
{
    protected static string $resource = TaxonResource::class;

    #[On('applyTaxonMatch')]
    public function applyTaxonMatch(string $matchedName, string $originalName): void
    {
        $currentNotes = $this->data['notes'] ?? '';
        $newNotes = trim(($currentNotes ? $currentNotes."\n" : '').'Original name before match: '.$originalName);

        $this->data['notes'] = $newNotes;
        $this->data['scientificname'] = $matchedName;
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
}
