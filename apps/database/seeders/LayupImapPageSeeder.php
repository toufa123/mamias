<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes /pages/imap (Resources › IMAP): the Integrated Monitoring and
 * Assessment Programme and its non-indigenous species indicator, built from
 * Decision IG.27/6 (COP 24, Cairo, December 2025), Annex II.
 *
 * Same construction as LayupSpaBdProtocolPageSeeder: one html widget whose
 * styles are scoped under `.mamias-imap` and read the site tokens from app.css,
 * so it follows DESIGN-SYSTEM.md and the light/dark switch without Tailwind
 * utilities. No <h1>: the site's page header shows the title.
 *
 * Overwrites the page content. Run: php artisan db:seed --class=LayupImapPageSeeder
 */
class LayupImapPageSeeder extends Seeder
{
    public const SLUG = 'pages/imap';

    public function run(): void
    {
        Page::withTrashed()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'deleted_at' => null,
                'title' => 'Non-indigenous species and IMAP',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => 'How the Barcelona Convention monitors non-indigenous species: Ecological Objective 2, Common Indicator 6, hot-spot monitoring and the regional NIS baseline.',
                ],
                'content' => [
                    'rows' => [[
                        'id' => 'row_imap',
                        'settings' => ['gap' => 'gap-0'],
                        'columns' => [[
                            'id' => 'col_imap',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => 'widget_imap',
                                'type' => 'html',
                                'data' => ['content' => self::html()],
                            ]],
                        ]],
                    ]],
                ],
            ]
        );
    }

    public static function html(): string
    {
        return <<<'HTML'
<style>
.mamias-imap {
    --im-ink: var(--foreground);
    --im-ink-2: #47606b;
    --im-muted: var(--muted-foreground);
    --im-line: var(--border);
    --im-line-soft: var(--secondary);
    --im-surface: var(--card);
    --im-sunken: var(--muted);
    --im-tint: var(--accent);
    --im-teal-text: var(--mamias-teal-600);
    --im-teal-deco: var(--mamias-teal-500);
    --im-teal-fill: var(--mamias-teal-600);
    --im-on-teal: #fff;
    display: grid; gap: 64px; padding-block: 24px 64px; color: var(--im-ink);
    font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-imap {
    --im-ink-2: #b3c7ce;
    --im-teal-text: var(--mamias-teal-300);
    --im-teal-deco: var(--mamias-teal-400);
    --im-teal-fill: var(--mamias-teal-400);
    --im-on-teal: var(--background);
}
.mamias-imap * { box-sizing: border-box; }
.mamias-imap h2, .mamias-imap h3 { margin: 0; color: var(--im-ink); text-wrap: balance; }
.mamias-imap h2 { font-size: 20px; line-height: 28px; font-weight: 500; }
.mamias-imap h3 { font-size: 16px; line-height: 24px; font-weight: 500; }
.mamias-imap p { margin: 0; }
.mamias-imap .im-stack { display: grid; gap: 16px; }
.mamias-imap .im-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--im-muted); }
.mamias-imap .im-lede { color: var(--im-ink-2); }
.mamias-imap .im-small { font-size: 14px; line-height: 22px; color: var(--im-ink-2); }
.mamias-imap em { font-style: italic; }
.mamias-imap a { color: var(--im-teal-text); text-underline-offset: 2px; }
.mamias-imap a:focus-visible { outline: 2px solid var(--im-teal-deco); outline-offset: 2px; }
.mamias-imap figure { margin: 0; display: grid; gap: 12px; }
.mamias-imap figcaption { font-size: 14px; line-height: 22px; color: var(--im-muted); }
.mamias-imap .im-def { display: grid; gap: 4px; align-content: start; padding-top: 12px; border-top: 1px solid var(--im-line); }
.mamias-imap .im-def p { font-size: 14px; line-height: 22px; color: var(--im-ink-2); }
.mamias-imap .im-two { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; align-items: start; }
@media (max-width: 760px) { .mamias-imap .im-two { grid-template-columns: minmax(0, 1fr); } }

