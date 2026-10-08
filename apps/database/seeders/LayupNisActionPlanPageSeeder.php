<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes /pages/mediterranean-action-plan (Resources › The Mediterranean
 * Action Plan) with the updated Action Plan concerning Species Introductions
 * and Invasive Species in the Mediterranean Sea: Decision IG.26/5 (COP 23,
 * 2023), Annex IV. Focus on its objectives, priorities, actions and the
 * 2024–2028 implementation timetable.
 *
 * Same construction as LayupSpaBdProtocolPageSeeder: one html widget whose
 * styles are scoped under `.mamias-nap` and read the site tokens from app.css.
 * No <h1>: the site's page header shows the title.
 *
 * Overwrites the page content. Run: php artisan db:seed --class=LayupNisActionPlanPageSeeder
 */
class LayupNisActionPlanPageSeeder extends Seeder
{
    public const SLUG = 'pages/mediterranean-action-plan';

    public function run(): void
    {
        Page::withTrashed()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'deleted_at' => null,
                'title' => 'Action Plan concerning Species Introductions and Invasive Species in the Mediterranean Sea',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => 'The updated NIS Action Plan of the Barcelona Convention (Decision IG.26/5, 2023): objectives, national and regional priorities, actions and the 2024–2028 timetable.',
                ],
                'content' => [
                    'rows' => [[
                        'id' => 'row_nap',
                        'settings' => ['gap' => 'gap-0'],
                        'columns' => [[
                            'id' => 'col_nap',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => 'widget_nap',
                                'type' => 'html',
                                'data' => ['content' => self::withCovers(self::html())],
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
.mamias-nap {
    --np-ink: var(--foreground);
    --np-ink-2: #47606b;
    --np-muted: var(--muted-foreground);
    --np-line: var(--border);
    --np-line-soft: var(--secondary);
    --np-surface: var(--card);
    --np-sunken: var(--muted);
    --np-tint: var(--accent);
    --np-teal-text: var(--mamias-teal-600);
    --np-teal-deco: var(--mamias-teal-500);
    --np-teal-fill: var(--mamias-teal-600);
    --np-on-teal: #fff;
    --np-navy: #2a2b74;
    --np-on-navy: #fff;
    display: grid; gap: 64px; padding-block: 24px 64px; color: var(--np-ink);
    font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-nap {
    --np-ink-2: #b3c7ce;
    --np-teal-text: var(--mamias-teal-300);
    --np-teal-deco: var(--mamias-teal-400);
    --np-teal-fill: var(--mamias-teal-400);
    --np-on-teal: var(--background);
    --np-navy: #8f91d9;
    --np-on-navy: var(--background);
}
.mamias-nap * { box-sizing: border-box; }
.mamias-nap h2, .mamias-nap h3 { margin: 0; color: var(--np-ink); text-wrap: balance; }
.mamias-nap h2 { font-size: 20px; line-height: 28px; font-weight: 500; }
.mamias-nap h3 { font-size: 16px; line-height: 24px; font-weight: 500; }
.mamias-nap p { margin: 0; }
.mamias-nap ul { margin: 0; padding: 0; list-style: none; }
.mamias-nap .np-stack { display: grid; gap: 16px; }
.mamias-nap .np-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--np-muted); }
.mamias-nap .np-lede { color: var(--np-ink-2); }
.mamias-nap .np-small { font-size: 14px; line-height: 22px; color: var(--np-ink-2); }
.mamias-nap a { color: var(--np-teal-text); text-underline-offset: 2px; }
.mamias-nap a:focus-visible { outline: 2px solid var(--np-teal-deco); outline-offset: 2px; }
.mamias-nap figure { margin: 0; display: grid; gap: 12px; }
.mamias-nap figcaption { font-size: 14px; line-height: 22px; color: var(--np-muted); }
.mamias-nap .np-two { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; align-items: start; }
@media (max-width: 760px) { .mamias-nap .np-two { grid-template-columns: minmax(0, 1fr); } }
.mamias-nap .np-scroll { overflow-x: auto; }
.mamias-nap .np-k { font: 400 12px/18px var(--font-mono); color: var(--np-teal-text); }
.mamias-nap svg text { font-family: var(--font-sans); fill: var(--np-ink); }
.mamias-nap svg .lbl { font-size: 13px; font-weight: 500; }
.mamias-nap svg .sub { font-size: 11px; fill: var(--np-muted); }
.mamias-nap svg text.on { fill: var(--np-on-teal); }

/* Timeline: teal-500 rule and markers, teal-600 years. */
.mamias-nap .np-tl { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(7, minmax(8.5rem, 1fr)); min-width: 62rem; }
.mamias-nap .np-tl li { position: relative; padding: 28px 16px 0 0; display: grid; gap: 2px; align-content: start; }
.mamias-nap .np-tl li::before { content: ""; position: absolute; left: 0; right: 0; top: 7px; height: 2px; background: var(--np-teal-deco); }
.mamias-nap .np-tl li::after { content: ""; position: absolute; left: 0; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--np-surface); border: 2px solid var(--np-teal-deco); }
.mamias-nap .np-tl .y { font: 400 20px/28px var(--font-mono); letter-spacing: -.4px; color: var(--np-teal-text); font-variant-numeric: tabular-nums; }
.mamias-nap .np-tl b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-nap .np-tl .d { font: 400 12px/18px var(--font-mono); color: var(--np-muted); }
@media (max-width: 640px) {
    .mamias-nap .np-tl { grid-template-columns: 1fr; min-width: 0; border-left: 2px solid var(--np-teal-deco); margin-left: 5px; }
    .mamias-nap .np-tl li { padding: 0 0 16px 20px; }
    .mamias-nap .np-tl li::before { display: none; }
    .mamias-nap .np-tl li::after { left: -8px; top: 8px; }
}

/* Context figures */
.mamias-nap .np-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr)); gap: 1px; background: var(--np-line); border: 1px solid var(--np-line); }
.mamias-nap .np-fact { background: var(--np-surface); padding: 14px 16px; display: grid; gap: 2px; align-content: start; }
.mamias-nap .np-fact b { font: 400 24px/32px var(--font-mono); letter-spacing: -.5px; }
.mamias-nap .np-fact span { font-size: 14px; line-height: 22px; color: var(--np-ink-2); }
.mamias-nap .np-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.mamias-nap .np-chip { font: 500 11px/16px var(--font-sans); letter-spacing: .08em; text-transform: uppercase; padding: 3px 8px; border: 1px solid var(--np-line); background: var(--np-surface); color: var(--np-ink-2); }

/* Objectives diagram */
.mamias-nap .np-goal { background: var(--np-teal-fill); color: var(--np-on-teal); padding: 16px 20px; display: grid; gap: 4px; }
.mamias-nap .np-goal .np-k { color: var(--np-on-teal); }
.mamias-nap .np-goal h3 { color: var(--np-on-teal); }
.mamias-nap .np-axes { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1px; background: var(--np-line); border: 1px solid var(--np-line); border-top: 0; position: relative; }
.mamias-nap .np-axis { background: var(--np-surface); padding: 20px; display: grid; gap: 12px; align-content: start; position: relative; }
.mamias-nap .np-axis::before { content: ""; position: absolute; top: 0; left: 50%; width: 2px; height: 14px; background: var(--np-teal-deco); }
.mamias-nap .np-axis .q { font-size: 14px; line-height: 22px; color: var(--np-ink-2); border-left: 2px solid var(--np-teal-deco); padding-left: 12px; }
.mamias-nap .np-axis ul { display: grid; gap: 6px; font-size: 14px; line-height: 22px; }
.mamias-nap .np-axis li { display: grid; grid-template-columns: 16px 1fr; gap: 8px; }
.mamias-nap .np-axis li::before { content: ""; width: 8px; height: 8px; margin-top: 7px; background: var(--np-teal-deco); }
.mamias-nap .np-steps { display: grid; gap: 0; counter-reset: s; }
.mamias-nap .np-steps li { display: grid; grid-template-columns: 32px 1fr; gap: 12px; padding: 10px 0; border-top: 1px solid var(--np-line-soft); font-size: 14px; line-height: 22px; }
.mamias-nap .np-steps li::before { counter-increment: s; content: counter(s); width: 28px; height: 28px; display: grid; place-items: center; font: 400 13px/1 var(--font-mono); color: var(--np-on-teal); background: var(--np-teal-fill); margin: 0; }
@media (max-width: 760px) { .mamias-nap .np-axes { grid-template-columns: minmax(0, 1fr); } }

/* Priority matrix */
.mamias-nap .np-matrix { min-width: 44rem; border-collapse: collapse; width: 100%; font-size: 13px; line-height: 20px; }
.mamias-nap .np-matrix th, .mamias-nap .np-matrix td { text-align: left; vertical-align: top; padding: 8px 12px; border-bottom: 1px solid var(--np-line-soft); }
.mamias-nap .np-matrix thead th { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--np-muted); background: var(--np-sunken); border-bottom: 1px solid var(--np-line); }
.mamias-nap .np-matrix tbody th { width: 19%; font-weight: 500; white-space: nowrap; }
.mamias-nap .np-matrix tbody th svg { width: 18px; height: 18px; display: inline-block; vertical-align: -4px; margin-right: 8px; }
.mamias-nap .np-matrix td ul { display: grid; gap: 2px; }
.mamias-nap .np-matrix td li { display: grid; grid-template-columns: 12px 1fr; gap: 6px; color: var(--np-ink-2); }
.mamias-nap .np-matrix td li::before { content: ""; width: 5px; height: 5px; margin-top: 8px; background: var(--np-teal-deco); }
.mamias-nap .np-matrix td.n { color: var(--np-muted); }
.mamias-nap .np-box { border: 1px solid var(--np-line); background: var(--np-surface); }
.mamias-nap .np-matrix tbody tr:last-child th, .mamias-nap .np-matrix tbody tr:last-child td { border-bottom: 0; }

