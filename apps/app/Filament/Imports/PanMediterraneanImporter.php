<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Models\StagingIntroEvent;
use App\Models\Taxon;
use App\Services\TaxonMatcher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Imports the PAN MEDITERRANEAN sheet of the supplementary workbook into
 * staging — never into the catalogue.
 *
 * The sheet supplies a Med-wide year, country and NIS status, plus one year
 * per EcAp subregion. It does NOT supply establishment status, at either
 * level: there is no such column anywhere in the workbook. That gap is left
 * as a gap. A first-record year is not evidence of establishment — the older
 * paired-column files stated the two separately ("cas" alongside a year) —
 * so inferring one from the other would put a claim in the catalogue that no
 * source made. The reviewer sets it, in bulk where that is honest.
 *
 * Every non-obvious call is written to `proposals` as
 * {field: {raw, proposed, reason}} so the review screen can justify a value
 * without re-deriving it, and so a bad import can be explained after the fact.
 *
 * Unmatched species stay unmatched. The importer will not create a taxon from
 * a spreadsheet cell: a row without taxon_id simply cannot be promoted, which
 * is a far better failure than 300 speculative taxa appearing in the
 * catalogue because one workbook spelled things its own way.
 */
class PanMediterraneanImporter extends Importer
{
    protected static ?string $model = StagingIntroEvent::class;

    /**
     * Raw cell values that produced a null, collected per row.
     *
     * @var array<string, array{raw: string|null, proposed: string|null, reason: string}>
     */
    protected array $rowProposals = [];

    /**
     * @return array<int, ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('raw_species')
                ->label('Species')
                ->requiredMapping()
                ->guess(['Species', 'species', 'species_id', 'Species / Author'])
                ->rules(['required']),

            ImportColumn::make('raw_author')
                ->label('Authority')
                ->guess(['author_id', 'Author', 'authority']),

            ImportColumn::make('first_introduction_year')
                ->label('Year of first introduction (Med)')
                ->guess(['YEAR OF FIRST INTRODUCTION IN Med', 'Year', 'year'])
                ->castStateUsing(fn (?string $state): ?int => self::castYear($state)),

            ImportColumn::make('first_country')
                ->label('Country of first introduction (Med)')
                ->guess(['COUNTRY OF FIRST INTRODUCTION MED', 'Country of first record in subregion']),

            ImportColumn::make('nis_status')
                ->label('NIS status')
                ->guess(['nis_status', 'Status of the species', 'status', 'Status'])
                ->castStateUsing(fn (?string $state): ?NisStatus => self::castNisStatus($state)),

            // Present in the RAC/SPA baseline ("ES success"), absent from the
            // diversity supplementary. Where it is absent the field stays
            // empty and the reviewer sets it.
            ImportColumn::make('establishment_status')
                ->label('Establishment status')
                ->guess(['establishment_status', 'ES success', 'Establishment'])
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::castEstablishmentStatus($state)),

            ImportColumn::make('notes')
                ->label('Provenance')
                ->guess(['notes']),

            // One status + one year per subregion, joined from that
            // subregion's own sheet by PanMediterraneanWorkbookReader. The
            // status is a NIS status, not an establishment status — no sheet
            // in the workbook carries the latter.
            ...self::subregionColumns(),
        ];
    }

    /**
     * @return array<int, ImportColumn>
     */
    protected static function subregionColumns(): array
    {
        $columns = [];

        foreach (StagingIntroEvent::SUBREGION_MAP as [$subregion, $establishmentColumn, $yearColumn, $nisColumn]) {
            $code = $subregion->value;

            $columns[] = ImportColumn::make($nisColumn)
                ->label($code.' NIS status')
                ->guess([$nisColumn])
                ->castStateUsing(fn (?string $state): ?NisStatus => self::castNisStatus($state));

            $columns[] = ImportColumn::make($establishmentColumn)
                ->label($code.' establishment status')
                ->guess([$establishmentColumn])
                ->castStateUsing(fn (?string $state): ?EstablishmentStatus => self::castEstablishmentStatus($state));

            $columns[] = ImportColumn::make($yearColumn)
                ->label($code.' first record')
                ->guess([$yearColumn, $code])
                ->castStateUsing(fn (?string $state): ?int => self::castYear($state));
        }

        return $columns;
    }

