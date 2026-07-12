<?php

namespace App\Filament\Imports;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use App\Services\TaxonNormalizer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

/**
 * Importer for Taxon models. Resolves records by scientific name and
 * runs the TaxonNormalizer before saving to populate rank, kingdom,
 * phylum, and other taxonomic fields.
 */
class TaxonImporter extends Importer
{
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
     * Resolves an existing Taxon by scientific name, or creates a new
     * instance with a default not_checked catalogue status.
     */
    public function resolveRecord(): Taxon
    {
        $scientificname = $this->data['scientificname'] ?? null;

        if ($scientificname) {
            $existing = Taxon::where('scientificname', $scientificname)->first();

            if ($existing) {
                return $existing;
            }
        }

        return (new Taxon)->fill([
            'catalogue_status' => Catalogue_Status::not_checked,
        ]);
    }

    /**
     * Runs the TaxonNormalizer on the record before persisting to
     * populate taxonomic fields (rank, kingdom, phylum, etc.).
     */
    public function beforeSave(): void
    {
        $normalizer = app(TaxonNormalizer::class);
        $normalizer->normalize($this->record);
    }

    /**
     * Returns the notification body shown after the import completes.
     *
     * @param  Import  $import  The completed import model.
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your taxon import has completed and '
            .Number::format($import->successful_rows).' '
            .str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    private static function sanitize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return app(TaxonNormalizer::class)->sanitizeEncodingArtifacts($value);
    }
}
