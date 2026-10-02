<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use Filament\Actions\Imports\Downloaders\Contracts\Downloader;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Failed rows as .xlsx instead of Filament's CSV, so they can be fixed in
 * Excel and uploaded again through ExcelOrCsvImportAction. Same columns as
 * CsvImportFailureContentGenerator: the row's original data plus an error
 * column. Every cell is written as an explicit string, so a value starting
 * with `=` stays text instead of becoming a formula.
 */
final class XlsxFailedRowsDownloader implements Downloader
{
    public function __invoke(Import $import): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_keys($import->failedRows()->first()->data ?? []);
        $headers[] = __('filament-actions::import.failure_csv.error_header');

        $write = static function (int $row, array $values) use ($sheet): void {
            foreach (array_values($values) as $i => $value) {
                $sheet->setCellValueExplicit([$i + 1, $row], (string) $value, DataType::TYPE_STRING);
            }
        };

        $write(1, $headers);
        $row = 2;

        $import->failedRows()->lazyById(100)->each(function (FailedImportRow $failed) use ($write, &$row): void {
            $write($row++, [
                ...$failed->data,
                $failed->validation_error ?? __('filament-actions::import.failure_csv.system_error'),
            ]);
        });

        return response()->streamDownload(
            fn () => (new XlsxWriter($spreadsheet))->save('php://output'),
            __('filament-actions::import.failure_csv.file_name', [
                'import_id' => $import->getKey(),
                'csv_name' => (string) str($import->file_name)->beforeLast('.')->remove('.'),
            ]).'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