/* Funnel */
.mamias-nap .np-funnel { display: grid; gap: 4px; justify-items: center; }
.mamias-nap .np-funnel div { width: var(--w); max-width: 100%; padding: 10px 16px; display: grid; gap: 2px; text-align: center; background: var(--np-tint); border: 1px solid var(--np-teal-deco); }
.mamias-nap .np-funnel div:last-child { background: var(--np-teal-fill); border-color: var(--np-teal-fill); }
.mamias-nap .np-funnel div:last-child b, .mamias-nap .np-funnel div:last-child span { color: var(--np-on-teal); }
.mamias-nap .np-funnel b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-nap .np-funnel span { font-size: 13px; line-height: 20px; color: var(--np-ink-2); }
@media (max-width: 640px) { .mamias-nap .np-funnel div { width: 100%; } }

/* Publications: cover on the left, details on the right. */
.mamias-nap .np-pubs { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 30rem), 1fr)); gap: 1px; background: var(--np-line); border: 1px solid var(--np-line); }
.mamias-nap .np-pub { background: var(--np-surface); padding: 20px; display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 20px; align-items: start; }
.mamias-nap .np-pub .cv { display: block; border: 1px solid var(--np-line); line-height: 0; }
.mamias-nap .np-pub .cv:hover { border-color: var(--np-teal-deco); }
.mamias-nap .np-pub img { width: 100%; height: auto; display: block; }
.mamias-nap .np-pub .bd { display: grid; gap: 10px; align-content: start; }
.mamias-nap .np-pub .bd p { font-size: 14px; line-height: 22px; color: var(--np-ink-2); }
.mamias-nap .np-pub .mt { font: 400 12px/18px var(--font-mono); color: var(--np-muted) !important; }
.mamias-nap .np-pub .tc { display: grid; gap: 4px; font-size: 13px; line-height: 20px; color: var(--np-ink-2); border-top: 1px solid var(--np-line-soft); padding-top: 10px; }
.mamias-nap .np-pub .tc li { display: grid; grid-template-columns: 14px minmax(0, 1fr); gap: 6px; }
.mamias-nap .np-pub .tc li::before { content: ""; width: 6px; height: 6px; margin-top: 7px; background: var(--np-teal-deco); }
.mamias-nap .np-pub .pl { border-left: 2px solid var(--np-teal-deco); padding-left: 10px; font-size: 13px !important; line-height: 20px !important; }
.mamias-nap .np-pub .dl { justify-self: start; display: inline-flex; align-items: center; gap: 10px; height: 40px; padding: 0 16px; border: 1px solid var(--np-line); color: var(--np-teal-text); text-decoration: none; font: 500 14px/22px var(--font-sans); }
.mamias-nap .np-pub .dl:hover { border-color: var(--np-teal-deco); }
.mamias-nap .np-pub .dl span { font: 400 12px/18px var(--font-mono); color: var(--np-muted); }
@media (max-width: 520px) { .mamias-nap .np-pub { grid-template-columns: 110px minmax(0, 1fr); gap: 14px; padding: 16px; } }

