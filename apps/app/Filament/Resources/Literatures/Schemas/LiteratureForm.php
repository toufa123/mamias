<?php

namespace App\Filament\Resources\Literatures\Schemas;

use App\Enums\LiteratureType;
use App\Models\Literature;
use App\Services\DoiMetadataService;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Configures the Filament form schema for literature records.
 * Provides fields for DOI auto-fetch, code, short/full reference,
 * resource type, and link.
 */
class LiteratureForm
{
    /**
     * @param  Schema  $schema  The form schema to configure.
     * @return Schema The configured schema instance.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getBibliographicReferenceSection(),
            ]);
    }

    /**
     * @return Section The bibliographic reference section containing DOI, code, short ref, type, full ref, and link fields.
     */
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
                self::getFilePathField(),
            ])
            ->compact()
            ->columns(4)
            ->columnSpanFull();
    }

    /**
     * @return TextInput The DOI text input with auto-fetch action from Crossref.
     */
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
            ->hintIcon(Heroicon::QuestionMarkCircle,
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

    /**
     * @return TextInput The auto-generated code field (disabled, for display only).
     */
    public static function getCodeField(): TextInput
    {
        return TextInput::make('code')
            ->label('Code')
            ->default(fn () => Literature::generateNextCode())
            ->disabled()
            ->dehydrated(false)
            ->columnSpan(1);
    }

    /**
     * @return TextInput The short reference text input (e.g. "Smith et al., 2024").
     */
    public static function getShortRefField(): TextInput
    {
        return TextInput::make('short_ref')
            ->label('Short Reference')
            ->maxLength(255)
            ->placeholder('Smith et al., 2024')
            ->required()
            ->columnSpan(1);
    }

    /**
     * @return Select The resource type select (mapped from LiteratureType enum).
     */
    public static function getTypeField(): Select
    {
        return Select::make('type')
            ->label('Resource Type')
            ->options(LiteratureType::class)
            ->required()
            ->columnSpan(1);
    }

    /**
     * @return Textarea The full reference textarea with uniqueness validation.
     */
    public static function getFullRefField(): Textarea
    {
        return Textarea::make('full_ref')
            ->label('Full Reference / Title')
            ->rows(3)
            ->placeholder('Smith, J., Doe, A. (2024). Title. Journal, 15(3), 123-145.')
            ->required()
            ->unique(ignoreRecord: true)
            ->live(onBlur: true)
            ->hintIcon(Heroicon::QuestionMarkCircle,
                tooltip: 'Real-time validation checks if this title or reference already exists..')
            ->columnSpanFull();
    }

    /**
     * @return TextInput The optional URL/DOI link field.
     */
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

    /**
     * The attached PDF.
     *
     * The literatures table has always had a nullable file_path column and the
     * tables render a file column for it, but the form had no field to populate
     * it. Hidden when viewing a record that has no attachment, so the view modal
     * does not show an empty upload control.
     *
     * @return FileUpload The PDF upload field.
     */
    public static function getFilePathField(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('PDF')
            ->disk('public')
            ->directory('literatures')
            // Filament defaults uploads to private visibility.
            ->visibility('public')
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize(10240)
            ->downloadable()
            ->openable()
            ->nullable()
            ->visible(fn (string $operation, ?Literature $record): bool => $operation !== 'view' || filled($record?->file_path))
            ->columnSpanFull();
    }
}
