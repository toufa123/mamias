<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes the MAMIAS "About" page as a Layup page.
 *
 * Mirrors resources/views/mamias/about.blade.php:
 * Every section — hero, mission & timeline, features, the EcAp/IMAP
 * section and sources — is static markup stored in `html` widgets, so the whole
 * page is editable from the page builder and no bespoke widget class exists for it.
 *
 * Served at /about via the named route in routes/web.php (slug 'about').
 */
class LayupAboutPageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About MAMIAS',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => 'Marine Mediterranean Invasive Alien Species — a science-driven platform for monitoring, reporting, and analysing Non-Indigenous Species data across the Mediterranean.',
                ],
                'content' => [
                    'rows' => [
                        self::htmlRow('row_hero', 'col_hero', 'widget_about_hero', self::heroHtml()),
                        self::htmlRow('row_mission', 'col_mission', 'widget_about_mission', self::missionHtml()),
                        // Moved here from the home page, as what the mission delivers.
                        self::htmlRow('row_features', 'col_features', 'widget_features', self::featuresHtml()),
                        self::htmlRow('row_imap', 'col_imap', 'widget_about_imap', self::imapHtml()),
                        self::htmlRow('row_network', 'col_network', 'widget_about_network', self::networkHtml()),
                        self::htmlRow('row_sources', 'col_sources', 'widget_about_sources', self::sourcesHtml()),
                    ],
                ],
            ]
        );
    }

    /**
     * Build a single full-width row holding one html widget.
     *
     * @return array<string, mixed>
     */
    private static function htmlRow(string $rowId, string $colId, string $widgetId, string $html): array
    {
        return [
            'id' => $rowId,
            'settings' => ['gap' => 'gap-0'],
            'columns' => [
                [
                    'id' => $colId,
                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                    'settings' => [],
                    'widgets' => [
                        [
                            'id' => $widgetId,
                            'type' => 'html',
                            'data' => ['content' => $html],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function heroHtml(): string
    {
        return <<<'HTML'
<section class="py-20" style="background: linear-gradient(135deg, #003d61 0%, #005f98 50%, #018d9a 100%);">
    <div class="kt-container-fixed text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">About MAMIAS</h1>
        <p class="text-lg text-white/70 max-w-2xl mx-auto leading-relaxed">
            Marine Mediterranean Invasive Alien Species — a science-driven platform for monitoring, reporting, and analysing Non-Indigenous Species data across the Mediterranean.
        </p>
    </div>
</section>
HTML;
    }

    private static function missionHtml(): string
    {
        return <<<'HTML'
<section class="py-8">
    <div class="kt-container-fixed">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div>
                <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 px-4 py-1.5 mb-4">Our Mission</span>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">A shared knowledge base on non-indigenous species in the Mediterranean</h2>
                <p class="text-gray-500 leading-relaxed mb-4">
                    MAMIAS is the regional database of marine Non-Indigenous Species (NIS) in the Mediterranean, developed and coordinated by SPA/RAC, the Specially Protected Areas Regional Activity Centre of UNEP/MAP – Barcelona Convention. Since 2012 it has brought together validated records of which species have arrived, where, when and by which pathway.
                </p>
                <p class="text-gray-500 leading-relaxed mb-4">
                    Its mission is to give the Contracting Parties to the Barcelona Convention, their national focal points, scientists and decision-makers a common evidence base: to detect new arrivals early, follow how species spread, and measure progress towards Good Environmental Status.
                </p>
                <p class="text-gray-500 leading-relaxed mb-4">
                    In doing so, MAMIAS supports the commitments of the Mediterranean countries on non-indigenous species:
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="/pages/spa-bd-protocol" class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 px-4 py-1.5">SPA/BD Protocol · Article 13</a>
                    <a href="/pages/mediterranean-action-plan" class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 px-4 py-1.5">NIS Action Plan</a>
                    <a href="/pages/imap" class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 px-4 py-1.5">IMAP · Common Indicator 6</a>
                    <a href="/pages/post-2020-sapbio" class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 px-4 py-1.5">Post-2020 SAPBIO</a>
                </div>
            </div>
            <div>
                <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 px-4 py-1.5 mb-4">Our Journey</span>
                <div class="space-y-8">
                    <div class="relative pl-10 border-l-2 border-[#4cafbf]">
                        <div class="absolute left-0 top-0 -translate-x-1/2 size-4 rounded-full bg-[#4cafbf] border-2 border-white"></div>
                        <span class="text-sm font-bold text-[#018d9a]">2012</span>
                        <h3 class="text-lg font-semibold text-gray-900 mt-1">Launch of MAMIAS</h3>
                        <p class="text-gray-500 text-sm leading-relaxed mt-1">Establishment of the Mediterranean platform for monitoring Non-Indigenous Species, coordinated by SPA/RAC.</p>
                    </div>
                    <div class="relative pl-10 border-l-2 border-[#4cafbf]">
                        <div class="absolute left-0 top-0 -translate-x-1/2 size-4 rounded-full bg-[#4cafbf] border-2 border-white"></div>
                        <span class="text-sm font-bold text-[#018d9a]">2016</span>
                        <h3 class="text-lg font-semibold text-gray-900 mt-1">Validated Inventories of Non-Indigenous Species (NIS) for the Mediterranean Sea as Tools for Regional Policy and Patterns of NIS Spread</h3>
                        <p class="text-gray-500 text-sm leading-relaxed mt-1">MAMIAS also becomes a data partner of EASIN, the European Alien Species Information Network.</p>
                    </div>
                    <div class="relative pl-10 border-l-2 border-[#4cafbf]">
                        <div class="absolute left-0 top-0 -translate-x-1/2 size-4 rounded-full bg-[#4cafbf] border-2 border-white"></div>
                        <span class="text-sm font-bold text-[#018d9a]">2026</span>
                        <h3 class="text-lg font-semibold text-gray-900 mt-1">Platform Redesign</h3>
                        <p class="text-gray-500 text-sm leading-relaxed mt-1">Modern user interface, enhanced data visualisation, and improved reporting tools for researchers and decision-makers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    /**
     * What the platform offers, grouped by what a visitor wants to do:
     * explore (public), contribute (registered) and trust (how the data are
     * kept reliable). Every row links to the real page; no figures, so nothing
     * here competes with the MED QSR as the source for NIS numbers.
     */
    private static function featuresHtml(): string
    {
        return <<<'HTML'
<style>
.mamias-feat {
    --ft-ink: var(--foreground);
    --ft-ink-2: #47606b;
    --ft-muted: var(--muted-foreground);
    --ft-line: var(--border);
    --ft-line-soft: var(--secondary);
    --ft-surface: var(--card);
    --ft-tint: var(--accent);
    --ft-teal-text: var(--mamias-teal-600);
    --ft-teal-deco: var(--mamias-teal-500);
    --ft-teal-fill: var(--mamias-teal-600);
    --ft-on-teal: #fff;
    color: var(--ft-ink); font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-feat {
    --ft-ink-2: #b3c7ce;
    --ft-teal-text: var(--mamias-teal-300);
    --ft-teal-deco: var(--mamias-teal-400);
    --ft-teal-fill: var(--mamias-teal-400);
    --ft-on-teal: var(--background);
}
.mamias-feat * { box-sizing: border-box; }
.mamias-feat .ft-head { display: grid; gap: 12px; justify-items: center; text-align: center; margin-bottom: 40px; }
.mamias-feat .ft-head p { color: var(--ft-ink-2); max-width: 44rem; }
.mamias-feat .ft-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--ft-muted); }
.mamias-feat .ft-cols { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1px; background: var(--ft-line); border: 1px solid var(--ft-line); }
.mamias-feat .ft-col { background: var(--ft-surface); display: grid; grid-template-rows: auto 1fr; align-content: start; }
.mamias-feat .ft-ch { padding: 18px 20px; display: grid; gap: 4px; border-bottom: 1px solid var(--ft-line); position: relative; }
.mamias-feat .ft-ch .k { font: 400 12px/18px var(--font-mono); color: var(--ft-teal-text); }
.mamias-feat .ft-ch h3 { margin: 0; font-size: 18px; line-height: 26px; font-weight: 500; color: var(--ft-ink); }
.mamias-feat .ft-ch p { margin: 0; font-size: 14px; line-height: 22px; color: var(--ft-ink-2); }
.mamias-feat .ft-col.c1 .ft-ch { border-top: 3px solid var(--ft-teal-deco); }
.mamias-feat .ft-col.c2 .ft-ch { border-top: 3px solid var(--ft-teal-fill); }
.mamias-feat .ft-col.c3 .ft-ch { background: var(--ft-teal-fill); border-top: 3px solid var(--ft-teal-fill); }
.mamias-feat .ft-col.c3 .ft-ch .k, .mamias-feat .ft-col.c3 .ft-ch h3, .mamias-feat .ft-col.c3 .ft-ch p { color: var(--ft-on-teal); }
.mamias-feat .ft-list { margin: 0; padding: 0; list-style: none; display: grid; align-content: start; }
.mamias-feat .ft-row { display: grid; grid-template-columns: 36px minmax(0, 1fr) auto; gap: 12px; align-items: start; padding: 14px 20px; border-bottom: 1px solid var(--ft-line-soft); color: inherit; text-decoration: none; }
.mamias-feat .ft-list li:last-child .ft-row { border-bottom: 0; }
.mamias-feat a.ft-row:hover { background: var(--ft-tint); }
.mamias-feat a.ft-row:focus-visible { outline: 2px solid var(--ft-teal-deco); outline-offset: -2px; }
.mamias-feat .ft-ic { width: 36px; height: 36px; display: grid; place-items: center; border: 1px solid var(--ft-teal-deco); color: var(--ft-teal-text); font-size: 18px; }
.mamias-feat .ft-row b { display: block; font-weight: 500; font-size: 15px; line-height: 22px; color: var(--ft-ink); }
.mamias-feat .ft-row span.d { display: block; font-size: 13px; line-height: 20px; color: var(--ft-ink-2); margin-top: 2px; }
.mamias-feat .ft-go { font-size: 14px; color: var(--ft-teal-text); padding-top: 4px; }
.mamias-feat .ft-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 8px 16px; margin-top: 24px; font-size: 14px; color: var(--ft-ink-2); }
.mamias-feat .ft-foot a { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; border: 1px solid var(--ft-line); color: var(--ft-teal-text); text-decoration: none; font-weight: 500; }
.mamias-feat .ft-foot a:hover { border-color: var(--ft-teal-deco); }
@media (max-width: 1000px) { .mamias-feat .ft-cols { grid-template-columns: minmax(0, 1fr); } }
</style>
<section id="features" class="py-8 mamias-feat">
    <div class="kt-container-fixed">
        <div class="ft-head">
            <span class="ft-eyebrow">What MAMIAS offers</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Explore, contribute, trust</h2>
            <p>Anyone can explore the Mediterranean's non-indigenous species records. Registered scientists can add to them. Every record is checked before it is published, so the same data can serve research and regional policy.</p>
        </div>
        <div class="ft-cols">
            <div class="ft-col c1">
                <div class="ft-ch"><span class="k">1 · Open to everyone</span><h3>Explore the data</h3><p>No account needed.</p></div>
                <ul class="ft-list">
                    <li><a class="ft-row" href="/pages/data"><span class="ft-ic"><i class="ki-filled ki-data"></i></span><span><b>NIS data table</b><span class="d">Search and filter records by species, country, pathway and status</span></span><span class="ft-go">→</span></a></li>
                    <li><a class="ft-row" href="/pages/map"><span class="ft-ic"><i class="ki-filled ki-map"></i></span><span><b>Interactive map</b><span class="d">See where each species has been recorded</span></span><span class="ft-go">→</span></a></li>
                    <li><a class="ft-row" href="/pages/data"><span class="ft-ic"><i class="ki-filled ki-magnifier"></i></span><span><b>Species pages</b><span class="d">Records, pathway, establishment status and references for each species</span></span><span class="ft-go">→</span></a></li>
                    <li><a class="ft-row" href="/pages/dashboard/mediterranean"><span class="ft-ic"><i class="ki-filled ki-chart-line-up"></i></span><span><b>Dashboards</b><span class="d">Trends, pathways and groups for the whole basin and <span style="white-space: nowrap">by country</span></span></span><span class="ft-go">→</span></a></li>
                </ul>
            </div>
            <div class="ft-col c2">
                <div class="ft-ch"><span class="k">2 · For registered users</span><h3>Contribute</h3><p>Sign in to add what you know.</p></div>
                <ul class="ft-list">
                    <li><a class="ft-row" href="/my-species-reports"><span class="ft-ic"><i class="ki-filled ki-plus-squared"></i></span><span><b>Report a species</b><span class="d">Submit a new sighting with its location, date and supporting evidence</span></span><span class="ft-go">→</span></a></li>
                    <li><a class="ft-row" href="/references"><span class="ft-ic"><i class="ki-filled ki-book-open"></i></span><span><b>Add references</b><span class="d">Link the publications behind the records</span></span><span class="ft-go">→</span></a></li>
                    <li><a class="ft-row" href="/my-suggestions"><span class="ft-ic"><i class="ki-filled ki-message-edit"></i></span><span><b>Suggest corrections</b><span class="d">Propose changes to existing records for expert review</span></span><span class="ft-go">→</span></a></li>
                </ul>
            </div>
            <div class="ft-col c3">
                <div class="ft-ch"><span class="k">3 · How the data are kept reliable</span><h3>Trust the data</h3><p>Built for science and policy.</p></div>
                <ul class="ft-list">
                    <li><div class="ft-row"><span class="ft-ic"><i class="ki-filled ki-verify"></i></span><span><b>Names checked against WoRMS</b><span class="d">Species names matched to the World Register of Marine Species</span></span><span></span></div></li>
                    <li><div class="ft-row"><span class="ft-ic"><i class="ki-filled ki-shield-tick"></i></span><span><b>Expert review</b><span class="d">Submitted records and references are reviewed before they are published</span></span><span></span></div></li>
                    <li><a class="ft-row" href="/terms-of-use"><span class="ft-ic"><i class="ki-filled ki-document"></i></span><span><b>Open licence</b><span class="d">Data reusable under CC BY 4.0, with attribution</span></span><span class="ft-go">→</span></a></li>
                    <li><a class="ft-row" href="#imap"><span class="ft-ic"><i class="ki-filled ki-abstract-26"></i></span><span><b>Used for regional policy</b><span class="d">Supports IMAP Common Indicator 6 and the MED QSR, and shares data with EASIN as a data partner</span></span><span class="ft-go">↓</span></a></li>
                </ul>
            </div>
        </div>
        <div class="ft-foot">
            <span>New to the platform?</span>
            <a href="/pages/manual"><i class="ki-filled ki-book"></i>Read the user manual</a>
        </div>
    </div>
</section>
HTML;
    }

    /**
     * How MAMIAS feeds the Ecosystem Approach, IMAP and the MED QSR, as set out
     * in Decision IG.27/6 (COP 24, 2025). Scoped under `.mamias-rel` and built on
     * the site tokens, like the Resources pages.
     */
    private static function imapHtml(): string
    {
        return <<<'HTML'
<style>
.mamias-rel {
    --rl-ink: var(--foreground);
    --rl-ink-2: #47606b;
    --rl-muted: var(--muted-foreground);
    --rl-line: var(--border);
    --rl-line-soft: var(--secondary);
    --rl-surface: var(--card);
    --rl-sunken: var(--muted);
    --rl-tint: var(--accent);
    --rl-teal-text: var(--mamias-teal-600);
    --rl-teal-deco: var(--mamias-teal-500);
    --rl-teal-fill: var(--mamias-teal-600);
    --rl-on-teal: #fff;
    --rl-navy: #2a2b74;
    color: var(--rl-ink); font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-rel {
    --rl-ink-2: #b3c7ce;
    --rl-teal-text: var(--mamias-teal-300);
    --rl-teal-deco: var(--mamias-teal-400);
    --rl-teal-fill: var(--mamias-teal-400);
    --rl-on-teal: var(--background);
    --rl-navy: #8f91d9;
}
.mamias-rel * { box-sizing: border-box; }
.mamias-rel .rl-wrap { display: grid; gap: 48px; }
.mamias-rel h2, .mamias-rel h3 { margin: 0; color: var(--rl-ink); text-wrap: balance; }
.mamias-rel h3 { font-size: 16px; line-height: 24px; font-weight: 500; }
.mamias-rel p { margin: 0; }
.mamias-rel ol, .mamias-rel ul { margin: 0; padding: 0; list-style: none; }
.mamias-rel .rl-stack { display: grid; gap: 16px; }
.mamias-rel .rl-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--rl-muted); }
.mamias-rel .rl-lede { color: var(--rl-ink-2); }
.mamias-rel .rl-small { font-size: 14px; line-height: 22px; color: var(--rl-ink-2); }
.mamias-rel .rl-src { font-size: 13px; line-height: 20px; color: var(--rl-muted); }
.mamias-rel .rl-k { font: 400 12px/18px var(--font-mono); color: var(--rl-teal-text); }
.mamias-rel figure { margin: 0; display: grid; gap: 12px; }
.mamias-rel figcaption { font-size: 14px; line-height: 22px; color: var(--rl-muted); }
.mamias-rel a { color: var(--rl-teal-text); text-underline-offset: 2px; }

/* EcAp cycle: six steps, MAMIAS plugs into step IV. */
.mamias-rel .rl-cycle { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 1px; background: var(--rl-line); border: 1px solid var(--rl-line); counter-reset: s; }
.mamias-rel .rl-cycle li { background: var(--rl-surface); padding: 14px; display: grid; gap: 4px; align-content: start; position: relative; }
.mamias-rel .rl-cycle .n { font: 400 12px/18px var(--font-mono); color: var(--rl-muted); }
.mamias-rel .rl-cycle b { font-weight: 500; font-size: 14px; line-height: 20px; }
.mamias-rel .rl-cycle span.d { font-size: 12px; line-height: 18px; color: var(--rl-ink-2); }
.mamias-rel .rl-cycle li.on { background: var(--rl-tint); border-top: 3px solid var(--rl-teal-deco); padding-top: 11px; }
.mamias-rel .rl-cycle li.on .n { color: var(--rl-teal-text); }
.mamias-rel .rl-cycle li.qsr { background: var(--rl-teal-fill); }
.mamias-rel .rl-cycle li.qsr b, .mamias-rel .rl-cycle li.qsr span, .mamias-rel .rl-cycle li.qsr .n { color: var(--rl-on-teal); }
.mamias-rel .rl-plug { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); }
.mamias-rel .rl-plug div { grid-column: 4 / span 2; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 10px; align-items: center; padding: 10px 14px; border: 1px solid var(--rl-teal-deco); border-top: 0; background: var(--rl-surface); font-size: 13px; line-height: 20px; color: var(--rl-ink-2); }
.mamias-rel .rl-plug b { font: 500 11px/16px var(--font-sans); letter-spacing: .1em; text-transform: uppercase; padding: 2px 8px; background: var(--rl-teal-fill); color: var(--rl-on-teal); }
.mamias-rel .rl-back { font: 400 12px/18px var(--font-mono); color: var(--rl-muted); text-align: right; }
@media (max-width: 900px) {
    .mamias-rel .rl-cycle { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .mamias-rel .rl-plug { grid-template-columns: minmax(0, 1fr); }
    .mamias-rel .rl-plug div { grid-column: 1; border-top: 1px solid var(--rl-teal-deco); margin-top: 8px; }
}
@media (max-width: 520px) { .mamias-rel .rl-cycle { grid-template-columns: minmax(0, 1fr); } }

/* Data flow: lanes from field to policy. */
.mamias-rel .rl-flow { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0; }
.mamias-rel .rl-node { position: relative; display: grid; gap: 6px; align-content: start; padding: 14px; border: 1px solid var(--rl-line); background: var(--rl-surface); margin-right: 28px; }
.mamias-rel .rl-node:last-child { margin-right: 0; }
.mamias-rel .rl-node:not(:last-child)::after { content: ""; position: absolute; right: -24px; top: 50%; width: 20px; height: 2px; background: var(--rl-teal-deco); }
.mamias-rel .rl-node:not(:last-child)::before { content: ""; position: absolute; right: -26px; top: calc(50% - 5px); border: 6px solid transparent; border-left: 7px solid var(--rl-teal-deco); border-right: 0; }
.mamias-rel .rl-node svg { width: 32px; height: 32px; }
.mamias-rel .rl-node b { font-weight: 500; font-size: 14px; line-height: 20px; }
.mamias-rel .rl-node span { font-size: 12px; line-height: 18px; color: var(--rl-ink-2); }
.mamias-rel .rl-node.mm { background: var(--rl-teal-fill); border-color: var(--rl-teal-fill); }
.mamias-rel .rl-node.mm b, .mamias-rel .rl-node.mm span { color: var(--rl-on-teal); }
.mamias-rel .rl-node.qs { border-color: var(--rl-navy); }
@media (max-width: 900px) {
    .mamias-rel .rl-flow { grid-template-columns: minmax(0, 1fr); gap: 26px; }
    .mamias-rel .rl-node { margin-right: 0; grid-template-columns: 32px minmax(0, 1fr); column-gap: 12px; }
    .mamias-rel .rl-node svg { grid-row: span 2; }
    .mamias-rel .rl-node:not(:last-child)::after { right: auto; left: 50%; top: auto; bottom: -22px; width: 2px; height: 18px; }
    .mamias-rel .rl-node:not(:last-child)::before { right: auto; left: calc(50% - 5px); top: auto; bottom: -26px; border: 6px solid transparent; border-top: 7px solid var(--rl-teal-deco); border-bottom: 0; }
}

/* Roles: what the decision asks of MAMIAS-type databases. */
.mamias-rel .rl-roles { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr)); gap: 1px; background: var(--rl-line); border: 1px solid var(--rl-line); }
.mamias-rel .rl-role { background: var(--rl-surface); padding: 16px; display: grid; gap: 6px; align-content: start; }
.mamias-rel .rl-role p { font-size: 14px; line-height: 22px; color: var(--rl-ink-2); }
.mamias-rel blockquote { margin: 0; padding: 12px 16px; border-left: 2px solid var(--rl-teal-deco); background: var(--rl-sunken); font-size: 15px; line-height: 24px; }
.mamias-rel blockquote cite { display: block; margin-top: 6px; font: 400 12px/18px var(--font-mono); font-style: normal; color: var(--rl-muted); }

/* 8-year cycle bar and MED QSR figures */
.mamias-rel .rl-bar { display: grid; grid-template-columns: 6fr 2fr; border: 1px solid var(--rl-line); }
.mamias-rel .rl-bar div { padding: 12px 16px; display: grid; gap: 2px; }
.mamias-rel .rl-bar div:first-child { background: var(--rl-tint); }
.mamias-rel .rl-bar div + div { border-left: 1px solid var(--rl-line); background: var(--rl-teal-fill); }
.mamias-rel .rl-bar div + div b, .mamias-rel .rl-bar div + div span { color: var(--rl-on-teal); }
.mamias-rel .rl-bar b { font: 400 20px/28px var(--font-mono); color: var(--rl-teal-text); }
.mamias-rel .rl-bar span { font-size: 13px; line-height: 20px; color: var(--rl-ink-2); }
.mamias-rel .rl-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr)); gap: 1px; background: var(--rl-line); border: 1px solid var(--rl-line); }
.mamias-rel .rl-fact { background: var(--rl-surface); padding: 14px 16px; display: grid; gap: 2px; }
.mamias-rel .rl-fact b { font: 400 24px/32px var(--font-mono); letter-spacing: -.5px; }
.mamias-rel .rl-fact span { font-size: 14px; line-height: 22px; color: var(--rl-ink-2); }
@media (max-width: 520px) { .mamias-rel .rl-bar { grid-template-columns: minmax(0, 1fr); } .mamias-rel .rl-bar div + div { border-left: 0; border-top: 1px solid var(--rl-line); } }
</style>
<section id="imap" class="py-8 mamias-rel">
    <div class="kt-container-fixed rl-wrap">

        <div class="rl-stack">
            <span class="rl-eyebrow">MAMIAS, the Ecosystem Approach and IMAP</span>
            <h2 class="text-3xl font-bold">Where MAMIAS fits in the Barcelona Convention's assessment of the sea</h2>
            <p class="rl-lede">The Contracting Parties to the Barcelona Convention judge the health of the Mediterranean through the Ecosystem Approach (EcAp). Its monitoring arm, the Integrated Monitoring and Assessment Programme (IMAP), was renewed by Decision IG.27/6 at COP 24 (Cairo, December 2025) for a third cycle running from 2026 to 2035. For non-indigenous species, that decision names MAMIAS as one of the regional databases countries build on.</p>
        </div>

        <figure class="rl-stack">
            <span class="rl-eyebrow">Diagram 1 · The EcAp cycle</span>
            <div>
                <ol class="rl-cycle" aria-label="The six steps of the Ecosystem Approach">
                    <li><span class="n">Step I</span><b>Ecological vision</b><span class="d">A healthy, clean, resilient Mediterranean</span></li>
                    <li><span class="n">Step II</span><b>Strategic goals</b><span class="d">Protect ecosystems, cut pollution, reduce risks</span></li>
                    <li><span class="n">Step III</span><b>11 objectives and their indicators</b><span class="d">Non-indigenous species are EO2, Common Indicator 6</span></li>
                    <li class="on"><span class="n">Step IV</span><b>National IMAP monitoring</b><span class="d">Countries monitor and report data</span></li>
                    <li class="qsr"><span class="n">Step V</span><b>MED QSR</b><span class="d">Regional assessment of Good Environmental Status</span></li>
                    <li><span class="n">Step VI</span><b>Measures</b><span class="d">National Action Plans and Programmes of Measures</span></li>
                </ol>
                <div class="rl-plug"><div><b>MAMIAS</b>Regional database used to set the list of invasive species each country monitors in Step IV</div></div>
            </div>
            <p class="rl-back">Step VI feeds back into Step III: targets are updated as results come in</p>
        </figure>

        <figure class="rl-stack">
            <span class="rl-eyebrow">Diagram 2 · From a survey to a policy decision</span>
            <h3>How information on non-indigenous species travels</h3>
            <div class="rl-flow" role="list" aria-label="Flow of NIS information">
                <div class="rl-node" role="listitem">
                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M3 22 q4 -4 8 0 t8 0 t8 0" fill="none" stroke="var(--rl-teal-deco)" stroke-width="2"/><circle cx="16" cy="11" r="6" fill="none" stroke="var(--rl-teal-deco)" stroke-width="2"/><path d="M20 15 l5 5" stroke="var(--rl-teal-deco)" stroke-width="2" stroke-linecap="round"/></svg>
                    <b>Field surveys at hot spots</b>
                    <span>Rapid Assessment Surveys at least yearly in ports, marinas, aquaculture sites; eDNA where possible</span>
                </div>
                <div class="rl-node mm" role="listitem">
                    <svg viewBox="0 0 32 32" aria-hidden="true"><ellipse cx="16" cy="8" rx="10" ry="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 8 V24 c0 2 4.5 4 10 4 s10 -2 10 -4 V8 M6 16 c0 2 4.5 4 10 4 s10 -2 10 -4" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    <b>MAMIAS and regional inventories</b>
                    <span>Validated regional knowledge used to update each country's list of invasive species to monitor</span>
                </div>
                <div class="rl-node" role="listitem">
                    <svg viewBox="0 0 32 32" aria-hidden="true"><rect x="4" y="6" width="24" height="20" fill="none" stroke="var(--rl-teal-deco)" stroke-width="2"/><path d="M4 12 H28 M10 18 H22 M10 22 H18" stroke="var(--rl-teal-deco)" stroke-width="2"/></svg>
                    <b>IMAP Info System</b>
                    <span>Quality-assured national data for 18 common indicators, CI6 among them, through 30 information standards</span>
                </div>
                <div class="rl-node qs" role="listitem">
                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M6 4 H21 L26 9 V28 H6 Z" fill="none" stroke="var(--rl-navy)" stroke-width="2" stroke-linejoin="round"/><path d="M10 22 V18 M15 22 V14 M20 22 V16" stroke="var(--rl-navy)" stroke-width="2"/></svg>
                    <b>MED QSR</b>
                    <span>Regional assessment of Good Environmental Status, built on the common indicators</span>
                </div>
                <div class="rl-node" role="listitem">
                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 4 V28 M8 28 H24 M6 9 H26" stroke="var(--rl-teal-deco)" stroke-width="2"/><path d="M6 9 L2 18 h8 Z M26 9 L22 18 h8 Z" fill="none" stroke="var(--rl-teal-deco)" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    <b>COP decisions and measures</b>
                    <span>National Action Plans, Programmes of Measures and the NIS Action Plan</span>
                </div>
            </div>
            <figcaption>Where data are missing, the assessment products may also build on scientific projects, comparable data from other regional organisations and the scientific literature.</figcaption>
        </figure>

        <div class="rl-stack">
            <span class="rl-eyebrow">What the decision says</span>
            <div class="rl-roles">
                <div class="rl-role"><span class="rl-k">Annex II · paragraph 66</span><h3>A starting list for every IMAP phase</h3><p>At the start of each new phase, each Contracting Party updates the list of invasive species in its national monitoring programme, based on regional databases and inventories, and starts collecting data on them.</p></div>
                <div class="rl-role"><span class="rl-k">Annex II · paragraphs 63–65</span><h3>Trend and risk-based monitoring</h3><p>NIS monitoring is trend monitoring focused on introduction hot spots, so that a small number of stations gives a picture of the whole region. Long, standardised datasets are the first requirement.</p></div>
                <div class="rl-role"><span class="rl-k">Annex I · Step V</span><h3>The MED QSR rests on quality-assured data</h3><p>The MED QSR uses nationally sourced, quality-assured data reported through the IMAP Info System or other reliable sources, and judges each area as in or out of Good Environmental Status.</p></div>
            </div>
            <blockquote>“…the Marine Mediterranean Invasive Alien Species database, (MAMIAS), the “Andromeda” invasive species database…”<cite>Decision IG.27/6, Annex II, paragraph 66</cite></blockquote>
        </div>

        <div class="rl-stack">
            <span class="rl-eyebrow">Infographic · The assessment rhythm</span>
            <h3>An eight-year cycle behind each MED QSR</h3>
            <div class="rl-bar" role="img" aria-label="Eight-year cycle: six years of data generation and assessment, then two years of report preparation and review.">
                <div><b>6 years</b><span>Data generation and assessment</span></div>
                <div><b>2 years</b><span>Report preparation and review</span></div>
            </div>
            <p class="rl-small">Two MED QSRs have been issued so far, in 2017 and 2023. The 2023 report found for non-indigenous species:</p>
            <div class="rl-facts">
                <div class="rl-fact"><b>1,011</b><span>non-indigenous species recorded in Mediterranean waters</span></div>
                <div class="rl-fact"><b>748</b><span>of them established, nearly 74%</span></div>
                <div class="rl-fact"><b>×2</b><span>established species since 2012</span></div>
            </div>
            <p class="rl-src">Source: 2023 MED QSR.</p>
        </div>

    </div>
