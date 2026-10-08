<?php

declare(strict_types=1);

namespace App\Layup\Widgets;

use App\Enums\Subregion;
use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use App\Services\MediterraneanDashboard;
use App\Services\MediterraneanNisPatterns;
use Crumbls\Layup\View\BaseWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

/**
 * One live MAMIAS graphic for Layup pages, picked from CHARTS. Figures come
 * from MediterraneanDashboard at render time, so a published page never goes
 * stale; the charts themselves are drawn by resources/js/mediterranean-dashboard.js
 * (ECharts, and jsvectormap for the maps).
 */
class MamiasChartWidget extends BaseWidget
{
    /** Chart key => [builder label, default title, default note]. */
    public const CHARTS = [
        // Mediterranean-wide
        'headline' => ['Headline figures', 'Reported NIS in the Mediterranean', null],
        'trend' => ['Cumulative reported NIS over time', 'Reported NIS by decade of first Mediterranean record', 'Bars: new reported NIS per decade · line: running total'],
        'pathways' => ['Introduction pathways', 'Introduction pathways of reported NIS (CBD)', 'An introduction event with several pathways counts in each'],
        'taxonomy' => ['Taxonomic composition', 'Taxonomic composition of reported NIS', 'Reported NIS per phylum, coloured by kingdom, with the share of all reported NIS'],
        'taxon-status' => ['Establishment status by taxon', 'Establishment status of reported NIS by phylum', 'Basin-level establishment status of the reported NIS in each phylum'],
        // Per EcAp sub-region
        'spread-bars' => ['Reported NIS by decade, per sub-region', 'Reported NIS by decade of first record, per EcAp sub-region', 'New reported NIS per decade of first record in each sub-region'],
        'subregion-status' => ['Establishment status by sub-region', 'Establishment status of reported NIS by EcAp sub-region', null],
        'subregion-pathways' => ['Introduction pathways by sub-region', 'Introduction pathways of reported NIS by EcAp sub-region', 'An introduction event with several pathways counts in each'],
        'spread-map' => ['Spread by sub-region (map)', 'Reported NIS in each sub-region over time', 'Reported NIS in each sub-region up to the selected decade; circle area follows the count'],
        'subregion-map' => ['Sub-region map', 'Reported NIS by EcAp sub-region', null],
        // Taxonomy
        'phylum-pathways' => ['Phylum × pathway heatmap', 'Introduction pathways of reported NIS by phylum', 'Reported NIS per phylum and CBD pathway; an introduction event with several pathways counts in each'],
        'phylum-subregions' => ['Phylum × sub-region heatmap', 'Reported NIS by phylum and EcAp sub-region', 'Reported NIS per phylum in each sub-region'],
        'taxonomy-treemap' => ['Taxonomy treemap (drill-down)', 'Taxonomy of reported NIS', 'Kingdom › phylum › class › family; click a block to drill down, the path above to go back'],
        // Word clouds (echarts-wordcloud): an overview to explore, each word opening the data explorer
        'pathway-cloud' => ['Pathway word cloud', 'How reported NIS arrive', 'CBD pathways, sized by number of reported NIS; an introduction event with several pathways counts in each. Click a pathway to list its species.'],
        'family-cloud' => ['Family word cloud', 'Families of reported NIS', 'The 45 families with the most reported NIS, coloured by kingdom. Click a family to list its species.'],
        // After Zenetos et al. 2023, Diversity 15:962 (MediterraneanNisPatterns)
        'pathway-shares' => ['Pathway shares by sub-region (Fig. 2)', 'Primary pathways of reported NIS, Mediterranean and by EcAp sub-region', 'Share of reported NIS per pathway; an event with several pathways is split equally between them'],
        'introduction-rate' => ['Annual introduction rate (Fig. 3a)', 'Annual rate of new reported NIS', 'Mean new reported NIS per year in each 10-year cycle, with standard error'],
        'yearly-rate' => ['Yearly new NIS since 1990 (Fig. 3b)', 'New reported NIS per year since 1990', 'Year-to-year fluctuations hidden by the 10-year means'],
        'shared-subregions' => ['Shared and unique NIS by sub-region (Fig. 4)', 'Reported NIS unique to or shared between EcAp sub-regions', 'Reported NIS by the exact combination of sub-regions they occur in, cumulative by decade; press play or pick a decade'],
        'groups-subregions' => ['Taxa groups by sub-region (Table 2)', 'Major taxa groups of reported NIS by EcAp sub-region', 'Reported NIS per broad taxa group, with its share of the column'],
        // Per country (always follow the page's ?country=)
        'country-selector' => ['Country selector', 'Country', null],
        'country-ranking' => ['First records by country', 'First Mediterranean records by country', 'Reported NIS first recorded in the Mediterranean in each country; co-first records count in each'],
    ];