/* Actions by level: a header band per level, then icon cards per group. */
.mamias-nap .np-level { display: grid; gap: 10px; }
.mamias-nap .np-lhead { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 12px; color: var(--np-teal-deco); }
.mamias-nap .np-lhead svg { width: 24px; height: 24px; flex: none; }
.mamias-nap .np-lhead > div { display: contents; }
.mamias-nap .np-lhead h3 { font-size: 18px; line-height: 26px; color: var(--np-ink); }
.mamias-nap .np-lhead p { font-size: 14px; line-height: 22px; color: var(--np-ink-2); }
.mamias-nap .np-lhead p::before { content: "·"; margin-right: 12px; color: var(--np-muted); }
.mamias-nap .np-lhead .n { font: 400 14px/22px var(--font-mono); color: var(--np-teal-text); }
.mamias-nap .np-lhead .n::before { content: "·"; margin-right: 12px; color: var(--np-muted); font-family: var(--font-sans); }
.mamias-nap .np-lhead .n small { font: inherit; margin-left: 4px; }
.mamias-nap .np-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr)); gap: 1px; background: var(--np-line); border: 1px solid var(--np-line); }
.mamias-nap .np-card { background: var(--np-surface); padding: 16px 18px 18px; display: grid; gap: 10px; align-content: start; }
.mamias-nap .np-card .t { display: grid; grid-template-columns: 32px minmax(0, 1fr) auto; gap: 10px; align-items: center; }
.mamias-nap .np-card .t svg { width: 32px; height: 32px; }
.mamias-nap .np-card .t b { font-weight: 500; font-size: 15px; line-height: 22px; }
.mamias-nap .np-card .t .c { font: 400 12px/18px var(--font-mono); color: var(--np-muted); white-space: nowrap; }
.mamias-nap .np-card .l { font: 400 12px/18px var(--font-mono); color: var(--np-teal-text); }
.mamias-nap .np-card ul { display: grid; gap: 8px; font-size: 14px; line-height: 22px; color: var(--np-ink-2); border-top: 1px solid var(--np-line-soft); padding-top: 10px; }
.mamias-nap .np-card li { display: grid; grid-template-columns: 16px minmax(0, 1fr); gap: 8px; }
.mamias-nap .np-card li::before { content: ""; width: 12px; height: 12px; margin-top: 5px; border: 1.5px solid var(--np-teal-deco); background: linear-gradient(135deg, transparent 45%, var(--np-teal-deco) 45%, var(--np-teal-deco) 55%, transparent 55%); }
.mamias-nap .np-tag { display: inline-block; margin-left: 6px; padding: 0 5px; font: 500 10px/16px var(--font-sans); letter-spacing: .1em; text-transform: uppercase; color: var(--np-on-teal); background: var(--np-teal-fill); vertical-align: 1px; }
.mamias-nap .np-card.on { background: var(--np-tint); }
.mamias-nap .np-link { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); gap: 12px; align-items: center; padding: 8px 0; font: 400 12px/18px var(--font-mono); color: var(--np-muted); }
.mamias-nap .np-link::before, .mamias-nap .np-link::after { content: ""; height: 1px; background: var(--np-line); }
@media (max-width: 560px) { .mamias-nap .np-lhead { grid-template-columns: auto minmax(0, 1fr); } .mamias-nap .np-lhead .n { grid-column: 1 / -1; text-align: left; } }
.mamias-nap .np-end { border-top: 1px solid var(--border); padding-top: 32px; }
</style>