</section>
HTML;
    }

    /**
     * MAMIAS in the wider network of NIS databases: EASIN data partner since
     * 2016, and the marine databases it aims to connect with next.
     */
    private static function networkHtml(): string
    {
        return <<<'HTML'
<style>
.mamias-net {
    --nt-ink: var(--foreground);
    --nt-ink-2: #47606b;
    --nt-muted: var(--muted-foreground);
    --nt-line: var(--border);
    --nt-surface: var(--card);
    --nt-tint: var(--accent);
    --nt-teal-text: var(--mamias-teal-600);
    --nt-teal-deco: var(--mamias-teal-500);
    --nt-teal-fill: var(--mamias-teal-600);
    --nt-on-teal: #fff;
    color: var(--nt-ink); font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-net {
    --nt-ink-2: #b3c7ce;
    --nt-teal-text: var(--mamias-teal-300);
    --nt-teal-deco: var(--mamias-teal-400);
    --nt-teal-fill: var(--mamias-teal-400);
    --nt-on-teal: var(--background);
}
.mamias-net * { box-sizing: border-box; }
.mamias-net .nt-wrap { display: grid; gap: 24px; }
.mamias-net .nt-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--nt-muted); }
.mamias-net .nt-head { display: grid; gap: 12px; }
.mamias-net .nt-head p { margin: 0; color: var(--nt-ink-2); }
.mamias-net .nt-k { font: 500 11px/16px var(--font-sans); letter-spacing: .1em; text-transform: uppercase; padding: 2px 8px; justify-self: start; }

