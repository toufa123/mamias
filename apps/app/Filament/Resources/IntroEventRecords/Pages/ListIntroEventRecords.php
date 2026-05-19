<?php

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Filament\Imports\IntroEventRecordImporter;
use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use League\Csv\Info as CsvInfo;
use League\Csv\Reader as CsvReader;

class ListIntroEventRecords extends ListRecords
{
    protected static string $resource = IntroEventRecordResource::class;

    protected function getHeaderActions(): array
    {
        // Captured here so the inner validator closures can use it without
        // relying on $this, which Filament rebinds to ImportAction during evaluation.
        $csvHeaders = $this->csvHeaders(...);

        return [
            ImportAction::make()
                ->importer(IntroEventRecordImporter::class)
                ->chunkSize(100)
                ->fileRules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($csvHeaders): void {
                        $headers = $csvHeaders($value->getRealPath());

                        if ($headers === null) {
                            return;
                        }

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