    /**
     * Always a new staging row. Staging is a scratch area per import run, so a
     * re-run is discarded by deleting that run's rows rather than by trying to
     * match a spreadsheet line to an existing one — the sheet has no stable
     * identifier to match on.
     */
    public function resolveRecord(): StagingIntroEvent
    {
        return new StagingIntroEvent;
    }

    protected function beforeSave(): void
    {
        $this->rowProposals = [];

        $this->record->import_id = $this->import->getKey();
        $this->record->source_sheet = 'PAN MEDITERRANEAN';
        $this->record->review_status = StagingIntroEvent::STATUS_PENDING;

        $this->resolveTaxon();
        $this->flagUnresolved('first_introduction_year', 'Year of first introduction');
        $this->flagUnresolved('nis_status', 'NIS status');
        $this->flagUnresolved('establishment_status', 'Establishment status');

        foreach (StagingIntroEvent::SUBREGION_MAP as [$subregion, $establishmentColumn, $yearColumn]) {
            $this->flagUnresolved($yearColumn, $subregion->value.' first record');
            $this->flagUnresolved($establishmentColumn, $subregion->value.' establishment status');
        }

        $this->deriveMediterraneanYear();

        // Recorded as an explicit absence rather than left silent, so the
        // review screen can say why the field is empty instead of looking
        // like the importer forgot it. Only when the workbook genuinely had
        // nothing to say — the RAC/SPA baseline does carry "ES success".
        if ($this->record->establishment_status === null && ! isset($this->rowProposals['establishment_status'])) {
            $this->rowProposals['establishment_status'] = [
                'raw' => null,
                'proposed' => null,
                'reason' => 'This workbook has no establishment status for the row. Set during review.',
            ];
        }

        $this->record->proposals = $this->rowProposals;
    }

    /**
     * The Mediterranean first-introduction year is the earliest subregion
     * record: a species entered the sea when it was first seen anywhere in it,
     * so the earliest of the four subregion years is the basin-wide date.
     *
     * The PAN sheet's own Med-wide column is kept in the comparison rather
     * than discarded — if it is earlier than every subregion year it is still
     * the first record, and the subregion sheets simply do not cover it. Any
     * disagreement is recorded so the reviewer sees that the value was
     * derived rather than read.
     */
    protected function deriveMediterraneanYear(): void
    {
        $stated = $this->record->first_introduction_year;

        $subregionYears = [];

        foreach (StagingIntroEvent::SUBREGION_MAP as [$subregion, , $yearColumn]) {
            if ($this->record->{$yearColumn} !== null) {
                $subregionYears[$subregion->value] = (int) $this->record->{$yearColumn};
            }
        }

        $earlier = self::earliestMediterraneanYear($stated, $subregionYears);

        if ($earlier === null) {
            return;
        }

        ['year' => $year, 'from' => $from] = $earlier;

        $this->record->first_introduction_year = $year;

        $this->rowProposals['first_introduction_year'] = [
            'raw' => $stated === null ? null : (string) $stated,
            'proposed' => (string) $year,
            'reason' => $stated === null
                ? "Taken from the earliest subregion record ({$from} {$year}); the sheet gave no Med-wide year."
                : "The sheet said {$stated}, but {$from} records {$year} — the earliest subregion record is the Mediterranean first record.",
        ];
    }

    /**
     * Returns the earliest subregion year and which subregion it came from,
     * or null when the stated Med-wide year already is the earliest and
     * nothing needs changing.
     *
     * @param  array<string, int>  $subregionYears
     * @return array{year: int, from: string}|null
     */
    public static function earliestMediterraneanYear(?int $stated, array $subregionYears): ?array
    {
        if ($subregionYears === []) {
            return null;
        }

        $earliest = min($subregionYears);

        if ($stated !== null && $stated <= $earliest) {
            return null;
        }

        return [
            'year' => $earliest,
            'from' => (string) array_search($earliest, $subregionYears, true),
        ];
    }

