<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CbdPathwayCategory;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use Illuminate\Support\Facades\DB;

/**
 * Figures modelled on Zenetos et al. 2023, "Validated Inventories of
 * Non-Indigenous Species (NIS) for the Mediterranean Sea as Tools for
 * Regional Policy and Patterns of NIS Spread", Diversity 15(9):962 —
 * Figures 2–4 and Table 2, computed from MAMIAS introduction events.
 *
 * Two deliberate departures from the paper, both forced by the data:
 * - Dates are the event's year of first Mediterranean record, as everywhere
 *   on the dashboard; the paper dates each sub-region by its own first record.
 * - The paper gives each species one primary pathway. Here an event may carry
 *   several, so its weight is split equally between them (1/n each) and the
 *   pathway shares still sum to 100%. Events without a pathway are "UNK".
 *
 * Everything is derived from one compact profile per event (profiles()), so
 * each figure costs no extra query.
 *
 * forCountry() turns each figure into a national one for the events first
 * recorded in the Mediterranean in that country, set beside the country's
 * sub-regions and the whole basin; Figure 4 then shows where those species
 * spread to afterwards.
 */
final class MediterraneanNisPatterns
{
    /** Broad taxa groups of the paper (Figure 5 legend), in its order. */
    public const GROUPS = [
        'PHY' => 'Macrophytes',
        'ANN' => 'Annelida',
        'MOL' => 'Mollusca',
        'ART' => 'Arthropoda',
        'FISH' => 'Fishes',
        'ASC' => 'Ascidiacea',
        'CNI' => 'Cnidaria',
        'BRY' => 'Bryozoa',
        'FOR' => 'Foraminifera',
        'MISC' => 'Miscellanea',
    ];

    /** Pathway codes of the paper (Figure 2 legend), CBD category => code. */
    public const PATHWAYS = [
        '1' => 'REL',
        '2' => 'EC',
        '3' => 'TS',
        '4' => 'TC',
        '5' => 'COR',
        '6' => 'UNA',
    ];

    public const UNKNOWN_PATHWAY = 'UNK';

    /**
     * The EcAp sub-regions each Mediterranean country's waters lie in (stored
     * country names), the comparison columns of a country's figures.
     */
    public const COUNTRY_SUBREGIONS = [
        'Spain' => ['WMED'],
        'France' => ['WMED'],
        'Monaco' => ['WMED'],
        'Morocco' => ['WMED'],
        'Algeria' => ['WMED'],
        'Tunisia' => ['WMED', 'CMED'],
        'Italy' => ['WMED', 'CMED', 'ADRIA'],
        'Malta' => ['CMED'],
        'Libya' => ['CMED', 'EMED'],
        'Slovenia' => ['ADRIA'],
        'Croatia' => ['ADRIA'],
        'Bosnia and Herzegovina' => ['ADRIA'],
        'Montenegro' => ['ADRIA'],
        'Albania' => ['ADRIA', 'CMED'],
        'Greece' => ['CMED', 'EMED'],
        'Türkiye' => ['EMED'],
        'Cyprus' => ['EMED'],
        'Syria' => ['EMED'],
        'Lebanon' => ['EMED'],
        'Israel' => ['EMED'],
        'Egypt' => ['EMED'],
    ];

    /** Phyla the paper groups as macrophytes (Heterokontophyta is an older name for brown algae). */
    private const MACROPHYTE_PHYLA = ['Rhodophyta', 'Chlorophyta', 'Ochrophyta', 'Heterokontophyta', 'Tracheophyta', 'Charophyta'];

    private const FISH_CLASSES = ['Teleostei', 'Actinopterygii', 'Elasmobranchii', 'Chondrichthyes'];

    /** @var list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>|null */
    private ?array $profiles = null;

    private ?string $country = null;

    /**
     * The same figures for the events first recorded in $country (null: the whole basin).
     */
    public function forCountry(?string $country): self
    {
        $scoped = clone $this;
        $scoped->country = $country;

        return $scoped;
    }

    /**
     * Figure 2: primary-pathway shares (%) for the whole basin and each sub-region.
     *
     * @return array{codes: list<string>, labels: array<string, string>, rows: list<array{name: string, total: int, shares: array<string, float>}>}
     */
    public function pathwayShares(): array
    {
        return [
            'codes' => self::pathwayCodes(),
            'labels' => self::pathwayLabels(),
            'rows' => array_map(fn (string $name, array $events): array => [
                'name' => $name,
                'total' => count($events),
                'shares' => self::pathwayProfile($events),
            ], array_keys($this->scopes()), $this->scopes()),
        ];
    }

