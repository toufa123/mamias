<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes /pages/post-2020-sapbio (Resources › Post-2020 SAPBIO): the
 * programme's structure and its non-indigenous species commitments, drawn from
 * Decision IG.25/11 (COP 22, 2021), with an analysis against the
 * Kunming-Montreal Global Biodiversity Framework (CBD Decision 15/4, 2022).
 *
 * Same construction as LayupSpaBdProtocolPageSeeder: one html widget whose
 * styles are scoped under `.mamias-sap` and read the site tokens from app.css.
 * No <h1>: the site's page header shows the title.
 *
 * Overwrites the page content. Run: php artisan db:seed --class=LayupSapbioPageSeeder
 */
class LayupSapbioPageSeeder extends Seeder
{
    public const SLUG = 'pages/post-2020-sapbio';

    public function run(): void
    {
        Page::withTrashed()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'deleted_at' => null,
                'title' => 'Non-indigenous species and the Post-2020 SAPBIO',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => 'The Post-2020 SAPBIO of the Barcelona Convention, its commitments on non-indigenous species, and how its 27 targets line up with the Kunming-Montreal Global Biodiversity Framework.',
                ],
                'content' => [
                    'rows' => [[
                        'id' => 'row_sapbio',
                        'settings' => ['gap' => 'gap-0'],
                        'columns' => [[
                            'id' => 'col_sapbio',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => 'widget_sapbio',
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
.mamias-sap {
    --sp-ink: var(--foreground);
    --sp-ink-2: #47606b;
    --sp-muted: var(--muted-foreground);
    --sp-line: var(--border);
    --sp-line-soft: var(--secondary);
    --sp-surface: var(--card);
    --sp-sunken: var(--muted);
    --sp-tint: var(--accent);
    --sp-teal-text: var(--mamias-teal-600);
    --sp-teal-deco: var(--mamias-teal-500);
    --sp-teal-fill: var(--mamias-teal-600);
    --sp-on-teal: #fff;
    --sp-navy: #2a2b74;
    display: grid; gap: 64px; padding-block: 24px 64px; color: var(--sp-ink);
    font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-sap {
    --sp-ink-2: #b3c7ce;
    --sp-teal-text: var(--mamias-teal-300);
    --sp-teal-deco: var(--mamias-teal-400);
    --sp-teal-fill: var(--mamias-teal-400);
    --sp-on-teal: var(--background);
    --sp-navy: #8f91d9;
}
.mamias-sap * { box-sizing: border-box; }
.mamias-sap h2, .mamias-sap h3 { margin: 0; color: var(--sp-ink); text-wrap: balance; }
.mamias-sap h2 { font-size: 20px; line-height: 28px; font-weight: 500; }
.mamias-sap h3 { font-size: 16px; line-height: 24px; font-weight: 500; }
.mamias-sap p { margin: 0; }
.mamias-sap .sp-stack { display: grid; gap: 16px; }
.mamias-sap .sp-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sp-muted); }
.mamias-sap .sp-lede { color: var(--sp-ink-2); }
.mamias-sap .sp-small { font-size: 14px; line-height: 22px; color: var(--sp-ink-2); }
.mamias-sap a { color: var(--sp-teal-text); text-underline-offset: 2px; }
.mamias-sap a:focus-visible { outline: 2px solid var(--sp-teal-deco); outline-offset: 2px; }
.mamias-sap figure { margin: 0; display: grid; gap: 12px; }
.mamias-sap figcaption { font-size: 14px; line-height: 22px; color: var(--sp-muted); }
.mamias-sap .sp-def { display: grid; gap: 4px; align-content: start; padding-top: 12px; border-top: 1px solid var(--sp-line); }
.mamias-sap .sp-def p { font-size: 14px; line-height: 22px; color: var(--sp-ink-2); }
.mamias-sap .sp-two { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; align-items: start; }
.mamias-sap .sp-three { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr)); gap: 24px; }
@media (max-width: 760px) { .mamias-sap .sp-two { grid-template-columns: minmax(0, 1fr); } }
.mamias-sap .sp-code { font: 400 13px/20px var(--font-mono); color: var(--sp-teal-text); background: var(--sp-tint); border: 1px solid var(--sp-line); padding: 0 6px; white-space: nowrap; }
.mamias-sap .sp-code.g { color: var(--sp-ink-2); background: var(--sp-line-soft); }

/* Timeline: teal-500 draws rule and markers only; teal-600 carries the years. */
.mamias-sap .sp-tl-scroll { overflow-x: auto; }
.mamias-sap .sp-tl { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(8, minmax(8rem, 1fr)); min-width: 66rem; }
.mamias-sap .sp-tl li { position: relative; padding: 28px 16px 0 0; display: grid; gap: 2px; align-content: start; }
.mamias-sap .sp-tl li::before { content: ""; position: absolute; left: 0; right: 0; top: 7px; height: 2px; background: var(--sp-teal-deco); }
.mamias-sap .sp-tl li::after { content: ""; position: absolute; left: 0; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--sp-surface); border: 2px solid var(--sp-teal-deco); }
.mamias-sap .sp-tl li.g::after { border-color: var(--sp-muted); }
.mamias-sap .sp-tl .y { font: 400 20px/28px var(--font-mono); letter-spacing: -.4px; color: var(--sp-teal-text); font-variant-numeric: tabular-nums; }
.mamias-sap .sp-tl li.g .y { color: var(--sp-ink-2); }
.mamias-sap .sp-tl b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-sap .sp-tl .d { font: 400 12px/18px var(--font-mono); color: var(--sp-muted); }
@media (max-width: 640px) {
    .mamias-sap .sp-tl { grid-template-columns: 1fr; min-width: 0; border-left: 2px solid var(--sp-teal-deco); margin-left: 5px; }
    .mamias-sap .sp-tl li { padding: 0 0 16px 20px; }
    .mamias-sap .sp-tl li::before { display: none; }
    .mamias-sap .sp-tl li::after { left: -8px; top: 8px; }
}

/* Structure ladder: Vision → Mission → Goals → Targets → Actions */
.mamias-sap .sp-ladder { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1px; background: var(--sp-line); border: 1px solid var(--sp-line); }
.mamias-sap .sp-ladder > div { background: var(--sp-surface); padding: 16px; display: grid; gap: 4px; align-content: start; }
.mamias-sap .sp-ladder .k { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sp-muted); }
.mamias-sap .sp-ladder .n { font: 400 28px/36px var(--font-mono); letter-spacing: -.6px; color: var(--sp-teal-text); }
.mamias-sap .sp-ladder p { font-size: 14px; line-height: 22px; color: var(--sp-ink-2); }
@media (max-width: 900px) { .mamias-sap .sp-ladder { grid-template-columns: minmax(0, 1fr); } }

.mamias-sap .sp-goals { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr)); gap: 1px; background: var(--sp-line); border: 1px solid var(--sp-line); }
.mamias-sap .sp-goal { background: var(--sp-surface); padding: 16px; display: grid; gap: 8px; align-content: start; }
.mamias-sap .sp-goal .k { font: 400 13px/20px var(--font-mono); color: var(--sp-teal-text); }
.mamias-sap .sp-goal ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 4px; font-size: 14px; line-height: 22px; color: var(--sp-ink-2); }
.mamias-sap .sp-goal li span { font-family: var(--font-mono); font-size: 12px; color: var(--sp-muted); margin-right: 6px; }
.mamias-sap .sp-goal li.on { color: var(--sp-ink); }
.mamias-sap .sp-goal li.on span { color: var(--sp-teal-text); }

