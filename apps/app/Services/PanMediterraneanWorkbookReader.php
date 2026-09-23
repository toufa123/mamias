<?php

declare(strict_types=1);

namespace App\Services;

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
 */
final class PanMediterraneanWorkbookReader
{
    public function __construct(private readonly TaxonMatcher $matcher) {}

    public const PAN_SHEET = 'PAN MEDITERRANEAN';

    /** @var list<string> */
    private const SUBREGIONS = ['wmed', 'cmed', 'adria', 'emed'];

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

    public function toCsvPath(string $sourcePath): string
    {
        $spreadsheet = IOFactory::load($sourcePath);

        $subregionData = [];

        foreach (self::SUBREGIONS as $code) {
            $sheet = $this->findSheet($spreadsheet, $code);

            if (! $sheet instanceof Worksheet) {
                continue;
            }

            foreach ($this->readSubregionSheet($sheet) as $speciesKey => $values) {
                $subregionData[$speciesKey][$code] = $values;
            }
        }

        $csvPath = tempnam(sys_get_temp_dir(), 'mamias_panmed_');
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, self::HEADERS, ',', '"', '');

        $seen = [];
        $pan = $this->findSheet($spreadsheet, self::PAN_SHEET);

        if ($pan instanceof Worksheet) {
            $columns = $this->panColumns($pan);

            for ($row = 2; $row <= $pan->getHighestDataRow(); $row++) {
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
     * @return array<string, array{nis: ?string, establishment: ?string, year: ?string, country: ?string, species: string}>
     */
    private function readSubregionSheet(Worksheet $sheet): array
    {
        $columns = $this->subregionColumns($sheet);
        $rows = [];

        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $species = $this->cell($sheet, $columns['species'], $row);
            $key = $this->matcher->canonical($species);

            if ($key === '') {
                continue;
            }

            $rows[$key] = [
                'species' => $species,
                'nis' => $this->cell($sheet, $columns['nis'] ?? null, $row),
                'establishment' => $this->cell($sheet, $columns['establishment'] ?? null, $row),
                'year' => $this->cell($sheet, $columns['year'] ?? null, $row),
                'country' => $this->cell($sheet, $columns['country'] ?? null, $row),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string|array{0: ?string, 1: ?string}>  $columns
     * @param  array<string, array<string, ?string>>  $perSubregion
     * @return list<string>
     */
    private function panRow(Worksheet $pan, array $columns, int $row, string $species, array $perSubregion): array
    {
        $values = [
            $species,
            $this->cell($pan, $columns['author'] ?? null, $row),
            $this->cell($pan, $columns['nis'] ?? null, $row),
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

        $values[] = $perSubregion === []
            ? ''
            : 'Subregion detail merged from: '.implode(', ', array_map('strtoupper', array_keys($perSubregion)));

        return $values;
    }

    /**
     * @param  array<string, array<string, ?string>>  $perSubregion
     * @return list<string>
     */
    private function subregionOnlyRow(array $perSubregion): array
    {
        $first = reset($perSubregion);
        $sheets = implode(', ', array_map('strtoupper', array_keys($perSubregion)));
        ['country' => $country, 'note' => $countryNote] = $this->splitCountry((string) ($first['country'] ?? ''));

        $values = [
            (string) $first['species'],
            '',
            '',
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