/* Network diagram: MAMIAS hub, current partner on a solid link, prospective ones on dashed links. */
.mamias-net .nt-map { display: grid; grid-template-columns: minmax(0, 1fr) 64px minmax(0, 1.1fr) 64px minmax(0, 1.4fr); align-items: center; }
.mamias-net .nt-hub { background: var(--nt-teal-fill); color: var(--nt-on-teal); padding: 20px; display: grid; gap: 6px; }
.mamias-net .nt-hub b { font-size: 22px; line-height: 28px; font-weight: 500; }
.mamias-net .nt-hub span { font-size: 13px; line-height: 20px; }
.mamias-net .nt-link { height: 2px; background: var(--nt-teal-deco); }
.mamias-net .nt-link.dash { background: repeating-linear-gradient(to right, var(--nt-teal-deco) 0 6px, transparent 6px 12px); }
.mamias-net .nt-partner { border: 2px solid var(--nt-teal-deco); background: var(--nt-surface); padding: 18px; display: grid; gap: 8px; }
.mamias-net .nt-partner .nt-k { background: var(--nt-teal-fill); color: var(--nt-on-teal); }
.mamias-net .nt-partner b { font-size: 20px; line-height: 28px; font-weight: 500; }
.mamias-net .nt-partner p { margin: 0; font-size: 14px; line-height: 22px; color: var(--nt-ink-2); }
.mamias-net .nt-next { display: grid; gap: 8px; }
.mamias-net .nt-next .nt-k { border: 1px dashed var(--nt-teal-deco); color: var(--nt-teal-text); }
.mamias-net .nt-next ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.mamias-net .nt-next li { border: 1px dashed var(--nt-teal-deco); padding: 10px 14px; display: grid; gap: 2px; }
.mamias-net .nt-next li b { font-size: 14px; line-height: 22px; font-weight: 500; }
.mamias-net .nt-next li span { font-size: 13px; line-height: 20px; color: var(--nt-ink-2); }
.mamias-net .nt-why { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); gap: 1px; background: var(--nt-line); border: 1px solid var(--nt-line); }
.mamias-net .nt-why div { background: var(--nt-surface); padding: 14px 16px; display: grid; gap: 2px; }
.mamias-net .nt-why b { font-size: 14px; line-height: 22px; font-weight: 500; }
.mamias-net .nt-why span { font-size: 13px; line-height: 20px; color: var(--nt-ink-2); }
@media (max-width: 1000px) {
    .mamias-net .nt-map { grid-template-columns: minmax(0, 1fr); }
    .mamias-net .nt-link { width: 2px; height: 28px; justify-self: center; }
    .mamias-net .nt-link.dash { background: repeating-linear-gradient(to bottom, var(--nt-teal-deco) 0 6px, transparent 6px 12px); }
}
</style>
<section id="network" class="py-8 mamias-net">
    <div class="kt-container-fixed nt-wrap">
        <div class="nt-head">
            <span class="nt-eyebrow">Data partnerships</span>
            <h2 class="text-3xl font-bold">Part of a wider network of databases</h2>
            <p>Non-indigenous species do not stop at regional borders, and neither should the data on them. MAMIAS shares its validated Mediterranean records with other information systems, so that the same knowledge reaches European and global assessments without being collected twice.</p>
        </div>

        <div class="nt-map" role="img" aria-label="MAMIAS is a data partner of EASIN since 2016 and aims to connect with other marine non-indigenous species databases.">
            <div class="nt-hub"><b>MAMIAS</b><span>Validated records of marine non-indigenous species in the Mediterranean</span></div>
            <div class="nt-link"></div>
            <div class="nt-partner">
                <span class="nt-k">Data partner since 2016</span>
                <b>EASIN</b>
                <p>The European Alien Species Information Network, run by the European Commission's Joint Research Centre. It brings together alien species data from many sources to support European policy.</p>
            </div>
            <div class="nt-link dash"></div>
            <div class="nt-next">
                <span class="nt-k">Next · prospective partners</span>
                <ul>
                    <li><b>GFCM Observatory for non-indigenous species</b><span>Launched in 2023 by the General Fisheries Commission for the Mediterranean (FAO) to monitor NIS in the Mediterranean and Black Sea</span></li>
                    <li><b>WRiMS</b><span>World Register of Introduced Marine Species</span></li>
                    <li><b>AquaNIS</b><span>Information system on aquatic non-indigenous and cryptogenic species</span></li>
                    <li><b>OBIS</b><span>Ocean Biodiversity Information System</span></li>
                </ul>
            </div>
        </div>

        <div class="nt-why">
            <div><b>One record, many uses</b><span>A sighting validated once in MAMIAS can inform regional, European and global reporting.</span></div>
            <div><b>Fewer gaps and duplicates</b><span>Cross-checking with partner databases improves the completeness of national lists.</span></div>
            <div><b>Shared standards</b><span>Common taxonomy and data formats make records comparable across seas.</span></div>
        </div>
    </div>
