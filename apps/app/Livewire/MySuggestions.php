<?php

namespace App\Livewire;

use App\Enums\LiteratureStatus;
use App\Filament\Resources\NisSuggestions\Schemas\NisSuggestionInfolist;
use App\Filament\Resources\NisSuggestions\Tables\NisSuggestionsTable;
use App\Models\NisSuggestion;
use App\Services\WormsService;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Icetalker\FilamentStepper\Forms\Components\Stepper;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
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
            ->actions([
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
            ->schema([
                Grid::make(4)->schema($this->getTaxonFields())->columnSpanFull(),
                Hidden::make('worms_status'),
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Location')
                            ->icon('tabler-map-pin')
                            ->schema($this->getLocationAndMediaFields()),
                        Tab::make('Bibliography')
                            ->icon('tabler-book')
                            ->schema($this->getDetailFields()),
                    ]),
            ])
            ->action(fn (array $data) => $this->handleSuggestionCreation($data));
    }

    private function getTaxonFields(): array
    {
        $wormsService = app(WormsService::class);

        return [
            Select::make('aphia_id')
                ->label('Scientific Name (search WoRMS)')
                ->searchable()
                ->required()
                ->columnSpan(2)
                ->getSearchResultsUsing(fn (string $search) => $this->searchWoRMS($search, $wormsService))
                ->getOptionLabelUsing(fn (mixed $value) => $this->getWormsLabel($value, $wormsService))
                ->live()
                ->afterStateUpdated(fn (Set $set, mixed $state) => $this->populateTaxonData($set, $state, $wormsService))
                ->hintIcon('tabler-info-circle', tooltip: 'Type at least 4 characters to search the WoRMS database'),
            Hidden::make('suggested_scientific_name')
                ->dehydrated(true)
                ->rules([Rule::unique('taxas', 'scientificname')])
                ->validationMessages(['unique' => 'This species already exists in the MAMIAS catalogue. Please contribute to the existing record instead.']),
            TextInput::make('authority')->label('Authority')->readOnly()->dehydrated(true),
            Stepper::make('depth')
                ->label('Depth (m)')
                ->minValue(0)
                ->maxValue(11000)
                ->step(1),
        ];
    }

    private function getDetailFields(): array
    {
        return [
            Textarea::make('bibliography')
                ->label('Bibliography (optional)')
                ->maxLength(1000)
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('doi')
                ->label('DOI (optional)')
                ->nullable()
                ->regex('/^10\.\d{4,9}\/[-._;()\/: A-Z0-9]+$/i')
                ->validationMessages(['regex' => 'Enter a valid DOI (e.g. 10.1000/182).'])
                ->columnSpanFull(),
            FileUpload::make('document_paths')
                ->label('Supporting Documents (optional)')
                ->multiple()
                ->acceptedFileTypes(['application/pdf', 'text/plain'])
                ->disk('local')
                ->directory('suggestions/documents')
                ->visibility('private')
                ->maxSize(10240)
                ->columnSpanFull(),
        ];
    }

    private function getLocationAndMediaFields(): array
    {
        return [
            MapPicker::make('location')
                ->label('Location (optional — click the map to set coordinates)')
                ->height(400)
                ->center(36, 14)
                ->zoom(5)
                ->extraAttributes(['style' => 'min-height:400px'])
                ->columnSpanFull(),
            FileUpload::make('photo_paths')
                ->label('Photos (optional)')
                ->multiple()
                ->image()
                ->disk('public')
                ->directory('suggestions/photos')
                ->visibility('public')
                ->maxSize(5120)
                ->columnSpanFull(),
        ];
    }

    private function searchWoRMS(string $search, WormsService $wormsService): array
    {
        if (strlen($search) < 4) {
            return [];
        }

        return collect($wormsService->searchSpecies($search))
            ->mapWithKeys(fn (array $r) => [
                $r['AphiaID'] => $r['scientificname'].($r['authority'] ? ' '.$r['authority'] : '').' ['.$r['status'].']',
            ])
            ->toArray();
    }

    private function getWormsLabel(mixed $value, WormsService $wormsService): ?string
    {
        if (! $value) {
            return null;
        }

        $record = $wormsService->getRecordByAphiaID((int) $value);

        return $record
            ? $record['scientificname'].($record['authority'] ? ' '.$record['authority'] : '').' ['.$record['status'].']'
            : (string) $value;
    }

    private function populateTaxonData(Set $set, mixed $state, WormsService $wormsService): void
    {
        if (! $state) {
            return;
        }

        $record = $wormsService->getRecordByAphiaID((int) $state);
        if ($record) {
            $set('suggested_scientific_name', $record['scientificname']);
            $set('authority', $record['authority'] ?? '');
            $set('worms_status', $record['status'] ?? '');
        }
    }

    private function handleSuggestionCreation(array $data): void
    {
        NisSuggestion::create([
            ...$data,
            'user_id' => auth()->id(),
            'status' => LiteratureStatus::PENDING,
        ]);

        Notification::make()
            ->title('Suggestion submitted')
            ->body('Thank you! Your suggestion will be reviewed by our team.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.my-suggestions')->extends('app')->section('content');
    }
}