/* Target statement */
.mamias-sap .sp-target { border: 1px solid var(--sp-teal-deco); background: var(--sp-tint); padding: 20px; display: grid; gap: 8px; }
.mamias-sap .sp-target .k { font: 400 13px/20px var(--font-mono); color: var(--sp-teal-text); }
.mamias-sap .sp-target ol { margin: 0; padding-left: 20px; font-size: 14px; line-height: 22px; color: var(--sp-ink-2); display: grid; gap: 2px; }

/* Tables */
.mamias-sap .sp-table { overflow-x: auto; border: 1px solid var(--sp-line); background: var(--sp-surface); }
.mamias-sap table { border-collapse: collapse; width: 100%; font-size: 14px; line-height: 22px; }
.mamias-sap th, .mamias-sap td { text-align: left; vertical-align: top; padding: 12px 16px; border-bottom: 1px solid var(--sp-line-soft); }
.mamias-sap thead th { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sp-muted); background: var(--sp-sunken); border-bottom: 1px solid var(--sp-line); white-space: nowrap; }
.mamias-sap tbody tr:last-child td { border-bottom: 0; }
.mamias-sap .sp-actions { min-width: 56rem; }
.mamias-sap .sp-actions td:first-child { width: 22%; }
.mamias-sap .sp-actions td:first-child b { display: block; font-weight: 500; }
.mamias-sap .sp-actions tr.on td { background: var(--sp-tint); }
.mamias-sap .sp-actions { min-width: 64rem; }
.mamias-sap .sp-actions td:not(:first-child) { width: 26%; }
.mamias-sap .sp-actions thead th.s1, .mamias-sap .sp-actions thead th.s2, .mamias-sap .sp-actions thead th.s3 { white-space: normal; color: var(--sp-ink); }
.mamias-sap .sp-actions thead th span { display: block; margin-top: 2px; font: 400 12px/18px var(--font-mono); letter-spacing: 0; text-transform: none; color: var(--sp-muted); }
.mamias-sap .sp-actions thead th.s2 { background: var(--sp-tint); border-bottom: 2px solid var(--sp-teal-deco); }
.mamias-sap .sp-actions thead th.s3 { background: var(--sp-teal-fill); color: var(--sp-on-teal); }
.mamias-sap .sp-actions thead th.s3 span { color: var(--sp-on-teal); }