/* Timeline: teal-500 draws rule and markers only; teal-600 carries years and the filled current-cycle band. */
.mamias-imap .im-tl-scroll { overflow-x: auto; }
.mamias-imap .im-tl { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(6, minmax(8.5rem, 1fr)) minmax(11rem, 1.5fr); min-width: 62rem; }
.mamias-imap .im-tl li { position: relative; padding: 28px 16px 0 0; display: grid; gap: 2px; align-content: start; }
.mamias-imap .im-tl li::before { content: ""; position: absolute; left: 0; right: 0; top: 7px; height: 2px; background: var(--im-teal-deco); }
.mamias-imap .im-tl li::after { content: ""; position: absolute; left: 0; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--im-surface); border: 2px solid var(--im-teal-deco); }
.mamias-imap .im-tl .y { font: 400 20px/28px var(--font-mono); letter-spacing: -.4px; color: var(--im-teal-text); font-variant-numeric: tabular-nums; }
.mamias-imap .im-tl b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-imap .im-tl .d { font: 400 12px/18px var(--font-mono); color: var(--im-muted); }
.mamias-imap .im-tl li.now { background: var(--im-teal-fill); padding: 28px 16px 14px; }
.mamias-imap .im-tl li.now::before { background: var(--im-teal-fill); }
.mamias-imap .im-tl li.now::after { left: 16px; background: var(--im-on-teal); border-color: var(--im-on-teal); }
.mamias-imap .im-tl li.now .y, .mamias-imap .im-tl li.now b, .mamias-imap .im-tl li.now .d { color: var(--im-on-teal); }
@media (max-width: 640px) {
    .mamias-imap .im-tl { grid-template-columns: 1fr; min-width: 0; border-left: 2px solid var(--im-teal-deco); margin-left: 5px; }
    .mamias-imap .im-tl li { padding: 0 0 16px 20px; }
    .mamias-imap .im-tl li::before { display: none; }
    .mamias-imap .im-tl li::after { left: -8px; top: 8px; }
    .mamias-imap .im-tl li.now { padding: 12px 16px 14px 20px; }
    .mamias-imap .im-tl li.now::after { left: -8px; top: 20px; background: var(--im-teal-fill); border-color: var(--im-teal-deco); }
}

.mamias-imap .im-eos { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr)); gap: 1px; background: var(--im-line); border: 1px solid var(--im-line); }
.mamias-imap .im-eo { background: var(--im-surface); padding: 12px 16px; display: grid; grid-template-columns: 3.2rem 1fr; gap: 8px; align-items: baseline; font-size: 14px; line-height: 22px; }
.mamias-imap .im-eo .k { font: 400 13px/20px var(--font-mono); color: var(--im-muted); }
.mamias-imap .im-eo.on { background: var(--im-tint); border-left: 2px solid var(--im-teal-deco); padding-left: 14px; }
.mamias-imap .im-eo.on .k { color: var(--im-teal-text); }

.mamias-imap .im-ges { margin: 0; border: 1px solid var(--im-line); background: var(--im-surface); }
.mamias-imap .im-ges > div { display: grid; grid-template-columns: minmax(0, 14rem) minmax(0, 1fr); gap: 16px; padding: 14px 16px; border-bottom: 1px solid var(--im-line-soft); }
.mamias-imap .im-ges > div:last-child { border-bottom: 0; }
.mamias-imap .im-ges dt { font: 500 11px/20px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--im-muted); }
.mamias-imap .im-ges dd { margin: 0; font-size: 14px; line-height: 22px; }
@media (max-width: 560px) { .mamias-imap .im-ges > div { grid-template-columns: 1fr; gap: 4px; } }

.mamias-imap .im-spots { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 1px; background: var(--im-line); border: 1px solid var(--im-line); }
.mamias-imap .im-spot { background: var(--im-surface); padding: 14px 16px; display: grid; grid-template-columns: 28px 1fr; gap: 4px 12px; align-items: start; }
.mamias-imap .im-spot svg { width: 28px; height: 28px; grid-row: span 2; }
.mamias-imap .im-spot b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-imap .im-spot span { font: 400 12px/18px var(--font-mono); color: var(--im-teal-text); }
.mamias-imap .im-spot span.n { color: var(--im-muted); }

