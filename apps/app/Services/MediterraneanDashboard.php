<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CbdPathwayCategory;
use App\Enums\EstablishmentStatus;
use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Models\IntroEventRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the public Mediterranean dashboard (Layup page
 * pages/dashboard/mediterranean). Everything counts live introduction events
 * and reads their own fields — basin-level establishment status, year of first
 * Mediterranean record — so the numbers agree across charts. Sub-region and
 * pathway records only say *where* and *how* an event is reported; each event
 * counts once per sub-region or pathway. Each method returns plain arrays,
 * ready for the chart payloads.
 *
 * forCountry() narrows every figure to the events first recorded in the
 * Mediterranean in one country (pages/dashboard/by-country). MAMIAS holds no
 * national presence records, so that is "entered the Mediterranean here",
 * not the country's national inventory.
 */
final class MediterraneanDashboard
{
    /**
     * Stored first countries reported under another Party of the Barcelona
     * Convention, which has no Gaza strip. The records keep what the
     * literature says; only the country figures merge them.
     */
    public const REPORTED_AS = ['Gaza strip' => 'Israel'];

    private ?string $country = null;

    /** The Barcelona Convention country a stored first country is reported under. */
    public static function reportedCountry(string $country): string
    {
        return self::REPORTED_AS[$country] ?? $country;
    }

    /** Status stack label => the statuses it groups, in stacking order. */
    public const STATUS_STACKS = [
        'Invasive' => [EstablishmentStatus::Invasive],
        'Established' => [EstablishmentStatus::Established],
        'Casual / vagrant' => [EstablishmentStatus::Casual, EstablishmentStatus::Vagrant],
    ];

    public const OTHER_STATUS = 'Unknown / other';

    /** A taxon's phylum, blank ones reported as "Unknown". */
    private const PHYLUM = "coalesce(nullif(taxas.phylum, ''), 'Unknown')";

    /**
     * The same figures, narrowed to events first recorded in $country (null: the whole basin).
     */
    public function forCountry(?string $country): self
    {
        $scoped = clone $this;
        $scoped->country = $country;

        return $scoped;
    }

    /**
     * Events first recorded in each Mediterranean country, largest first,
     * countries without one included. An event with co-first countries
     * counts in each.
     *
     * @return list<array{country: string, value: int}>
     */
    public function countryRanking(): array
    {
        $counts = IntroEventRecord::query()
            ->baseline()
            ->toBase()
            ->crossJoin(DB::raw('jsonb_array_elements_text(intro_event_records.first_country::jsonb) as first_countries(country)'))
            ->selectRaw(self::reportedCountrySql('first_countries.country').' as country, count(distinct intro_event_records.id) as total')
            ->groupByRaw(self::reportedCountrySql('first_countries.country'))
            ->pluck('total', 'country');

        return collect([...CountrySelectWithMedPriority::mediterraneanNames(), ...$counts->keys()])
            ->unique()
            ->map(fn (string $country): array => ['country' => $country, 'value' => (int) $counts->get($country, 0)])
            ->sortBy([['value', 'desc'], ['country', 'asc']])
            ->values()
            ->all();
    }

    /**
     * Scoped to a country, also its rank among the countries with a first
     * record and its share of all reported NIS.
     *
     * @return array{events: int, established: int, casual: int, recent: int, recentFrom: int, recentTo: int, country?: string, rank?: int, countries?: int, share?: float}
     */
    public function headline(): array
    {
        // Basin-wide, so a country's "recent" decade is the same ten years as everyone's.
        $latest = (int) IntroEventRecord::query()->baseline()->max('first_introduction_year');

        $row = $this->events()
            ->toBase()
            ->selectRaw('count(*) as events')
            ->selectRaw('count(*) filter (where establishment_status = ?) as established', [EstablishmentStatus::Established->value])
            ->selectRaw('count(*) filter (where establishment_status in (?, ?)) as casual', [EstablishmentStatus::Casual->value, EstablishmentStatus::Vagrant->value])
            ->selectRaw('count(*) filter (where first_introduction_year > ?) as recent', [$latest - 10])
            ->first();

        $headline = [
            'events' => (int) $row->events,
            'established' => (int) $row->established,
            'casual' => (int) $row->casual,
            'recent' => (int) $row->recent,
            'recentFrom' => $latest - 9,
            'recentTo' => $latest,
        ];

        if ($this->country === null) {
            return $headline;
        }

        $ranked = array_values(array_filter($this->countryRanking(), fn (array $row): bool => $row['value'] > 0));
        $position = array_search($this->country, array_column($ranked, 'country'), true);
        $all = IntroEventRecord::query()->baseline()->count();

        return [
            ...$headline,
            'country' => $this->country,
            'rank' => $position === false ? 0 : $position + 1,
            'countries' => count($ranked),
            'share' => $all > 0 ? round($headline['events'] / $all * 100, 1) : 0.0,
        ];
    }

