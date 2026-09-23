<?php

declare(strict_types=1);

use App\Filament\Actions\ExcelOrCsvImportAction;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Filament\Resources\Taxons\Pages\ListTaxons;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

function excelOrCsvImportAction(): ExcelOrCsvImportAction
{
    return ExcelOrCsvImportAction::make('import')->importer(IntroEventRecordImporter::class);
}

it('accepts spreadsheet extensions alongside csv', function () {
    $rules = array_filter(excelOrCsvImportAction()->getFileValidationRules(), 'is_string');

    // Guards against a Filament upgrade changing the hardcoded rule our override rewrites.
    expect($rules)->not->toContain('extensions:csv,txt')
        ->and($rules)->toContain('extensions:csv,txt,xlsx,xls,xlsm,ods');
});

it('offers an xlsx example download next to the csv one', function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    $description = Livewire::test(ListTaxons::class)
        ->mountAction('import')
        ->instance()
        ->getMountedAction()
        ->getModalDescription();

    expect((string) $description)->toContain('Download example CSV file', 'Download example XLSX file');
});

it('streams an example xlsx carrying the importer headers', function () {
    $action = excelOrCsvImportAction();

    $response = (new ReflectionMethod($action, 'streamExampleXlsx'))->invoke($action);

    ob_start();
    $response->sendContent();
    $contents = ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'example_').'.xlsx';
    file_put_contents($path, $contents);

    $headers = IOFactory::load($path)->getActiveSheet()->toArray()[0];

    expect($response->headers->get('content-disposition'))->toContain('.xlsx')
        ->and($headers)->toBe(array_map(
            fn (ImportColumn $column): string => $column->getExampleHeader(),
            IntroEventRecordImporter::getColumns(),
        ));

    unlink($path);
});
