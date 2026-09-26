<?php

namespace App\Filament\Resources\Literatures\Schemas;

use App\Enums\LiteratureType;
use App\Filament\Resources\Literatures\LiteratureResource;
use App\Models\Literature;
use App\Services\DoiMetadataService;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Configures the Filament form schema for literature records.
 * Provides fields for DOI auto-fetch, code, short/full reference,
 * resource type, year, and link. Both the DOI and the full reference point
 * at an existing record instead of only failing validation.
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
                ...self::getDetailFields(),
            ])
            ->compact()
            ->columns(4)
            ->columnSpanFull();
    }

    /**
     * DOI-first submission: a DOI fills the details step from Crossref, where
     * the submitter checks and corrects them. Without a DOI they go straight on.
     *
     * @return array<Step>
     */
    public static function getSubmissionSteps(): array
    {
        return [
            Step::make('DOI')
                ->icon('tabler-link')
                ->description('Fills the details for you')
                ->schema([
                    self::getDoiField(),
                    Text::make('No DOI? Leave it empty and click Next to enter the reference by hand.'),
                ]),
            Step::make('Details')
                ->icon('tabler-book')
                ->description('Check or complete')
                ->schema(self::getDetailFields())
                ->columns(4),
        ];
    }

    /**
     * @return array<Component> Every field but the DOI.
     */
    private static function getDetailFields(): array
    {
        return [
            self::getCodeField(),
            self::getShortRefField(),
            self::getTypeField(),
            self::getYearField(),
            self::getFullRefField(),
            self::getLinkField(),
            self::getFilePathField(),
        ];
    }

    /**
     * @return TextInput The DOI text input with auto-fetch action from Crossref.
     */
    public static function getDoiField(): TextInput
    {
        return TextInput::make('doi')
            ->label('DOI')
            ->unique(ignoreRecord: true)
            ->validationMessages(['unique' => 'This reference is already in MAMIAS — see the note below the field.'])
            ->belowContent(fn (?string $state, ?Literature $record): array => self::existingReferenceNote(
                $state ? Literature::where('doi', DoiMetadataService::normalize($state))->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))->first() : null,
            ))
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state) {
                $doi = DoiMetadataService::normalize($state);

                // Normalize before validation sees it, so the unique rule
                // matches a stored DOI pasted as a URL or with "doi:".
                $set('doi', $doi);

                if ($doi && self::fillFromDoi($set, $doi)) {
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
                    // Nothing to fetch into a read-only form (the View modal).
                    ->hidden(fn (TextInput $component): bool => $component->isDisabled())
                    ->action(function (Set $set, ?string $state) {
                        $doi = DoiMetadataService::normalize($state);

                        if (! $doi) {
                            Notification::make()
                                ->title('Missing DOI')
                                ->warning()
                                ->send();

                            return;
                        }

                        $set('doi', $doi);

                        if (self::fillFromDoi($set, $doi)) {
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
            ->columnSpanFull();
    }

    /**
     * "Already in MAMIAS as …" under a field, with a link for those who may
     * open the record. Empty when there is no match.
     *
     * @return array<Component|Action>
     */
    private static function existingReferenceNote(?Literature $existing, string $prefix = 'Already in MAMIAS as'): array
    {
        if (! $existing) {
            return [];
        }

        return [
            Text::make("{$prefix} {$existing->code} — {$existing->short_ref} ({$existing->status->getLabel()}).")
                ->color('warning'),
            Action::make('openExisting'.$existing->getKey())
                ->label('Open')
                ->link()
                ->url(LiteratureResource::getUrl('edit', ['record' => $existing], panel: 'mamias'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => LiteratureResource::canEdit($existing)),
        ];
    }

    /**
     * Fill the reference fields from Crossref. Returns false when the DOI is unknown.
     */
    private static function fillFromDoi(Set $set, string $doi): bool
    {
        $metadata = app(DoiMetadataService::class)->fetchFromCrossref($doi);

        if (! $metadata) {
            return false;
        }

        $set('full_ref', $metadata['full_ref']);
        $set('short_ref', $metadata['short_ref']);
        $set('type', $metadata['type']->value);
        $set('link', $metadata['link']);
        $set('year', $metadata['year']);

        if ($metadata['is_retracted']) {
            Notification::make()
                ->title('This article has been retracted')
                ->body('Crossref lists a retraction notice for this DOI.')
                ->danger()
                ->persistent()
                ->send();
        }

        return true;
    }

    /**
     * @return TextInput The code, shown when viewing only: it is assigned on
     *                   save, and the edit page carries it in its heading.
     */
    public static function getCodeField(): TextInput
    {
        return TextInput::make('code')
            ->label('Code')
            ->disabled()
            ->dehydrated(false)
            ->visibleOn('view')
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
            ->columnSpan(2);
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
     * @return TextInput The publication year, used to order references on species pages.
     */
    public static function getYearField(): TextInput
    {
        return TextInput::make('year')
            ->label('Year')
            ->integer()
            ->minValue(1700)
            ->maxValue((int) date('Y') + 1)
            ->nullable()
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
            ->validationMessages(['unique' => 'This reference is already in MAMIAS.'])
            ->live(onBlur: true)
            ->hintIcon(Heroicon::QuestionMarkCircle,
                tooltip: 'Checked against existing references as you type, including near matches.')
            // Exact duplicates fail validation; this catches the same paper
            // typed with other punctuation or casing.
            ->belowContent(fn (?string $state, string $operation, ?Literature $record): array => $operation === 'view' || mb_strlen((string) $state) < 20
                ? []
                : self::existingReferenceNote(
                    Literature::similarTo($state)->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))->first(),
                    'Looks like',
                ))
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
