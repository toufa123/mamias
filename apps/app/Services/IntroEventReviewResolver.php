<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Filament\Imports\IntroEventRecordImporter;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;
use Illuminate\Support\Collection;

/**
 * Proposes values for the introduction events the importer flagged
 * needs_review, reading each unresolved cell kept in the event's notes
 * ("Needs review (unresolved on import) — WMED First Arrival Year: 2014-15; …").
 *
 * Every proposal carries a category, so the reviewer decides per category
 * what is applied (see `php artisan mamias:review-intro-events`):
 *
 *   range        "1972-74", "2003/2004"          → earliest year (first-record convention)
 *   correction   "1965-67 not 1985"              → the value before "not", read by the other rules
 *   approximate  "2004?", "~ 1990"               → that year
 *   before       "<2007", "≤2016"                → that year, the latest it can be (opt-in)
 *   decade       "1970s", "1990's"               → first year of the decade (opt-in)
 *   historical   "1791" (outside the importer's 1800–now range) → that year (opt-in)
 *   derived      an undated event                → its earliest resolved sub-region year
 *   status       "A:"                            → EASIN status A (alien) = NIS
 *   manual       anything else                   → no proposal, left for a person
 *
 * Each proposal is checked against EASIN's first EU records
 * (FirstIntroductionsInEU) in Mediterranean member states. EASIN records the
 * first record in the EU, not in the Mediterranean or a sub-region, so it can
 * only contradict a proposal, never supply one: a proposal later than an EASIN
 * record is a conflict and is never applied.
 */
final class IntroEventReviewResolver
{
    public const CATEGORIES = ['range', 'correction', 'approximate', 'before', 'decade', 'historical', 'derived', 'status', 'manual'];

    /** Categories applied without being asked for: the reading is unambiguous. */
    public const SAFE_CATEGORIES = ['range', 'correction', 'approximate', 'derived', 'status'];

    /** EU member states with a Mediterranean coast (EASIN country codes). */
    private const MEDITERRANEAN_STATES = ['ES', 'FR', 'IT', 'MT', 'SI', 'HR', 'EL', 'GR', 'CY'];

    /** States whose whole coast lies in one sub-region, so an EASIN record there dates that sub-region. */
    private const STATE_SUBREGION = ['SI' => 'ADRIA', 'HR' => 'ADRIA', 'MT' => 'CMED', 'CY' => 'EMED'];

    /** Note labels (IntroEventRecordImporter::columnLabel) of the sub-region year columns. */
    private const SUBREGION_LABELS = ['WMED' => 'WMED', 'CMED' => 'CMED', 'Adriatic' => 'ADRIA', 'EMED' => 'EMED'];

    public function __construct(private EasinService $easin) {}

    /**
     * One proposal per unresolved cell of every flagged event.
     *
     * @return Collection<int, array{event: int, species: string, field: string, subregion: ?string, raw: string, proposed: int|string|null, category: string, rule: string, easin: string, conflict: bool}>
     */
    public function proposals(): Collection
    {
        return IntroEventRecord::query()
            ->where('needs_review', true)
            ->with(['taxon:id,scientificname', 'subregionRecords'])
            ->orderBy('id')
            ->get()
            ->flatMap(fn (IntroEventRecord $event): array => $this->proposalsFor($event));
    }

    /**
     * @return list<array{event: int, species: string, field: string, subregion: ?string, raw: string, proposed: int|string|null, category: string, rule: string, easin: string, conflict: bool}>
     */
    public function proposalsFor(IntroEventRecord $event): array
    {
        $species = (string) $event->taxon?->getAttribute('scientificname');
        $easin = $this->easinMediterraneanRecords($species);
        $proposals = [];

        $alreadyResolved = self::resolvedFields((string) $event->notes);

        foreach (self::unresolvedCells((string) $event->notes) as [$label, $raw]) {
            if (in_array($label, $alreadyResolved, true)) {
                continue;
            }

            $subregion = self::subregionFor($label);
            [$proposed, $category, $rule] = str_ends_with($label, 'Year')
                ? self::readYear($raw)
                : self::readStatus($label, $raw);

            $proposals[] = ['event' => $event->id, 'species' => $species, 'field' => $label, 'subregion' => $subregion, 'raw' => $raw, 'proposed' => $proposed, 'category' => $category, 'rule' => $rule, 'easin' => '', 'conflict' => false];
        }

        // An undated event takes its earliest sub-region year, resolved or already stored.
        $basin = collect($proposals)->firstWhere('field', 'First Introduction Year');

        if ($event->first_introduction_year === null && ($basin === null || $basin['proposed'] === null)) {
            $years = [
                ...$event->subregionRecords->pluck('first_arrival_year')->filter()->all(),
                ...collect($proposals)->whereNotNull('subregion')->pluck('proposed')->filter(fn (mixed $year): bool => is_int($year))->all(),
            ];

            if ($years !== []) {
                $derived = ['event' => $event->id, 'species' => $species, 'field' => 'First Introduction Year', 'subregion' => null, 'raw' => $basin['raw'] ?? '(empty)', 'proposed' => min($years), 'category' => 'derived', 'rule' => 'earliest sub-region year', 'easin' => '', 'conflict' => false];
                $proposals = $basin === null ? [...$proposals, $derived] : array_map(fn (array $p): array => $p['field'] === 'First Introduction Year' ? $derived : $p, $proposals);
            }
        }

        $basinYear = collect($proposals)->firstWhere('field', 'First Introduction Year')['proposed'] ?? $event->first_introduction_year;

        return array_map(fn (array $proposal): array => $this->checked($proposal, $easin, is_int($basinYear) ? $basinYear : null), $proposals);
    }