.mamias-imap .im-bars { display: grid; gap: 8px; }
.mamias-imap .im-bar { display: grid; grid-template-columns: 7.5rem minmax(0, 1fr) 3rem; gap: 12px; align-items: center; font-size: 14px; line-height: 22px; }
.mamias-imap .im-bar .t { height: 14px; background: var(--im-sunken); border: 1px solid var(--im-line-soft); }
.mamias-imap .im-bar .f { height: 100%; background: var(--im-teal-deco); }
.mamias-imap .im-bar .v { font: 400 13px/20px var(--font-mono); font-variant-numeric: tabular-nums; text-align: right; }
.mamias-imap .im-est { display: grid; grid-template-columns: 748fr 263fr; height: 28px; border: 1px solid var(--im-line); }
.mamias-imap .im-est div { display: flex; align-items: center; padding: 0 10px; font: 400 12px/1 var(--font-mono); white-space: nowrap; overflow: hidden; }
.mamias-imap .im-est div:first-child { background: var(--im-teal-fill); color: var(--im-on-teal); }
.mamias-imap .im-est div:last-child { background: var(--im-sunken); color: var(--im-ink-2); }

.mamias-imap .im-subs { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr)); gap: 1px; background: var(--im-line); border: 1px solid var(--im-line); }
.mamias-imap .im-subs div { background: var(--im-surface); padding: 12px 16px; display: grid; gap: 2px; }
.mamias-imap .im-subs b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-imap .im-subs span { font: 400 12px/18px var(--font-mono); color: var(--im-muted); }

.mamias-imap blockquote { margin: 0; padding: 12px 16px; border-left: 2px solid var(--im-teal-deco); background: var(--im-sunken); font-size: 15px; line-height: 24px; }
.mamias-imap blockquote cite { display: block; margin-top: 6px; font: 400 12px/18px var(--font-mono); font-style: normal; color: var(--im-muted); }

.mamias-imap .im-cta { display: flex; flex-wrap: wrap; gap: 12px; }
.mamias-imap .im-btn { display: inline-flex; align-items: center; height: 40px; padding: 0 16px; border: 1px solid var(--im-line); color: var(--im-teal-text); background: var(--im-surface); text-decoration: none; font: 500 14px/22px var(--font-sans); }
.mamias-imap .im-btn:hover { border-color: var(--im-teal-deco); }
.mamias-imap .im-btn.p { background: var(--primary); border-color: var(--primary); color: var(--primary-foreground); }
.mamias-imap .im-btn.p:hover { background: var(--mamias-teal-700); border-color: var(--mamias-teal-700); }
html.dark .mamias-imap .im-btn.p:hover { background: var(--mamias-teal-300); border-color: var(--mamias-teal-300); }
.mamias-imap .im-sources { font-size: 14px; line-height: 22px; color: var(--im-muted); display: grid; gap: 4px; padding-left: 20px; margin: 0; }
.mamias-imap .im-end { border-top: 1px solid var(--border); padding-top: 32px; }
</style>

