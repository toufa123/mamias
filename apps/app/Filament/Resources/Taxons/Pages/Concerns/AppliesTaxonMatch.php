<?php

declare(strict_types=1);

namespace App\Filament\Resources\Taxons\Pages\Concerns;

use App\Enums\Catalogue_Status;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

/**
 * Trait that applies a WoRMS taxon name match to a taxon create/edit form.
 *
 * Listens for the `applyTaxonMatch` event and updates the scientific name,
 * notes, and catalogue status accordingly.
 */
trait AppliesTaxonMatch
{
    #[On('applyTaxonMatch')]
    public function applyTaxonMatch(string $matchedName, string $originalName): void
    {
        $currentNotes = $this->data['notes'] ?? '';
        $newNotes = trim(($currentNotes ? $currentNotes."\n" : '').'Original name before match: '.$originalName);

        $this->data['notes'] = $newNotes;
        $this->data['scientificname'] = $matchedName;
        $this->data['catalogue_status'] = Catalogue_Status::checked_not_accepted->value;

        $this->onTaxonMatchApplied($matchedName);

        Notification::make()
            ->title('Match Applied')
            ->body("Scientific name updated to '{$matchedName}'. Original name saved to notes.")
            ->success()
            ->send();
    }

    protected function onTaxonMatchApplied(string $matchedName): void {}
}