</section>
HTML;
    }

    private static function sourcesHtml(): string
    {
        return <<<'HTML'
<section style="padding-block: 2rem 3rem;">
    <div class="kt-container-fixed" style="border-top: 1px solid var(--border); padding-top: 32px; display: grid; gap: 12px;">
        <span style="font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--muted-foreground);">Sources</span>
        <ol style="font-size: 14px; line-height: 22px; color: var(--muted-foreground); display: grid; gap: 4px; padding-left: 20px; margin: 0; list-style: decimal;">
            <li>UNEP/MAP, Decision IG.27/6, Ecosystem Approach (EcAp) Policy and Roadmap 2026–2035 and Integrated Monitoring and Assessment Programme for the Mediterranean Sea and Coast (IMAP), COP 24, Cairo, 2025. UNEP/MED IG.27/21. <a href="https://wedocs.unep.org/rest/api/core/bitstreams/87e5024d-fa3c-4a31-9f70-495699bb6642/content" target="_blank" rel="noopener" style="color: var(--primary);">Document</a></li>
            <li>UNEP/MAP, 2023 Mediterranean Quality Status Report (MED QSR), for all figures on non-indigenous species. <a href="https://medqsr2023.info-rac.org/" target="_blank" rel="noopener" style="color: var(--primary);">medqsr2023.info-rac.org</a></li>
        </ol>
    </div>
</section>
HTML;
    }
}
