<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * Publishes the by-country dashboard as a Layup page, served at
 * /pages/dashboard/by-country?country=… (the navbar's Dashboard › By Country).
 *
 * The Mediterranean charts, scoped to the NIS first recorded in the
 * Mediterranean in the country picked at the top and set beside its
 * sub-regions and the basin (after Galanidi et al. 2023, Diversity 15:962).
 * MAMIAS holds no national presence records, so these are a country's
 * first records, not its national inventory; the page says so.
 */
class LayupCountryDashboardSeeder extends LayupMediterraneanDashboardSeeder
{
    public const SLUG = 'pages/dashboard/by-country';

    public const TITLE = 'Dashboard by country';

    public const DESCRIPTION = 'Non-Indigenous Species first recorded in the Mediterranean in each country: how many, since when, by which pathway, in which groups and where they spread next.';

    /**
     * @return list<array<string, mixed>>
     */
    protected static function rows(): array
    {
        $country = fn (string $chart, ?string $title = null, ?string $note = null): array => ['mamias-chart', array_filter(['chart' => $chart, 'scope' => 'country', 'title' => $title, 'note' => $note])];

        return [
            self::row('hero', [['html', ['content' => self::heroHtml()]]]),
            self::row('selector', [['mamias-chart', ['chart' => 'country-selector']]]),
            self::row('headline', [$country('headline', 'First Mediterranean records in {country}')]),
            self::row('ranking', [['mamias-chart', ['chart' => 'country-ranking']]]),

            self::row('when_heading', [['html', ['content' => self::sectionHeading('Since when', 'First Mediterranean records in the chosen country over time, against its sub-regions and the whole basin.')]]]),
            self::row('trend', [$country('trend', 'NIS first recorded in {country}, by decade', 'Bars: new first records per decade · line: running total')]),
            self::row('rates', [
                $country('introduction-rate', 'Annual rate of first records: {country} vs its sub-regions and the Mediterranean'),
                $country('yearly-rate', 'First records per year since 1990: {country} vs its sub-regions and the Mediterranean'),
            ]),

            self::row('how_heading', [['html', ['content' => self::sectionHeading('How and what', 'Pathways and taxa of the NIS first recorded in the chosen country.')]]]),
            self::row('pathway_shares', [$country('pathway-shares', 'Primary pathways: {country} vs its sub-regions and the Mediterranean')]),
            self::row('composition', [
                $country('pathways', 'Introduction pathways of NIS first recorded in {country} (CBD)'),
                $country('taxonomy', 'Taxonomic composition of NIS first recorded in {country}'),
            ]),
            self::row('groups', [$country('groups-subregions', 'Major taxa groups: {country} vs its sub-regions and the Mediterranean')]),
            self::row('taxon_status', [$country('taxon-status', 'Establishment status of NIS first recorded in {country}, by phylum')]),

            self::row('where_heading', [['html', ['content' => self::sectionHeading('Where next', 'Where the NIS that entered the Mediterranean in the chosen country occur today.')]]]),
            self::row('shared', [$country('shared-subregions', 'Sub-regions now reached by the NIS first recorded in {country}', 'NIS first recorded in {country}, by the exact combination of EcAp sub-regions they occur in, cumulative by decade')]),
        ];
    }

    private static function heroHtml(): string
    {
        return <<<'HTML'
<section class="py-14 rounded-xl" style="background: linear-gradient(135deg, #003d61 0%, #005f98 50%, #018d9a 100%);">
    <div class="kt-container-fixed text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">Dashboard by country</h1>
        <p class="text-base text-white/75 max-w-2xl mx-auto leading-relaxed">
            Non-Indigenous Species first recorded in the Mediterranean in each country: how many, since when, how they arrived, which groups they belong to and where they spread next. Figures update as the MAMIAS database does.
        </p>
    </div>
</section>
HTML;
    }
}
