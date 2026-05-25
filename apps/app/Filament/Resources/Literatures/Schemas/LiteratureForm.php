<?php

    namespace App\Filament\Resources\Literatures\Schemas;

    use App\Enums\LiteratureType;
    use App\Models\Literature;
    use App\Services\DoiMetadataService;
    use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
    use Filament\Actions\Action;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\Textarea;
    use Filament\Forms\Components\TextInput;
    use Filament\Notifications\Notification;
    use Filament\Schemas\Components\Section;
    use Filament\Schemas\Components\Utilities\Set;
    use Filament\Schemas\Schema;

    class LiteratureForm
    {
        public static function configure(Schema $schema): Schema
        {
            return $schema
                ->components([
                    self::getBibliographicReferenceSection(),
                ]);
        }

        public static function getBibliographicReferenceSection(): Section
        {
            return Section::make('Bibliographic Reference')
                ->schema([
                    self::getDoiField(),
                    self::getCodeField(),
                    self::getShortRefField(),
                    self::getTypeField(),
                    self::getFullRefField(),
                    self::getLinkField(),
                ])
                ->compact()
                ->columns(4)
                ->columnSpanFull();
        }

        public static function getDoiField(): TextInput
        {
            return TextInput::make('doi')
                ->label('DOI')
                ->unique(ignoreRecord: true)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, $state) {
                    if (empty($state)) {
                        return;
                    }

                    $service = app(DoiMetadataService::class);
                    $metadata = $service->fetchFromCrossref($state);

                    if ($metadata) {
                        $set('full_ref', $metadata['full_ref']);
                        $set('short_ref', $metadata['short_ref']);
                        $set('type', $metadata['type']->value);
                        $set('link', $metadata['link']);

                        Notification::make()
                            ->title('Metadata fetched automatically')
                            ->success()
                            ->send();
                    }
                })
                ->placeholder('10.1000/182')
                ->hintIcon('heroicon-m-question-mark-circle',
                    tooltip: 'Enter the DOI and click "Fetch" to automatically fill in the fields. Validation checks for duplicates upon entry.')
                ->prefixAction(
                    Action::make('openDoi')
                        ->icon(TablerIcon::Link)
                        ->color('primary')
                        ->tooltip('Open DOI in new tab')
                        ->url(fn ($state) => $state ? "https://doi.org/{$state}" : null)
                        ->openUrlInNewTab()
                        ->hidden(fn ($state) => empty($state))
                )
                ->suffixActions([
                    Action::make('fetchFromDoi')
                        ->icon('tabler-refresh')
                        ->tooltip('Fetch metadata')
                        ->action(function (Set $set, $state) {
                            if (empty($state)) {
                                Notification::make()
                                    ->title('Missing DOI')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $service = app(DoiMetadataService::class);
                            $metadata = $service->fetchFromCrossref($state);

                            if ($metadata) {
                                $set('full_ref', $metadata['full_ref']);
                                $set('short_ref', $metadata['short_ref']);
                                $set('type', $metadata['type']->value);
                                $set('link', $metadata['link']);

                                Notification::make()
                                    ->title('Metadata fetched successfully')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Could not find this DOI')
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->columnSpan(1);
        }

        public static function getCodeField(): TextInput
        {
            return TextInput::make('code')
                ->label('Code')
                ->default(fn () => Literature::generateNextCode())
                ->disabled()
                ->dehydrated(false)
                ->columnSpan(1);
        }

        public static function getShortRefField(): TextInput
        {
            return TextInput::make('short_ref')
                ->label('Short Reference')
                ->maxLength(255)
                ->placeholder('Smith et al., 2024')
                ->required()
                ->columnSpan(1);
        }

        public static function getTypeField(): Select
        {
            return Select::make('type')
                ->label('Resource Type')
                ->options(LiteratureType::class)
                ->required()
                ->columnSpan(1);
        }

        public static function getFullRefField(): Textarea
        {
            return Textarea::make('full_ref')
                ->label('Full Reference / Title')
                ->rows(3)
                ->placeholder('Smith, J., Doe, A. (2024). Title. Journal, 15(3), 123-145.')
                ->required()
                ->unique(ignoreRecord: true)
                ->live(onBlur: true)
                ->hintIcon('heroicon-m-question-mark-circle',
                    tooltip: 'Real-time validation checks if this title or reference already exists..')
                ->columnSpanFull();
        }

        public static function getLinkField(): TextInput
        {
            return TextInput::make('link')
                ->label('Link')
                ->url()
                ->maxLength(2048)
                ->placeholder('https://doi.org/...')
                ->nullable()
                ->columnSpanFull();
        }
    }
