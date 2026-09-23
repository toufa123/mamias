<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Converts an uploaded spreadsheet (.xlsx/.xls) to a plain CSV file, so
 * Filament's CSV-only ImportAction/Importer machinery can read it unchanged.
 */
class SpreadsheetToCsvConverter
{
    /** @var list<string> */
    public const SPREADSHEET_EXTENSIONS = ['xlsx', 'xls', 'xlsm', 'ods'];

    public function isSpreadsheet(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::SPREADSHEET_EXTENSIONS, true);
    }

    /**
     * Converts the first sheet to a new temporary CSV file and returns its
     * path. Re-converts on every call rather than caching — Filament's
     * ImportAction calls getUploadedFileStream() a few times per import, but
     * re-parsing a spreadsheet that many times is cheap at the row counts
     * these imports handle, and it avoids a stale-cache class of bug.
     */
    public function toCsvPath(string $sourcePath): string
    {
        $spreadsheet = IOFactory::load($sourcePath);

        $csvPath = tempnam(sys_get_temp_dir(), 'mamias_import_');

        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->setDelimiter(',');
        $writer->setSheetIndex(0);
        $writer->save($csvPath);

        return $csvPath;
    }
}