/* Target 1.2 levers */
.mamias-sap .sp-levers { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 1px; background: var(--sp-line); border: 1px solid var(--sp-line); margin-top: 4px; }
.mamias-sap .sp-lever { background: var(--sp-surface); padding: 14px 16px; display: grid; grid-template-columns: 40px 1fr; gap: 2px 12px; align-items: start; }
.mamias-sap .sp-lever svg { width: 40px; height: 40px; grid-row: span 2; }
.mamias-sap .sp-lever .k { font: 400 12px/18px var(--font-mono); color: var(--sp-teal-text); }
.mamias-sap .sp-lever b { font-weight: 500; font-size: 14px; line-height: 22px; }

/* Flow diagram */
.mamias-sap .sp-fig { overflow-x: auto; }
.mamias-sap svg text { font-family: var(--font-sans); fill: var(--sp-ink); }
.mamias-sap svg .lbl { font-size: 13px; font-weight: 500; }
.mamias-sap svg .sub { font-size: 11px; fill: var(--sp-muted); }
.mamias-sap svg text.on { fill: var(--sp-on-teal); }

/* Review stages: start-up (tint) → 2027 (teal-500 rule) → 2030 (teal-600 fill) */
.mamias-sap .sp-review { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1px; background: var(--sp-line); border: 1px solid var(--sp-line); }
.mamias-sap .sp-review .st { background: var(--sp-surface); padding: 16px; display: grid; gap: 4px; align-content: start; position: relative; }
.mamias-sap .sp-review .st .y { font: 400 24px/32px var(--font-mono); letter-spacing: -.5px; color: var(--sp-teal-text); }
.mamias-sap .sp-review .st p { font-size: 14px; line-height: 22px; color: var(--sp-ink-2); }
.mamias-sap .sp-review .s1 { background: var(--sp-tint); }
.mamias-sap .sp-review .s2 { border-top: 3px solid var(--sp-teal-deco); }
.mamias-sap .sp-review .s3 { border-top: 3px solid var(--sp-teal-fill); }
.mamias-sap .sp-review .br { padding: 10px 16px; font-size: 13px; line-height: 20px; display: grid; gap: 2px; }
.mamias-sap .sp-review .br span { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; }
.mamias-sap .sp-review .b1 { background: var(--sp-sunken); color: var(--sp-ink-2); }
.mamias-sap .sp-review .b1 span { color: var(--sp-muted); }
.mamias-sap .sp-review .b2 { grid-column: span 2; background: var(--sp-teal-fill); color: var(--sp-on-teal); }
@media (max-width: 760px) {
    .mamias-sap .sp-review { grid-template-columns: minmax(0, 1fr); }
    .mamias-sap .sp-review .s1 { order: 1; } .mamias-sap .sp-review .b1 { order: 2; }
    .mamias-sap .sp-review .s2 { order: 3; } .mamias-sap .sp-review .s3 { order: 4; }
    .mamias-sap .sp-review .b2 { order: 5; grid-column: auto; }
}
.mamias-sap .sp-prio { display: inline-block; font: 500 11px/16px var(--font-sans); letter-spacing: .08em; text-transform: uppercase; padding: 1px 6px; border: 1px solid var(--sp-line); color: var(--sp-ink-2); margin-top: 6px; }
.mamias-sap .sp-prio.vh { border-color: var(--sp-teal-deco); color: var(--sp-teal-text); }