<div class="mamias-nap">

    <header class="np-stack">
        <span class="np-eyebrow">SPA/BD Protocol · Article 13 · Decision IG.26/5, Annex IV</span>
        <p class="np-lede">The Action Plan concerning Species Introductions and Invasive Species in the Mediterranean Sea is how the Contracting Parties to the Barcelona Convention organise their joint work on non-indigenous species (NIS). The updated plan was adopted at COP 23 in 2023 and runs for five years. It turns Article 13 of the SPA/BD Protocol into concrete national and regional actions, coordinated by SPA/RAC.</p>
        <div class="np-scroll">
            <ol class="np-tl" aria-label="NIS Action Plan timeline">
                <li><span class="y">1975</span><b>Mediterranean Action Plan</b><span class="d">First Regional Seas Programme</span></li>
                <li><span class="y">1995</span><b>SPA/BD Protocol</b><span class="d">Article 13 on NIS</span></li>
                <li><span class="y">2003</span><b>First NIS Action Plan</b><span class="d">Regional coordination</span></li>
                <li><span class="y">2017</span><b>First update</b><span class="d">Actions for 2017–2020</span></li>
                <li><span class="y">2022</span><b>BWM Strategy</b><span class="d">Ballast water, 2022–2027</span></li>
                <li><span class="y">2023</span><b>Updated plan adopted</b><span class="d">COP 23 · IG.26/5</span></li>
                <li><span class="y">2024–2028</span><b>Five-year implementation</b><span class="d">Progress report at the end</span></li>
            </ol>
        </div>
    </header>

    <section class="np-stack">
        <span class="np-eyebrow">Why an update</span>
        <h2>From building capacity to managing pathways and impacts</h2>
        <p>The 2017 plan focused on capacity, legislation, baseline studies, monitoring and data sharing, goals the plan reports as largely achieved. The updated plan moves on to concrete management of pathways and a sharp reduction of invasive populations and their impacts, in line with Target 6 of the global biodiversity framework, Target 1.2 of the Post-2020 SAPBIO and the EU Biodiversity Strategy for 2030. Shipping vectors are handled by the Ballast Water Management Strategy (2022–2027); this plan covers the other pathways and the impacts of priority invasive species.</p>
        <div class="np-facts">
            <div class="np-fact"><b>1,011</b><span>non-indigenous species recorded in Mediterranean waters</span></div>
            <div class="np-fact"><b>748</b><span>of them considered established, nearly 74%</span></div>
            <div class="np-fact"><b>×2</b><span>established species since 2012</span></div>
        </div>
        <p class="np-small" style="color: var(--np-muted)">Source: 2023 Mediterranean Quality Status Report (MED QSR).</p>
        <div class="np-chips" aria-label="Main pathways named in the plan">
            <span class="np-chip">Shipping · ballast water and hull fouling</span>
            <span class="np-chip">Corridors</span>
            <span class="np-chip">Aquaculture</span>
            <span class="np-chip">Aquarium and live food trade</span>
            <span class="np-chip">Fishing activities and aquarium exhibits</span>
        </div>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Diagram 1 · Objectives</span>
        <h2>One main objective, two operational axes</h2>
        <figure>
            <div>
                <div class="np-goal">
                    <span class="np-k">Main objective</span>
                    <h3>Coordinated efforts and management measures across the Mediterranean, to make progress towards Good Environmental Status for non-indigenous species</h3>
                </div>
                <div class="np-axes">
                    <div class="np-axis">
                        <span class="np-k">Operational objective 2.1</span>
                        <h3>Introductions and spread are minimised</h3>
                        <p class="q">Introduction and spread of NIS linked to human activities are minimised, in particular for potential invasive species.</p>
                        <ul>
                            <li>Support IMAP and the operationalisation of its indicators</li>
                            <li>Develop a regional early-warning system within MAMIAS</li>
                            <li>Keep producing guidelines and technical documentation</li>
                            <li>Strengthen the legal and institutional framework for pathway management</li>
                            <li>Support the Mediterranean BWM Strategy (2022–2027)</li>
                            <li>Promote voluntary codes of conduct where no legal framework exists</li>
                        </ul>
                    </div>
                    <div class="np-axis">
                        <span class="np-k">Operational objective 2.2</span>
                        <h3>Impacts on ecosystems are limited</h3>
                        <p class="q">The impact of non-indigenous, particularly invasive, species on ecosystems is limited, through prioritisation and impact quantification in three steps.</p>
                        <ol class="np-steps">
                            <li>Risk assessment and prioritisation, with an emphasis on prevention and mitigation</li>
                            <li>Identify the invasive population levels that cause unacceptable effects</li>
                            <li>Prepare and carry out rapid response and management plans for the most invasive NIS</li>
                        </ol>
                    </div>
                </div>
            </div>
            <figcaption>Both axes follow the Ecosystem Approach: Ecological Objective 2 and IMAP Common Indicator 6.</figcaption>
        </figure>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Infographic · Priorities</span>
        <h2>Who focuses on what</h2>
        <p>The plan splits its priorities between the countries and the regional level. Countries are asked to fill the data and knowledge gaps that impact assessments, horizon scanning and management depend on; the region builds the common methods, criteria and tools.</p>
        <div class="np-scroll np-box">
            <table class="np-matrix">
                <thead><tr><th>Theme</th><th>National priorities</th><th>Regional priorities</th></tr></thead>
                <tbody>
                    <tr>
                        <th><svg viewBox="0 0 28 28" aria-hidden="true"><rect x="4" y="5" width="20" height="18" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M8 18 L12 13 L16 15 L21 9" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg>Monitoring and data</th>
                        <td><ul><li>Regular NIS monitoring under national programmes</li><li>Updated baselines to MAMIAS and yearly data to the IMAP Info System</li></ul></td>
                        <td><ul><li>Refine IMAP targets and the impact side of CI6</li><li>Activate the updated version of MAMIAS</li></ul></td>
                    </tr>
                    <tr>
                        <th><svg viewBox="0 0 28 28" aria-hidden="true"><path d="M14 4 L25 23 H3 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M14 11 V16 M14 19 V20" stroke="var(--status-invasive-text)" stroke-width="2" stroke-linecap="round"/></svg>Risk and impacts</th>
                        <td><ul><li>Prioritisation, risk assessment and targeted impact research</li><li>Risk assessments of aquaculture, ornamental trade and live food trade</li></ul></td>
                        <td><ul><li>Criteria to identify and prioritise pathways, and their economic impact</li><li>Coordinate risk assessment methods for priority species</li><li>Coordinate targeted NIS impact studies</li></ul></td>
                    </tr>
                    <tr>
                        <th><svg viewBox="0 0 28 28" aria-hidden="true"><path d="M7 20 V13 a7 7 0 0 1 14 0 V20 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M4 20 H24 M12 24 H16" stroke="var(--np-teal-deco)" stroke-width="2"/></svg>Early warning and response</th>
                        <td><ul><li>Early-warning system and rapid response plans</li></ul></td>
                        <td><ul><li>Develop an early-warning system linked to MAMIAS</li></ul></td>
                    </tr>
                    <tr>
                        <th><svg viewBox="0 0 28 28" aria-hidden="true"><path d="M3 18 H25 L22 23 H6 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M9 18 V10 H19 V18" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg>Ballast water and shipping</th>
                        <td><ul><li>Ratify and implement the BWM Convention and the Mediterranean BWM Strategy and its Action Plan</li></ul></td>
                        <td><ul><li>Support the BWM Strategy and its Action Plan, with REMPEC</li></ul></td>
                    </tr>
                    <tr>
                        <th><svg viewBox="0 0 28 28" aria-hidden="true"><circle cx="10" cy="10" r="4" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><circle cx="19" cy="12" r="3" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M3 23 c1 -5 4 -7 7 -7 s6 2 7 7 M15 21 c1 -3 3 -4 4 -4 s4 1 5 4" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg>Capacity and awareness</th>
                        <td><ul><li>Training and awareness on risks, legal issues, best practice and management</li></ul></td>
                        <td><ul><li>Training for status assessments of aquaculture and the trade sectors</li><li>Training for targeted impact studies</li></ul></td>
                    </tr>
                    <tr>
                        <th><svg viewBox="0 0 28 28" aria-hidden="true"><circle cx="14" cy="14" r="10" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M4 14 H24 M14 4 c-4 5 -4 15 0 20 M14 4 c4 5 4 15 0 20" fill="none" stroke="var(--np-teal-deco)" stroke-width="1.5"/></svg>Cooperation</th>
                        <td class="n">—</td>
                        <td><ul><li>International cooperation and harmonisation with related policies</li></ul></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Diagram 2 · National prioritisation</span>
        <h2>From a long list to the species, sites and pathways to manage</h2>
        <div class="np-two">
            <figure>
                <div class="np-funnel" role="list" aria-label="Prioritisation steps">
                    <div role="listitem" style="--w: 100%"><b>1 · Horizon scanning</b><span>Existing NIS and likely future arrivals, including higher risk from climate change</span></div>
                    <div role="listitem" style="--w: 88%"><b>2 · Priority lists</b><span>High-risk species, prioritised for distribution and abundance monitoring</span></div>
                    <div role="listitem" style="--w: 76%"><b>3 · Risk assessment</b><span>Established protocols, taking the potential for management into account</span></div>
                    <div role="listitem" style="--w: 64%"><b>4 · Impact mapping with CIMPAL</b><span>Quantify and map impacts, find hotspots</span></div>
                    <div role="listitem" style="--w: 52%"><b>5 · Management targets</b><span>Priority species, sites and pathways</span></div>
                </div>
                <figcaption>The national prioritisation actions, in the order the plan describes them.</figcaption>
            </figure>
            <div class="np-stack">
                <p class="np-small">CIMPAL is a cumulative impact assessment method: it combines where invasive species occur, how strong their documented impacts are and how sensitive the local habitats are, to show where the pressure is highest.</p>
                <p class="np-small">The same logic applies to sectors. Countries assess the risks of aquaculture, the ornamental trade and the live food trade, and carry out environmental impact assessments before any action on a pathway that could increase introductions.</p>
                <p class="np-small">Targeted impact studies, in the field, in the laboratory and through modelling, then establish the abundance levels at which a priority species becomes unacceptable.</p>
            </div>
        </div>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Diagram 3 · Early warning</span>
        <h2>From the first sighting to a rapid response</h2>
        <p>The plan asks countries to set up reporting mechanisms among the people most likely to notice a new species first, to link them to a regional early-warning system within MAMIAS, and to cooperate with neighbouring states on new detections.</p>
        <figure>
            <div class="np-scroll">
                <svg viewBox="0 0 900 300" width="100%" style="min-width: 720px" role="img" aria-labelledby="np-ew-t">
                    <title id="np-ew-t">Fishers, divers, aquaculture operators, border officials and citizen scientists report sightings to a national reporting mechanism, which feeds the national early-warning system, linked to MAMIAS and to neighbouring states, leading to rapid response and management plans.</title>
                    <defs><marker id="np-arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0 0 L10 5 L0 10 Z" fill="var(--np-teal-deco)"/></marker></defs>
                    <g>
                        <rect x="10" y="20" width="150" height="40" fill="var(--np-surface)" stroke="var(--np-line)"/><text x="85" y="45" text-anchor="middle" class="lbl">Fishers</text>
                        <rect x="10" y="72" width="150" height="40" fill="var(--np-surface)" stroke="var(--np-line)"/><text x="85" y="97" text-anchor="middle" class="lbl">Divers</text>
                        <rect x="10" y="124" width="150" height="40" fill="var(--np-surface)" stroke="var(--np-line)"/><text x="85" y="149" text-anchor="middle" class="lbl">Aquaculture operators</text>
                        <rect x="10" y="176" width="150" height="40" fill="var(--np-surface)" stroke="var(--np-line)"/><text x="85" y="201" text-anchor="middle" class="lbl">Border officials</text>
                        <rect x="10" y="228" width="150" height="40" fill="var(--np-surface)" stroke="var(--np-line)"/><text x="85" y="253" text-anchor="middle" class="lbl">Citizen scientists</text>
                    </g>
                    <path d="M160 40 C190 40 190 144 214 144" fill="none" stroke="var(--np-line)" stroke-width="1.5"/>
                    <path d="M160 92 C190 92 190 144 214 144" fill="none" stroke="var(--np-line)" stroke-width="1.5"/>
                    <path d="M160 144 H214" fill="none" stroke="var(--np-line)" stroke-width="1.5"/>
                    <path d="M160 196 C190 196 190 144 214 144" fill="none" stroke="var(--np-line)" stroke-width="1.5"/>
                    <path d="M160 248 C190 248 190 144 214 144" fill="none" stroke="var(--np-line)" stroke-width="1.5"/>
                    <path d="M214 144 H226" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <rect x="230" y="114" width="150" height="60" fill="var(--np-surface)" stroke="var(--np-teal-deco)" stroke-width="1.5"/>
                    <text x="305" y="140" text-anchor="middle" class="lbl">National reporting</text>
                    <text x="305" y="157" text-anchor="middle" class="sub">sightings collected</text>
                    <path d="M380 144 H416" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <rect x="420" y="114" width="160" height="60" fill="var(--np-tint)" stroke="var(--np-teal-deco)" stroke-width="1.5"/>
                    <text x="500" y="140" text-anchor="middle" class="lbl">National early warning</text>
                    <text x="500" y="157" text-anchor="middle" class="sub">species expected next</text>
                    <path d="M500 114 V82" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <path d="M520 78 V110" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <rect x="420" y="18" width="160" height="60" fill="var(--np-teal-fill)"/>
                    <text x="500" y="44" text-anchor="middle" class="lbl on">MAMIAS</text>
                    <text x="500" y="61" text-anchor="middle" class="sub on">regional early warning</text>
                    <path d="M500 174 V206" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <path d="M520 210 V178" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <rect x="420" y="210" width="160" height="60" fill="var(--np-surface)" stroke="var(--np-navy)" stroke-width="1.5"/>
                    <text x="500" y="236" text-anchor="middle" class="lbl">Neighbouring states</text>
                    <text x="500" y="253" text-anchor="middle" class="sub">shared detections</text>
                    <path d="M580 144 H676" stroke="var(--np-teal-deco)" stroke-width="2" marker-end="url(#np-arr)"/>
                    <rect x="680" y="104" width="210" height="80" fill="var(--status-invasive-fill)" stroke="var(--status-invasive-border)" stroke-width="1.5"/>
                    <text x="785" y="134" text-anchor="middle" class="lbl">Rapid response and</text>
                    <text x="785" y="151" text-anchor="middle" class="lbl">management plans</text>
                    <text x="785" y="169" text-anchor="middle" class="sub">eradication or population control</text>
                </svg>
            </div>
            <figcaption>Response plans must be specific, with clear procedures, jurisdictions and resources.</figcaption>
        </figure>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Infographic · Actions</span>
        <h2>What the plan asks for, at each level</h2>
        <p>Section 4 of the plan sets out 20 measures for the countries, under six headings, and 13 for the regional level, under four. National measures build the evidence and the rules; regional measures supply the shared methods, protocols and training that make national work comparable across the Mediterranean.</p>

        <div class="np-level nat">
            <div class="np-lhead">
                <svg viewBox="0 0 36 36" aria-hidden="true"><path d="M6 30 V14 L18 6 L30 14 V30 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 30 V21 H22 V30" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                <div><h3>National level</h3><p>Led by the Contracting Parties</p></div>
                <span class="n">20<small>measures</small></span>
            </div>
            <div class="np-cards">
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><rect x="4" y="5" width="24" height="22" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M8 21 L13 15 L18 18 L24 10" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg><b>IMAP implementation</b><span class="c">3 measures</span></div>
                    <span class="l">a</span>
                    <ul>
                        <li>Run IMAP-compliant monitoring programmes and adapt them as IMAP evolves</li>
                        <li>Update national baselines from monitoring, research and the literature</li>
                        <li>Raise confidence in pathways and vectors, in support of the ballast water plan</li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4 6 H28 L19 17 V27 L13 24 V17 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/></svg><b>Prioritisation and planning</b><span class="c">5 measures</span></div>
                    <span class="l">b</span>
                    <ul>
                        <li>Horizon scanning to build priority lists of high-risk species</li>
                        <li>Risk assessments of priority species</li>
                        <li>Impact mapping with CIMPAL to find hotspots</li>
                        <li>Risk analysis of aquaculture, ornamental and live food trade</li>
                        <li>Environmental impact assessment before any action on a pathway</li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M12 4 V13 L5 26 H27 L20 13 V4" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M10 4 H22 M9 20 H23" stroke="var(--np-teal-deco)" stroke-width="2"/></svg><b>Research on impacts</b><span class="c">1 measure</span></div>
                    <span class="l">c</span>
                    <ul>
                        <li>Field, laboratory and modelling studies on priority species to set acceptable abundance levels</li>
                    </ul>
                </div>
                <div class="np-card on">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><ellipse cx="16" cy="8" rx="10" ry="4" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M6 8 V24 c0 2 4.5 4 10 4 s10 -2 10 -4 V8 M6 16 c0 2 4.5 4 10 4 s10 -2 10 -4" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg><b>Regional data infrastructure</b><span class="c">2 measures</span></div>
                    <span class="l">d</span>
                    <ul>
                        <li>Submit monitoring data to the IMAP Info System, following its data standards</li>
                        <li>Supply baselines, pathways, impact study results and new records<span class="np-tag">MAMIAS</span></li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 4 V28 M8 28 H24 M6 9 H26" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M6 9 L2 18 h8 Z M26 9 L22 18 h8 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="1.6" stroke-linejoin="round"/></svg><b>Legislation</b><span class="c">2 measures</span></div>
                    <span class="l">e</span>
                    <ul>
                        <li>Enact national laws controlling the introduction of marine species, as quickly as possible</li>
                        <li>Write the IMO Ballast Water Management Convention and related codes into national law</li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4 28 H28 M6 28 V13 M12 28 V13 M20 28 V13 M26 28 V13 M3 13 H29 L16 4 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/></svg><b>Institutional framework</b><span class="c">7 measures</span></div>
                    <span class="l">f</span>
                    <ul>
                        <li>Reporting mechanisms for first sightings, linked to the regional early warning<span class="np-tag">MAMIAS</span></li>
                        <li>Rapid response and management plans, with clear procedures and resources</li>
                        <li>Research on methods to mitigate invasions through existing pathways</li>
                        <li>Best-practice guidelines and codes of conduct for pathways outside the ballast water plan</li>
                        <li>Controls on the intentional import and export of alien marine species</li>
                        <li>Citizen science programmes for data collection</li>
                        <li>Awareness raising for target groups and the general public</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="np-link" aria-hidden="true">countries supply the data · the region supplies methods, protocols and training</div>

        <div class="np-level reg">
            <div class="np-lhead">
                <svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="13" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M5 18 H31 M18 5 c-6 7 -6 19 0 26 M18 5 c6 7 6 19 0 26" fill="none" stroke="var(--np-teal-deco)" stroke-width="1.6"/></svg>
                <div><h3>Regional level</h3><p>Led by SPA/RAC, with REMPEC on ballast water and ports</p></div>
                <span class="n">13<small>measures</small></span>
            </div>
            <div class="np-cards">
                <div class="np-card on">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="11" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><circle cx="16" cy="16" r="6" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><circle cx="16" cy="16" r="2" fill="var(--np-teal-deco)"/></svg><b>IMAP and Common Indicator 6</b><span class="c">6 measures</span></div>
                    <span class="l">a</span>
                    <ul>
                        <li>Reference conditions and thresholds for trends in temporal occurrence, with other Regional Seas Conventions and the EU</li>
                        <li>Methods and quantitative targets for trends in spatial distribution</li>
                        <li>Quantitative targets for trends in abundance, linked to the impact objective</li>
                        <li>Aggregation scales and integration with other objectives and indicators</li>
                        <li>Early-warning system linked to national systems<span class="np-tag">MAMIAS</span></li>
                        <li>Port monitoring and data collection coordinated with REMPEC</li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M3 20 H29 L26 26 H6 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M10 20 V11 H22 V20 M16 11 V5" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg><b>Ballast Water Strategy 2022–2027</b><span class="c">4 measures</span></div>
                    <span class="l">b · with REMPEC</span>
                    <ul>
                        <li>Join the regional ballast water working group</li>
                        <li>Port monitoring and baseline surveys integrated with IMAP</li>
                        <li>Port risk assessments and a regional procedure for exemptions</li>
                        <li>First regional work on ship biofouling</li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="11" cy="11" r="4" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><circle cx="22" cy="13" r="3" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/><path d="M3 27 c1 -6 4 -9 8 -9 s7 3 8 9 M17 25 c1 -4 3 -5 5 -5 s4 1 6 5" fill="none" stroke="var(--np-teal-deco)" stroke-width="2"/></svg><b>Training and capacity</b><span class="c">2 measures</span></div>
                    <span class="l">c</span>
                    <ul>
                        <li>An updated guide for risk analysis of NIS impacts, with training on species, pathway and environmental risk assessment</li>
                        <li>Guidance and training for field and modelling studies, and pilot studies on density–impact relationships</li>
                    </ul>
                </div>
                <div class="np-card">
                    <div class="t"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4 13 V19 H9 L20 26 V6 L9 13 Z" fill="none" stroke="var(--np-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M24 11 q4 5 0 10 M27 8 q6 8 0 16" fill="none" stroke="var(--np-teal-deco)" stroke-width="1.8"/></svg><b>Education and awareness</b><span class="c">1 measure</span></div>
                    <span class="l">d</span>
                    <ul>
                        <li>Best-practice guidelines for the activities and sectors that most spread NIS, aimed at stakeholders and decision-makers</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Diagram 4 · Coordination and participation</span>
        <h2>Countries implement, SPA/RAC coordinates</h2>
        <div class="np-two">
            <figure>
                <div class="np-scroll">
                    <svg viewBox="0 0 460 320" width="100%" role="img" aria-labelledby="np-co-t">
                        <title id="np-co-t">SPA/RAC coordinates the plan with the Contracting Parties, REMPEC, INFO/RAC and Action Plan Partners, and reports to the National Focal Points for SPAs.</title>
                        <line x1="230" y1="160" x2="90" y2="60" stroke="var(--np-line)" stroke-width="1.5"/>
                        <line x1="230" y1="160" x2="370" y2="60" stroke="var(--np-line)" stroke-width="1.5"/>
                        <line x1="230" y1="160" x2="90" y2="260" stroke="var(--np-line)" stroke-width="1.5"/>
                        <line x1="230" y1="160" x2="370" y2="260" stroke="var(--np-line)" stroke-width="1.5"/>
                        <line x1="230" y1="160" x2="230" y2="290" stroke="var(--np-line)" stroke-width="1.5"/>
                        <rect x="160" y="125" width="140" height="70" fill="var(--np-teal-fill)"/>
                        <text x="230" y="156" text-anchor="middle" class="lbl on">SPA/RAC</text>
                        <text x="230" y="173" text-anchor="middle" class="sub on">regional coordination</text>
                        <rect x="10" y="30" width="160" height="56" fill="var(--np-surface)" stroke="var(--np-teal-deco)" stroke-width="1.5"/>
                        <text x="90" y="54" text-anchor="middle" class="lbl">Contracting Parties</text>
                        <text x="90" y="71" text-anchor="middle" class="sub">national implementation</text>
                        <rect x="290" y="30" width="160" height="56" fill="var(--np-surface)" stroke="var(--np-navy)" stroke-width="1.5"/>
                        <text x="370" y="54" text-anchor="middle" class="lbl">REMPEC</text>
                        <text x="370" y="71" text-anchor="middle" class="sub">ballast water, ports</text>
                        <rect x="10" y="232" width="160" height="56" fill="var(--np-surface)" stroke="var(--np-navy)" stroke-width="1.5"/>
                        <text x="90" y="256" text-anchor="middle" class="lbl">INFO/RAC</text>
                        <text x="90" y="273" text-anchor="middle" class="sub">data and information</text>
                        <rect x="290" y="232" width="160" height="56" fill="var(--np-surface)" stroke="var(--np-line)"/>
                        <text x="370" y="256" text-anchor="middle" class="lbl">Action Plan Partners</text>
                        <text x="370" y="273" text-anchor="middle" class="sub">organisations, NGOs, labs</text>
                        <rect x="165" y="290" width="130" height="28" fill="var(--np-sunken)" stroke="var(--np-line)"/>
                        <text x="230" y="309" text-anchor="middle" class="sub">reports to SPA Focal Points</text>
                    </svg>
                </div>
            </figure>
            <div class="np-stack">
                <p class="np-small">Implementing the plan is the job of national authorities. SPA/RAC, on behalf of the MAP Secretariat, carries out the regional actions, helps countries with national ones as far as its means allow, and promotes exchanges among Mediterranean specialists.</p>
                <p class="np-small">SPA/RAC reports regularly to the National Focal Points for SPAs, and prepares a progress report at the end of the five-year period. The Focal Points then make follow-up suggestions to the Parties.</p>
                <p class="np-small">Any organisation, NGO or laboratory that carries out or funds concrete work under the plan can ask for the status of Action Plan Partner, granted by the Contracting Parties on the suggestion of the SPA Focal Points. SPA/RAC also invites REMPEC and INFO/RAC to contribute, and sets up a regular dialogue between participants.</p>
                <div class="np-cta" style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <a href="/pages/data" style="display: inline-flex; align-items: center; height: 40px; padding: 0 16px; background: var(--primary); color: var(--primary-foreground); text-decoration: none; font: 500 14px/22px var(--font-sans);">Explore NIS data</a>
                    <a href="/pages/ballast-water/strategy" style="display: inline-flex; align-items: center; height: 40px; padding: 0 16px; border: 1px solid var(--np-line); color: var(--np-teal-text); text-decoration: none; font: 500 14px/22px var(--font-sans);">Ballast Water Management</a>
                </div>
            </div>
        </div>
    </section>

    <section class="np-stack">
        <span class="np-eyebrow">Publications · SPA/RAC guidance</span>
        <h2>Tools for putting the plan into practice</h2>
        <div class="np-pubs">
            <article class="np-pub">
                <a class="cv" href="https://legacy.spa-rac.org/en/publication/download/1754/guide-for-risk-analysis-assessing-the-impacts-of-the-introduction-of-nis" target="_blank" rel="noopener"><img src="{{COVER_RISK}}" width="300" height="424" alt="Cover of the Guide for Risk Analysis Assessing the Impacts of the Introduction of Non-Indigenous Species" loading="lazy"></a>
                <div class="bd">
                    <span class="np-k">UNEP/MAP-SPA/RAC · 2024</span>
                    <h3>Guide for Risk Analysis Assessing the Impacts of the Introduction of Non-Indigenous Species</h3>
                    <p class="mt">By Marika Galanidi · 26 pp. + annex · ISBN 978-9938-79-371-0</p>
                    <p>Risk analysis is a standard way of judging how likely an introduction is to cause harm and how serious that harm would be, so that limited resources go where they matter most. The guide sets out the five stages of a risk analysis for non-indigenous species and reviews the protocols currently used in the Mediterranean and in Europe, at both species and pathway level.</p>
                    <ul class="tc">
                        <li>Endpoint and hazard identification</li>
                        <li>Risk assessment, management and communication</li>
                        <li>Species-based risk assessment and minimum standards</li>
                        <li>Horizon scanning and prioritisation</li>
                        <li>Pathway-based risk assessment</li>
                    </ul>
                    <p class="pl">Answers the Action Plan's call for an updated guide for risk analysis of NIS impacts (timetable item 5).</p>
                    <a class="dl" href="https://legacy.spa-rac.org/en/publication/download/1754/guide-for-risk-analysis-assessing-the-impacts-of-the-introduction-of-nis" target="_blank" rel="noopener">Download the guide <span>PDF · 1.3 MB</span></a>
                </div>
            </article>
            <article class="np-pub">
                <a class="cv" href="https://legacy.spa-rac.org/en/publication/download/1756/guidelines-for-controlling-the-vectors-of-introduction-into-the-mediterranean-of-non-indigenous-species-and-invasive-marine-species" target="_blank" rel="noopener"><img src="{{COVER_VECTORS}}" width="300" height="424" alt="Cover of the Guidelines for Controlling the Vectors of Introduction into the Mediterranean of Non-Indigenous Species and Invasive Marine Species" loading="lazy"></a>
                <div class="bd">
                    <span class="np-k">UNEP/MAP-SPA/RAC · 2024</span>
                    <h3>Guidelines for Controlling the Vectors of Introduction into the Mediterranean of Non-Indigenous Species and Invasive Marine Species</h3>
                    <p class="mt">By Marika Galanidi · 25 pp. · ISBN 978-9938-79-369-7</p>
                    <p>At sea, eradication and long-term control are rarely possible, so prevention at the level of vectors and pathways is the main line of defence. The guidelines review the rules and best practice for the three vectors that matter most after the Suez corridor, and recommend priority actions for countries and for the region.</p>
                    <ul class="tc">
                        <li>Ballast water: the IMO BWM Convention and the Mediterranean BWM Strategy</li>
                        <li>Hull fouling: IMO guidelines, the European code for recreational boating, national rules</li>
                        <li>Aquaculture: prevention, eradication and control</li>
                        <li>Recommended actions at national and regional level</li>
                    </ul>
                    <p class="pl">Supports the Action Plan's best-practice guidelines for sectors that act as vectors (timetable item 4).</p>
                    <a class="dl" href="https://legacy.spa-rac.org/en/publication/download/1756/guidelines-for-controlling-the-vectors-of-introduction-into-the-mediterranean-of-non-indigenous-species-and-invasive-marine-species" target="_blank" rel="noopener">Download the guidelines <span>PDF · 0.9 MB</span></a>
                </div>
            </article>
        </div>
    </section>


    <section class="np-end np-stack">
        <span class="np-eyebrow">Sources</span>
        <ol style="font-size: 14px; line-height: 22px; color: var(--np-muted); display: grid; gap: 4px; padding-left: 20px; margin: 0;">
            <li>UNEP/MAP, Decision IG.26/5, Specially Protected Areas (SPAs), Specially Protected Areas of Mediterranean Importance (SPAMIs) and Ecosystem Restoration, COP 23, 2023. Annex IV: Updated Action Plan concerning Species Introductions and Invasive Species in the Mediterranean Sea. UNEP/MED IG.26/22. <a href="https://wedocs.unep.org/rest/api/core/bitstreams/713c1596-ff30-4cbb-ada7-457dda82c09b/content" target="_blank" rel="noopener">Document</a></li>
            <li>UNEP/MAP, 2023 Mediterranean Quality Status Report (MED QSR), for all figures on non-indigenous species. <a href="https://medqsr2023.info-rac.org/" target="_blank" rel="noopener">medqsr2023.info-rac.org</a></li>
        </ol>
    </section>

</div>
HTML;
    }

    /**
     * Inline the publication covers as data URIs, so the page carries its own
     * images (the CSP allows `data:` images) and needs no uploaded media.
     */
    private static function withCovers(string $html): string
    {
        $cover = fn (string $file): string => 'data:image/webp;base64,'.base64_encode((string) file_get_contents(__DIR__.'/images/'.$file));

        return strtr($html, [
            '{{COVER_RISK}}' => $cover('spa-rac-2024-risk-analysis-guide.webp'),
            '{{COVER_VECTORS}}' => $cover('spa-rac-2024-vectors-guidelines.webp'),
        ]);
    }
}