    /**
     * Matches the species against the catalogue, which is the reference: a
     * name resolves only if the catalogue itself claims it, by accepted name
     * or by a recorded synonym. Ambiguous and uncertain names are left for a
     * human rather than guessed at — see TaxonMatcher.
     */
    protected function resolveTaxon(): void
    {
        $name = trim((string) ($this->record->raw_species ?? ''));

        if ($name === '') {
            return;
        }

        $result = app(TaxonMatcher::class)->match($name);

        $this->record->taxon_id = $result['taxon_id'];

        // A clean accepted-name hit needs no explanation; everything else
        // does — including a successful synonym match, because the reviewer
        // is being shown a different name than the file contained.
        if ($result['reason'] === null) {
            return;
        }

        $this->rowProposals['taxon_id'] = [
            'raw' => trim($name.' '.(string) ($this->record->raw_author ?? '')),
            'proposed' => $result['taxon_id'] === null
                ? null
                : (string) Taxon::whereKey($result['taxon_id'])->value('scientificname'),
            'reason' => $result['reason'],
        ];
    }

    /**
     * Records a proposal when the file had something in a cell but nothing
     * survived casting — the case a reviewer most needs to see.
     */
    protected function flagUnresolved(string $column, string $label): void
    {
        $raw = $this->originalData[$this->columnMap[$column] ?? ''] ?? null;

        if (blank($raw) || $this->record->{$column} !== null) {
            return;
        }

        $this->rowProposals[$column] = [
            'raw' => trim((string) $raw),
            'proposed' => null,
            'reason' => $label.' could not be read from "'.trim((string) $raw).'".',
        ];
    }

    /**
     * Years arrive as "2018", "1965-67", "before 1900", "2018?" and similar.
     * A four-digit year within a plausible range is taken; a range yields its
     * earliest year. Anything else stays null and is flagged.
     */
    protected static function castYear(?string $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        if (! preg_match_all('/\b(1[5-9]\d{2}|20[0-4]\d)\b/', (string) $state, $matches)) {
            return null;
        }

        return (int) min(array_map('intval', $matches[1]));
    }

    protected static function castNisStatus(?string $state): ?NisStatus
    {
        if (blank($state)) {
            return null;
        }

        // The baseline writes shorthand ("al"), the supplementary writes it
        // out ("non-indigenous"). Anything unrecognised — and the status
        // column in the baseline does contain stray country names — returns
        // null and is flagged for review rather than forced into a case.
        return match (mb_strtolower(trim((string) $state))) {
            'non-indigenous', 'nis', 'alien', 'al' => NisStatus::NIS,
            'cryptogenic', 'cry' => NisStatus::Cryptogenic,
            'questionable', 'que' => NisStatus::Questionable,
            'range expansion', 'range-expansion', 'rex' => NisStatus::RangeExpansion,
            default => null,
        };
    }

    /**
     * The baseline's "ES success" vocabulary. Its own values leak between
     * columns — "al" and "likely alien" turn up here, which are NIS statuses
     * rather than establishment ones — so only the establishment vocabulary
     * is accepted and the rest goes to review.
     */
    protected static function castEstablishmentStatus(?string $state): ?EstablishmentStatus
    {
        if (blank($state)) {
            return null;
        }

        return match (mb_strtolower(trim((string) $state))) {
            'established', 'est' => EstablishmentStatus::Established,
            'casual', 'cas' => EstablishmentStatus::Casual,
            'invasive', 'inv' => EstablishmentStatus::Invasive,
            'unknown', 'unk' => EstablishmentStatus::Unknown,
            'questionable', 'que' => EstablishmentStatus::Questionable,
            'vagrant', 'vag' => EstablishmentStatus::Vagrant,
            'data deficient', 'dd' => EstablishmentStatus::DataDeficient,
            'excluded', 'exc' => EstablishmentStatus::Excluded,
            default => null,
        };
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $rows = number_format($import->successful_rows);
        $failed = $import->getFailedRowsCount();

        $body = "{$rows} rows staged for review. Nothing has been written to the catalogue yet — open Staging intro events to confirm and promote them.";

        return $failed > 0
            ? $body.' '.number_format($failed).' rows failed.'
            : $body;
    }
}
