<?php

declare(strict_types=1);

use App\Services\PanMediterraneanWorkbookReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @param  array<int, array<string, string|int>>  $rows  row => [column => value]
 */
function fillSheet(Worksheet $sheet, array $rows): void
{
    foreach ($rows as $row => $cells) {
        foreach ($cells as $column => $value) {
            $sheet->setCellValue($column.$row, $value);
        }
    }
}

/**
 * The workbook as the importer would stage it, keyed by species.
 *
 * @return array<string, array<string, string>>
 */
function stagedRows(Spreadsheet $spreadsheet): array
{
    $path = tempnam(sys_get_temp_dir(), 'annex_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $handle = fopen(app(PanMediterraneanWorkbookReader::class)->toCsvPath($path), 'r');
    $header = fgetcsv($handle, null, ',', '"', '');
    $staged = [];

    while (($line = fgetcsv($handle, null, ',', '"', '')) !== false) {
        $record = array_combine($header, $line);
        $staged[$record['raw_species']] = $record;
    }

    return $staged;
}

/**
 * The RAC/SPA baseline ends with a data-deficient annex, laid out one column
 * right of the header. It is staged under a held-out status, never as NIS.
 */
it('stages the data-deficient annex under its block status', function (): void {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet()->setTitle('PAN MEDITERRANEAN ');

    $rows = [
        1 => ['B' => 'Species', 'C' => 'author_id', 'D' => 'YEAR OF FIRST INTRODUCTION IN Med', 'E' => 'WMED', 'F' => 'WMED', 'G' => 'CMED', 'H' => 'CMED', 'I' => 'ADRIA', 'J' => 'ADRIA', 'K' => 'EMED', 'L' => 'EMED', 'M' => 'COUNTRY OF FIRST INTRODUCTION MED', 'N' => 'status', 'O' => 'ES success', 'P' => ' citation'],
        2 => ['B' => 'Zafra savignyi', 'D' => 1952, 'K' => 'est', 'L' => 1952, 'M' => 'Israel', 'N' => 'AL', 'O' => 'est'],
        3 => ['E' => 'DATA DEFICIENT'],
        4 => ['B' => 'Debatable (diverging expert opinions)'],
        5 => ['C' => 'Percnon gibbesi (H. Milne Edwards, 1853)', 'H' => 'NIS in TR, CRY-EX elsewhere in the MED'],
        6 => ['B' => 'Excluded from the regional list'],
        7 => ['B' => 'only on vector', 'C' => 'Boccardia proboscidea', 'D' => 'Hartman, 1940', 'E' => 2014, 'F' => 'que', 'G' => 2014, 'N' => 'France', 'O' => 'AL', 'P' => 'que', 'Q' => 'Radashevsky et al., 2019'],
        8 => ['B' => 'Likely alien polychaeta'],
        9 => ['A' => 'REMOVE', 'B' => 'DD', 'C' => 'Sigambra parva', 'E' => 1975, 'N' => 'Greece'],
        10 => ['C' => 'Removed foraminifera'],
        11 => ['B' => 'NAT', 'C' => 'Nodophthalmidium antillarum', 'E' => 1965, 'N' => 'Lebanon'],
    ];

    fillSheet($sheet, $rows);

    $staged = stagedRows($spreadsheet);

    expect(array_keys($staged))->toBe(['Zafra savignyi', 'Percnon gibbesi (H. Milne Edwards, 1853)', 'Boccardia proboscidea'])
        ->and($staged['Zafra savignyi']['nis_status'])->toBe('AL')
        ->and($staged['Percnon gibbesi (H. Milne Edwards, 1853)'])->toMatchArray([
            'nis_status' => 'Data Deficient',
            'cmed_establishment_status' => '',
            'first_country' => '',
        ])
        ->and($staged['Percnon gibbesi (H. Milne Edwards, 1853)']['notes'])->toContain('NIS in TR, CRY-EX elsewhere')
        ->and($staged['Boccardia proboscidea'])->toMatchArray([
            'raw_author' => 'Hartman, 1940',
            'nis_status' => 'Questionable',
            'establishment_status' => 'que',
            'first_introduction_year' => '2014',
            'first_country' => 'France',
            'wmed_establishment_status' => 'que',
            'wmed_first_arrival_year' => '2014',
        ])
        ->and($staged['Boccardia proboscidea']['notes'])->toContain('Radashevsky et al., 2019');
});

it('reads held-out blocks of a subregion sheet as held out, never as presence', function (): void {
    $spreadsheet = new Spreadsheet;
    fillSheet($spreadsheet->getActiveSheet()->setTitle('PAN MEDITERRANEAN'), [
        1 => ['B' => 'Species', 'D' => 'YEAR OF FIRST INTRODUCTION IN Med', 'E' => 'WMED', 'F' => 'WMED', 'M' => 'COUNTRY OF FIRST INTRODUCTION MED', 'N' => 'status'],
        2 => ['B' => 'Zafra savignyi', 'D' => 1952, 'M' => 'Israel', 'N' => 'AL'],
        3 => ['B' => 'Ophiactis savignyi', 'D' => 1924, 'M' => 'Egypt', 'N' => 'AL'],
        4 => ['B' => 'Morula aspera', 'D' => 1990, 'M' => 'Israel', 'N' => 'AL'],
    ]);
    fillSheet($spreadsheet->createSheet()->setTitle('WMED'), [
        1 => ['A' => 'SubR', 'B' => 'Species / Author', 'C' => 'Status of the species', 'E' => 'Date (of first observation in country)', 'G' => 'establishment success of the species', 'H' => 'Country of first record in subregion'],
        2 => ['A' => 'WMED', 'B' => 'Zafra savignyi', 'C' => 'non-indigenous', 'E' => 2001, 'G' => 'casual', 'H' => 'ES'],
        4 => ['B' => 'QUESTIONABLE RECORDS', 'G' => 'id needs confirmation'],
        5 => ['A' => 'WMED', 'B' => 'Ophiactis savignyi (Müller & Troschel, 1842)', 'C' => 'non-indigenous', 'E' => 1968, 'G' => 'questionable', 'H' => 'FR'],
        6 => ['A' => 'WMED', 'B' => 'Zafra savignyi', 'C' => 'non-indigenous', 'E' => 1999],
        7 => ['A' => 'WMED', 'B' => 'Plocamium secundatum', 'E' => 1976, 'H' => 'ES'],
        9 => ['B' => 'To be removed'],
        10 => ['A' => 'WMED', 'B' => 'Morula aspera (Lamarck, 1816)', 'E' => 2015, 'G' => 'casual', 'H' => 'TN'],
        12 => ['B' => 'Polychaeta reported as debatable/questionable in WMED'],
        13 => ['B' => 'Sigambra parva (Day, 1963)', 'E' => 'IT, FR'],
    ]);

    $staged = stagedRows($spreadsheet);

    expect($staged['Zafra savignyi'])->toMatchArray(['wmed_nis_status' => 'non-indigenous', 'wmed_first_arrival_year' => '2001'])
        ->and($staged['Ophiactis savignyi'])->toMatchArray(['wmed_nis_status' => 'Questionable', 'wmed_first_arrival_year' => '1968'])
        ->and($staged['Morula aspera'])->toMatchArray(['wmed_nis_status' => '', 'wmed_first_arrival_year' => ''])
        ->and($staged['Plocamium secundatum'])->toMatchArray(['nis_status' => 'Questionable', 'wmed_nis_status' => 'Questionable'])
        ->and($staged['Sigambra parva (Day, 1963)'])->toMatchArray(['nis_status' => 'Data Deficient', 'wmed_first_arrival_year' => '']);
});