    /**
     * Write the proposals whose category is in $categories and that have no
     * conflict. The event's needs_review clears once none of its cells is
     * left, and the notes record what was resolved, from what, and why.
     *
     * @param  list<string>  $categories
     * @return array{applied: int, events_cleared: int}
     */
    public function apply(array $categories): array
    {
        $applied = 0;
        $cleared = 0;

        foreach ($this->proposals()->groupBy('event') as $eventId => $proposals) {
            $event = IntroEventRecord::query()->with('subregionRecords')->findOrFail($eventId);
            $done = $proposals->filter(fn (array $p): bool => $p['proposed'] !== null && ! $p['conflict'] && in_array($p['category'], $categories, true));

            if ($done->isEmpty()) {
                continue;
            }

            foreach ($done as $proposal) {
                $this->write($event, $proposal);
            }

            $applied += $done->count();
            $resolved = $done->map(fn (array $p): string => "{$p['field']} '{$p['raw']}' → {$p['proposed']} ({$p['rule']})")->implode('; ');
            $event->notes = trim($event->notes."\nResolved on review ".now()->toDateString().': '.$resolved);

            // Every original cell answered, now or on an earlier run (a derived basin year also answers an unreadable one)?
            $answered = [...$done->pluck('field')->all(), ...self::resolvedFields((string) $event->getOriginal('notes'))];
            $open = collect(self::unresolvedCells((string) $event->getOriginal('notes')))->reject(fn (array $cell): bool => in_array($cell[0], $answered, true));

            if ($open->isEmpty()) {
                $event->needs_review = false;
                $cleared++;
            }

            $event->save();
        }

        return ['applied' => $applied, 'events_cleared' => $cleared];
    }

    /**
     * Read an ambiguous year cell.
     *
     * @return array{0: ?int, 1: string, 2: string}
     */
    public static function readYear(string $raw): array
    {
        $value = trim($raw);

        if (preg_match('/^(.+?)\s+(?:not|no)\s+.+$/iu', $value, $m) === 1) {
            [$year, , $rule] = self::readYear($m[1]);

            return $year === null ? [null, 'manual', 'unreadable correction'] : [$year, 'correction', "corrected value, {$rule}"];
        }

        return match (true) {
            preg_match('/^(\d{4})\s*[-–\/]\s*(\d{1,4})$/u', $value, $m) === 1 => self::range((int) $m[1], $m[2]),
            preg_match('/^[<≤]\s*(\d{4})$/u', $value, $m) === 1 => [(int) $m[1], 'before', "recorded by {$m[1]}: latest possible year"],
            preg_match('/^(\d{4})\s*\?$/u', $value, $m) === 1, preg_match('/^[~≈]\s*(\d{4})$/u', $value, $m) === 1 => [(int) $m[1], 'approximate', 'approximate year'],
            preg_match("/^(\d{3})0\s*'?s$/iu", $value, $m) === 1 => [(int) ($m[1].'0'), 'decade', "{$m[1]}0s: first year of the decade"],
            preg_match('/^(\d{4})$/', $value, $m) === 1 => [(int) $m[1], 'historical', 'year before 1800, outside the import range'],
            default => [null, 'manual', 'unreadable year'],
        };
    }

    /**
     * @return array{0: ?int, 1: string, 2: string}
     */
    private static function range(int $from, string $to): array
    {
        $end = (int) (strlen($to) === 4 ? $to : substr((string) $from, 0, 4 - strlen($to)).$to);

        return $end > $from ? [$from, 'range', "range {$from}–{$end}: earliest year"] : [null, 'manual', 'range that does not run forward'];
    }

    /**
     * @return array{0: ?string, 1: string, 2: string}
     */
    private static function readStatus(string $label, string $raw): array
    {
        return $label === 'NIS Status' && preg_match('/^A\s*:?$/i', trim($raw)) === 1
            ? [NisStatus::NIS->value, 'status', 'EASIN code A (alien) = NIS']
            : [null, 'manual', 'status code with no clear equivalent'];
    }

