<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Services\PanMediterraneanWorkbookReader;
use App\Services\SpreadsheetToCsvConverter;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Import action for the five-sheet PAN Mediterranean workbook.
 *
 * Identical to ExcelOrCsvImportAction except for where the CSV comes from:
 * when the upload contains a PAN MEDITERRANEAN sheet, the whole workbook is
 * flattened into one wide CSV first, so a single upload carries both the
 * Med-wide values and each subregion's establishment status.
 *
 * Anything else — a plain CSV, a single-sheet export — falls through to the
 * ordinary behaviour, so the action stays usable if the format changes.
 */
class PanMediterraneanImportAction extends ExcelOrCsvImportAction
{
    /**
     * @return resource|false
     */
    public function getUploadedFileStream(TemporaryUploadedFile $file)
    {
        $converter = app(SpreadsheetToCsvConverter::class);

        if (! $converter->isSpreadsheet($file->getClientOriginalName())) {
            return parent::getUploadedFileStream($file);
        }

        $reader = app(PanMediterraneanWorkbookReader::class);
        $path = $file->getRealPath();

        if (! $reader->isSupported($path)) {
            return parent::getUploadedFileStream($file);
        }

        return fopen($reader->toCsvPath($path), 'r') ?: false;
    }
}
