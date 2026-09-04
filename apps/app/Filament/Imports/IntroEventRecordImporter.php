<?php

namespace App\Filament\Imports;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Enums\PathwayType;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\PathwayRecord;
use App\Models\SubregionRecord;
use App\Models\Taxon;
use App\Services\TaxonNormalizer;
use App\Services\WormsService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Importer for IntroEventRecord models (MAMIAS Mediterranean baseline files).
 *
 * Resolves the scientific name to a Taxon (local match first, WoRMS accepted-name
 * fallback), maps status enums, and expands the wide-format subregion and CBD
 * pathway columns into SubregionRecord/PathwayRecord rows via after-save hooks.
 * Rows whose non-blank values fail to resolve (unknown taxon, unrecognised status,
 * ambiguous year such as "1965-67") are imported and flagged needs_review, with the
 * original raw value preserved in notes for manual triage.
 */
class IntroEventRecordImporter extends Importer
{
    protected static ?string $model = IntroEventRecord::class;

    /**
     * Wide-format pathway columns → [CBD category, best-fit subcategory, description].
     * Each column flagged with "1" in a row becomes a PathwayRecord in afterSave().
     * The app's CbdPathwaySubcategory enum is coarser than the baseline file, so the
     * exact source pathway is preserved in the description to avoid losing granularity.
     *
     * @var array<string, array{0: CbdPathwayCategory, 1: CbdPathwaySubcategory, 2: string}>
     */
    private const PATHWAY_MAP = [
        'pathway_release_fishery' => [CbdPathwayCategory::ReleaseIntoNature, CbdPathwaySubcategory::Release_1_1, 'Release in nature: Fishery in the wild (incl. game fishing)'],
        'pathway_release_other' => [CbdPathwayCategory::ReleaseIntoNature, CbdPathwaySubcategory::Release_1_2, 'Release in nature: Other intentional release'],
        'pathway_escape_farmed' => [CbdPathwayCategory::EscapeFromConfinement, CbdPathwaySubcategory::Escape_2_1, 'Escape from confinement: Farmed animals'],
        'pathway_escape_aquaculture' => [CbdPathwayCategory::EscapeFromConfinement, CbdPathwaySubcategory::Escape_2_1, 'Escape from confinement: Aquaculture / mariculture'],
        'pathway_escape_botanical' => [CbdPathwayCategory::EscapeFromConfinement, CbdPathwaySubcategory::Escape_2_2, 'Escape from confinement: Botanical garden / zoo / aquaria'],
        'pathway_escape_pet' => [CbdPathwayCategory::EscapeFromConfinement, CbdPathwaySubcategory::Escape_2_2, 'Escape from confinement: Pet / aquarium / terrarium species'],
        'pathway_escape_livefood' => [CbdPathwayCategory::EscapeFromConfinement, CbdPathwaySubcategory::Escape_2_2, 'Escape from confinement: Live food and live bait'],
        'pathway_contam_nursery' => [CbdPathwayCategory::TransportContaminant, CbdPathwaySubcategory::TransportContaminant_4_1, 'Transport-contaminant: Contaminant nursery material'],
        'pathway_contam_animals' => [CbdPathwayCategory::TransportContaminant, CbdPathwaySubcategory::TransportContaminant_4_3, 'Transport-contaminant: Contaminant on animals'],
        'pathway_contam_parasites' => [CbdPathwayCategory::TransportContaminant, CbdPathwaySubcategory::TransportContaminant_4_2, 'Transport-contaminant: Parasites on animals'],
        'pathway_contam_plants' => [CbdPathwayCategory::TransportContaminant, CbdPathwaySubcategory::TransportContaminant_4_1, 'Transport-contaminant: Contaminant on plants'],
        'pathway_contam_habitat' => [CbdPathwayCategory::TransportContaminant, CbdPathwaySubcategory::TransportContaminant_4_3, 'Transport-contaminant: Transportation of habitat material'],
        'pathway_stowaway_angling' => [CbdPathwayCategory::TransportStowaway, CbdPathwaySubcategory::TransportStowaway_3_5, 'Transport-stowaway: Angling / fishing equipment'],
        'pathway_stowaway_hitchhiker' => [CbdPathwayCategory::TransportStowaway, CbdPathwaySubcategory::TransportStowaway_3_1, 'Transport-stowaway: Hitchhikers on ship/boat'],
        'pathway_stowaway_ballast' => [CbdPathwayCategory::TransportStowaway, CbdPathwaySubcategory::TransportStowaway_3_1, 'Transport-stowaway: Ship/boat ballast water'],
        'pathway_stowaway_hull' => [CbdPathwayCategory::TransportStowaway, CbdPathwaySubcategory::TransportStowaway_3_1, 'Transport-stowaway: Ship/boat hull fouling'],
        'pathway_stowaway_packing' => [CbdPathwayCategory::TransportStowaway, CbdPathwaySubcategory::TransportStowaway_3_4, 'Transport-stowaway: Organic packing material'],
        'pathway_stowaway_other' => [CbdPathwayCategory::TransportStowaway, CbdPathwaySubcategory::TransportStowaway_3_3, 'Transport-stowaway: Other means of transport'],
        'pathway_corridor' => [CbdPathwayCategory::Corridor, CbdPathwaySubcategory::Corridor_5_1, 'Corridor: Interconnected waterways / basins / seas'],
        'pathway_unaided' => [CbdPathwayCategory::Unaided, CbdPathwaySubcategory::Unaided_6_1, 'Unaided: Natural dispersal across borders'],
    ];

