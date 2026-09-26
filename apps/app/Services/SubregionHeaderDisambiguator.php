<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Resolves the paired subregion columns in MAMIAS intro-event spreadsheets.
 *
 * The files partners send carry two columns per subregion sharing one header:
 *
 *     … | WMED | WMED | CMED | CMED | ADRIA | ADRIA | EMED | EMED | …
 *       | ''   | ''   | cas  | 2018 | ''    | ''    | cas  | 2019 |
 *
 * The first of each pair is the establishment status, the second the first
 * arrival year — the two are told apart by position and nothing else. That is
 * fine in a spreadsheet a human reads, but Filament maps import columns by
 * header name, so two columns called WMED are indistinguishable to it and one
 * of every pair would be silently dropped.
 *
 * Renaming them by position, to the names IntroEventRecordImporter already
 * declares, lets the file import exactly as it arrives. Partners keep their
 * format; nobody hand-edits a header row before every import.
 *
 * Only headers that actually repeat are touched. A file with a single WMED
 * column is left alone: one column carries no positional information, so
 * guessing which of the two roles it fills would be inventing data.
 */
final class SubregionHeaderDisambiguator
{
    /**
     * Subregion code => the importer column each occurrence maps to, in order.
     *
     * @var array<string, list<string>>
     */
    private const SUBREGIONS = [
        'WMED' => ['wmed_establishment_status', 'wmed_first_arrival_year'],
        'CMED' => ['cmed_establishment_status', 'cmed_first_arrival_year'],
        'ADRIA' => ['adria_establishment_status', 'adria_first_arrival_year'],
        'EMED' => ['emed_establishment_status', 'emed_first_arrival_year'],
    ];

    private const DELIMITERS = [',', ';', '|', "\t"];

    /**
     * @param  list<string>  $headers
     * @return list<string>
     */
    public function disambiguate(array $headers): array
    {
        $normalised = array_map(
            static fn (mixed $header): string => strtoupper(trim((string) $header)),
            $headers,
        );

        $counts = array_count_values($normalised);
        $seen = [];

        foreach ($normalised as $index => $code) {
            // Untouched unless it is a subregion code AND it repeats — a third
            // occurrence has no defined role, so it falls through to the
            // duplicate-header guard rather than being given a name.
            if (! isset(self::SUBREGIONS[$code]) || ($counts[$code] ?? 0) < 2) {
                continue;
            }

            $occurrence = $seen[$code] ?? 0;
            $seen[$code] = $occurrence + 1;

            $headers[$index] = self::SUBREGIONS[$code][$occurrence] ?? $headers[$index];
        }

        return array_values($headers);
    }

    /**
     * Returns a fresh stream whose header row has been disambiguated.
     *
     * The incoming stream is consumed and closed. The copy is written to
     * php://temp rather than a file so nothing has to be cleaned up, and the
     * body is moved across verbatim — only the first line is rewritten, so a
     * quoted field containing newlines further down is never re-parsed.
     *
     * @param  resource  $stream
     * @return resource
     */
    public function rewriteStream($stream)
    {
        // From the start: Filament's CSV encoding check reads up to 20 lines
        // of the upload and leaves the pointer there, which used to empty a
        // short CSV (no header) and cut the first rows off a long one.
        rewind($stream);

        $buffer = fopen('php://temp', 'r+');
        stream_copy_to_stream($stream, $buffer);
        fclose($stream);

        rewind($buffer);
        $firstLine = (string) fgets($buffer);
        $delimiter = $this->detectDelimiter($firstLine);

        rewind($buffer);
        $headers = fgetcsv($buffer, 0, $delimiter, '"', '');

        if ($headers === false || $headers === null) {
            rewind($buffer);

            return $buffer;
        }

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $this->disambiguate($headers), $delimiter, '"', '');

        // $buffer is already positioned just past the header row.
        stream_copy_to_stream($buffer, $out);
        fclose($buffer);

        rewind($out);

        return $out;
    }

    /**
     * Picks whichever candidate appears most often in the header line.
     *
     * Mirrors the detection ListIntroEventRecords uses for its own header
     * read, so the two never disagree about where a column boundary is.
     */
    private function detectDelimiter(string $line): string
    {
        $counts = [];

        foreach (self::DELIMITERS as $candidate) {
            $counts[$candidate] = substr_count($line, $candidate);
        }

        $best = array_search(max($counts), $counts, true);

        return ($best === false || $counts[$best] === 0) ? ',' : (string) $best;
    }
}
