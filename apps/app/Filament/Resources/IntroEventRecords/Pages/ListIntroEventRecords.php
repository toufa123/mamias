<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Enums\NisStatus;
use App\Filament\Actions\ExcelOrCsvImportAction;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\IntroEventRecord;
use App\Services\SpreadsheetToCsvConverter;
use App\Services\SubregionHeaderDisambiguator;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Info as CsvInfo;
use League\Csv\Reader as CsvReader;

/**
 * Page for listing intro event records.
 */
class ListIntroEventRecords extends ListRecords
{
    protected static string $resource = IntroEventRecordResource::class;

    protected function getHeaderActions(): array
    {
        // Captured here so the inner validator closures can use it without
        // relying on $this, which Filament rebinds to ImportAction during evaluation.
        $csvHeaders = $this->csvHeaders(...);
        $converter = app(SpreadsheetToCsvConverter::class);
        $disambiguator = app(SubregionHeaderDisambiguator::class);

        return [
            ExcelOrCsvImportAction::make()
                ->importer(IntroEventRecordImporter::class)
                ->chunkSize(100)
                ->fileRules([
                    // Header-duplicate check reads the file as CSV text directly, so
                    // an .xlsx/.xls upload needs converting first — the same
                    // conversion ExcelOrCsvImportAction itself does for the actual
                    // import, just run a step earlier since fileRules() validates
                    // before that action ever sees the file.
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($csvHeaders, $converter, $disambiguator): void {
                        $path = $value->getRealPath();

                        if ($converter->isSpreadsheet($value->getClientOriginalName())) {
                            $path = $converter->toCsvPath($path);
                        }

                        $headers = $csvHeaders($path);

                        if ($headers === null) {
                            return;
                        }

                        // Resolve the paired subregion columns first, exactly as
                        // the import itself will. Without this the validator
                        // rejects the very files ExcelOrCsvImportAction is now
                        // able to read, and what remains flagged is only the
                        // ambiguity nothing can resolve by position.
                        $headers = $disambiguator->disambiguate($headers);

                        $counts = array_count_values($headers);
                        $duplicates = [];

                        foreach (['WMED', 'CMED', 'ADRIA', 'EMED'] as $keyword) {
                            foreach ($counts as $header => $count) {
                                if ($count > 1 && stripos($header, $keyword) !== false) {
                                    $duplicates[] = $header;
                                }
                            }
                        }

                        if ($duplicates !== []) {
                            $fail('The file must not contain duplicate column headers: '.implode(', ', array_unique($duplicates)).'.');
                        }
                    },
                ]),
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $countByStatus = IntroEventRecord::query()
            ->selectRaw('nis_status, COUNT(*) as total')
            ->groupBy('nis_status')
            ->pluck('total', 'nis_status')
            ->map(fn ($count): int => (int) $count)
            ->all();

        // Counted separately rather than summing the grouped result: a row whose
        // NIS status did not resolve on import is stored null, so it belongs to
        // no status tab but still has to show up under All.
        $tabs = [
            'all' => Tab::make('All')
                ->icon('tabler-list')
                ->badge(IntroEventRecord::count()),
        ];

        foreach (NisStatus::cases() as $status) {
            $value = $status->value;

            $tabs[$status->name] = Tab::make($status->getLabel())
                ->icon($status->getIcon())
                ->badgeColor($status->getColor())
                ->badge($countByStatus[$value] ?? 0)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('nis_status', $value));
        }

        $tabs['needs_review'] = Tab::make('Needs review')
            ->icon('tabler-flag')
            ->badgeColor('danger')
            ->badge(IntroEventRecord::where('needs_review', true)->count())
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('needs_review', true));

        // The other tabs rely on the model's SoftDeletingScope to hide trashed
        // rows; onlyTrashed() lifts that scope for this tab alone.
        $tabs['trashed'] = Tab::make('Trashed')
            ->icon('tabler-trash')
            ->badgeColor('danger')
            ->badge(IntroEventRecord::onlyTrashed()->count())
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed());

        return $tabs;
    }

    /**
     * Open a CSV file with auto-detected delimiter and return its headers
     * with trailing empty columns stripped (common Excel export artifacts).
     *
     * @return string[]|null null when the path is unreadable
     */
    private function csvHeaders(?string $path): ?array
    {
        if (! $path || ! file_exists($path)) {
            return null;
        }

        $reader = CsvReader::createFromPath($path);

        $stats = CsvInfo::getDelimiterStats($reader, [',', ';', '|', "\t"], limit: 10);
        $delimiter = (string) array_search(max($stats), $stats);

        if ($delimiter !== '') {
            $reader->setDelimiter($delimiter);
        }

        $reader->setHeaderOffset(0);
        $headers = $reader->getHeader();

        while ($headers !== [] && blank(end($headers))) {
            array_pop($headers);
        }

        return $headers;
    }
}
