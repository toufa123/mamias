<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NisStatus;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Flattens a MAMIAS Mediterranean workbook into one CSV the row-based importer
 * can read, joining the PAN sheet with the four EcAp subregion sheets.
 *
 * Two layouts exist in the wild and both are supported, because columns are
 * located by HEADER rather than by position:
 *
 *  - the RAC/SPA baseline, where each subregion has a PAIR of columns
 *    (establishment status, then year) sharing one header, and the PAN sheet
 *    also carries a Med-wide "status" and "ES success";
 *  - the diversity supplementary, where each subregion has a SINGLE column
 *    holding a year, and no establishment status exists anywhere.
 *
 * Reading by header is what lets one reader serve both. Reading by position
 * would need a second class the day a column moves, and a column moving is the
 * one thing these spreadsheets reliably do.
 *
 * What it will not do: invent a value. Where a workbook has no establishment
 * status the field stays empty for the reviewer, rather than being guessed
 * from the presence of a year.
 *
 * @phpstan-type SubregionRow array{species: string, nis: ?string, establishment: ?string, year: ?string, country: ?string, block: ?string, removed: bool}
 */
final class PanMediterraneanWorkbookReader
{
    public function __construct(private readonly TaxonMatcher $matcher) {}

    public const PAN_SHEET = 'PAN MEDITERRANEAN';

    /** @var list<string> */
    private const SUBREGIONS = ['wmed', 'cmed', 'adria', 'emed'];

    /** A held-out block title on a subregion sheet; the row has no subregion code in A. */
    private const SUBREGION_BLOCK_TITLE = '/^(questionable records|status unresolved|to be removed|removed|shells only|cryptogenic|likely alien polychaeta|polychaeta reported)/i';

    /**
     * Subregion block title fragment => the NIS status its rows take (null:
     * removed, not read as presence). Matched in order: the polychaeta titles
     * also say "questionable or cryptogenic", and "to be removed" says "shells".
     *
     * @var array<string, ?NisStatus>
     */
    private const SUBREGION_BLOCKS = [
        'removed' => null,
        'shells' => null,
        'polychaeta' => NisStatus::DataDeficient,
        'status unresolved' => NisStatus::DataDeficient,
        'questionable' => NisStatus::Questionable,
        'cryptogenic' => NisStatus::Cryptogenic,
    ];

    /** The cell that opens the baseline's data-deficient annex, below the validated list. */
    private const ANNEX_MARKER = 'data deficient';

    /**
     * Annex block title fragment => [NIS status to stage it under (null: not
     * imported), whether its rows carry the year/country/subregion detail].
     * Matched in order, so "removed" wins over "foraminifera".
     *
     * @var array<string, array{0: ?NisStatus, 1: bool}>
     */
    private const ANNEX_BLOCKS = [
        'debatable' => [NisStatus::DataDeficient, false],
        'excluded' => [NisStatus::Questionable, true],
        'removed' => [null, true],
        'polychaeta' => [NisStatus::DataDeficient, true],
        'one location' => [NisStatus::DataDeficient, true],
        'foraminifera' => [NisStatus::DataDeficient, true],
    ];

    /**
     * The emitted header row: the importer's own column names, so mapping is
     * exact rather than guessed from the workbook's prose headers.
     *
     * @var list<string>
     */
    public const HEADERS = [
        'raw_species',
        'raw_author',
        'nis_status',
        'establishment_status',
        'first_introduction_year',
        'first_country',
        'wmed_nis_status', 'wmed_establishment_status', 'wmed_first_arrival_year',
        'cmed_nis_status', 'cmed_establishment_status', 'cmed_first_arrival_year',
        'adria_nis_status', 'adria_establishment_status', 'adria_first_arrival_year',
        'emed_nis_status', 'emed_establishment_status', 'emed_first_arrival_year',
        'notes',
    ];

    public function isSupported(string $path): bool
    {
        return $this->findSheet(IOFactory::load($path), self::PAN_SHEET) instanceof Worksheet;
    }

    /**
     * Every species row of the four subregion sheets, held-out blocks
     * included and flagged, keyed by subregion code then canonical name.
     *
     * @return array<string, array<string, SubregionRow>>
     */
    public function subregionSheets(string $path): array
    {
        return $this->readSubregions(IOFactory::load($path));
    }

