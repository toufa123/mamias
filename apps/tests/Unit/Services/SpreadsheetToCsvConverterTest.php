<?php

declare(strict_types=1);

use App\Services\SpreadsheetToCsvConverter;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('recognises spreadsheet filenames by extension', function () {
    $converter = new SpreadsheetToCsvConverter;

    expect($converter->isSpreadsheet('taxa.xlsx'))->toBeTrue()
        ->and($converter->isSpreadsheet('taxa.xls'))->toBeTrue()
        ->and($converter->isSpreadsheet('taxa.XLSX'))->toBeTrue()
        ->and($converter->isSpreadsheet('taxa.csv'))->toBeFalse()
        ->and($converter->isSpreadsheet('taxa.txt'))->toBeFalse();
});

it('converts an xlsx file to a readable csv with the same headers and rows', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['scientificname', 'kingdom'],
        ['Mnemiopsis leidyi', 'Animalia'],
        ['Caulerpa taxifolia', 'Plantae'],
    ], null, 'A1');

    $xlsxPath = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
    (new Xlsx($spreadsheet))->save($xlsxPath);

    $converter = new SpreadsheetToCsvConverter;
    $csvPath = $converter->toCsvPath($xlsxPath);

    $csv = Reader::from($csvPath);
    $csv->setHeaderOffset(0);

    expect($csv->getHeader())->toBe(['scientificname', 'kingdom'])
        ->and(iterator_to_array($csv->getRecords()))->toBe([
            1 => ['scientificname' => 'Mnemiopsis leidyi', 'kingdom' => 'Animalia'],
            2 => ['scientificname' => 'Caulerpa taxifolia', 'kingdom' => 'Plantae'],
        ]);

    @unlink($xlsxPath);
    @unlink($csvPath);
});
