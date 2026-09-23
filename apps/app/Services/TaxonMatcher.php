<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Taxon;

/**
 * Resolves a species name written in an import file to a catalogue taxon.
 *
 * The catalogue is the reference: every key this matcher compares against is
 * built from what is already in `taxas`, never from an idea of how a name
 * ought to look. A file spelling only matches if the catalogue itself — by
 * accepted name or by a recorded synonym — says it is that species.
 *
 * It refuses more than it accepts, on purpose:
 *
 *  - a canonical key shared by two or more taxa is ambiguous and matches
 *    nothing, because silently picking one writes an occurrence against the
 *    wrong species and nothing downstream would ever reveal it;
 *  - an open nomenclature name (cf., aff., sp., indet.) is an explicit
 *    statement that the determination is uncertain, so it is never matched
 *    to a definite taxon.
 *
 * Both cases come back with a reason, which the review screen shows.
 */
final class TaxonMatcher
{
    /**
     * Canonical binomial => list of taxon ids using it as their accepted name.
     *
     * @var array<string, list<int>>|null
     */
    private ?array $acceptedIndex = null;

    /**
     * Canonical binomial => list of taxon ids carrying it as a synonym.
     *
     * @var array<string, list<int>>|null
     */
    private ?array $synonymIndex = null;

    /**
     * Lowercased full accepted name => taxon id.
     *
     * @var array<string, int>|null
     */
    private ?array $exactIndex = null;

    /** Markers that say the determination is uncertain. */
    private const OPEN_NOMENCLATURE = [
        '/\bcfr?\.?\b/iu',   // cf. and the cfr the workbook also uses
        '/\baff\.?\b/iu',
        '/\bsp{1,2}\.?\b/iu',
        '/\bindet\.?\b/iu',
        '/\bnr\.?\b/iu',
    ];

    /**
     * @return array{taxon_id: int|null, how: string, reason: string|null}
     */
    public function match(?string $rawName): array
    {
        $name = trim((string) $rawName);

        if ($name === '') {
            return ['taxon_id' => null, 'how' => 'empty', 'reason' => 'No species name in the file.'];
        }

        foreach (self::OPEN_NOMENCLATURE as $pattern) {
            if (preg_match($pattern, $name) === 1) {
                return [
                    'taxon_id' => null,
                    'how' => 'open_nomenclature',
                    'reason' => 'The name is an uncertain determination ("'.$name.'"). Link it by hand if you know which taxon is meant.',
                ];
            }
        }

        $this->buildIndexes();

        $exact = $this->exactIndex[mb_strtolower($name)] ?? null;

        if ($exact !== null) {
            return ['taxon_id' => $exact, 'how' => 'exact', 'reason' => null];
        }

        $key = $this->canonical($name);

        if ($key === '') {
            return ['taxon_id' => null, 'how' => 'unparseable', 'reason' => 'Could not read a genus and species from "'.$name.'".'];
        }

        foreach (['accepted' => $this->acceptedIndex, 'synonym' => $this->synonymIndex] as $how => $index) {
            $candidates = $index[$key] ?? [];
            $candidates = array_values(array_unique($candidates));

            if (count($candidates) === 1) {
                return [
                    'taxon_id' => $candidates[0],
                    'how' => $how,
                    'reason' => $how === 'synonym'
                        ? 'Matched through a synonym recorded in the catalogue.'
                        : null,
                ];
            }

            if (count($candidates) > 1) {
                $names = Taxon::whereIn('id', $candidates)->pluck('scientificname')->implode(', ');

                return [
                    'taxon_id' => null,
                    'how' => 'ambiguous',
                    'reason' => '"'.$name.'" matches more than one catalogue taxon ('.$names.'). Pick the right one by hand.',
                ];
            }
        }

        return [
            'taxon_id' => null,
            'how' => 'unmatched',
            'reason' => 'No catalogue taxon matches "'.$name.'", by accepted name or synonym.',
        ];
    }

    /**
     * Genus + species, lowercased, with subgenus parentheses, authorities,
     * years and rank markers removed. Applied identically to both sides, so
     * the comparison is between like and like.
     */
    public function canonical(string $name): string
    {
        // Non-breaking spaces are invisible on screen but stop the name
        // tokenising, so a name carrying one never matches anything.
        $name = preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $name) ?? $name;
        $name = preg_replace('/\(.*?\)/u', ' ', $name);          // (Subgenus), (Author, 1846)
        $name = preg_replace('/,?\s*\d{4}\s*$/u', '', (string) $name); // trailing year
        $name = preg_replace('/\b(var|subsp|ssp|f|forma)\.?\b/iu', ' ', (string) $name);
        $name = preg_replace('/[^\p{L}\s-]+/u', ' ', (string) $name);
        $name = preg_replace('/\s+/u', ' ', (string) $name);

        $parts = array_values(array_filter(explode(' ', trim((string) $name))));

        if (count($parts) < 2) {
            return '';
        }

        return mb_strtolower($parts[0].' '.$parts[1]);
    }

    private function buildIndexes(): void
    {
        if ($this->exactIndex !== null) {
            return;
        }

        $this->exactIndex = [];
        $this->acceptedIndex = [];
        $this->synonymIndex = [];

        Taxon::query()
            ->select(['id', 'scientificname', 'synonyms_data'])
            ->chunk(500, function ($taxa): void {
                foreach ($taxa as $taxon) {
                    $name = trim((string) $taxon->scientificname);

                    if ($name === '') {
                        continue;
                    }

                    $this->exactIndex[mb_strtolower($name)] ??= $taxon->id;

                    $key = $this->canonical($name);

                    if ($key !== '') {
                        $this->acceptedIndex[$key][] = $taxon->id;
                    }

                    foreach ($this->synonymNames($taxon) as $synonym) {
                        $synonymKey = $this->canonical($synonym);

                        if ($synonymKey !== '' && $synonymKey !== $key) {
                            $this->synonymIndex[$synonymKey][] = $taxon->id;
                        }
                    }
                }
            });
    }

    /**
     * @return list<string>
     */
    private function synonymNames(Taxon $taxon): array
    {
        $data = $taxon->synonyms_data;

        if (! is_array($data)) {
            return [];
        }

        $names = [];

        foreach ($data as $entry) {
            $name = is_array($entry) ? ($entry['scientificname'] ?? null) : $entry;

            if (is_string($name) && trim($name) !== '') {
                $names[] = trim($name);
            }
        }

        return $names;
    }
}
