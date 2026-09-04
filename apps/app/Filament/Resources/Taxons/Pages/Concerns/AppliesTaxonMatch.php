<?php

declare(strict_types=1);

namespace App\Filament\Resources\Taxons\Pages\Concerns;

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

/**
 * Trait that applies a WoRMS taxon name match or GBIF data to a taxon create/edit form.
 *
 * Listens for the `applyTaxonMatch` event (WoRMS fuzzy match) and the
 * `applyGbifMatch` event (GBIF fallback), updating form fields accordingly.
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

    /**
     * @param  array<string, mixed>  $gbifData
     */
    #[On('applyGbifMatch')]
    public function applyGbifMatch(array $gbifData): void
    {
        $fieldsToApply = ['scientificname', 'authority', 'kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'rank'];

        foreach ($fieldsToApply as $field) {
            if (array_key_exists($field, $gbifData) && $gbifData[$field] !== null) {
                $this->data[$field] = $gbifData[$field];
            }
        }

        $this->data['worms_status'] = Worms_Status::not_applicable->value;
        $this->data['catalogue_status'] = Catalogue_Status::manual_entry->value;
        $this->data['scientificname_editable'] = true;

        $this->onTaxonMatchApplied($gbifData['scientificname'] ?? '');

        Notification::make()
            ->title('GBIF Data Applied')
            ->body('Taxonomic classification filled from GBIF. Review the fields and save when ready.')
            ->success()
            ->send();
    }

    protected function onTaxonMatchApplied(string $matchedName): void {}
}