    /**
     * New NIS per decade of first Mediterranean record, and the running total.
     * Empty decades stay as zero so the time axis is linear.
     *
     * @return array{labels: list<string>, counts: list<int>, cumulative: list<int>}
     */
    public function trend(): array
    {
        $byDecade = $this->events()
            ->toBase()
            ->whereNotNull('first_introduction_year')
            ->selectRaw('(first_introduction_year / 10) * 10 as decade, count(*) as total')
            ->groupBy('decade')
            ->pluck('total', 'decade');

        $labels = $counts = $cumulative = [];
        $runningTotal = 0;

        foreach (self::decades($byDecade->keys()) as $decade) {
            $count = (int) $byDecade->get($decade, 0);
            $runningTotal += $count;
            $labels[] = "{$decade}s";
            $counts[] = $count;
            $cumulative[] = $runningTotal;
        }

        return ['labels' => $labels, 'counts' => $counts, 'cumulative' => $cumulative];
    }

    /**
     * NIS per sub-region, every sub-region present (zero included).
     *
     * @return array<string, int>
     */
    public function nisBySubregion(): array
    {
        $counts = $this->subregionRecords()
            ->groupBy('subregion_records.subregion')
            ->selectRaw('subregion_records.subregion, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->pluck('total', 'subregion');

        return self::perSubregion(fn (Subregion $subregion): int => (int) ($counts[$subregion->value] ?? 0));
    }

    /**
     * NIS per sub-region, split into the status stacks by the sub-region's own
     * establishment status: invasiveness is only ever assessed per sub-region,
     * so the basin-level status would hide it.
     *
     * @return array{subregions: list<string>, stacks: array<string, list<int>>}
     */
    public function statusBySubregion(): array
    {
        $rows = $this->subregionRecords()
            ->groupBy('subregion_records.subregion', 'subregion_records.establishment_status')
            ->selectRaw('subregion_records.subregion, subregion_records.establishment_status, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->get();

        $stacks = array_fill_keys([...array_keys(self::STATUS_STACKS), self::OTHER_STATUS], []);

        foreach ($rows as $row) {
            $stack = self::stackFor(EstablishmentStatus::tryFrom((string) $row->establishment_status));
            $stacks[$stack][$row->subregion] = ($stacks[$stack][$row->subregion] ?? 0) + (int) $row->total;
        }

        return [
            'subregions' => array_map(fn (Subregion $subregion): string => $subregion->value, Subregion::cases()),
            'stacks' => array_filter(array_map(
                fn (array $bySubregion): array => array_values(self::perSubregion(fn (Subregion $subregion): int => $bySubregion[$subregion->value] ?? 0)),
                $stacks,
            ), fn (array $values): bool => array_sum($values) > 0),
        ];
    }

    /**
     * NIS per CBD pathway category, largest first. An event with several
     * pathways counts once in each.
     *
     * @return list<array{label: string, value: int}>
     */
    public function pathways(): array
    {
        $counts = $this->events()
            ->join('pathway_records', 'pathway_records.intro_event_id', '=', 'intro_event_records.id')
            ->groupBy('pathway_records.category')
            ->selectRaw('pathway_records.category, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->pluck('total', 'category');

        return collect(CbdPathwayCategory::cases())
            ->map(fn (CbdPathwayCategory $category): array => ['label' => (string) $category->getLabel(), 'value' => (int) $counts->get($category->value, 0)])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * NIS as a kingdom → phylum tree.
     *
     * @return list<array{name: string, value: int, children: list<array{name: string, value: int}>}>
     */
    public function taxonomy(): array
    {
        return $this->events()
            ->join('taxas', 'taxas.id', '=', 'intro_event_records.taxon_id')
            ->groupBy('taxas.kingdom', 'taxas.phylum')
            ->selectRaw("coalesce(nullif(taxas.kingdom, ''), 'Unknown') as kingdom, coalesce(nullif(taxas.phylum, ''), 'Unknown') as phylum, count(distinct intro_event_records.id) as total")
            ->toBase()
            ->get()
            ->groupBy('kingdom')
            ->map(fn (Collection $phyla, string $kingdom): array => [
                'name' => $kingdom,
                'value' => (int) $phyla->sum('total'),
                'children' => $phyla
                    ->map(fn (object $row): array => ['name' => (string) $row->phylum, 'value' => (int) $row->total])
                    ->sortByDesc('value')
                    ->values()
                    ->all(),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * NIS per phylum, split into the status stacks (basin-level status).
     * Phyla run largest first; the chart folds the tail into "Other phyla".
     *
     * @return array{phyla: list<string>, stacks: array<string, list<int>>}
     */
    public function statusByPhylum(): array
    {
        $rows = $this->events()
            ->join('taxas', 'taxas.id', '=', 'intro_event_records.taxon_id')
            ->groupBy('phylum', 'intro_event_records.establishment_status')
            ->selectRaw("coalesce(nullif(taxas.phylum, ''), 'Unknown') as phylum, intro_event_records.establishment_status, count(distinct intro_event_records.id) as total")
            ->toBase()
            ->get();

        $phyla = $rows->groupBy('phylum')->map->sum('total')->sortDesc()->keys()->map(fn (mixed $phylum): string => (string) $phylum)->all();
        $stacks = array_fill_keys([...array_keys(self::STATUS_STACKS), self::OTHER_STATUS], array_fill_keys($phyla, 0));

        foreach ($rows as $row) {
            $stacks[self::stackFor(EstablishmentStatus::tryFrom((string) $row->establishment_status))][$row->phylum] += (int) $row->total;
        }

        return [
            'phyla' => $phyla,
            'stacks' => array_filter(array_map(array_values(...), $stacks), fn (array $values): bool => array_sum($values) > 0),
        ];
    }

    /**
     * NIS per phylum and CBD pathway category, for a heatmap. An event with
     * several pathways counts once in each.
     *
     * @return array{rows: list<string>, columns: list<string>, values: list<list<int>>}
     */
    public function pathwaysByPhylum(): array
    {
        $rows = $this->taxa()
            ->join('pathway_records', 'pathway_records.intro_event_id', '=', 'intro_event_records.id')
            ->groupBy('phylum', 'pathway_records.category')
            ->selectRaw(self::PHYLUM.' as phylum, pathway_records.category as dimension, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->get();

        return self::phylumMatrix(
            $rows,
            array_map(fn (CbdPathwayCategory $category): string => $category->value, CbdPathwayCategory::cases()),
            array_map(fn (CbdPathwayCategory $category): string => (string) $category->getLabel(), CbdPathwayCategory::cases()),
        );
    }

    /**
     * NIS per phylum and EcAp sub-region, for a heatmap.
     *
     * @return array{rows: list<string>, columns: list<string>, values: list<list<int>>}
     */
    public function subregionsByPhylum(): array
    {
        $rows = $this->taxa()
            ->join('subregion_records', 'subregion_records.intro_event_id', '=', 'intro_event_records.id')
            ->groupBy('phylum', 'subregion_records.subregion')
            ->selectRaw(self::PHYLUM.' as phylum, subregion_records.subregion as dimension, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->get();

        $codes = array_map(fn (Subregion $subregion): string => $subregion->value, Subregion::cases());

        return self::phylumMatrix($rows, $codes, $codes);
    }

    /**
     * NIS as a kingdom → phylum → class → family tree, for a drill-down treemap.
     *
     * @return list<array{name: string, value: int, children?: list<array<string, mixed>>}>
     */
    public function taxonomyTree(): array
    {
        $rows = $this->taxa()
            ->groupBy('kingdom', 'phylum', 'class', 'family')
            ->selectRaw("coalesce(nullif(taxas.kingdom, ''), 'Unknown') as kingdom, ".self::PHYLUM." as phylum, coalesce(nullif(taxas.class, ''), 'Unknown') as class, coalesce(nullif(taxas.family, ''), 'Unknown') as family, count(distinct intro_event_records.id) as total")
            ->toBase()
            ->get();

        return self::nest($rows, ['kingdom', 'phylum', 'class', 'family']);
    }

    /**
     * NIS per sub-region, split by CBD pathway category (in category order).
     * An event with several pathways counts once in each.
     *
     * @return array{subregions: list<string>, series: array<string, list<int>>}
     */
    public function pathwaysBySubregion(): array
    {
        $totals = $this->subregionRecords()
            ->join('pathway_records', 'pathway_records.intro_event_id', '=', 'intro_event_records.id')
            ->groupBy('subregion_records.subregion', 'pathway_records.category')
            ->selectRaw('subregion_records.subregion, pathway_records.category, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->get()
            ->keyBy(fn (object $row): string => $row->subregion.'|'.$row->category);

        return [
            'subregions' => array_map(fn (Subregion $subregion): string => $subregion->value, Subregion::cases()),
            'series' => collect(CbdPathwayCategory::cases())->mapWithKeys(fn (CbdPathwayCategory $category): array => [
                (string) $category->getLabel() => array_values(self::perSubregion(
                    fn (Subregion $subregion): int => (int) ($totals->get($subregion->value.'|'.$category->value)->total ?? 0),
                )),
            ])->all(),
        ];
    }

    /**
     * New NIS per sub-region per decade of the introduction event's first
     * Mediterranean record.
     *
     * @return array{labels: list<string>, series: array<string, list<int>>}
     */
    public function spread(): array
    {
        $rows = $this->subregionRecords()
            ->whereNotNull('intro_event_records.first_introduction_year')
            ->groupBy('subregion_records.subregion', 'decade')
            ->selectRaw('subregion_records.subregion, (intro_event_records.first_introduction_year / 10) * 10 as decade, count(distinct intro_event_records.id) as total')
            ->toBase()
            ->get();

        $decades = self::decades($rows->pluck('decade'));
        $totals = $rows->keyBy(fn (object $row): string => $row->subregion.'|'.$row->decade);

        return [
            'labels' => array_map(fn (int $decade): string => "{$decade}s", $decades),
            'series' => self::perSubregion(fn (Subregion $subregion): array => array_map(
                fn (int $decade): int => (int) ($totals->get($subregion->value.'|'.$decade)->total ?? 0),
                $decades,
            )),
        ];
    }

    /**
     * Live introduction events in the validated baseline; the soft-delete
     * scope rides on the model.
     *
     * @return Builder<IntroEventRecord>
     */
    private function events(): Builder
    {
        return IntroEventRecord::query()
            ->baseline()
            ->when($this->country !== null, fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                foreach ([$this->country, ...array_keys(self::REPORTED_AS, $this->country, true)] as $stored) {
                    $query->orWhereJsonContains('intro_event_records.first_country', $stored);
                }
            }));
    }

    /**
     * SQL mapping a stored first-country column to its reported country.
     */
    private static function reportedCountrySql(string $column): string
    {
        $cases = collect(self::REPORTED_AS)->map(fn (string $to, string $from): string => 'when '.$column.' = '.DB::getPdo()->quote($from).' then '.DB::getPdo()->quote($to))->implode(' ');

        return "case {$cases} else {$column} end";
    }

    /**
     * @return Builder<IntroEventRecord>
     */
    private function taxa(): Builder
    {
        return $this->events()->join('taxas', 'taxas.id', '=', 'intro_event_records.taxon_id');
    }

    /**
     * Rows of (phylum, dimension, total) as a phylum × dimension grid: phyla
     * largest first (by row total), columns in the given key order, gaps as 0.
     *
     * @param  Collection<int, \stdClass>  $rows
     * @param  list<int|string>  $keys  dimension values, in column order
     * @param  list<string>  $labels  column labels, aligned with $keys
     * @return array{rows: list<string>, columns: list<string>, values: list<list<int>>}
     */
    private static function phylumMatrix(Collection $rows, array $keys, array $labels): array
    {
        $cells = $rows->keyBy(fn (object $row): string => $row->phylum.'|'.$row->dimension);
        $phyla = $rows->groupBy('phylum')->map->sum('total')->sortDesc()->keys()->map(fn (mixed $phylum): string => (string) $phylum)->all();

        return [
            'rows' => $phyla,
            'columns' => $labels,
            'values' => array_map(
                fn (string $phylum): array => array_map(fn (int|string $key): int => (int) ($cells->get($phylum.'|'.$key)->total ?? 0), $keys),
                $phyla,
            ),
        ];
    }

    /**
     * Group flat (level…, total) rows into a nested name/value/children tree,
     * each level largest first.
     *
     * @param  Collection<int, \stdClass>  $rows
     * @param  list<string>  $levels
     * @return list<array{name: string, value: int, children?: list<array<string, mixed>>}>
     */
    private static function nest(Collection $rows, array $levels): array
    {
        [$level, $rest] = [$levels[0], array_slice($levels, 1)];

        return $rows->groupBy($level)
            ->map(fn (Collection $group, string $name): array => [
                'name' => $name,
                'value' => (int) $group->sum('total'),
                ...($rest === [] ? [] : ['children' => self::nest($group, $rest)]),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * @return Builder<IntroEventRecord>
     */
    private function subregionRecords(): Builder
    {
        return $this->events()->join('subregion_records', 'subregion_records.intro_event_id', '=', 'intro_event_records.id');
    }

    /**
     * Every decade from the earliest to the latest key, gaps included.
     *
     * @param  Collection<array-key, mixed>  $keys
     * @return list<int>
     */
    private static function decades(Collection $keys): array
    {
        return $keys->isEmpty() ? [] : range((int) $keys->min(), (int) $keys->max(), 10);
    }

    /**
     * @template T
     *
     * @param  callable(Subregion): T  $value
     * @return array<string, T>
     */
    private static function perSubregion(callable $value): array
    {
        return collect(Subregion::cases())->mapWithKeys(fn (Subregion $subregion): array => [$subregion->value => $value($subregion)])->all();
    }

    private static function stackFor(?EstablishmentStatus $status): string
    {
        foreach (self::STATUS_STACKS as $stack => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $stack;
            }
        }

        return self::OTHER_STATUS;
    }
}