    /**
     * EASIN's first EU records in Mediterranean member states, earliest first.
     *
     * @return list<array{country: string, year: int}>
     */
    private function easinMediterraneanRecords(string $species): array
    {
        $entry = $species === '' ? null : $this->easin->findSpecies($species);

        return collect($entry['FirstIntroductionsInEU'] ?? [])
            ->map(fn (array $record): array => ['country' => strtoupper((string) ($record['Country'] ?? '')), 'year' => (int) substr((string) ($record['Year'] ?? ''), 0, 4)])
            ->filter(fn (array $record): bool => in_array($record['country'], self::MEDITERRANEAN_STATES, true) && $record['year'] > 0)
            ->sortBy('year')
            ->values()
            ->all();
    }

    /**
     * Compare a proposal with EASIN (and a sub-region year with the basin year).
     *
     * @param  array{event: int, species: string, field: string, subregion: ?string, raw: string, proposed: int|string|null, category: string, rule: string, easin: string, conflict: bool}  $proposal
     * @param  list<array{country: string, year: int}>  $easin
     * @return array{event: int, species: string, field: string, subregion: ?string, raw: string, proposed: int|string|null, category: string, rule: string, easin: string, conflict: bool}
     */
    private function checked(array $proposal, array $easin, ?int $basinYear): array
    {
        if (! is_int($proposal['proposed'])) {
            return $proposal;
        }

        $relevant = $proposal['subregion'] === null
            ? $easin
            : array_values(array_filter($easin, fn (array $r): bool => (self::STATE_SUBREGION[$r['country']] ?? null) === $proposal['subregion']));

        $first = $relevant[0] ?? null;
        $context = $easin[0] ?? null;
        $proposal['easin'] = match (true) {
            $first !== null => "EASIN first EU record {$first['country']} {$first['year']}",
            // A record in a state spanning several sub-regions (IT, EL, ES, FR) cannot date this one: context only.
            $context !== null => "EASIN first Mediterranean EU record {$context['country']} {$context['year']} (not specific to this sub-region)",
            default => 'no Mediterranean record in EASIN',
        };

        if ($first !== null && $first['year'] < $proposal['proposed']) {
            $proposal['conflict'] = true;
            $proposal['easin'] .= ' is earlier than the proposal';
        }

        if ($proposal['subregion'] !== null && $basinYear !== null && $proposal['proposed'] < $basinYear) {
            $proposal['conflict'] = true;
            $proposal['easin'] .= "; earlier than the basin first record ({$basinYear})";
        }

        return $proposal;
    }

    /**
     * @param  array{event: int, species: string, field: string, subregion: ?string, raw: string, proposed: int|string|null, category: string, rule: string, easin: string, conflict: bool}  $proposal
     */
    private function write(IntroEventRecord $event, array $proposal): void
    {
        match (true) {
            $proposal['field'] === 'First Introduction Year' => $event->first_introduction_year = (int) $proposal['proposed'],
            $proposal['field'] === 'NIS Status' => $event->nis_status = NisStatus::from((string) $proposal['proposed']),
            $proposal['subregion'] !== null => SubregionRecord::query()->updateOrCreate(
                ['intro_event_id' => $event->id, 'subregion' => Subregion::from($proposal['subregion'])],
                ['first_arrival_year' => (int) $proposal['proposed']],
            ),
            default => null,
        };
    }

    /**
     * The (label, raw value) cells listed on the event's "Needs review" note line.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function unresolvedCells(string $notes): array
    {
        $prefix = preg_quote(IntroEventRecordImporter::REVIEW_NOTE_PREFIX, '/');

        if (preg_match("/{$prefix}(.+)/u", $notes, $m) !== 1) {
            return [];
        }

        return array_values(array_filter(array_map(function (string $pair): ?array {
            $parts = explode(': ', $pair, 2);

            return count($parts) === 2 ? [trim($parts[0]), trim($parts[1])] : null;
        }, explode('; ', trim($m[1])))));
    }

    /**
     * Field labels already answered on an earlier "Resolved on review" note line.
     *
     * @return list<string>
     */
    private static function resolvedFields(string $notes): array
    {
        preg_match_all("/^Resolved on review [\d-]+: (.+)$/mu", $notes, $lines);

        return collect($lines[1])
            ->flatMap(fn (string $line): array => explode('; ', $line))
            ->map(fn (string $item): string => trim(strstr($item, " '", true) ?: $item))
            ->unique()
            ->values()
            ->all();
    }

    private static function subregionFor(string $label): ?string
    {
        foreach (self::SUBREGION_LABELS as $prefix => $code) {
            if (str_starts_with($label, "{$prefix} ")) {
                return str_ends_with($label, 'Year') ? $code : null;
            }
        }

        return null;
    }
}
