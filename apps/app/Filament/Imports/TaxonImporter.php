<?php

namespace App\Filament\Imports;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use App\Services\TaxonNormalizer;
use App\Services\WormsService;
use Filament\Actions\Action;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Notifications\Notification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Importer for Taxon models. Skips rows whose scientific name already exists
 * in the catalogue, either literally or once WoRMS resolves it to its accepted
 * name. Taxon itself normalizes scientific names in its `saving` hook, so the
 * importer only mirrors that normalization when deciding whether a row is a
 * duplicate.
 */
class TaxonImporter extends Importer
{
    /**
     * Validation error recorded against rows skipped because the scientific
     * name is already in the catalogue. Also used to count them afterwards.
     */
    public const DUPLICATE_ROW_MESSAGE = 'Scientific name already exists in the database. Row not imported.';

    /**
     * Opening of the error recorded against rows WoRMS resolves onto a taxon
     * already in the catalogue. The accepted name is appended, so skipped-row
     * counting matches on this prefix rather than the whole message.
     */
    public const WORMS_DUPLICATE_MESSAGE_PREFIX = 'WoRMS matches this scientific name to ';

    protected static ?string $model = Taxon::class;

    /**
     * Defines the CSV-to-model column mappings for the import.
     *
     * @return array<int, ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('scientificname')
                ->label('Scientific Name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->castStateUsing(fn (?string $state): ?string => self::sanitize($state)),

            //            ImportColumn::make('aphia_id')
            //                ->label('Aphia ID')
            //                ->numeric()
            //                ->rules(['nullable', 'integer']),
            //
            //            ImportColumn::make('authority')
            //                ->label('Authority')
            //                ->rules(['nullable', 'max:255'])
            //                ->castStateUsing(fn (?string $state): ?string => self::sanitize($state)),
            //
            //            ImportColumn::make('rank')
            //                ->label('Rank')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('kingdom')
            //                ->label('Kingdom')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('phylum')
            //                ->label('Phylum')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('class')
            //                ->label('Class')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('order')
            //                ->label('Order')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('family')
            //                ->label('Family')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('genus')
            //                ->label('Genus')
            //                ->rules(['nullable', 'max:100']),
            //
            //            ImportColumn::make('Easin_id')
            //                ->label('EASIN ID')
            //                ->rules(['nullable', 'max:255']),
            //
            //            ImportColumn::make('notes')
            //                ->label('Notes')
            //                ->rules(['nullable']),
        ];
    }

    /**
     * Creates a new Taxon with a default not_checked catalogue status, or
     * skips the row when the scientific name is already in the catalogue.
     *
     * @throws RowImportFailedException When the name, or the accepted name
     *                                  WoRMS resolves it to, already exists.
     */
    public function resolveRecord(): Taxon
    {
        $scientificname = $this->data['scientificname'] ?? null;

        if (filled($scientificname)) {
            if (Taxon::findDuplicateOf($scientificname)) {
                throw new RowImportFailedException(self::DUPLICATE_ROW_MESSAGE);
            }

            if ($existing = self::findWormsDuplicateOf($scientificname)) {
                throw new RowImportFailedException(self::wormsDuplicateMessage($existing));
            }
        }

        return (new Taxon)->fill([
            'catalogue_status' => Catalogue_Status::not_checked,
        ]);
    }

    /**
     * Resolves the row's name through WoRMS and looks for a catalogue entry
     * under the accepted AphiaID or accepted name. Catches synonyms and
     * superseded names that a literal comparison reads as new species.
     *
     * A WoRMS outage resolves to nothing, so the row imports rather than
     * blocking the catalogue on a third party being up.
     */
    private static function findWormsDuplicateOf(string $scientificname): ?Taxon
    {
        $accepted = app(WormsService::class)->getAcceptedIdentity($scientificname);

        if (filled($accepted['aphia_id'])) {
            $existing = Taxon::withTrashed()->where('aphia_id', $accepted['aphia_id'])->first();

            if ($existing) {
                return $existing;
            }
        }

        return filled($accepted['name'])
            ? Taxon::findDuplicateOf($accepted['name'])
            : null;
    }

    private static function wormsDuplicateMessage(Taxon $existing): string
    {
        return self::WORMS_DUPLICATE_MESSAGE_PREFIX.$existing->scientificname.', which is already in the database. Row not imported.';
    }

    /**
     * Saves inside a savepoint so a unique-name collision this importer could
     * not predict is recorded as a skipped row instead of aborting the whole
     * chunk. Postgres poisons the surrounding transaction on any failed
     * statement, which used to leave the import stuck part-way through.
     *
     * @throws RowImportFailedException When the scientific name is taken.
     */
    public function saveRecord(): void
    {
        try {
            DB::transaction(fn () => parent::saveRecord());
        } catch (UniqueConstraintViolationException) {
            throw new RowImportFailedException(self::DUPLICATE_ROW_MESSAGE);
        }
    }

    /**
     * Returns the notification body shown after the import completes,
     * breaking out rows skipped as already present in the catalogue.
     *
     * @param  Import  $import  The completed import model.
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your taxon import has completed and '
            .Number::format($import->successful_rows).' '
            .str('row')->plural($import->successful_rows).' imported.';

        $skippedRowsCount = static::getSkippedRowsCount($import);

        if ($skippedRowsCount) {
            $body .= ' '.Number::format($skippedRowsCount).' '
                .str('species')->plural($skippedRowsCount)
                .' skipped because '.($skippedRowsCount === 1 ? 'it is' : 'they are')
                .' already in the database.';
        }

        $failedRowsCount = $import->getFailedRowsCount() - $skippedRowsCount;

        if ($failedRowsCount > 0) {
            $body .= ' '.Number::format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    /**
     * Adds a download action for the spreadsheet of rows that were not
     * imported (skipped duplicates and failures alike).
     */
    public static function modifyCompletedNotification(Notification $notification, Import $import): Notification
    {
        if (! $import->getFailedRowsCount()) {
            return $notification;
        }

        return $notification->actions([
            ...$notification->getActions(),
            Action::make('downloadNotImportedRowsXlsx')
                ->label('Download not imported species (Excel)')
                ->color('warning')
                ->url(
                    route('imports.not-imported-rows.download', ['import' => $import, 'format' => 'xlsx'], absolute: false),
                    shouldOpenInNewTab: true,
                )
                ->markAsRead(),
        ]);
    }

    /**
     * Counts the rows of an import that were skipped because their scientific
     * name is already in the catalogue.
     */
    public static function getSkippedRowsCount(Import $import): int
    {
        return $import->failedRows()
            ->where(fn ($query) => $query
                ->where('validation_error', self::DUPLICATE_ROW_MESSAGE)
                ->orWhere('validation_error', 'like', self::WORMS_DUPLICATE_MESSAGE_PREFIX.'%'))
            ->count();
    }

    private static function sanitize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return app(TaxonNormalizer::class)->sanitizeEncodingArtifacts($value);
    }
}