    public function toCsvPath(string $sourcePath): string
    {
        $spreadsheet = IOFactory::load($sourcePath);

        $subregionData = [];

        foreach ($this->readSubregions($spreadsheet) as $code => $species) {
            foreach ($species as $speciesKey => $values) {
                // A "removed" block says the species is not in this subregion at all.
                if (! $values['removed']) {
                    $subregionData[$speciesKey][$code] = $values;
                }
            }
        }

        $csvPath = tempnam(sys_get_temp_dir(), 'mamias_panmed_');
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, self::HEADERS, ',', '"', '');

        $seen = [];
        $pan = $this->findSheet($spreadsheet, self::PAN_SHEET);

        if ($pan instanceof Worksheet) {
            $columns = $this->panColumns($pan);
            $annexColumns = null;
            $block = null;

            for ($row = 2; $row <= $pan->getHighestDataRow(); $row++) {
                if ($annexColumns === null && $this->isAnnexMarker($pan, $row)) {
                    $annexColumns = self::shiftColumns($columns);

                    continue;
                }

                if ($annexColumns !== null) {
                    $title = $this->annexTitle($pan, $columns, $annexColumns, $row);

                    if ($title !== null) {
                        $block = ['title' => $title, ...self::annexBlock($title)];

                        continue;
                    }

                    $values = $block === null ? null : $this->annexRow($pan, $columns, $annexColumns, $row, $block, $subregionData, $seen);

                    if ($values !== null) {
                        fputcsv($handle, $values, ',', '"', '');
                    }

                    continue;
                }

                $species = $this->cell($pan, $columns['species'] ?? null, $row);
                $key = $this->matcher->canonical($species);

                // No readable binomial means the cell is not a species — a
                // stray marker like "DD" or a spacer row.
                if ($key === '') {
                    continue;
                }

                $seen[$key] = true;

                fputcsv($handle, $this->panRow($pan, $columns, $row, $species, $subregionData[$key] ?? []), ',', '"', '');
            }
        }

        foreach ($subregionData as $key => $perSubregion) {
            if (! isset($seen[$key])) {
                fputcsv($handle, $this->subregionOnlyRow($perSubregion), ',', '"', '');
            }
        }

        fclose($handle);

