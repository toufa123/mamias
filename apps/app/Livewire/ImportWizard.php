<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Waad\FilamentImportWizard\Livewire\ImportWizard as BaseImportWizard;

class ImportWizard extends BaseImportWizard
{
    public ?UploadedFile $uploadedFile = null;

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

    public function updatedUploadedFile(?UploadedFile $file): void
    {
        if (! $file) {
            return;
        }

        $this->validate();

        $this->processUploadedFile($file);
    }

    protected function sendCompletionNotification(): void
    {
        if ($this->status === 'failed') {
            Notification::make()
                ->title('Import failed')
                ->body($this->errorMessage ?? 'An error occurred during import.')
                ->danger()
                ->persistent()
                ->send();
        } elseif ($this->failedRows > 0) {
            Notification::make()
                ->title('Import completed with errors')
                ->body(number_format($this->successRows).' rows imported, '.number_format($this->failedRows).' rows failed.')
                ->warning()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Import completed')
                ->body(number_format($this->successRows).' rows imported successfully.')
                ->success()
                ->send();
        }
    }

    public function startImport()
    {
        parent::startImport();

        if ($this->status !== 'processing') {
            $this->sendCompletionNotification();
        }
    }

    public function pollSessionStatus(): void
    {
        if ($this->status !== 'processing' || ! $this->session) {
            return;
        }

        $previousStatus = $this->status;

        $this->session->refresh();

        $this->processedRows = $this->session->processed_rows ?? 0;
        $this->successRows = $this->session->success_rows ?? 0;
        $this->failedRows = $this->session->failed_rows ?? 0;
        $this->status = $this->session->status;

        if ($previousStatus !== $this->status && in_array($this->status, ['completed', 'completed_with_errors', 'failed'])) {
            $this->sendCompletionNotification();
        }
    }
}
