<?php

namespace App\Filament\Imports;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use App\Services\TaxonNormalizer;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class TaxonImporter extends Importer
{
    protected static ?string $model = Taxon::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('scientificname')
                ->label('Scientific Name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->castStateUsing(fn (?string $state): ?string => self::sanitize($state)),
        ];
    }

    public function resolveRecord(): Taxon
    {
        $scientificname = $this->data['scientificname'] ?? null;

        if ($scientificname) {
            $existing = Taxon::where('scientificname', $scientificname)->first();

            if ($existing) {
                $importId = $this->import->getKey();
                $duplicates = Cache::get("taxon-import-duplicates-{$importId}", []);
                $duplicates[] = [
                    'scientificname' => $scientificname,
                    'id' => $existing->getKey(),
                ];
                Cache::put("taxon-import-duplicates-{$importId}", $duplicates, now()->addHour());

                throw new RowImportFailedException("Duplicate taxon: {$scientificname} (ID: {$existing->getKey()})");
            }
        }

        return (new Taxon)->fill([
            'catalogue_status' => Catalogue_Status::not_checked,
        ]);
    }

    public function beforeSave(): void
    {
        $normalizer = app(TaxonNormalizer::class);
        $normalizer->normalize($this->record);
    }

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