/* NIS side-by-side */
.mamias-sap .sp-vs { min-width: 46rem; }
.mamias-sap .sp-vs td:first-child { width: 18%; font-weight: 500; }
.mamias-sap .sp-vs td { width: 41%; }

.mamias-sap blockquote { margin: 0; padding: 12px 16px; border-left: 2px solid var(--sp-teal-deco); background: var(--sp-sunken); font-size: 15px; line-height: 24px; }
.mamias-sap blockquote cite { display: block; margin-top: 6px; font: 400 12px/18px var(--font-mono); font-style: normal; color: var(--sp-muted); }
.mamias-sap .sp-cta { display: flex; flex-wrap: wrap; gap: 12px; }
.mamias-sap .sp-btn { display: inline-flex; align-items: center; height: 40px; padding: 0 16px; border: 1px solid var(--sp-line); color: var(--sp-teal-text); background: var(--sp-surface); text-decoration: none; font: 500 14px/22px var(--font-sans); }
.mamias-sap .sp-btn:hover { border-color: var(--sp-teal-deco); }
.mamias-sap .sp-btn.p { background: var(--primary); border-color: var(--primary); color: var(--primary-foreground); }
.mamias-sap .sp-btn.p:hover { background: var(--mamias-teal-700); border-color: var(--mamias-teal-700); }
html.dark .mamias-sap .sp-btn.p:hover { background: var(--mamias-teal-300); border-color: var(--mamias-teal-300); }
.mamias-sap .sp-sources { font-size: 14px; line-height: 22px; color: var(--sp-muted); display: grid; gap: 4px; padding-left: 20px; margin: 0; }
.mamias-sap .sp-end { border-top: 1px solid var(--border); padding-top: 32px; }
</style>