    /**
     * Per-subregion column pairs: [Subregion, establishment-status column, first-arrival-year column].
     *
     * @var array<int, array{0: Subregion, 1: string, 2: string}>
     */
    private const SUBREGION_MAP = [
        [Subregion::WMED, 'wmed_establishment_status', 'wmed_first_arrival_year'],
        [Subregion::CMED, 'cmed_establishment_status', 'cmed_first_arrival_year'],
        [Subregion::ADRIA, 'adria_establishment_status', 'adria_first_arrival_year'],
        [Subregion::EMED, 'emed_establishment_status', 'emed_first_arrival_year'],
    ];

    /**
     * Columns where a non-blank raw value that resolves to null signals a data
     * problem and should flag the record for manual review.
     *
     * @var array<int, string>
     */
    private const WATCHED_COLUMNS = [
        'taxon_id',
        'first_introduction_year',
        'nis_status',
        'establishment_status',
        'wmed_establishment_status', 'cmed_establishment_status',
        'adria_establishment_status', 'emed_establishment_status',
        'wmed_first_arrival_year', 'cmed_first_arrival_year',
        'adria_first_arrival_year', 'emed_first_arrival_year',
    ];

    /**
     * Defines the CSV-to-model column mappings for the import.
     *
     * @return array<int, ImportColumn>
     */
    public static function getColumns(): array
    {
        return array_merge([
            ImportColumn::make('taxon_id')
                ->label('Scientific Name')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state): ?int => self::resolveTaxonId($state))
                ->rules(['nullable']),

            ImportColumn::make('first_introduction_year')
                ->label('First Introduction Year')
                ->castStateUsing(fn (?string $state): ?int => self::castCleanYear($state))
                ->rules(['nullable', 'integer']),

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

            // ── Subregion columns (wide format: one status/year pair per EcAp subregion) ──
            // In the baseline files the per-subregion "status" is establishment success
            // (est/cas/unk), not NIS status. fillRecordUsing(fn () => null) keeps these off
            // the IntroEventRecord; values remain in $this->data for afterSave().

            ImportColumn::make('wmed_establishment_status')
                ->label('WMED – Establishment Status')
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::resolveEnum(EstablishmentStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('wmed_first_arrival_year')
                ->label('WMED – First Arrival Year')
                ->castStateUsing(fn (?string $state): ?int => self::castCleanYear($state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('cmed_establishment_status')
                ->label('CMED – Establishment Status')
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::resolveEnum(EstablishmentStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('cmed_first_arrival_year')
                ->label('CMED – First Arrival Year')
                ->castStateUsing(fn (?string $state): ?int => self::castCleanYear($state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('adria_establishment_status')
                ->label('Adriatic – Establishment Status')
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::resolveEnum(EstablishmentStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('adria_first_arrival_year')
                ->label('Adriatic – First Arrival Year')
                ->castStateUsing(fn (?string $state): ?int => self::castCleanYear($state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('emed_establishment_status')
                ->label('EMED – Establishment Status')
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::resolveEnum(EstablishmentStatus::class, $state))
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('emed_first_arrival_year')
                ->label('EMED – First Arrival Year')
                ->castStateUsing(fn (?string $state): ?int => self::castCleanYear($state))
                ->fillRecordUsing(fn () => null),
        ], self::pathwayColumns());
    }

    /**
     * Build the 20 wide-format CBD pathway flag columns from PATHWAY_MAP.
     *
     * @return array<int, ImportColumn>
     */
    private static function pathwayColumns(): array
    {
        return array_map(
            fn (string $key, array $meta): ImportColumn => ImportColumn::make($key)
                ->label($meta[2])
                ->castStateUsing(fn (?string $state): bool => self::isFlagged($state))
                ->fillRecordUsing(fn () => null),
            array_keys(self::PATHWAY_MAP),
            array_values(self::PATHWAY_MAP),
        );
    }

    /**
     * Resolves an existing IntroEventRecord by taxon_id, or creates a new instance
     * if none exists (or the taxon could not be resolved).
     */
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
        $needsReview = false;
        $ambiguousNotes = [];

        foreach (self::WATCHED_COLUMNS as $columnName) {
            $rawValue = $this->rawValue($columnName);

            if (blank($rawValue)) {
                continue;
            }

            if (($this->data[$columnName] ?? null) === null) {
                $needsReview = true;
                $ambiguousNotes[] = self::columnLabel($columnName).': '.trim($rawValue);
            }
        }

        if ($ambiguousNotes !== []) {
            $review = 'Needs review (unresolved on import) — '.implode('; ', $ambiguousNotes);

            $this->record->notes = blank($this->record->notes)
                ? $review
                : $this->record->notes."\n".$review;
        }

        $this->record->needs_review = $needsReview;
    }

    protected function afterSave(): void
    {
        $this->syncSubregionRecords();
        $this->syncPathwayRecords();
    }

    /**
     * Upsert one SubregionRecord per subregion that has a resolved establishment
     * status or first-arrival year. An ambiguous raw year is preserved in the
     * subregion record's notes for triage.
     */
    private function syncSubregionRecords(): void
    {
        foreach (self::SUBREGION_MAP as [$subregion, $statusKey, $yearKey]) {
            $establishmentStatus = $this->data[$statusKey] ?? null;
            $year = $this->data[$yearKey] ?? null;

            $rawYear = $this->rawValue($yearKey);
            $ambiguousYearNote = ($year === null && filled($rawYear))
                ? 'First arrival year (raw): '.trim($rawYear)
                : null;

            if ($establishmentStatus === null && $year === null && $ambiguousYearNote === null) {
                continue;
            }

            SubregionRecord::updateOrCreate(
                [
                    'intro_event_id' => $this->record->id,
                    'subregion' => $subregion,
                ],
                array_filter(
                    [
                        'establishment_status' => $establishmentStatus,
                        'first_arrival_year' => $year,
                        'notes' => $ambiguousYearNote,
                    ],
                    fn (mixed $v): bool => $v !== null,
                ),
            );
        }
    }

    /**
     * Upsert a PathwayRecord for each flagged CBD pathway column. Keyed on
     * (intro_event, category, subcategory, description) so re-imports are idempotent.
     */
    private function syncPathwayRecords(): void
    {
        foreach (self::PATHWAY_MAP as $columnKey => [$category, $subcategory, $description]) {
            if (empty($this->data[$columnKey])) {
                continue;
            }

            PathwayRecord::updateOrCreate(
                [
                    'intro_event_id' => $this->record->id,
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'description' => $description,
                ],
                [
                    'pathway_type' => PathwayType::Primary,
                    'uncertainty' => DataQuality::NA,
                ],
            );
        }
    }

    /**
     * Returns the notification body shown after the import completes.
     *
     * @param  Import  $import  The completed import model.
     */
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

    /**
     * Resolve a scientific name to a local Taxon id. Tries an exact local match
     * first (the catalogue is already WoRMS-normalised), then falls back to WoRMS
     * to translate a synonym/misspelling into the accepted name before matching.
     * Returns null when unresolved so the row is flagged needs_review.
     */
    private static function resolveTaxonId(?string $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        $name = app(TaxonNormalizer::class)->sanitizeEncodingArtifacts($state);
        $name = preg_replace('/\s+/', ' ', trim($name));

        if (blank($name)) {
            return null;
        }

        $localId = Taxon::where('scientificname', $name)->value('id');

        if ($localId) {
            return $localId;
        }

        return self::resolveTaxonViaWorms($name);
    }

    /**
     * Ask WoRMS for the accepted name/AphiaID of a provided name, then match a
     * local Taxon by AphiaID (preferred) or accepted scientific name. Cached per
     * name to avoid duplicate API calls across import chunks.
     */
    private static function resolveTaxonViaWorms(string $name): ?int
    {
        $accepted = Cache::remember(
            'worms_v2.accepted.'.md5($name),
            now()->addDay(),
            function () use ($name): array {
                $record = app(WormsService::class)->getRecordByName($name);

                if (! $record) {
                    return ['name' => null, 'aphia_id' => null];
                }

                $isAccepted = ($record['status'] ?? null) === 'accepted';

                return [
                    'name' => $isAccepted
                        ? ($record['scientificname'] ?? null)
                        : ($record['valid_name'] ?? $record['scientificname'] ?? null),
                    'aphia_id' => $isAccepted
                        ? ($record['AphiaID'] ?? null)
                        : ($record['valid_AphiaID'] ?? $record['AphiaID'] ?? null),
                ];
            },
        );

        if (! empty($accepted['aphia_id'])) {
            $id = Taxon::where('aphia_id', $accepted['aphia_id'])->value('id');

            if ($id) {
                return $id;
            }
        }

        if (filled($accepted['name'])) {
            return Taxon::where('scientificname', $accepted['name'])->value('id');
        }

        return null;
    }

    /**
     * Cast a year cell to an int only when it is an unambiguous 4-digit year in
     * range. Ambiguous values ("1965-67", "<1929", "1970s", "2004?") return null,
     * which flags the row needs_review while still importing it.
     */
    private static function castCleanYear(?string $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        $trimmed = trim($state);

        if (preg_match('/^\d{4}$/', $trimmed) === 1) {
            $year = (int) $trimmed;

            if ($year >= 1800 && $year <= (int) now()->year) {
                return $year;
            }
        }

        return null;
    }

    /**
     * Whether a wide-format flag cell is set (any non-blank value other than "0").
     */
    private static function isFlagged(?string $state): bool
    {
        if (blank($state)) {
            return false;
        }

        $trimmed = trim($state);

        return $trimmed !== '' && $trimmed !== '0';
    }

    /**
     * The raw (pre-cast) CSV value for a mapped column, or null when unmapped/blank.
     */
    private function rawValue(string $column): ?string
    {
        $csvKey = $this->columnMap[$column] ?? null;

        if (blank($csvKey)) {
            return null;
        }

        $raw = $this->originalData[$csvKey] ?? null;

        return blank($raw) ? null : (string) $raw;
    }

    /**
     * Human-readable label for a watched column, used in needs_review notes.
     */
    private static function columnLabel(string $column): string
    {
        return match ($column) {
            'taxon_id' => 'Scientific Name',
            'first_introduction_year' => 'First Introduction Year',
            'nis_status' => 'NIS Status',
            'establishment_status' => 'Establishment Status',
            'wmed_establishment_status' => 'WMED Establishment Status',
            'cmed_establishment_status' => 'CMED Establishment Status',
            'adria_establishment_status' => 'Adriatic Establishment Status',
            'emed_establishment_status' => 'EMED Establishment Status',
            'wmed_first_arrival_year' => 'WMED First Arrival Year',
            'cmed_first_arrival_year' => 'CMED First Arrival Year',
            'adria_first_arrival_year' => 'Adriatic First Arrival Year',
            'emed_first_arrival_year' => 'EMED First Arrival Year',
            default => $column,
        };
    }

    /**
     * Resolve a raw status string to an enum case, tolerating the baseline files'
     * shorthand codes and artifacts (trailing "?", parenthetical qualifiers,
     * slash-duplicated values). Returns null when unrecognised.
     */
    private static function resolveEnum(string $enumClass, ?string $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        // Pre-clean common baseline artifacts before matching.
        $cleaned = preg_replace('/\s*\([^)]*\)\s*/', '', trim($state)); // drop "(FR)" etc.
        $cleaned = trim(preg_replace('/\?+$/', '', $cleaned));          // drop trailing "?"

        if (str_contains($cleaned, '/')) {
            $parts = array_values(array_filter(array_map('trim', explode('/', $cleaned))));

            if (count(array_unique($parts)) === 1) {
                $cleaned = $parts[0]; // "est/est" → "est"
            }
        }

        if (blank($cleaned)) {
            return null;
        }

        $shorthandMap = [
            NisStatus::class => [
                'al' => 'NIS',
                'cry' => 'Cryptogenic',
                'que' => 'Questionable',
                'ques' => 'Questionable',
                'qr' => 'Questionable',
                'rex' => 'Range Expansion',
            ],
            EstablishmentStatus::class => [
                'cas' => 'Casual',
                'est' => 'Established',
                'unk' => 'Unknown',
                'inv' => 'Invasive',
                'dd' => 'DataDeficient',
                'qr' => 'Questionable',
                // No 'rex' here on purpose: range expansion is a NisStatus concept
                // and EstablishmentStatus has no such case, so the entry could only
                // ever fall through to null. It stays mapped under NisStatus above.
            ],
        ];

        $lower = strtolower($cleaned);
        $state = $shorthandMap[$enumClass][$lower] ?? $cleaned;

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