    /**
     * Figure 3a: mean annual number of new NIS per 10-year cycle (with its
     * standard error), basin-wide and per sub-region. Cycles count back from
     * the latest year (…, 2001–2010, 2011–2020) and start from 1970.
     *
     * @return array{cycles: list<string>, series: array<string, array{mean: list<float>, se: list<float>}>}
     */
    public function introductionRates(int $since = 1971): array
    {
        $latest = $this->latestYear();
        $cycles = [];

        for ($end = $latest; $end - 9 >= $since; $end -= 10) {
            array_unshift($cycles, [$end - 9, $end]);
        }

        $series = [];

        foreach ($this->scopes() as $name => $events) {
            $perYear = self::countByYear($events);
            $mean = $se = [];

            foreach ($cycles as [$from, $to]) {
                $counts = array_map(fn (int $year): int => $perYear[$year] ?? 0, range($from, $to));
                $average = array_sum($counts) / count($counts);
                $variance = array_sum(array_map(fn (int $count): float => ($count - $average) ** 2, $counts)) / (count($counts) - 1);
                $mean[] = round($average, 2);
                $se[] = round(sqrt($variance) / sqrt(count($counts)), 2);
            }

            $series[$name] = ['mean' => $mean, 'se' => $se];
        }

        return ['cycles' => array_map(fn (array $cycle): string => "{$cycle[0]}–{$cycle[1]}", $cycles), 'series' => $series];
    }

    /**
     * Figure 3b: new NIS per year, basin-wide and per sub-region.
     *
     * @return array{years: list<int>, series: array<string, list<int>>}
     */
    public function yearlyRates(int $since = 1990): array
    {
        $years = range($since, max($since, $this->latestYear()));

        return [
            'years' => $years,
            'series' => array_map(function (array $events) use ($years): array {
                $perYear = self::countByYear($events);

                return array_map(fn (int $year): int => $perYear[$year] ?? 0, $years);
            }, $this->scopes()),
        ];
    }

    /**
     * Figure 4: NIS recorded by the end of each decade, split by the exact
     * combination of sub-regions they occur in ("EMED only", "WMED+EMED", …,
     * all four). Combinations run from one sub-region up to all four.
     *
     * @return array{steps: list<int>, combinations: list<string>, counts: array<int, list<int>>}
     */
    public function sharedBySubregion(int $from = 1970): array
    {
        $combinations = self::combinations();
        $steps = range($from, (int) (ceil($this->latestYear() / 10) * 10), 10);
        $counts = [];

        foreach ($steps as $step) {
            $tally = array_fill_keys($combinations, 0);

            foreach ($this->scoped() as $event) {
                if ($event['year'] !== null && $event['year'] <= $step && $event['subregions'] !== []) {
                    $tally[implode('+', $event['subregions'])]++;
                }
            }

            $counts[$step] = array_values($tally);
        }

        return ['steps' => $steps, 'combinations' => $combinations, 'counts' => $counts];
    }

    /**
     * Table 2: NIS per broad taxa group, basin-wide and per sub-region.
     *
     * @return array{groups: array<string, string>, columns: list<string>, counts: array<string, list<int>>, totals: list<int>}
     */
    public function groupsBySubregion(): array
    {
        $scopes = $this->scopes();
        $columns = array_keys($scopes);
        $counts = [];

        foreach (array_keys(self::GROUPS) as $group) {
            $counts[$group] = array_map(
                fn (string $column): int => count(array_filter($scopes[$column], fn (array $event): bool => $event['group'] === $group)),
                $columns,
            );
        }

        return [
            'groups' => self::GROUPS,
            'columns' => $columns,
            'counts' => $counts,
            'totals' => array_map(fn (string $column): int => count($scopes[$column]), $columns),
        ];
    }

    /**
     * One row per live introduction event: first-record year, broad taxa
     * group, the sub-regions it is reported in and its pathway codes.
     *
     * @return list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>
     */
    public function profiles(): array
    {
        if ($this->profiles !== null) {
            return $this->profiles;
        }

        $events = IntroEventRecord::query()
            ->baseline()
            ->join('taxas', 'taxas.id', '=', 'intro_event_records.taxon_id')
            ->select('intro_event_records.id', 'intro_event_records.first_introduction_year', 'intro_event_records.first_country', 'taxas.phylum', 'taxas.class')
            ->toBase()
            ->get();

        $subregions = DB::table('subregion_records')->whereIn('intro_event_id', $events->pluck('id'))->get(['intro_event_id', 'subregion'])->groupBy('intro_event_id');
        $pathways = DB::table('pathway_records')->whereIn('intro_event_id', $events->pluck('id'))->get(['intro_event_id', 'category'])->groupBy('intro_event_id');
        $order = array_flip(self::subregionCodes());

        return $this->profiles = $events->map(function (object $event) use ($subregions, $pathways, $order): array {
            $codes = collect($subregions->get($event->id, []))->pluck('subregion')->unique()->filter(fn (mixed $code): bool => isset($order[$code]))->sortBy(fn (string $code): int => $order[$code])->values()->all();
            $paths = collect($pathways->get($event->id, []))->pluck('category')->unique()->map(fn (mixed $category): string => self::PATHWAYS[(string) $category] ?? self::UNKNOWN_PATHWAY)->values()->all();

            return [
                'year' => $event->first_introduction_year === null ? null : (int) $event->first_introduction_year,
                'group' => self::groupFor((string) $event->phylum, (string) $event->class),
                'subregions' => $codes,
                'pathways' => $paths === [] ? [self::UNKNOWN_PATHWAY] : $paths,
                'countries' => array_values(array_unique(array_map(MediterraneanDashboard::reportedCountry(...), (array) json_decode((string) $event->first_country, true)))),
            ];
        })->values()->all();
    }

