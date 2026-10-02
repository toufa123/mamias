<?php

namespace App\Filament\Resources\Occurrences\Pages;

use App\Filament\Resources\Occurrences\OccurrenceResource;
use App\Filament\Widgets\OccurrencesMap;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListOccurrences extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = OccurrenceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [OccurrencesMap::class];
    }

    /** A map pin was clicked: open that row's view modal, which carries approve/reject. */
    #[On('open-occurrence')]
    public function openOccurrence(int $id): void
    {
        $this->mountAction('view', context: ['table' => true, 'recordKey' => (string) $id]);
    }
}