<div class="mamias-sap">

    <header class="sp-stack">
        <span class="sp-eyebrow">Barcelona Convention · SPA/BD Protocol · Decision IG.25/11</span>
        <p class="sp-lede">The Post-2020 Strategic Action Programme for the Conservation of Biodiversity and Sustainable Management of Natural Resources in the Mediterranean Region (Post-2020 SAPBIO) is the Mediterranean's biodiversity strategy to 2030. It was adopted under Article 3 of the SPA/BD Protocol, which asks Parties to adopt strategies, plans and programmes for biodiversity, and it was built to contribute to the CBD's global framework through a Mediterranean lens. Non-indigenous and invasive species are one of its priority threats.</p>
        <div class="sp-tl-scroll">
            <ol class="sp-tl" aria-label="SAPBIO timeline">
                <li><span class="y">2003</span><b>First SAPBIO adopted</b><span class="d">Barcelona Convention</span></li>
                <li><span class="y">2018</span><b>SAPBIO evaluated</b><span class="d">Period 2004–2018</span></li>
                <li><span class="y">2019</span><b>Post-2020 SAPBIO requested</b><span class="d">COP 21 · IG.24/7</span></li>
                <li><span class="y">2021</span><b>Post-2020 SAPBIO adopted</b><span class="d">COP 22 · IG.25/11</span></li>
                <li class="g"><span class="y">2022</span><b>Kunming-Montreal GBF</b><span class="d">CBD COP 15 · 15/4</span></li>
                <li><span class="y">2025</span><b>Mid-term assessment</b><span class="d">Start-up activities</span></li>
                <li><span class="y">2027</span><b>Review</b><span class="d">Results for 2027</span></li>
                <li><span class="y">2030</span><b>Review · targets due</b><span class="d">Vision to 2050</span></li>
            </ol>
        </div>
    </header>

    <section class="sp-stack">
        <span class="sp-eyebrow">Structure</span>
        <h2>Built on the same ladder as the CBD framework</h2>
        <p>The programme follows the hierarchy used by the CBD: a long-term vision, a mission for this decade, goals, targets and actions. Its three goals are adapted from those of the global framework, and its targets are meant to be specific, measurable and time-bound, with expected results set for both 2027 and 2030.</p>
        <div class="sp-ladder">
            <div><span class="k">Vision</span><span class="n">2050</span><p>Marine and coastal biodiversity is valued, conserved, restored and wisely used, sustaining a healthy Mediterranean for nature and people.</p></div>
            <div><span class="k">Mission</span><span class="n">2030</span><p>Start to reverse the loss of biodiversity and put the Mediterranean on the path to recovery.</p></div>
            <div><span class="k">Goals</span><span class="n">3</span><p>Reduce threats · meet people's needs · enable transformative change.</p></div>
            <div><span class="k">Targets</span><span class="n">27</span><p>Grouped under 10 headings drawn from the sub-regional assessments.</p></div>
            <div><span class="k">Actions</span><span class="n">42</span><p>About a third regional, the rest mainly national, each with results for 2027 and 2030.</p></div>
        </div>
        <div class="sp-goals">
            <div class="sp-goal">
                <span class="k">Goal 1 · 8 targets</span>
                <h3>Reduce the threats to biodiversity</h3>
                <ul>
                    <li><span>1.1</span>Specific pressures on protected species and habitats</li>
                    <li class="on"><span>1.2</span>Non-indigenous and invasive species</li>
                    <li><span>1.3</span>Pollution control</li>
                    <li><span>1.4</span>30% protected through MCPAs and OECMs</li>
                    <li><span>1.5</span>Areas with enhanced protection</li>
                    <li><span>1.6</span>Ecosystem restoration</li>
                    <li><span>1.7</span>Good Environmental Status</li>
                    <li><span>1.8</span>Climate change</li>
                </ul>
            </div>
            <div class="sp-goal">
                <span class="k">Goal 2 · 9 targets</span>
                <h3>Ensure biodiversity meets people's needs</h3>
                <ul>
                    <li><span>2.1</span>Knowledge on threatened species</li>
                    <li><span>2.2</span>Knowledge on threatened habitats</li>
                    <li><span>2.3</span>Knowledge sharing in an open platform</li>
                    <li><span>2.4</span>Fishing gears, by-catch and IUU fishing</li>
                    <li><span>2.5</span>Small-scale fisheries</li>
                    <li><span>2.6</span>Sustainable aquaculture</li>
                    <li><span>2.7</span>Ecosystem approach and spatial planning</li>
                    <li><span>2.8</span>Cross-sectoral integration</li>
                    <li><span>2.9</span>Governance and participation</li>
                </ul>
            </div>
            <div class="sp-goal">
                <span class="k">Goal 3 · 10 targets</span>
                <h3>Enable transformative change</h3>
                <ul>
                    <li><span>3.1</span>IMAP compliance</li>
                    <li><span>3.2</span>SAPBIO assessment and reporting</li>
                    <li><span>3.3</span>Means for assessment</li>
                    <li><span>3.4</span>Capacity development</li>
                    <li><span>3.5</span>Networking and knowledge sharing</li>
                    <li><span>3.6</span>Public awareness</li>
                    <li><span>3.7</span>Outreach and education</li>
                    <li><span>3.8</span>Employment in conservation</li>
                    <li><span>3.9</span>Sustainable funding</li>
                    <li><span>3.10</span>Cooperation</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="sp-stack">
        <span class="sp-eyebrow">Focus · Target 1.2 and four actions</span>
        <h2>What SAPBIO commits to on non-indigenous species</h2>
        <p>SAPBIO describes invasive species as one of the main threats to Mediterranean marine biodiversity, spread by shipping through ballast water and hull fouling, by corridors and waterways, by aquaculture and by the trade in live organisms, and amplified by warming waters. It calls for priority species and pathways to be listed in every country and for data to be shared through Mediterranean networks such as MAMIAS.</p>
        <div class="sp-target">
            <span class="k">Target 1.2 · by 2030</span>
            <h3>Prevent, manage and control NIS and their introduction pathways</h3>
            <p class="sp-small">Minimise the impact of non-indigenous species, and of invasive species in particular, on ecosystem integrity, through three levers:</p>
            <div class="sp-levers">
                <div class="sp-lever">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><path d="M20 5 L33 10 V19 C33 27 27 33 20 36 C13 33 7 27 7 19 V10 Z" fill="none" stroke="var(--sp-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M12 22 q4 -5 8 0 t8 0" fill="none" stroke="var(--sp-teal-deco)" stroke-width="2"/></svg>
                    <span class="k">i</span><b>Protect the most vulnerable ecosystems</b>
                </div>
                <div class="sp-lever">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><path d="M5 25 H35 L31 32 H9 Z" fill="none" stroke="var(--sp-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M13 25 V14 H27 V25" fill="none" stroke="var(--sp-teal-deco)" stroke-width="2"/><path d="M17 20 q3 4 6 0" fill="none" stroke="var(--status-invasive-text)" stroke-width="1.8"/><path d="M20 14 V8" stroke="var(--sp-teal-deco)" stroke-width="2"/></svg>
                    <span class="k">ii</span><b>Implement the regional ballast water strategy in every country</b>
                </div>
                <div class="sp-lever">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><path d="M6 20 H16 M24 20 H34" stroke="var(--sp-teal-deco)" stroke-width="2" stroke-linecap="round"/><path d="M14 10 L26 30 M26 10 L14 30" stroke="var(--sp-teal-deco)" stroke-width="2" stroke-linecap="round"/><rect x="16" y="16" width="8" height="8" fill="var(--sp-surface)" stroke="var(--status-invasive-text)" stroke-width="1.8"/></svg>
                    <span class="k">iii</span><b>Manage the other pathways of introduction</b>
                </div>
            </div>
        </div>
        <figure>
            <div class="sp-table">
                <table class="sp-actions">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th class="s1">Start-up activities<span>Checked at the mid-term assessment</span></th>
                            <th class="s2">Expected by 2027<span>Checked at the 2027 review</span></th>
                            <th class="s3">Expected by 2030<span>Checked at the 2030 review</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b>4 · NIS/IAS commitment</b>Ratify the IMO Ballast Water Management Convention and adopt the regional ballast water strategy (2022–2027)<br><span class="sp-prio">High · national</span></td>
                            <td>Countries have started the steps to write the BWM Convention and the biofouling guidelines into national law.</td>
                            <td>Most countries have taken those steps.</td>
                            <td>All countries cooperate in enforcing the Mediterranean Ballast Water Management Strategy.</td>
                        </tr>
                        <tr>
                            <td><b>5 · NIS/IAS capacity</b>Strengthen national capacity to deal with marine alien species<br><span class="sp-prio vh">Very high · regional and national</span></td>
                            <td>Countries have started baseline studies: year of first record, pathway and its certainty, population status.</td>
                            <td>Most countries have baseline studies with dated, georeferenced records and run monitoring within IMAP.</td>
                            <td>All countries monitor alien species, their pathways and population trends within IMAP, including species used in aquaculture.</td>
                        </tr>
                        <tr>
                            <td><b>6 · NIS/IAS control</b>Take field action to mitigate the impact of NIS and IAS<br><span class="sp-prio">High · national</span></td>
                            <td>Most countries have identified vulnerable areas and priority sites and started monitoring, especially at ports and entry pathways.</td>
                            <td>A significant reduction in the rate of new introductions, and control or eradication of the most problematic species in at least 50% of priority sites.</td>
                            <td>Impacts prevented in 100% of the most vulnerable areas, 50% fewer protected species threatened, 50% of the most significant pathways managed.</td>
                        </tr>
                        <tr class="on">
                            <td><b>20 · NIS/IAS database</b>Develop MAMIAS, the shared georeferenced database, to monitor status and pathways and support early warning<br><span class="sp-prio vh">Very high · regional</span></td>
                            <td>National baselines and early-warning systems in place, and NIS data starting to flow into MAMIAS.</td>
                            <td>NIS data are shared through MAMIAS, with online tools and web services to search and extract them.</td>
                            <td>All Mediterranean countries continuously monitor NIS status and pathways and share them in MAMIAS.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <figcaption>The four NIS actions of Annex III, read left to right. Each stage is the reference for one of the three reviews described below.</figcaption>
        </figure>
        <p class="sp-small">According to the 2023 Mediterranean Quality Status Report (MED QSR), 1,011 non-indigenous species have been recorded in Mediterranean waters, 748 of them established; see the <a href="/pages/imap">IMAP page</a>.</p>
    </section>

    <section class="sp-stack">
        <span class="sp-eyebrow">Analysis · SAPBIO and the Kunming-Montreal GBF</span>
        <h2>SAPBIO Target 1.2 next to GBF Target 6</h2>
        <p>SAPBIO was adopted in December 2021, a year before the CBD agreed the Kunming-Montreal Global Biodiversity Framework (GBF) in December 2022. Its own correspondence table therefore links Target 1.2 to Target 5 of the draft framework, which became Target 6 in the final text.</p>
        <p>Both texts follow the same logic of pathways, prevention, then eradication and control. Where they differ is in what they count. The GBF sets a single headline number for the rate of new introductions and establishments, while SAPBIO counts pathways managed, sites protected and protected species relieved.</p>
        <div class="sp-table">
            <table class="sp-vs">
                <thead><tr><th>Element</th><th>Post-2020 SAPBIO (2021)</th><th>Kunming-Montreal GBF (2022)</th></tr></thead>
                <tbody>
                    <tr><td>Pathways</td><td>Ballast water strategy in all countries; 50% of the most significant pathways effectively managed by 2030 (Action 6)</td><td>Identify and manage pathways of introduction</td></tr>
                    <tr><td>New introductions</td><td>A significant reduction in the rate of new introductions by 2027, with no fixed percentage</td><td>Rate of introduction and establishment of other known or potential invasive species reduced by at least 50% by 2030</td></tr>
                    <tr><td>Priority species</td><td>Control or eradication of the most problematic species, in at least 50% of priority sites by 2027</td><td>Prevent introduction and establishment of priority invasive species</td></tr>
                    <tr><td>Priority places</td><td>Impacts prevented in 100% of the most vulnerable areas by 2030</td><td>Eradicate or control invasive species, especially in priority sites such as islands</td></tr>
                    <tr><td>Biodiversity outcome</td><td>50% fewer protected species threatened by invasive species by 2030</td><td>Eliminate, minimise, reduce or mitigate impacts on biodiversity and ecosystem services</td></tr>
                    <tr><td>Measured by</td><td>IMAP Common Indicator 6 and national data shared in MAMIAS (Actions 5 and 20)</td><td>Headline indicator 6.1, rate of invasive alien species establishment</td></tr>
                </tbody>
            </table>
        </div>
        <div class="sp-two">
            <div class="sp-stack">
                <div class="sp-def"><h3>What it means in practice</h3><p>To report on GBF Target 6, a Mediterranean country needs a dated, validated record of each first introduction and establishment. That is exactly what SAPBIO Actions 5 and 20 ask for: baseline studies with the year of first record, the pathway and its certainty, shared through MAMIAS.</p></div>
                <div class="sp-def"><h3>Where MAMIAS helps</h3><p>A single regional database lets the same records serve three reporting duties at once: SAPBIO progress at the Barcelona Convention, IMAP Common Indicator 6, and the GBF national report on headline indicator 6.1.</p></div>
            </div>
            <figure>
                <div class="sp-fig">
                    <svg viewBox="0 0 460 300" width="100%" role="img" aria-labelledby="sp-flow-t">
                        <title id="sp-flow-t">One validated NIS record in MAMIAS feeds three reports: SAPBIO progress, IMAP Common Indicator 6 and GBF headline indicator 6.1.</title>
                        <defs><marker id="sp-arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0 0 L10 5 L0 10 Z" fill="var(--sp-teal-deco)"/></marker></defs>
                        <rect x="8" y="112" width="120" height="76" fill="var(--sp-surface)" stroke="var(--sp-line)"/>
                        <text x="68" y="140" text-anchor="middle" class="lbl">NIS record</text>
                        <text x="68" y="158" text-anchor="middle" class="sub">first year · pathway</text>
                        <text x="68" y="173" text-anchor="middle" class="sub">location · certainty</text>
                        <path d="M128 150 H160" stroke="var(--sp-teal-deco)" stroke-width="2" marker-end="url(#sp-arr)"/>
                        <rect x="164" y="104" width="112" height="92" fill="var(--sp-teal-fill)"/>
                        <text x="220" y="146" text-anchor="middle" class="lbl on">MAMIAS</text>
                        <text x="220" y="164" text-anchor="middle" class="sub on">validated · shared</text>
                        <path d="M276 130 C300 130 300 52 318 52" fill="none" stroke="var(--sp-teal-deco)" stroke-width="2" marker-end="url(#sp-arr)"/>
                        <path d="M276 150 H318" stroke="var(--sp-teal-deco)" stroke-width="2" marker-end="url(#sp-arr)"/>
                        <path d="M276 170 C300 170 300 248 318 248" fill="none" stroke="var(--sp-teal-deco)" stroke-width="2" marker-end="url(#sp-arr)"/>
                        <rect x="322" y="24" width="130" height="56" fill="var(--sp-surface)" stroke="var(--sp-navy)" stroke-width="1.5"/>
                        <text x="387" y="47" text-anchor="middle" class="lbl">SAPBIO progress</text>
                        <text x="387" y="64" text-anchor="middle" class="sub">Barcelona Convention</text>
                        <rect x="322" y="122" width="130" height="56" fill="var(--sp-tint)" stroke="var(--sp-teal-deco)" stroke-width="1.5"/>
                        <text x="387" y="145" text-anchor="middle" class="lbl">IMAP · CI6</text>
                        <text x="387" y="162" text-anchor="middle" class="sub">EO2 assessment</text>
                        <rect x="322" y="220" width="130" height="56" fill="var(--sp-sunken)" stroke="var(--sp-muted)" stroke-width="1.5"/>
                        <text x="387" y="243" text-anchor="middle" class="lbl">GBF indicator 6.1</text>
                        <text x="387" y="260" text-anchor="middle" class="sub">CBD national report</text>
                    </svg>
                </div>
                <figcaption>One record, three reports.</figcaption>
            </figure>
        </div>
        <div class="sp-cta">
            <a class="sp-btn p" href="/pages/data">Explore NIS data</a>
            <a class="sp-btn" href="/pages/imap">Read about IMAP</a>
        </div>
    </section>

    <section class="sp-stack">
        <span class="sp-eyebrow">Implementation</span>
        <h2>Who carries it out, and how progress is checked</h2>
        <div class="sp-two">
            <div class="sp-def"><h3>Countries</h3><p>Identify national contributions and update their National Biodiversity Strategies and Action Plans, then report on progress to the Barcelona Convention COP.</p></div>
            <div class="sp-def"><h3>SPA/RAC</h3><p>Supports implementation through technical cooperation, capacity building and resource mobilisation, with a network of SAPBIO National Correspondents in every country.</p></div>
        </div>
        <figure>
            <div class="sp-review" role="list" aria-label="Post-2020 SAPBIO review stages">
                <div class="st s1" role="listitem">
                    <span class="y">2025</span>
                    <h3>Mid-term assessment</h3>
                    <p>Focuses on the Post-2020 SAPBIO start-up activities: whether countries have started the baselines, legal steps and monitoring each action calls for.</p>
                </div>
                <div class="st s2" role="listitem">
                    <span class="y">2027</span>
                    <h3>Review</h3>
                    <p>Measures progress against the expected results for 2027.</p>
                </div>
                <div class="st s3" role="listitem">
                    <span class="y">2030</span>
                    <h3>Review</h3>
                    <p>Measures achievement of the expected results and targets for 2030.</p>
                </div>
                <div class="br b1"><span>Reference</span>Start-up activities, Annex III</div>
                <div class="br b2"><span>Reference</span>Monitoring Framework for the assessment of the collective implementation of the Post-2020 SAPBIO, in line with the monitoring framework of the Kunming-Montreal Global Biodiversity Framework</div>
            </div>
        </figure>
    </section>

    <section class="sp-end sp-stack">
        <span class="sp-eyebrow">Sources</span>
        <ol class="sp-sources">
            <li>UNEP/MAP, Decision IG.25/11, Post-2020 Strategic Action Programme for the Conservation of Biodiversity and Sustainable Management of Natural Resources in the Mediterranean Region (Post-2020 SAPBIO), COP 22, 2021. UNEP/MED IG.25/27. <a href="https://wedocs.unep.org/bitstream/handle/20.500.11822/37133/21ig25_27_2511_eng.pdf" target="_blank" rel="noopener">Document</a></li>
            <li>Convention on Biological Diversity, Decision 15/4, Kunming-Montreal Global Biodiversity Framework, 2022.</li>
            <li>UNEP/MAP, Decision IG.27/6, EcAp Policy and Roadmap 2026–2035 and IMAP, COP 24, 2025.</li>
            <li>UNEP/MAP, 2023 Mediterranean Quality Status Report (MED QSR), for all figures on non-indigenous species. <a href="https://medqsr2023.info-rac.org/" target="_blank" rel="noopener">medqsr2023.info-rac.org</a></li>
        </ol>
    </section>

</div>
HTML;
    }
}