    public static function groupFor(string $phylum, string $class): string
    {
        return match (true) {
            in_array($phylum, self::MACROPHYTE_PHYLA, true) => 'PHY',
            $phylum === 'Annelida' => 'ANN',
            $phylum === 'Mollusca' => 'MOL',
            $phylum === 'Arthropoda' => 'ART',
            $phylum === 'Chordata' && in_array($class, self::FISH_CLASSES, true) => 'FISH',
            $phylum === 'Chordata' && $class === 'Ascidiacea' => 'ASC',
            $phylum === 'Cnidaria' => 'CNI',
            $phylum === 'Bryozoa' => 'BRY',
            $phylum === 'Foraminifera' => 'FOR',
            default => 'MISC',
        };
    }

    /**
     * The Mediterranean as a whole, then each sub-region. Scoped to a
     * country: the country, then its sub-regions, then the Mediterranean.
     *
     * @return array<string, list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>>
     */
    private function scopes(): array
    {
        if ($this->country === null) {
            $scopes = ['Mediterranean' => $this->profiles()];

            foreach (self::subregionCodes() as $code) {
                $scopes[$code] = $this->inSubregion($code);
            }

            return $scopes;
        }

        $scopes = [$this->country => $this->scoped()];

        foreach (self::COUNTRY_SUBREGIONS[$this->country] ?? [] as $code) {
            $scopes[$code] = $this->inSubregion($code);
        }

        return [...$scopes, 'Mediterranean' => $this->profiles()];
    }

    /**
     * The events the figures are about: all of them, or those first recorded in the country.
     *
     * @return list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>
     */
    private function scoped(): array
    {
        return $this->country === null
            ? $this->profiles()
            : array_values(array_filter($this->profiles(), fn (array $event): bool => in_array($this->country, $event['countries'], true)));
    }

    /**
     * @return list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>
     */
    private function inSubregion(string $code): array
    {
        return array_values(array_filter($this->profiles(), fn (array $event): bool => in_array($code, $event['subregions'], true)));
    }

    private function latestYear(): int
    {
        return (int) max([0, ...array_filter(array_column($this->profiles(), 'year'))]);
    }

    /**
     * Pathway shares (%), each event's weight split equally between its pathways.
     *
     * @param  list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>  $events
     * @return array<string, float>
     */
    private static function pathwayProfile(array $events): array
    {
        $weights = array_fill_keys(self::pathwayCodes(), 0.0);

        foreach ($events as $event) {
            foreach ($event['pathways'] as $code) {
                $weights[$code] += 1 / count($event['pathways']);
            }
        }

        return self::percentages($weights, count($events));
    }

    /**
     * @param  array<string, int|float>  $values
     * @return array<string, float>
     */
    private static function percentages(array $values, int $total): array
    {
        return array_map(fn (int|float $value): float => $total > 0 ? round($value / $total * 100, 1) : 0.0, $values);
    }

    /**
     * @param  list<array{year: ?int, group: string, subregions: list<string>, pathways: list<string>, countries: list<string>}>  $events
     * @return array<int, int>
     */
    private static function countByYear(array $events): array
    {
        $years = array_filter(array_column($events, 'year'), fn (?int $year): bool => $year !== null);

        return array_count_values($years);
    }

    /**
     * Every non-empty combination of sub-regions, singles first, all four last.
     *
     * @return list<string>
     */
    private static function combinations(): array
    {
        $codes = self::subregionCodes();
        $combinations = [];

        for ($mask = 1; $mask < 2 ** count($codes); $mask++) {
            $combinations[] = implode('+', array_values(array_filter($codes, fn (string $code, int $index): bool => (bool) ($mask & (1 << $index)), ARRAY_FILTER_USE_BOTH)));
        }

        usort($combinations, fn (string $a, string $b): int => substr_count($a, '+') <=> substr_count($b, '+'));

        return $combinations;
    }

    /**
     * @return list<string>
     */
    private static function pathwayCodes(): array
    {
        return [...array_values(self::PATHWAYS), self::UNKNOWN_PATHWAY];
    }

    /**
     * @return array<string, string>
     */
    private static function pathwayLabels(): array
    {
        $labels = [];

        foreach (CbdPathwayCategory::cases() as $category) {
            $labels[self::PATHWAYS[$category->value]] = (string) $category->getLabel();
        }

        return [...$labels, self::UNKNOWN_PATHWAY => 'Unknown'];
    }

    /**
     * @return list<string>
     */
    private static function subregionCodes(): array
    {
        return array_map(fn (Subregion $subregion): string => $subregion->value, Subregion::cases());
    }
}
