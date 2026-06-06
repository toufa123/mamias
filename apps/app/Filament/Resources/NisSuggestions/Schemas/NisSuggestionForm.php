<?php

namespace App\Filament\Resources\NisSuggestions\Schemas;

use App\Filament\Resources\Literatures\Schemas\LiteratureForm;
use App\Services\WormsService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class NisSuggestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    public static function getComponents(): array
    {
        return [
            Grid::make(3)->schema(self::getTaxonFields())->columnSpanFull(),
            Hidden::make('worms_status'),
            Hidden::make('kingdom'),
            Tabs::make('Details')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Photos')
                        ->schema([
                            FileUpload::make('photo_paths')
                                ->label('Photos')
                                ->required()
                                ->panelLayout('grid')
                                ->loadingIndicatorPosition('left')
                                ->panelAspectRatio('8:1')
                                ->removeUploadedFileButtonPosition('right')
                                ->uploadButtonPosition('left')
                                ->uploadProgressIndicatorPosition('left')
                                ->multiple()
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->disk('public')
                                ->directory('suggestions/photos')
                                ->visibility('public')
                                ->maxSize(5120)
                                ->imagePreviewHeight('40')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Notes')
                        ->schema([
                            Textarea::make('notes')
                                ->label('Notes')
                                ->rows(5)
                                ->columnSpanFull(),
                            Select::make('literatures')
                                ->label('Bibliographic References')
                                ->relationship('literatures', 'short_ref')
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->createOptionForm([LiteratureForm::getBibliographicReferenceSection()])
                                ->hintIcon('tabler-info-circle', tooltip: 'Select existing references or create a new one with DOI auto-fill.')
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    public static function getTaxonFields(): array
    {
        $wormsService = app(WormsService::class);

        return [
            Select::make('suggested_scientific_name')
                ->label('Scientific Name (search WoRMS)')
                ->searchable()
                ->required()
                ->getSearchResultsUsing(fn (string $search) => self::searchWoRMS($search, $wormsService))
                ->getOptionLabelUsing(fn (mixed $value) => self::getWormsLabel($value, $wormsService))
                ->live()
                ->afterStateUpdated(fn (Set $set, mixed $state) => self::populateTaxonData($set, $state, $wormsService))
                ->hintIcon('tabler-info-circle', tooltip: 'Type at least 4 characters to search the WoRMS database'),
            TextInput::make('authority')
                ->label('Authority')
                ->readOnly()
                ->dehydrated(true),
            TextInput::make('aphia_id')
                ->label('Aphia ID')
                ->readOnly()
                ->dehydrated(true),
        ];
    }

    public static function searchWoRMS(string $search, WormsService $wormsService): array
    {
        if (strlen($search) < 4) {
            return [];
        }

        return collect($wormsService->searchSpecies($search))
            ->mapWithKeys(fn (array $r) => [
                $r['AphiaID'] => $r['scientificname'].' ['.($r['status'] === 'accepted' ? 'verified' : $r['status']).']',
            ])
            ->toArray();
    }

    public static function getWormsLabel(mixed $value, WormsService $wormsService): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_numeric($value)) {
            $record = $wormsService->getRecordByAphiaID((int) $value);

            return $record
                ? $record['scientificname'].' ['.($record['status'] === 'accepted' ? 'verified' : $record['status']).']'
                : (string) $value;
        }

        return (string) $value;
    }

    public static function populateTaxonData(Set $set, mixed $state, WormsService $wormsService): void
    {
        if (! $state) {
            $set('aphia_id', null);
            $set('authority', null);
            $set('worms_status', null);
            $set('kingdom', null);

            return;
        }

        $record = $wormsService->getRecordByAphiaID((int) $state);
        if ($record) {
            $set('suggested_scientific_name', $record['scientificname']);
            $set('aphia_id', (int) $state);
            $set('authority', $record['authority'] ?? '');
            $set('worms_status', $record['status'] ?? '');
            $set('kingdom', $record['kingdom'] ?? '');
        }
    }
}