        return $csvPath;
    }

    /**
     * Locates the PAN sheet's columns by header text.
     *
     * A subregion header appearing twice is the baseline's paired layout —
     * establishment status first, year second. Appearing once, it is a year.
     *
     * @return array<string, string|array{0: ?string, 1: ?string}>
     */
    private function panColumns(Worksheet $sheet): array
    {
        $headers = $this->headerMap($sheet);
        $columns = [];

        foreach ($headers as $label => $letters) {
            $first = $letters[0];

            match (true) {
                // "species_id" is what one sheet calls the species column;
                // guard against matching "Species / Author" twice.
                (bool) preg_match('/^species/', $label) => $columns['species'] ??= $first,
                (bool) preg_match('/author/', $label) => $columns['author'] ??= $first,
                (bool) preg_match('/year of first introduction/', $label) => $columns['year'] ??= $first,
                (bool) preg_match('/country of first introduction/', $label) => $columns['country'] ??= $first,
                (bool) preg_match('/^(es success|establishment)/', $label) => $columns['establishment'] ??= $first,
                (bool) preg_match('/^status|^status of the species/', $label) => $columns['nis'] ??= $first,
                (bool) preg_match('/citation/', $label) => $columns['citation'] ??= $first,
                in_array($label, self::SUBREGIONS, true) => $columns[$label] = count($letters) > 1
                    ? [$letters[0], $letters[1]]   // paired: status, year
                    : [null, $letters[0]],          // single: year only
                default => null,
            };
        }

        return $columns;
    }

    /**
     * Locates a subregion sheet's columns. Each of the four was maintained
     * separately: the species column is "Species", "Species / Author" or
     * "species_id"; the year is "Year", "Date (of first observation…)" or
     * "Year of first record…"; establishment may be "Establishment",
     * "Establishment success" or absent entirely.
     *
     * @return array<string, string>
     */
    private function subregionColumns(Worksheet $sheet): array
    {
        $headers = $this->headerMap($sheet);
        $columns = [];

        foreach ($headers as $label => $letters) {
            $first = $letters[0];

            match (true) {
                (bool) preg_match('/^species/', $label) => $columns['species'] ??= $first,
                (bool) preg_match('/^establishment/', $label) => $columns['establishment'] ??= $first,
                (bool) preg_match('/^(year|date)/', $label) => $columns['year'] ??= $first,
                (bool) preg_match('/^country/', $label) => $columns['country'] ??= $first,
                (bool) preg_match('/^status/', $label) => $columns['nis'] ??= $first,
                default => null,
            };
        }

        // The species column is second in every subregion sheet (the first is
        // the subregion code), so fall back to B rather than skipping a sheet
        // whose header is blank — EMED's first header is empty.
        $columns['species'] ??= 'B';

        return $columns;
    }

    /**
     * @return array<string, array<string, SubregionRow>>
     */
    private function readSubregions(Spreadsheet $spreadsheet): array
    {
        $sheets = [];

        foreach (self::SUBREGIONS as $code) {
            $sheet = $this->findSheet($spreadsheet, $code);

            if ($sheet instanceof Worksheet) {
                $sheets[$code] = $this->readSubregionSheet($sheet);
            }
        }

        return $sheets;
    }

    /**
     * Each subregion sheet ends, like the PAN sheet, with held-out blocks
     * under a title row ("QUESTIONABLE RECORDS", "STATUS UNRESOLVED", "To be
     * removed"…). A block row takes the block's NIS status in place of its
     * own; a removed block's rows are flagged so they are never read as
     * presence. A species already on the validated list keeps that entry.
     *
     * @return array<string, array{species: string, nis: ?string, establishment: ?string, year: ?string, country: ?string, block: ?string, removed: bool}>
     */
    private function readSubregionSheet(Worksheet $sheet): array
    {
        $columns = $this->subregionColumns($sheet);
        $rows = [];
        $block = null;

        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $species = $this->cell($sheet, $columns['species'], $row);

            if ($this->cell($sheet, 'A', $row) === '' && preg_match(self::SUBREGION_BLOCK_TITLE, $species) === 1) {
                $block = ['title' => $species, 'status' => self::subregionBlockStatus($species)];

                continue;
            }

            $key = $this->matcher->canonical($species);

            if ($key === '' || ($block !== null && isset($rows[$key]))) {
                continue;
            }

            $year = $this->cell($sheet, $columns['year'] ?? null, $row);

            $rows[$key] = [
                'species' => $species,
                'nis' => $block === null ? $this->cell($sheet, $columns['nis'] ?? null, $row) : ($block['status']->value ?? ''),
                'establishment' => $this->cell($sheet, $columns['establishment'] ?? null, $row),
                // The polychaeta lists keep country codes where the year goes.
                'year' => $block === null || preg_match('/\d{4}/', $year) === 1 ? $year : '',
                'country' => $this->cell($sheet, $columns['country'] ?? null, $row),
                'block' => $block['title'] ?? null,
                'removed' => $block !== null && $block['status'] === null,
            ];
        }

        return $rows;
    }

    /**
     * The NIS status a subregion block stands for; null for a removed block.
     */
    private static function subregionBlockStatus(string $title): ?NisStatus
    {
        foreach (self::SUBREGION_BLOCKS as $fragment => $status) {
            if (str_contains(mb_strtolower($title), $fragment)) {
                return $status;
            }
        }

        return NisStatus::DataDeficient;
    }

    /**
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $columns
     * @param  array<string, SubregionRow>  $perSubregion
     * @return list<string>
     */
    private function panRow(Worksheet $pan, array $columns, int $row, string $species, array $perSubregion, ?NisStatus $nisStatus = null, string $note = ''): array
    {
        $values = [
            $species,
            $this->cell($pan, $columns['author'] ?? null, $row),
            $nisStatus->value ?? $this->cell($pan, $columns['nis'] ?? null, $row),
            $this->cell($pan, $columns['establishment'] ?? null, $row),
            $this->cell($pan, $columns['year'] ?? null, $row),
            $this->cell($pan, $columns['country'] ?? null, $row),
        ];

        foreach (self::SUBREGIONS as $code) {
            [$statusColumn, $yearColumn] = $columns[$code] ?? [null, null];

            $panStatus = $this->cell($pan, $statusColumn, $row);
            $panYear = $this->cell($pan, $yearColumn, $row);

            $values[] = $perSubregion[$code]['nis'] ?? '';
            // The PAN sheet's paired status wins; the subregion sheet fills
            // the gap only where PAN is silent.
            $values[] = $panStatus !== '' ? $panStatus : ($perSubregion[$code]['establishment'] ?? '');
            $values[] = $panYear !== '' ? $panYear : ($perSubregion[$code]['year'] ?? '');
        }

        $values[] = trim($note.' '.($perSubregion === []
            ? ''
            : 'Subregion detail merged from: '.implode(', ', array_map('strtoupper', array_keys($perSubregion)))));

        return $values;
    }

    /**
     * Whether this row opens the data-deficient annex. Everything below it is
     * the paper's held-out material, laid out one column right of the header.
     */
    private function isAnnexMarker(Worksheet $pan, int $row): bool
    {
        foreach ($pan->rangeToArray("A{$row}:".$pan->getHighestDataColumn().$row, null, false, false)[0] as $value) {
            if (mb_strtolower(trim((string) $value)) === self::ANNEX_MARKER) {
                return true;
            }
        }

        return false;
    }

    /**
     * The header's column map moved one column right, which is where the
     * annex keeps every value: species in C, year in E, country in N.
     *
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $columns
     * @return array<string, string|array{0: ?string, 1: ?string}>
     */
    private static function shiftColumns(array $columns): array
    {
        $next = fn (?string $letter): ?string => $letter === null
            ? null
            : Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($letter) + 1);

        return array_map(fn (string|array $letter): string|array => is_array($letter) ? array_map($next, $letter) : $next($letter), $columns);
    }

    /**
     * A block title ("Likely alien polychaeta", "Removed foraminifera") is
     * text in the species or the column before it, with no year, country or
     * comment beside it. Debatable species always carry a comment, so they
     * are never mistaken for one.
     *
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $columns
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $annexColumns
     */
    private function annexTitle(Worksheet $pan, array $columns, array $annexColumns, int $row): ?string
    {
        $detail = $this->cell($pan, $annexColumns['year'] ?? null, $row)
            .$this->cell($pan, $annexColumns['country'] ?? null, $row)
            .$this->cell($pan, $annexColumns['cmed'][0] ?? null, $row);

        if ($detail !== '') {
            return null;
        }

        $title = $this->cell($pan, $columns['species'] ?? null, $row) ?: $this->cell($pan, $annexColumns['species'] ?? null, $row);

        return $title === '' ? null : $title;
    }

    /**
     * @return array{status: ?NisStatus, detail: bool}
     */
    private static function annexBlock(string $title): array
    {
        foreach (self::ANNEX_BLOCKS as $fragment => [$status, $detail]) {
            if (str_contains(mb_strtolower($title), $fragment)) {
                return ['status' => $status, 'detail' => $detail];
            }
        }

        // An unknown block is still annex material: held out, never NIS.
        return ['status' => NisStatus::DataDeficient, 'detail' => true];
    }

    /**
     * One annex species as a CSV row, staged under its block's status, or null
     * when the row is not imported: a removed block, a row marked "REMOVE" or
     * "NAT" (native), or a cell with no readable binomial.
     *
     * Debatable species carry only a name and the experts' comment ("NIS in
     * TR - CRY elsewhere"); the comment goes to the notes rather than being
     * read as a CMED status.
     *
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $columns
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $annexColumns
     * @param  array{title: string, status: ?NisStatus, detail: bool}  $block
     * @param  array<string, array<string, SubregionRow>>  $subregionData
     * @param  array<string, true>  $seen
     * @return list<string>|null
     */
    private function annexRow(Worksheet $pan, array $columns, array $annexColumns, int $row, array $block, array $subregionData, array &$seen): ?array
    {
        $flags = [mb_strtoupper($this->cell($pan, 'A', $row)), mb_strtoupper($this->cell($pan, $columns['species'] ?? null, $row))];

        if ($block['status'] === null || array_intersect($flags, ['REMOVE', 'NAT']) !== []) {
            return null;
        }

        $species = $this->cell($pan, $annexColumns['species'] ?? null, $row);
        $key = $this->matcher->canonical($species);

        if ($key === '') {
            return null;
        }

        $seen[$key] = true;

        $comment = $block['detail'] ? '' : $this->cell($pan, $annexColumns['cmed'][0] ?? null, $row);
        $citation = $this->cell($pan, $annexColumns['citation'] ?? null, $row);
        $note = trim("RAC/SPA data-deficient annex: {$block['title']}.".($comment === '' ? '' : " {$comment}.").($citation === '' ? '' : " Ref: {$citation}."));

        $rowColumns = $block['detail']
            ? $annexColumns
            : array_intersect_key($annexColumns, array_flip(['species', 'author', 'year']));

        return $this->panRow($pan, $rowColumns, $row, $species, $subregionData[$key] ?? [], $block['status'], $note);
    }

    /**
     * @param  array<string, SubregionRow>  $perSubregion
     * @return list<string>
     */
    private function subregionOnlyRow(array $perSubregion): array
    {
        $first = reset($perSubregion);
        $sheets = implode(', ', array_map('strtoupper', array_keys($perSubregion)));
        ['country' => $country, 'note' => $countryNote] = $this->splitCountry((string) ($first['country'] ?? ''));

        // Held out in every subregion that lists it: held out basin-wide too,
        // under the shared status, else Data Deficient. Never left blank, or
        // the species would count as an unclassified NIS.
        $heldOut = array_filter($perSubregion, fn (array $values): bool => ($values['block'] ?? null) !== null);
        $statuses = array_unique(array_column($heldOut, 'nis'));
        $nisStatus = count($heldOut) < count($perSubregion) ? '' : (count($statuses) === 1 ? reset($statuses) : NisStatus::DataDeficient->value);

        $values = [
            (string) $first['species'],
            '',
            $nisStatus,
            '',
            '',
            $country,
        ];

        foreach (self::SUBREGIONS as $code) {
            $values[] = $perSubregion[$code]['nis'] ?? '';
            $values[] = $perSubregion[$code]['establishment'] ?? '';
            $values[] = $perSubregion[$code]['year'] ?? '';
        }

        $values[] = trim("Not on the PAN sheet — built from {$sheets} only. Med-wide values need checking. {$countryNote}");

        return $values;
    }

    /**
     * Lowercased, collapsed header text => the column letters carrying it.
     * A header appearing more than once keeps every letter, in order: that is
     * how the baseline's paired subregion columns are recognised.
     *
     * @return array<string, list<string>>
     */
    private function headerMap(Worksheet $sheet): array
    {
        $map = [];

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $label = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $cell->getValue())));

                if ($label !== '') {
                    $map[$label][] = $cell->getColumn();
                }
            }
        }

        return $map;
    }

    /**
     * Reads a cell, normalising the non-breaking spaces these spreadsheets are
     * full of. A U+00A0 between genus and species is invisible on screen but
     * stops the name tokenising, so the species silently fails to match.
     */
    private function cell(Worksheet $sheet, ?string $column, int $row): string
    {
        if ($column === null) {
            return '';
        }

        $value = (string) $sheet->getCell($column.$row)->getValue();

        return trim(preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $value) ?? $value);
    }

    /**
     * A country cell long enough to be a sentence is a comment, not a country
     * — the CMED sheet carries reviewer notes there ("Since bryozoa in Malta
     * are not well-studied…"). Keeping it as the country both overflows the
     * column and asserts something false, so it moves to the notes instead.
     */
    private function splitCountry(string $value): array
    {
        return mb_strlen($value) > 120
            ? ['country' => '', 'note' => $value]
            : ['country' => $value, 'note' => ''];
    }

    /**
     * Matches a sheet by name, tolerating the whitespace the workbooks ship:
     * the PAN sheet is named "PAN MEDITERRANEAN " with a trailing space, so
     * getSheetByName() with the obvious name returns null and every PAN row
     * would be silently skipped.
     */
    private function findSheet(Spreadsheet $spreadsheet, string $name): ?Worksheet
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (strcasecmp(trim($sheet->getTitle()), trim($name)) === 0) {
                return $sheet;
            }
        }

        return null;
    }
}
