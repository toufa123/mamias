<?php

namespace App\Filament\Imports;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Services\TaxonNormalizer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class IntroEventRecordImporter extends Importer
{
    protected static ?string $model = IntroEventRecord::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('taxon_id')
                ->label('Scientific Name')
                ->requiredMapping()
                ->castStateUsing(function (?string $state): ?int {
                    if (blank($state)) {
                        return null;
                    }

                    $name = app(TaxonNormalizer::class)->sanitizeEncodingArtifacts($state);
                    $name = preg_replace('/\s+/', ' ', trim($name));

                    return Taxon::where('scientificname', $name)->value('id');
                })
                ->rules(['required']),

            ImportColumn::make('first_introduction_year')
                ->label('First Introduction Year')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1800', 'max:'.now()->year]),

            ImportColumn::make('first_country')
                ->label('Country')
                ->castStateUsing(function (?string $state): ?array {
                    if (blank($state)) {
                        return null;
                    }

                    return array_values(array_filter(array_map('trim', explode(',', $state))));
                })
                ->rules(['nullable']),

            ImportColumn::make('nis_status')
                ->label('NIS Status')
                ->castStateUsing(fn (?string $state): ?NisStatus => self::resolveEnum(NisStatus::class, $state))
                ->rules(['nullable']),

            ImportColumn::make('establishment_status')
                ->label('Establishment Status')
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::resolveEnum(EstablishmentStatus::class, $state))
                ->rules(['nullable']),

            ImportColumn::make('notes')
                ->label('Notes')
                ->rules(['nullable']),

            // ── Subregion columns (wide format: one column-pair per EcAp subregion) ──
            // fillRecordUsing(fn () => null) prevents Filament from writing these onto
            // IntroEventRecord. Values remain in $this->data for afterSave().

            ImportColumn::make('wmed_nis_status')
                ->label('WMED – NIS Status')
                ->castStateUsing(fn (?string $state): ?NisStatus => self::resolveEnum(NisStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('wmed_first_arrival_year')
                ->label('WMED – First Arrival Year')
                ->numeric()
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('cmed_nis_status')
                ->label('CMED – NIS Status')
                ->castStateUsing(fn (?string $state): ?NisStatus => self::resolveEnum(NisStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('cmed_first_arrival_year')
                ->label('CMED – First Arrival Year')
                ->numeric()
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('adria_nis_status')
                ->label('Adriatic – NIS Status')
                ->castStateUsing(fn (?string $state): ?NisStatus => self::resolveEnum(NisStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('adria_first_arrival_year')
                ->label('Adriatic – First Arrival Year')
                ->numeric()
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('emed_nis_status')
                ->label('EMED – NIS Status')
                ->castStateUsing(fn (?string $state): ?NisStatus => self::resolveEnum(NisStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('emed_first_arrival_year')
                ->label('EMED – First Arrival Year')
                ->numeric()
                ->fillRecordUsing(fn () => null),
        ];
    }

    public function resolveRecord(): IntroEventRecord
    {
        $taxonId = $this->data['taxon_id'] ?? null;

        if ($taxonId) {
            $existing = IntroEventRecord::where('taxon_id', $taxonId)->first();

            if ($existing) {
                return $existing;
            }
        }

        return new IntroEventRecord;
    }

    protected function afterFill(): void
    {
        // Columns where a non-blank raw value that resolves to null signals a data problem.
        $watchedColumns = [
            'nis_status',
            'establishment_status',
            'wmed_nis_status', 'cmed_nis_status', 'adria_nis_status', 'emed_nis_status',
        ];

        $needsReview = false;

        foreach ($watchedColumns as $columnName) {
            $csvKey = $this->columnMap[$columnName] ?? null;

            if (blank($csvKey)) {
                continue;
            }

            $rawValue = $this->originalData[$csvKey] ?? null;

            if (blank($rawValue)) {
                continue;
            }

            if (($this->data[$columnName] ?? null) === null) {
                $needsReview = true;
                break;
            }
        }

        $this->record->needs_review = $needsReview;
    }

    protected function afterSave(): void
    {
        $subregionMap = [
            Subregion::WMED => ['wmed_nis_status',  'wmed_first_arrival_year'],
            Subregion::CMED => ['cmed_nis_status',  'cmed_first_arrival_year'],
            Subregion::ADRIA => ['adria_nis_status', 'adria_first_arrival_year'],
            Subregion::EMED => ['emed_nis_status',  'emed_first_arrival_year'],
        ];

        foreach ($subregionMap as $subregion => [$statusKey, $yearKey]) {
            $nisStatus = $this->data[$statusKey] ?? null;
            $year = $this->data[$yearKey] ?? null;

            if ($nisStatus === null && $year === null) {
                continue;
            }

            SubregionRecord::updateOrCreate(
                [
                    'intro_event_id' => $this->record->id,
                    'subregion' => $subregion,
                ],
                array_filter(
                    ['nis_status' => $nisStatus, 'first_arrival_year' => $year],
                    fn (mixed $v): bool => $v !== null,
                ),
            );
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Intro event import completed: '
            .Number::format($import->successful_rows).' '
            .str('row')->plural($import->successful_rows).' imported.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '
                .str('row')->plural($failed).' failed.';
        }

        return $body;
    }

    private static function resolveEnum(string $enumClass, ?string $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        $trimmed = trim($state);

        $shorthandMap = [
            NisStatus::class => [
                'al' => 'NIS',
                'cry' => 'Cryptogenic',
                'que' => 'Questionable',
                'ques' => 'Questionable',
            ],
            EstablishmentStatus::class => [
                'cas' => 'Casual',
                'est' => 'Established',
                'unk' => 'Unknown',
                'inv' => 'Invasive',
                'dd' => 'DataDeficient',
            ],
        ];

        $lower = strtolower($trimmed);

        if (isset($shorthandMap[$enumClass][$lower])) {
            $state = $shorthandMap[$enumClass][$lower];
        }

        $normalized = Str::studly(strtolower(str_replace([' ', '-'], '_', $state)));

        foreach ($enumClass::cases() as $case) {
            if (strtolower($case->name) === strtolower($state) ||
                strtolower($case->name) === strtolower($normalized)) {
                return $case;
            }
        }

        return null;
    }
}
