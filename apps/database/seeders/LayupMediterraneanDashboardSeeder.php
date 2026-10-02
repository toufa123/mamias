<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes the public Mediterranean dashboard as a Layup page, served at
 * /pages/dashboard/mediterranean (the navbar's Dashboard › Mediterranean).
 *
 * Every graphic is a `mamias-chart` widget (App\Layup\Widgets\MamiasChartWidget)
 * holding only its chart key, so the figures are read live on each request and
 * editors can reorder, retitle or drop charts in the page builder.
 */
class LayupMediterraneanDashboardSeeder extends Seeder
{
    public const SLUG = 'pages/dashboard/mediterranean';

    public const TITLE = 'Mediterranean dashboard';

    public const DESCRIPTION = 'Reported Non-Indigenous Species across the Mediterranean: how many, since when, where, by which pathway and in which groups.';

    public function run(): void
    {
        // withTrashed: Layup pages soft-delete, and a trashed page still holds its path.
        Page::withTrashed()->updateOrCreate(
            ['slug' => static::SLUG],
            [
                'deleted_at' => null,
                'title' => static::TITLE,
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => static::DESCRIPTION,
                ],
                'content' => ['rows' => static::rows()],
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function rows(): array
    {
        return [
            self::row('hero', [['html', ['content' => self::heroHtml()]]]),
            self::row('headline', [['mamias-chart', ['chart' => 'headline']]]),

            // Mediterranean-wide first…
            self::row('basin_heading', [['html', ['content' => self::sectionHeading('Mediterranean-wide', 'Reported NIS across the whole basin.')]]]),
            self::row('trend', [['mamias-chart', ['chart' => 'trend']]]),
            self::row('rates', [['mamias-chart', ['chart' => 'introduction-rate']], ['mamias-chart', ['chart' => 'yearly-rate']]]),
            self::row('composition', [['mamias-chart', ['chart' => 'pathways']], ['mamias-chart', ['chart' => 'taxonomy']]]),
            self::row('taxon_status', [['mamias-chart', ['chart' => 'taxon-status']]]),
            self::row('phylum_pathways', [['mamias-chart', ['chart' => 'phylum-pathways']]]),
            self::row('taxonomy_treemap', [['mamias-chart', ['chart' => 'taxonomy-treemap']]]),

            // …then by EcAp sub-region.
            self::row('subregion_heading', [['html', ['content' => self::sectionHeading('By EcAp sub-region', 'Reported NIS in the Western, Central, Adriatic and Eastern Mediterranean.')]]]),
            self::row('subregions', [['mamias-chart', ['chart' => 'spread-bars']], ['mamias-chart', ['chart' => 'subregion-status']]]),
            self::row('pathway_shares', [['mamias-chart', ['chart' => 'pathway-shares']]]),
            self::row('subregion_pathways', [['mamias-chart', ['chart' => 'subregion-pathways']], ['mamias-chart', ['chart' => 'phylum-subregions']]]),
            self::row('groups_subregions', [['mamias-chart', ['chart' => 'groups-subregions']]]),
            self::row('shared_subregions', [['mamias-chart', ['chart' => 'shared-subregions']]]),
            self::row('spread', [['mamias-chart', ['chart' => 'spread-map']]]),
        ];
    }

    /**
     * One row of equal columns, one widget each: full width alone, halves
     * from lg up when paired. Grid layout, not Layup's default flex: flex
     * columns size with `w-full lg:w-6/12`, and the theme's precompiled
     * assets/css/styles.css re-declares `.w-full` after app.css, which wins
     * and keeps every column full width. The grid's col-span classes have no
     * such twin.
     *
     * @param  list<array{0: string, 1: array<string, mixed>}>  $widgets  [type, data] per column
     * @return array<string, mixed>
     */
    protected static function row(string $key, array $widgets): array
    {
        $span = 12 / count($widgets);

        return [
            'id' => "row_med_{$key}",
            'settings' => ['layout' => 'grid', 'gap' => 'gap-6', 'margin' => ['bottom' => 1.5, 'unit' => 'rem']],
            'columns' => array_map(fn (int $index, array $widget): array => [
                'id' => "col_med_{$key}_{$index}",
                'span' => ['sm' => 12, 'md' => 12, 'lg' => $span, 'xl' => $span],
                'settings' => [],
                'widgets' => [[
                    'id' => "widget_med_{$key}_{$index}",
                    'type' => $widget[0],
                    'data' => $widget[1],
                ]],
            ], array_keys($widgets), $widgets),
        ];
    }

    private static function heroHtml(): string
    {
        return <<<'HTML'
<section class="py-14 rounded-xl" style="background: linear-gradient(135deg, #003d61 0%, #005f98 50%, #018d9a 100%);">
    <div class="kt-container-fixed text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">Mediterranean dashboard</h1>
        <p class="text-base text-white/75 max-w-2xl mx-auto leading-relaxed">
            Reported Non-Indigenous Species across the whole basin: how many, since when, where they occur, how they arrived and which groups they belong to. Figures update as the MAMIAS database does.
        </p>
    </div>
</section>
HTML;
    }

    protected static function sectionHeading(string $title, string $lede): string
    {
        return <<<HTML
<div class="border-t border-border pt-6">
    <h2 class="text-mono text-2xl font-bold">{$title}</h2>
    <p class="text-secondary-foreground mt-1">{$lede}</p>
</div>
HTML;
    }
}
