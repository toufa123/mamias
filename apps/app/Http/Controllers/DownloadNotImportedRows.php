<?php

namespace App\Http\Controllers;

use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the rows an import did not create — species skipped because the
 * scientific name already exists, plus any rows that failed validation — as
 * Excel or CSV. Each row keeps its original spreadsheet columns and gains a
 * leading "Row" number and a trailing "Reason" explaining why it was skipped.
 *
 * The number counts this file's own rows: Filament records a failed row
 * without the offset it had in the uploaded spreadsheet, so the original line
 * number is not recoverable here.
 */
class DownloadNotImportedRows extends Controller
{
    private const FORMATS = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv; charset=UTF-8',
    ];

    public function __invoke(Request $request, Import $import, string $format = 'xlsx'): StreamedResponse
    {
        abort_unless(array_key_exists($format, self::FORMATS), 404);

        $hasValidSignature = $request->hasValidSignature(absolute: false);
        $authGuard = $hasValidSignature ? $request->query('authGuard') : null;

        abort_unless(auth($authGuard)->check(), 401);
        abort_unless($import->user()->is(auth($authGuard)->user()), 403);

        $fileName = str($import->file_name)->beforeLast('.')->remove('.')
            ->prepend("import-{$import->getKey()}-not-imported-")
            ->append(".{$format}")
            ->toString();

        $writer = $format === 'csv' ? new CsvWriter : app(XlsxWriter::class);

        return response()->streamDownload(function () use ($fileName, $import, $writer): void {
            $writer->openToBrowser($fileName);

            $headers = null;
            $number = 0;

            foreach ($import->failedRows()->lazyById(500) as $failedRow) {
                if ($headers === null) {
                    $headers = array_keys($failedRow->data ?? []);

                    $writer->addRow(Row::fromValues(['Row', ...$headers, 'Reason']));
                }

                $writer->addRow(Row::fromValues([++$number, ...$this->toValues($failedRow, $headers)]));
            }

            if ($headers === null) {
                $writer->addRow(Row::fromValues(['Row', 'Reason']));
            }

            $writer->close();
        }, $fileName, [
            'Content-Type' => self::FORMATS[$format],
        ]);
    }

    /**
     * Flattens a failed row into the header order, appending its reason.
     *
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    protected function toValues(FailedImportRow $failedRow, array $headers): array
    {
        $data = $failedRow->data ?? [];

        $values = array_map(
            fn (string $header): string => $this->stringify($data[$header] ?? null),
            $headers,
        );

        $values[] = $failedRow->validation_error ?? '';

        return $values;
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : json_encode($value);
    }
}