<div class="mamias-imap">

    <header class="im-stack">
        <span class="im-eyebrow">Barcelona Convention · Ecosystem Approach</span>
        <p class="im-lede">IMAP is how the Contracting Parties to the Barcelona Convention measure the state of the Mediterranean Sea and Coast. It sets the common indicators every country monitors, the methods it uses and the criteria for judging Good Environmental Status (GES). Non-indigenous species are covered by Ecological Objective 2 and Common Indicator 6.</p>
        <div class="im-tl-scroll">
            <ol class="im-tl" aria-label="IMAP timeline">
                <li><span class="y">2008</span><b>Ecosystem Approach agreed</b><span class="d">COP 15 · IG.17/6</span></li>
                <li><span class="y">2013</span><b>GES definitions and targets</b><span class="d">COP 18 · IG.21/3</span></li>
                <li><span class="y">2016</span><b>IMAP adopted</b><span class="d">COP 19 · IG.22/7</span></li>
                <li><span class="y">2017</span><b>First MED QSR</b><span class="d">COP 20 · IG.23/6</span></li>
                <li><span class="y">2023</span><b>Second MED QSR</b><span class="d">COP 23 · IG.26/3</span></li>
                <li><span class="y">2025</span><b>Third cycle adopted</b><span class="d">COP 24 · IG.27/6</span></li>
                <li class="now"><span class="y">2026–2035</span><b>Third IMAP cycle</b><span class="d">11 objectives · 43 indicators</span></li>
            </ol>
        </div>
    </header>

    <section class="im-stack">
        <span class="im-eyebrow">Ecological Objectives</span>
        <h2>Eleven objectives, three monitoring clusters</h2>
        <p>IMAP groups its work into three clusters: Biodiversity and Non-Indigenous Species, Pollution and Marine Litter, and Coast and Hydrography. SPA/RAC coordinates the biodiversity and NIS cluster, MED POL the pollution cluster, and PAP/RAC the coast cluster.</p>
        <div class="im-eos">
            <div class="im-eo"><span class="k">EO1</span>Biodiversity</div>
            <div class="im-eo on"><span class="k">EO2</span>Non-indigenous species</div>
            <div class="im-eo"><span class="k">EO3</span>Commercially exploited fish and shellfish</div>
            <div class="im-eo"><span class="k">EO4</span>Marine food webs</div>
            <div class="im-eo"><span class="k">EO5</span>Eutrophication</div>
            <div class="im-eo"><span class="k">EO6</span>Sea-floor integrity</div>
            <div class="im-eo"><span class="k">EO7</span>Hydrographic alterations</div>
            <div class="im-eo"><span class="k">EO8</span>Coastal ecosystems and landscapes</div>
            <div class="im-eo"><span class="k">EO9</span>Contaminants</div>
            <div class="im-eo"><span class="k">EO10</span>Marine litter</div>
            <div class="im-eo"><span class="k">EO11</span>Energy, including underwater noise</div>
        </div>
    </section>

    <section class="im-stack">
        <span class="im-eyebrow">Focus · EO2 and Common Indicator 6</span>
        <h2>How IMAP defines good status for non-indigenous species</h2>
        <dl class="im-ges">
            <div><dt>Ecological Objective 2</dt><dd>Non-indigenous species introduced by human activities are at levels that do not adversely alter the ecosystem.</dd></div>
            <div><dt>Operational objective 2.1</dt><dd>Invasive non-indigenous species introductions are minimized.</dd></div>
            <div><dt>Common Indicator 6</dt><dd>Trends in abundance, temporal occurrence and spatial distribution of non-indigenous species, particularly invasive non-indigenous species, notably in risk areas, in relation to the main vectors and pathways of spreading.</dd></div>
            <div><dt>GES definition</dt><dd>Decreasing abundance of introduced NIS in risk areas.</dd></div>
            <div><dt>GES target</dt><dd>Abundance of NIS introduced by human activities reduced to levels giving no detectable impact.</dd></div>
        </dl>
        <p class="im-small">In IMAP, a non-indigenous species is a species, subspecies or lower taxon introduced outside its natural range and outside its natural dispersal potential. Invasive alien species are the subset of established NIS that spread and affect biodiversity, ecosystem functioning, socio-economic values or human health.</p>
    </section>

    <section class="im-stack">
        <span class="im-eyebrow">Infographic · Risk-based monitoring</span>
        <h2>Where countries look for new arrivals</h2>
        <p>NIS monitoring is trend monitoring, and it follows a risk-based approach. Instead of sampling the whole coast, countries survey the introduction hot spots where new species are most likely to appear first. That gives a basin-wide picture from a relatively small number of stations, provided the same sites are surveyed every period.</p>
        <div class="im-spots">
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M14 4 V22 M8 10 H20 M6 18 q8 8 16 0" fill="none" stroke="var(--im-teal-deco)" stroke-width="2" stroke-linecap="round"/><circle cx="14" cy="4" r="2" fill="var(--im-teal-deco)"/></svg>
                <b>Ports and their wider area</b><span>At least once a year</span>
            </div>
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M4 18 H24 L21 23 H7 Z" fill="none" stroke="var(--im-teal-deco)" stroke-width="2"/><path d="M14 5 V18 M14 6 L21 15 H14" fill="none" stroke="var(--im-teal-deco)" stroke-width="2" stroke-linejoin="round"/></svg>
                <b>Smaller harbours and marinas</b><span>Every two years</span>
            </div>
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><rect x="4" y="8" width="20" height="14" fill="none" stroke="var(--im-teal-deco)" stroke-width="2"/><path d="M4 15 H24 M11 8 V22 M17 8 V22" stroke="var(--im-teal-deco)" stroke-width="1.2"/></svg>
                <b>Aquaculture installations</b><span>Every two years</span>
            </div>
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M4 22 H24 M7 22 V12 H12 V22 M15 22 V8 H21 V22" fill="none" stroke="var(--im-teal-deco)" stroke-width="2"/></svg>
                <b>Docks</b><span class="n">Hot spot</span>
            </div>
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M6 22 V10 L12 13 V8 L18 11 V22 Z" fill="none" stroke="var(--im-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M20 16 q2 -2 4 0 M20 20 q2 -2 4 0" fill="none" stroke="var(--status-invasive-text)" stroke-width="1.6"/></svg>
                <b>Heated power-plant effluents</b><span class="n">Hot spot</span>
            </div>
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M8 24 L11 10 H17 L20 24 M11 16 H17 M14 4 V10 M9 6 H19" fill="none" stroke="var(--im-teal-deco)" stroke-width="2" stroke-linejoin="round"/></svg>
                <b>Offshore structures</b><span class="n">Hot spot</span>
            </div>
            <div class="im-spot">
                <svg viewBox="0 0 28 28" aria-hidden="true"><circle cx="14" cy="14" r="9" fill="none" stroke="var(--im-teal-deco)" stroke-width="2" stroke-dasharray="3 3"/><circle cx="14" cy="14" r="3" fill="var(--im-teal-deco)"/></svg>
                <b>Marine protected areas and lagoons</b><span class="n">Case by case, near hot spots</span>
            </div>
        </div>
        <div class="im-two">
            <div class="im-def"><h3>Rapid Assessment Surveys</h3><p>The main method: a Rapid Assessment Survey in hot-spot areas at least once a year. Countries with the resources are encouraged to add environmental DNA (eDNA) surveys.</p></div>
            <div class="im-def"><h3>Citizen surveys</h3><p>During the third cycle, UNEP/MAP will develop citizen-survey guidance for NIS, a low-cost method that also builds public awareness and participation.</p></div>
        </div>
    </section>

    <section class="im-stack">
        <span class="im-eyebrow">Baseline · records to the end of 2020</span>
        <h2>1,011 non-indigenous species recorded in Mediterranean waters</h2>
        <p>According to the 2023 Mediterranean Quality Status Report (MED QSR), 1,011 validated non-indigenous species have been recorded in Mediterranean waters, of which 748 are considered established, an establishment rate of nearly 74%.</p>
        <figure>
            <div class="im-est" role="img" aria-label="748 established out of 1,011 recorded non-indigenous species."><div>748 established</div><div>263 other</div></div>
            <div class="im-bars" role="img" aria-label="Recorded non-indigenous species by group.">
                <div class="im-bar"><span>Mollusca</span><div class="t"><div class="f" style="width: 100%"></div></div><span class="v">224</span></div>
                <div class="im-bar"><span>Chordata</span><div class="t"><div class="f" style="width: 90.6%"></div></div><span class="v">203</span></div>
                <div class="im-bar"><span>Arthropoda</span><div class="t"><div class="f" style="width: 83.9%"></div></div><span class="v">188</span></div>
                <div class="im-bar"><span>Macrophytes</span><div class="t"><div class="f" style="width: 64.3%"></div></div><span class="v">144</span></div>
                <div class="im-bar"><span>Annelida</span><div class="t"><div class="f" style="width: 37.1%"></div></div><span class="v">83</span></div>
                <div class="im-bar"><span>Foraminifera</span><div class="t"><div class="f" style="width: 21%"></div></div><span class="v">47</span></div>
                <div class="im-bar"><span>Cnidaria</span><div class="t"><div class="f" style="width: 18.8%"></div></div><span class="v">42</span></div>
                <div class="im-bar"><span>Bryozoa</span><div class="t"><div class="f" style="width: 14.7%"></div></div><span class="v">33</span></div>
                <div class="im-bar"><span>Other taxa</span><div class="t"><div class="f" style="width: 21%"></div></div><span class="v">47</span></div>
            </div>
            <figcaption>Recorded NIS by group. Source: 2023 MED QSR, which also found that the number of established NIS has doubled since 2012.</figcaption>
        </figure>
    </section>

    <section class="im-stack">
        <span class="im-eyebrow">Assessment</span>
        <h2>Judged by sub-region, every six years</h2>
        <p>CI6 can be calculated for the whole basin or for a country, but it is most meaningful at the level of the four EcAp sub-regions, and within them for each country's national part. The assessment period follows the 6-year cycle already used by EU countries under the Marine Strategy Framework Directive.</p>
        <div class="im-subs">
            <div><b>Western Mediterranean</b><span>WMED</span></div>
            <div><b>Adriatic Sea</b><span>ADRIA</span></div>
            <div><b>Central Mediterranean</b><span>CMED</span></div>
            <div><b>Aegean and Levantine Seas</b><span>EMED</span></div>
        </div>
        <div class="im-two">
            <div class="im-def"><h3>Threshold values are not set yet</h3><p>Neither the EU nor the Mediterranean has a threshold for new introductions. The recommended approach is a percentage reduction in new NIS, set separately for each sub-region and based on data from 2000 onwards, when the rate of new records rose sharply.</p></div>
            <div class="im-def"><h3>What still has to be agreed</h3><p>Which species groups count in the trend, and how impacts are taken into account. The next step is to develop criteria and targets for vulnerable species and habitats, coordinated with EO1 and EO6.</p></div>
        </div>
    </section>

    <section class="im-stack">
        <span class="im-eyebrow">MAMIAS and IMAP</span>
        <h2>Where MAMIAS fits in</h2>
        <div class="im-two">
            <div class="im-stack">
                <p>At the start of each IMAP phase, every Contracting Party updates its list of invasive alien species to monitor. The decision names MAMIAS first among the regional databases that list should build on.</p>
                <blockquote>“…the Marine Mediterranean Invasive Alien Species database, (MAMIAS)…”<cite>Decision IG.27/6, Annex II, paragraph 66</cite></blockquote>
            </div>
            <div class="im-stack">
                <p>Monitoring data then go to the IMAP Info System, which accepts data for 18 common indicators, CI6 among them, through 30 information standards. Its published data are open to the public.</p>
                <div class="im-cta">
                    <a class="im-btn p" href="/pages/data">Explore NIS data</a>
                    <a class="im-btn" href="https://imapinfosystem.info-rac.org/app/#/" target="_blank" rel="noopener">IMAP Info System</a>
                </div>
            </div>
        </div>
    </section>

    <section class="im-end im-stack">
        <span class="im-eyebrow">Sources</span>
        <ol class="im-sources">
            <li>UNEP/MAP, Decision IG.27/6, Ecosystem Approach (EcAp) Policy and Roadmap 2026–2035 and Integrated Monitoring and Assessment Programme for the Mediterranean Sea and Coast (IMAP), COP 24, Cairo, 2025. UNEP/MED IG.27/21. <a href="https://wedocs.unep.org/rest/api/core/bitstreams/87e5024d-fa3c-4a31-9f70-495699bb6642/content" target="_blank" rel="noopener">Document</a></li>
            <li>UNEP/MAP, Decision IG.22/7, Integrated Monitoring and Assessment Programme of the Mediterranean Sea and Coast and Related Assessment Criteria, COP 19, Athens, 2016.</li>
            <li>UNEP/MAP, 2023 Mediterranean Quality Status Report (MED QSR), for all figures on non-indigenous species. <a href="https://medqsr2023.info-rac.org/" target="_blank" rel="noopener">medqsr2023.info-rac.org</a></li>
        </ol>
    </section>

</div>
HTML;
    }
}
