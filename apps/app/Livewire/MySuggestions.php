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
use Filament\Notifications\Notification;
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
                NisSuggestionsTable::getMapColumn(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Suggestion Details')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->schema(NisSuggestionInfolist::getComponents()),
                $this->editAction(),
                $this->resubmitAction(),
            ])
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
            ->modalHeading('Suggest a New NIS Species')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(NisSuggestionForm::getComponents())
            ->action(function (array $data): void {
                $suggestion = NisSuggestion::create([
                    ...$data,
                    'user_id' => auth()->id(),
                    'status' => LiteratureStatus::PENDING,
                ]);

                if ($literatures = $data['literatures'] ?? []) {
                    $suggestion->literatures()->sync($literatures);
                }

                Notification::make()
                    ->title('Suggestion submitted')
                    ->body('Thank you! Your suggestion will be reviewed by our team.')
                    ->success()
                    ->send();
            });
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit')
            ->icon('tabler-pencil')
            ->color('gray')
            ->visible(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::PENDING)
            ->modalHeading('Edit Suggestion')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(NisSuggestionForm::getComponents())
            ->fillForm(fn (NisSuggestion $record): array => $record->toArray())
            ->action(function (NisSuggestion $record, array $data): void {
                $record->update($data);

                if (array_key_exists('literatures', $data)) {
                    $record->literatures()->sync($data['literatures'] ?? []);
                }

                Notification::make()
                    ->title('Suggestion updated')
                    ->success()
                    ->send();
            });
    }

    public function resubmitAction(): Action
    {
        return Action::make('resubmit')
            ->label('Resubmit')
            ->icon('tabler-refresh')
            ->color('warning')
            ->visible(fn (NisSuggestion $record): bool => $record->status === LiteratureStatus::REJECTED)
            ->requiresConfirmation()
            ->modalHeading('Resubmit suggestion')
            ->modalDescription('A new suggestion will be created with the same data for re-review.')
            ->action(function (NisSuggestion $record): void {
                abort_unless($record->user_id === auth()->id(), 403);

                $resubmitted = NisSuggestion::create([
                    'user_id' => auth()->id(),
                    'aphia_id' => $record->aphia_id,
                    'suggested_scientific_name' => $record->suggested_scientific_name,
                    'authority' => $record->authority,
                    'worms_status' => $record->worms_status,
                    'suggested_common_name' => $record->suggested_common_name,
                    'location' => $record->location,
                    'depth' => $record->depth,
                    'photo_paths' => $record->photo_paths,
                    'document_paths' => $record->document_paths,
                    'status' => LiteratureStatus::PENDING,
                    'resubmitted_from_id' => $record->id,
                ]);

                $resubmitted->literatures()->sync($record->literatures->pluck('id'));

                Notification::make()
                    ->title('Suggestion resubmitted')
                    ->body("Your suggestion for \"{$resubmitted->suggested_scientific_name}\" has been resubmitted for review.")
                    ->success()
                    ->send();
            });
    }

    public function getStats(): array
    {
        $row = NisSuggestion::where('user_id', auth()->id())
            ->selectRaw('count(*) as total, count(*) filter (where status = ?) as pending, count(*) filter (where status = ?) as approved, count(*) filter (where status = ?) as rejected', [
                LiteratureStatus::PENDING->value,
                LiteratureStatus::APPROVED->value,
                LiteratureStatus::REJECTED->value,
            ])
            ->first();

        return [
            'total' => $row->total,
            'pending' => $row->pending,
            'approved' => $row->approved,
            'rejected' => $row->rejected,
        ];
    }

    public function getSpeciesLocations(?string $scientificName): array
    {
        if (! $scientificName) {
            return [];
        }

        return NisSuggestion::query()
            ->where('suggested_scientific_name', $scientificName)
            ->whereNotNull('location')
            ->get(['location'])
            ->flatMap(function (NisSuggestion $s): array {
                $data = json_decode($s->getRawOriginal('location'), true);
                if (isset($data['lat'], $data['lng'])) {
                    $data = [$data];
                }

                if (! is_array($data)) {
                    return [];
                }

                return collect($data)
                    ->filter(fn (array $c) => isset($c['lat'], $c['lng']))
                    ->map(fn (array $c) => ['lat' => (float) $c['lat'], 'lng' => (float) $c['lng']])
                    ->all();
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.my-suggestions', [
            'stats' => $this->getStats(),
        ])->extends('app')->section('content');
    }
}
