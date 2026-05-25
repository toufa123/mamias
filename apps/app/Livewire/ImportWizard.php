<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Waad\FilamentImportWizard\Livewire\ImportWizard as BaseImportWizard;

class ImportWizard extends BaseImportWizard
{
    public $uploadedFile = null;

    protected function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $rows = [];

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getFormattedValue();
                $cells[] = is_scalar($value) || is_null($value) ? $value : (string) $value;
            }

            if ($rowIndex === 1) {
                foreach ($cells as $i => $h) {
                    $headerName = $h ? Str::of($h)->trim()->studly()->toString() : '';
                    $headers[] = $headerName ?: 'Column'.($i + 1);
                }

                continue;
            }

            if (count($cells) === count($headers) && ! empty(array_filter($cells))) {
                $rows[] = array_combine($headers, $cells);
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    public function updatedUploadedFile($file): void
    {
        if (! $file) {
            return;
        }

        $this->validate();

        $this->processUploadedFile($file);
    }
}
