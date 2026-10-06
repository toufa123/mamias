<?php

namespace App\Filament\Resources\Occurrences\Pages;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Filament\Resources\Occurrences\OccurrenceResource;
use App\Filament\Resources\Occurrences\Schemas\OccurrenceForm;
use App\Filament\Widgets\OccurrencesMap;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Livewire\Attributes\On;

class ListOccurrences extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = OccurrenceResource::class;

    /** Staff-entered occurrences skip the review queue: the moderators are the ones adding them. */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New occurrence')
                ->icon('tabler-binoculars')
                ->modalWidth(Width::FiveExtraLarge)
                ->schema(OccurrenceForm::getComponents())
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'user_id' => auth()->id(),
                    'status' => OccurrenceStatus::APPROVED,
                ])
                ->after(fn () => $this->dispatch('occurrence-moderated')),
            LiteratureGuide::action('occurrences', 'Occurrences guide'),
        ];
    }

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