    /** Charts that are about a country whatever their scope setting. */
    public const COUNTRY_CHARTS = ['country-selector', 'country-ranking'];

    /** Chart key => minimum chart height in px; anything not listed gets DEFAULT_HEIGHT. */
    public const HEIGHTS = [
        'spread-map' => 540,
        'taxonomy-treemap' => 540,
        'taxonomy' => 460,
        'taxon-status' => 460,
        'phylum-pathways' => 460,
        'phylum-subregions' => 460,
        'pathway-cloud' => 420,
        'family-cloud' => 420,
        'shared-subregions' => 460,
        'groups-subregions' => 460,
        'country-ranking' => 460,
    ];

    public const DEFAULT_HEIGHT = 380;

    /**
     * CBD pathway subcategories as a word cloud shows them: a few words each,
     * the full CBD wording staying in the tooltip.
     */
    public const PATHWAY_WORDS = [
        '1.1' => 'Hunting & angling',
        '1.2' => 'Deliberate release',
        '1.3' => 'Pet & garden release',
        '2.1' => 'Aquaculture escapes',
        '2.2' => 'Pet & aquarium escapes',
        '2.3' => 'Research escapes',
        '2.4' => 'Transport confinement escapes',
        '3.1' => 'Shipping',
        '3.2' => 'Marine debris',
        '3.3' => 'Vehicles',
        '3.4' => 'Packing & containers',
        '3.5' => 'Tourism & leisure',
        '4.1' => 'Seed & plant material',
        '4.2' => 'Parasites on imports',
        '4.3' => 'Contaminated imports',
        '5.1' => 'Canals (Suez, Gibraltar)',
        '5.2' => 'Interbasin transfer',
        '6.1' => 'Natural spread',
        '6.2' => 'Climate-driven spread',
    ];

    public static function getType(): string
    {
        return 'mamias-chart';
    }

