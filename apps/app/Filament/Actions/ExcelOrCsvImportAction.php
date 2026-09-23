<?php

namespace App\Filament\Actions;

use App\Services\SpreadsheetToCsvConverter;
use App\Services\SubregionHeaderDisambiguator;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader as CsvReader;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Filament's ImportAction only accepts CSV — its FileUpload's
 * acceptedFileTypes() MIME whitelist is hardcoded inside a private schema
 * closure with no narrower extension point (checked
 * vendor/filament/actions/src/ImportAction.php directly: no public method
 * exposes just that array). This subclass re-declares that same schema
 * verbatim (Filament ^5.0), with one addition — the xlsx MIME type — and
 * converts any spreadsheet upload to CSV in getUploadedFileStream(), which
 * every closure Filament wires up already calls via `$this->`, so overriding
 * it here is picked up everywhere without touching those closures at all.
 *
 * TaxonImporter and IntroEventRecordImporter are completely untouched — this
 * only widens what file the *action* accepts before handing rows to them.
 *
 * Re-diff this against vendor/filament/actions/src/ImportAction.php after
 * any Filament upgrade — if that file's schema-building block changes, this
 * copy needs updating to match.
 */
class ExcelOrCsvImportAction extends ImportAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema(fn (ImportAction $action): array => array_merge([
            FileUpload::make('file')
                ->label(__('filament-actions::import.modal.form.file.label'))
                ->placeholder(__('filament-actions::import.modal.form.file.placeholder'))
                ->acceptedFileTypes([
                    'text/csv',
                    'text/x-csv',
                    'application/csv',
                    'application/x-csv',
                    'text/comma-separated-values',
                    'text/x-comma-separated-values',
                    'text/plain',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel.sheet.macroEnabled.12',
                    'application/vnd.oasis.opendocument.spreadsheet',
                ])
                ->rules($action->getFileValidationRules())
                ->afterStateUpdated(function (FileUpload $component, Component $livewire, Set $set, ?TemporaryUploadedFile $state) use ($action): void {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    try {
                        $livewire->validateOnly($component->getStatePath());
                    } catch (ValidationException $exception) {
                        $component->state([]);

                        throw $exception;
                    }

                    $csvStream = $this->getUploadedFileStream($state);

                    if (! $csvStream) {
                        return;
                    }

                    $csvReader = CsvReader::from($csvStream);

                    if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                        $csvReader->setDelimiter($csvDelimiter);
                    }

                    $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                    $csvColumns = $csvReader->getHeader();

                    $lowercaseCsvColumnValues = array_map(Str::lower(...), $csvColumns);
                    $lowercaseCsvColumnKeys = array_combine(
                        $lowercaseCsvColumnValues,
                        $csvColumns,
                    );

                    $set('columnMap', array_reduce($action->getImporter()::getColumns(), function (array $carry, ImportColumn $column) use ($lowercaseCsvColumnKeys, $lowercaseCsvColumnValues) {
                        $carry[$column->getName()] = $lowercaseCsvColumnKeys[
                        Arr::first(
                            array_intersect(
                                $lowercaseCsvColumnValues,
                                $column->getGuesses(),
                            ),
                        )
                        ] ?? null;

                        return $carry;
                    }, []));
                })
                ->storeFiles(false)
                ->visibility('private')
                ->required()
                ->hiddenLabel(),
            Fieldset::make(__('filament-actions::import.modal.form.columns.label'))
                ->columns(1)
                ->inlineLabel()
                ->schema(function (Get $get) use ($action): array {
                    $csvFile = $get('file');

                    if (! $csvFile instanceof TemporaryUploadedFile) {
                        return [];
                    }

                    $csvStream = $this->getUploadedFileStream($csvFile);

                    if (! $csvStream) {
                        return [];
                    }

                    $csvReader = CsvReader::from($csvStream);

                    if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                        $csvReader->setDelimiter($csvDelimiter);
                    }

                    $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                    $csvColumns = $csvReader->getHeader();
                    $csvColumnOptions = array_combine($csvColumns, $csvColumns);

                    return array_map(
                        fn (ImportColumn $column): Select => $column->getSelect()->options($csvColumnOptions),
                        $action->getImporter()::getColumns(),
                    );
                })
                ->statePath('columnMap')
                ->visible(fn (Get $get): bool => $get('file') instanceof TemporaryUploadedFile),
        ], $action->getImporter()::getOptionsFormComponents()));

        $this->registerModalActions([
            Action::make('downloadExampleXlsx')
                ->label(__('filament-actions::import.modal.actions.download_example_xlsx.label'))
                ->link()
                ->action(fn (): StreamedResponse => $this->streamExampleXlsx()),
        ]);

        // Parent uses the single downloadExample action as the modal description;
        // render both example links there instead.
        $this->modalDescription(fn (ImportAction $action): Htmlable => new HtmlString(
            $action->getModalAction('downloadExample')->toHtml().
            ' &middot; '.
            $action->getModalAction('downloadExampleXlsx')->toHtml()
        ));
    }

    private function streamExampleXlsx(): StreamedResponse
    {
        $columns = $this->getImporter()::getColumns();

        $examples = array_map(fn (ImportColumn $column): array => array_values($column->getExamples()), $columns);
        $rowCount = max([0, ...array_map('count', $examples)]);

        $rows = [array_map(fn (ImportColumn $column): string => $column->getExampleHeader(), $columns)];

        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = array_map(fn (array $example): mixed => $example[$i] ?? '', $examples);
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $writer = new XlsxWriter($spreadsheet);

        return response()->streamDownload(
            function () use ($writer): void {
                $writer->save('php://output');
            },
            __('filament-actions::import.example_xlsx.file_name', [
                'importer' => (string) str($this->getImporter())->classBasename()->kebab(),
            ]),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * Parent hardcodes `extensions:csv,txt` in this array; swap in the
     * spreadsheet extensions getUploadedFileStream() can convert, leaving the
     * duplicate-header closure and any fileRules() additions untouched.
     *
     * @return array<mixed>
     */
    public function getFileValidationRules(): array
    {
        $extensions = implode(',', ['csv', 'txt', ...SpreadsheetToCsvConverter::SPREADSHEET_EXTENSIONS]);

        return array_map(
            fn (mixed $rule): mixed => $rule === 'extensions:csv,txt' ? "extensions:{$extensions}" : $rule,
            parent::getFileValidationRules(),
        );
    }

    /**
     * @return resource|false
     */
    public function getUploadedFileStream(TemporaryUploadedFile $file)
    {
        $converter = app(SpreadsheetToCsvConverter::class);

        $stream = $converter->isSpreadsheet($file->getClientOriginalName())
            ? (fopen($converter->toCsvPath($file->getRealPath()), 'r') ?: false)
            : parent::getUploadedFileStream($file);

        if ($stream === false) {
            return false;
        }

        // Paired subregion columns (two headers called WMED, two called CMED…)
        // are renamed by position before anything reads the header row, so the
        // column mapper sees one distinct name per column. Applied to CSV
        // uploads too, not just converted spreadsheets: the same sheet
        // exported as CSV carries the same duplicate headers.
        return app(SubregionHeaderDisambiguator::class)->rewriteStream($stream);
    }
}
