<?php

use App\Filament\Imports\TaxonImporter;
use App\Filament\Imports\XlsxFailedRowsDownloader;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('downloads failed rows as xlsx with formulas kept as text', function () {
    $import = Import::create([
        'user_id' => User::factory()->create()->getKey(),
        'file_name' => 'taxa.xlsx',
        'file_path' => 'taxa.csv',
        'importer' => TaxonImporter::class,
        'total_rows' => 2,
    ]);
    $import->failedRows()->create(['data' => ['name' => '=1+1'], 'validation_error' => 'bad name']);

    expect(TaxonImporter::getFailedRowsDownloader())->toBeInstanceOf(XlsxFailedRowsDownloader::class);

    ob_start();
    TaxonImporter::getFailedRowsDownloader()($import)->sendContent();
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, ob_get_clean());

    $sheet = IOFactory::load($path)->getActiveSheet();

    expect($sheet->toArray(calculateFormulas: false))->toBe([['name', 'error'], ['=1+1', 'bad name']])
        ->and($sheet->getCell('A2')->getDataType())->toBe(DataType::TYPE_STRING);
});