    public static function getLabel(): string
    {
        return 'MAMIAS chart';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    /**
     * @return list<Select|TextInput|Textarea>
     */
    public static function getContentFormSchema(): array
    {
        return [
            Select::make('chart')
                ->label('Chart')
                ->options(array_map(fn (array $chart): string => $chart[0], self::CHARTS))
                ->required()
                ->default('trend'),
            Select::make('scope')
                ->label('Scope')
                ->options(['mediterranean' => 'Whole Mediterranean', 'country' => 'Country picked on the page (?country=)'])
                ->default('mediterranean')
                ->helperText('Country scope counts the NIS first recorded in the Mediterranean in that country. {country} in the title or note becomes its name.'),
            TextInput::make('title')
                ->label('Title')
                ->placeholder('Default title for the chart'),
            Textarea::make('note')
                ->label('Note')
                ->rows(2)
                ->placeholder('Default note for the chart'),
        ];
    }

    public static function getDefaultData(): array
    {
        return ['chart' => 'trend', 'scope' => 'mediterranean', 'title' => null, 'note' => null];
    }

    public static function getPreview(array $data): string
    {
        return 'MAMIAS chart: '.(self::CHARTS[$data['chart'] ?? ''][0] ?? 'none');
    }

    public function render(): View
    {
        $chart = array_key_exists($this->data['chart'] ?? '', self::CHARTS) ? $this->data['chart'] : 'trend';
        [, $title, $note] = self::CHARTS[$chart];
        $country = in_array($chart, self::COUNTRY_CHARTS, true) || ($this->data['scope'] ?? null) === 'country' ? self::selectedCountry() : null;
        $named = fn (?string $text): ?string => $text === null ? null : str_replace('{country}', (string) $country, $text);

        return view('layup.widgets.mamias-chart', [
            'data' => $this->data,
            'chart' => $chart,
            'title' => $named(filled($this->data['title'] ?? null) ? $this->data['title'] : $title),
            'note' => $named(filled($this->data['note'] ?? null) ? $this->data['note'] : $note),
            'height' => self::HEIGHTS[$chart] ?? self::DEFAULT_HEIGHT,
            'payload' => self::payload($chart, app(MediterraneanDashboard::class), $country),
        ]);
    }

    /**
     * The country in the page's ?country= (any spelling), or the first one
     * alphabetically — the top of the selector — when it is missing or not a
     * Mediterranean country.
     */
    public static function selectedCountry(): string
    {
        // Not once(): that outlives the request in a worker and would pin one visitor's country.
        $countries = array_column(app(MediterraneanDashboard::class)->countryRanking(), 'country');
        sort($countries);
        $requested = MediterraneanDashboard::reportedCountry((string) CountrySelectWithMedPriority::canonicalName((string) request()->query('country', '')));

        return in_array($requested, $countries, true) ? $requested : $countries[0];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(string $chart, MediterraneanDashboard $dashboard, ?string $country = null): array
    {
        $patterns = app(MediterraneanNisPatterns::class)->forCountry($country);
        $dashboard = $dashboard->forCountry($country);
        $labels = collect(Subregion::cases())->mapWithKeys(fn (Subregion $subregion): array => [$subregion->value => $subregion->getLabel()])->all();

        return match ($chart) {
            'headline' => $dashboard->headline(),
            'trend' => $dashboard->trend(),
            'subregion-map' => ['values' => $dashboard->nisBySubregion(), 'labels' => $labels],
            'subregion-status' => $dashboard->statusBySubregion(),
            'pathways' => ['rows' => $dashboard->pathways()],
            'taxonomy' => ['tree' => $dashboard->taxonomy()],
            'taxon-status' => [
                ...$dashboard->statusByPhylum(),
                'kingdoms' => array_map(fn (array $kingdom): array => ['name' => $kingdom['name'], 'value' => $kingdom['value']], $dashboard->taxonomy()),
            ],
            'subregion-pathways' => $dashboard->pathwaysBySubregion(),
            'phylum-pathways' => $dashboard->pathwaysByPhylum(),
            'phylum-subregions' => $dashboard->subregionsByPhylum(),
            'taxonomy-treemap' => ['tree' => $dashboard->taxonomyTree()],
            'pathway-cloud' => ['words' => array_map(fn (array $row): array => [
                'name' => self::PATHWAY_WORDS[$row['code']] ?? $row['label'],
                'full' => $row['label'],
                'group' => (int) $row['code'],
                'value' => $row['value'],
                'url' => route('data', ['pathway' => $row['code']]),
            ], $dashboard->pathwaySubcategories())],
            'family-cloud' => ['words' => array_map(fn (array $row): array => [
                'name' => $row['name'],
                'full' => trim($row['name'].' · '.$row['phylum'], ' ·'),
                'group' => $row['kingdom'],
                'value' => $row['value'],
                'url' => route('data', ['search' => $row['name']]),
            ], $dashboard->families())],
            'spread-bars', 'spread-map' => [...$dashboard->spread(), 'names' => $labels],
            'pathway-shares' => $patterns->pathwayShares(),
            'introduction-rate' => $patterns->introductionRates(),
            'yearly-rate' => $patterns->yearlyRates(),
            'shared-subregions' => $patterns->sharedBySubregion(),
            'groups-subregions' => $patterns->groupsBySubregion(),
            'country-selector', 'country-ranking' => ['rows' => $dashboard->forCountry(null)->countryRanking(), 'selected' => $country],
            default => throw new InvalidArgumentException("Unknown MAMIAS chart [{$chart}]."),
        };
    }
}
