<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes /pages/ballast-water/strategy (Resources › Ballast Water
 * Management): ships' ballast water as a pathway for non-indigenous species,
 * the IMO Ballast Water Management Convention and the Ballast Water
 * Management Strategy for the Mediterranean Sea (2022-2027).
 *
 * Built from the IMO ballast water management page, the REMPEC strategy page
 * and the Strategy text (Decision IG.25/17). NIS figures follow the 2023 MED QSR.
 *
 * Same construction as LayupSpaBdProtocolPageSeeder: one html widget whose
 * styles are scoped under `.mamias-bw` and read the site tokens from app.css.
 * No <h1>: the site's page header shows the title.
 *
 * Overwrites the page content. Run: php artisan db:seed --class=LayupBallastWaterPageSeeder
 */
class LayupBallastWaterPageSeeder extends Seeder
{
    public const SLUG = 'pages/ballast-water/strategy';

    public function run(): void
    {
        Page::withTrashed()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'deleted_at' => null,
                'title' => 'Non-indigenous species and Ballast Water Management',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => 'How ships move non-indigenous species in ballast water, the IMO Ballast Water Management Convention, and the Ballast Water Management Strategy for the Mediterranean Sea (2022–2027).',
                ],
                'content' => [
                    'rows' => [[
                        'id' => 'row_bw',
                        'settings' => ['gap' => 'gap-0'],
                        'columns' => [[
                            'id' => 'col_bw',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => 'widget_bw',
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
.mamias-bw {
    --bw-ink: var(--foreground);
    --bw-ink-2: #47606b;
    --bw-muted: var(--muted-foreground);
    --bw-line: var(--border);
    --bw-line-soft: var(--secondary);
    --bw-surface: var(--card);
    --bw-sunken: var(--muted);
    --bw-tint: var(--accent);
    --bw-teal-text: var(--mamias-teal-600);
    --bw-teal-deco: var(--mamias-teal-500);
    --bw-teal-fill: var(--mamias-teal-600);
    --bw-on-teal: #fff;
    --bw-navy: #2a2b74;
    display: grid; gap: 64px; padding-block: 24px 64px; color: var(--bw-ink);
    font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-bw {
    --bw-ink-2: #b3c7ce;
    --bw-teal-text: var(--mamias-teal-300);
    --bw-teal-deco: var(--mamias-teal-400);
    --bw-teal-fill: var(--mamias-teal-400);
    --bw-on-teal: var(--background);
    --bw-navy: #8f91d9;
}
.mamias-bw * { box-sizing: border-box; }
.mamias-bw h2, .mamias-bw h3 { margin: 0; color: var(--bw-ink); text-wrap: balance; }
.mamias-bw h2 { font-size: 20px; line-height: 28px; font-weight: 500; }
.mamias-bw h3 { font-size: 16px; line-height: 24px; font-weight: 500; }
.mamias-bw p { margin: 0; }
.mamias-bw ul, .mamias-bw ol { margin: 0; padding: 0; list-style: none; }
.mamias-bw .bw-stack { display: grid; gap: 16px; }
.mamias-bw .bw-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--bw-muted); }
.mamias-bw .bw-lede { color: var(--bw-ink-2); }
.mamias-bw .bw-small { font-size: 14px; line-height: 22px; color: var(--bw-ink-2); }
.mamias-bw .bw-src { font-size: 13px; line-height: 20px; color: var(--bw-muted); }
.mamias-bw .bw-k { font: 400 12px/18px var(--font-mono); color: var(--bw-teal-text); }
.mamias-bw a { color: var(--bw-teal-text); text-underline-offset: 2px; }
.mamias-bw a:focus-visible { outline: 2px solid var(--bw-teal-deco); outline-offset: 2px; }
.mamias-bw figure { margin: 0; display: grid; gap: 12px; }
.mamias-bw figcaption { font-size: 14px; line-height: 22px; color: var(--bw-muted); }
.mamias-bw .bw-scroll { overflow-x: auto; }
.mamias-bw .bw-two { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; align-items: start; }
@media (max-width: 760px) { .mamias-bw .bw-two { grid-template-columns: minmax(0, 1fr); } }
.mamias-bw .bw-def { display: grid; gap: 4px; align-content: start; padding-top: 12px; border-top: 1px solid var(--bw-line); }
.mamias-bw .bw-def p { font-size: 14px; line-height: 22px; color: var(--bw-ink-2); }
.mamias-bw svg text { font-family: var(--font-sans); fill: var(--bw-ink); }
.mamias-bw svg .lbl { font-size: 13px; font-weight: 500; }
.mamias-bw svg .sub { font-size: 11px; fill: var(--bw-muted); }
.mamias-bw svg .mono { font-family: var(--font-mono); font-size: 11px; fill: var(--bw-teal-text); }
.mamias-bw svg text.on { fill: var(--bw-on-teal); }

/* Timeline: teal-500 rule and markers, teal-600 years. */
.mamias-bw .bw-tl { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(7, minmax(8.5rem, 1fr)); min-width: 62rem; }
.mamias-bw .bw-tl li { position: relative; padding: 28px 16px 0 0; display: grid; gap: 2px; align-content: start; }
.mamias-bw .bw-tl li::before { content: ""; position: absolute; left: 0; right: 0; top: 7px; height: 2px; background: var(--bw-teal-deco); }
.mamias-bw .bw-tl li::after { content: ""; position: absolute; left: 0; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--bw-surface); border: 2px solid var(--bw-teal-deco); }
.mamias-bw .bw-tl li.g::after { border-color: var(--bw-muted); }
.mamias-bw .bw-tl .y { font: 400 20px/28px var(--font-mono); letter-spacing: -.4px; color: var(--bw-teal-text); font-variant-numeric: tabular-nums; }
.mamias-bw .bw-tl li.g .y { color: var(--bw-ink-2); }
.mamias-bw .bw-tl b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-bw .bw-tl .d { font: 400 12px/18px var(--font-mono); color: var(--bw-muted); }
@media (max-width: 640px) {
    .mamias-bw .bw-tl { grid-template-columns: 1fr; min-width: 0; border-left: 2px solid var(--bw-teal-deco); margin-left: 5px; }
    .mamias-bw .bw-tl li { padding: 0 0 16px 20px; }
    .mamias-bw .bw-tl li::before { display: none; }
    .mamias-bw .bw-tl li::after { left: -8px; top: 8px; }
}

/* Figures strip */
.mamias-bw .bw-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr)); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); }
.mamias-bw .bw-fact { background: var(--bw-surface); padding: 14px 16px; display: grid; gap: 2px; align-content: start; }
.mamias-bw .bw-fact b { font: 400 24px/32px var(--font-mono); letter-spacing: -.5px; }
.mamias-bw .bw-fact span { font-size: 14px; line-height: 22px; color: var(--bw-ink-2); }

/* Ratification dots */
.mamias-bw .bw-dots { display: grid; grid-template-columns: repeat(21, minmax(0, 1fr)); gap: 6px; max-width: 34rem; }
.mamias-bw .bw-dots i { aspect-ratio: 1; border-radius: 50%; border: 2px solid var(--bw-teal-deco); max-width: 100%; }
.mamias-bw .bw-dots i.y { background: var(--bw-teal-fill); border-color: var(--bw-teal-fill); }
.mamias-bw .bw-ratio { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; }
.mamias-bw .bw-ratio b { font: 400 32px/40px var(--font-mono); letter-spacing: -.6px; color: var(--bw-teal-text); }

/* Standards comparison */
.mamias-bw .bw-std { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); }
.mamias-bw .bw-std > div { background: var(--bw-surface); padding: 20px; display: grid; gap: 12px; align-content: start; }
.mamias-bw .bw-std .hd { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; }
.mamias-bw .bw-std .tag { font: 500 11px/16px var(--font-sans); letter-spacing: .08em; text-transform: uppercase; padding: 2px 8px; border: 1px solid var(--bw-line); color: var(--bw-ink-2); }
.mamias-bw .bw-std .d2 { background: var(--bw-tint); }
.mamias-bw .bw-std .d2 .tag { background: var(--bw-teal-fill); border-color: var(--bw-teal-fill); color: var(--bw-on-teal); }
.mamias-bw .bw-std ul { display: grid; gap: 8px; font-size: 14px; line-height: 22px; color: var(--bw-ink-2); }
.mamias-bw .bw-std li { display: grid; grid-template-columns: 14px minmax(0, 1fr); gap: 8px; }
.mamias-bw .bw-std li::before { content: ""; width: 6px; height: 6px; margin-top: 8px; background: var(--bw-teal-deco); }
.mamias-bw .bw-limits { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 9rem), 1fr)); gap: 8px; }
.mamias-bw .bw-limit { border: 1px solid var(--bw-teal-deco); background: var(--bw-surface); padding: 10px 12px; display: grid; gap: 2px; }
.mamias-bw .bw-limit b { font: 400 18px/24px var(--font-mono); color: var(--bw-teal-text); }
.mamias-bw .bw-limit span { font-size: 12px; line-height: 18px; color: var(--bw-ink-2); }
@media (max-width: 760px) { .mamias-bw .bw-std { grid-template-columns: minmax(0, 1fr); } }

/* Ship requirements */
.mamias-bw .bw-reqs { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr)); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); }
.mamias-bw .bw-req { background: var(--bw-surface); padding: 14px 16px; display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 4px 12px; align-items: start; }
.mamias-bw .bw-req svg { width: 28px; height: 28px; grid-row: span 2; }
.mamias-bw .bw-req b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-bw .bw-req span { font-size: 13px; line-height: 20px; color: var(--bw-ink-2); }

/* Strategy tree: objectives → priorities → actions → activities */
.mamias-bw .bw-obj { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr)); gap: 1px; background: var(--bw-teal-fill); border: 1px solid var(--bw-teal-fill); }
.mamias-bw .bw-obj div { background: var(--bw-teal-fill); color: var(--bw-on-teal); padding: 14px 16px; font-size: 14px; line-height: 22px; display: grid; gap: 2px; }
.mamias-bw .bw-obj .bw-k { color: var(--bw-on-teal); }
.mamias-bw .bw-count { display: flex; flex-wrap: wrap; gap: 8px 24px; align-items: baseline; font-size: 14px; color: var(--bw-ink-2); }
.mamias-bw .bw-count b { font: 400 24px/32px var(--font-mono); color: var(--bw-teal-text); margin-right: 6px; }
.mamias-bw .bw-tree { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr)); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); }
.mamias-bw .bw-pri { background: var(--bw-surface); padding: 16px; display: grid; gap: 10px; align-content: start; }
.mamias-bw .bw-pri h3 { font-size: 15px; line-height: 22px; }
.mamias-bw .bw-pri ul { display: grid; gap: 6px; }
.mamias-bw .bw-pri li { display: grid; grid-template-columns: 2.4rem minmax(0, 1fr) auto; gap: 8px; align-items: baseline; font-size: 13px; line-height: 20px; color: var(--bw-ink-2); padding: 6px 0; border-top: 1px solid var(--bw-line-soft); }
.mamias-bw .bw-pri li .a { font: 400 12px/18px var(--font-mono); color: var(--bw-teal-text); }
.mamias-bw .bw-pri li .n { font: 400 11px/18px var(--font-mono); color: var(--bw-muted); white-space: nowrap; }
.mamias-bw .bw-pri li.on { background: var(--bw-tint); }

/* Pathway flow: three step cards joined by arrows; stacks with downward arrows on narrow screens. */
.mamias-bw .bw-flow { display: grid; grid-template-columns: minmax(0, 1fr) 40px minmax(0, 1fr) 40px minmax(0, 1fr); align-items: stretch; }
.mamias-bw .bw-flow .st { border: 1px solid var(--bw-line); background: var(--bw-surface); padding: 16px; display: grid; grid-template-rows: auto auto 1fr; gap: 10px; position: relative; }
.mamias-bw .bw-flow .st.end { background: var(--status-invasive-fill); border-color: var(--status-invasive-border); }
.mamias-bw .bw-flow .st svg { width: 100%; max-width: 220px; height: auto; aspect-ratio: 3 / 2; justify-self: center; }
.mamias-bw .bw-flow .n { position: absolute; top: 12px; left: 12px; width: 26px; height: 26px; display: grid; place-items: center; font: 400 13px/1 var(--font-mono); color: var(--bw-on-teal); background: var(--bw-teal-fill); }
.mamias-bw .bw-flow .tx { display: grid; gap: 4px; align-content: start; }
.mamias-bw .bw-flow .tx p { font-size: 14px; line-height: 22px; color: var(--bw-ink-2); }
.mamias-bw .bw-flow .ar { display: grid; place-items: center; }
.mamias-bw .bw-flow .ar span { display: block; width: 100%; height: 2px; background: var(--bw-teal-deco); position: relative; }
.mamias-bw .bw-flow .ar span::after { content: ""; position: absolute; right: -1px; top: -5px; border: 6px solid transparent; border-left: 8px solid var(--bw-teal-deco); border-right: 0; }
.mamias-bw .bw-load { display: grid; grid-template-columns: auto minmax(0, 1fr) minmax(0, 1fr); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); }
.mamias-bw .bw-load > div { background: var(--bw-surface); padding: 12px 16px; display: grid; gap: 2px; align-content: center; }
.mamias-bw .bw-load-k { background: var(--bw-tint) !important; }
.mamias-bw .bw-load-v b { font: 400 24px/32px var(--font-mono); letter-spacing: -.5px; color: var(--bw-teal-text); }
.mamias-bw .bw-load-v span { font-size: 13px; line-height: 20px; color: var(--bw-ink-2); }
.mamias-bw .bw-also { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 24px; padding: 12px 16px; border-left: 2px solid var(--bw-teal-deco); background: var(--bw-sunken); }
.mamias-bw .bw-also div { display: grid; gap: 0; }
.mamias-bw .bw-also b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-bw .bw-also div span { font-size: 13px; line-height: 20px; color: var(--bw-ink-2); }
@media (max-width: 760px) {
    .mamias-bw .bw-flow { grid-template-columns: minmax(0, 1fr); }
    .mamias-bw .bw-flow .st { grid-template-columns: 96px minmax(0, 1fr); grid-template-rows: auto; align-items: center; padding-left: 16px; }
    .mamias-bw .bw-flow .st svg { max-width: 96px; }
    .mamias-bw .bw-flow .ar { height: 28px; }
    .mamias-bw .bw-flow .ar span { width: 2px; height: 100%; }
    .mamias-bw .bw-flow .ar span::after { right: auto; left: -5px; top: auto; bottom: -1px; border: 6px solid transparent; border-top: 8px solid var(--bw-teal-deco); border-bottom: 0; }
    .mamias-bw .bw-load { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .mamias-bw .bw-load-k { grid-column: 1 / -1; }
}
/* Exchange routes diagram, biofouling, links */
.mamias-bw .bw-steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); counter-reset: s; }
.mamias-bw .bw-steps li { background: var(--bw-surface); padding: 14px 16px; display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 4px 12px; font-size: 14px; line-height: 22px; color: var(--bw-ink-2); }
.mamias-bw .bw-steps li::before { counter-increment: s; content: counter(s); width: 28px; height: 28px; display: grid; place-items: center; font: 400 13px/1 var(--font-mono); color: var(--bw-on-teal); background: var(--bw-teal-fill); grid-row: span 2; }
.mamias-bw .bw-steps li b { color: var(--bw-ink); font-weight: 500; }
.mamias-bw .bw-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 1px; background: var(--bw-line); border: 1px solid var(--bw-line); }
.mamias-bw .bw-links a { background: var(--bw-surface); padding: 14px 16px; display: grid; gap: 2px; text-decoration: none; }
.mamias-bw .bw-links a:hover { background: var(--bw-tint); }
.mamias-bw .bw-links b { font-weight: 500; font-size: 14px; line-height: 22px; color: var(--bw-ink); }
.mamias-bw .bw-links span { font-size: 13px; line-height: 20px; color: var(--bw-ink-2); }
.mamias-bw .bw-end { border-top: 1px solid var(--border); padding-top: 32px; }
.mamias-bw .bw-sources { font-size: 14px; line-height: 22px; color: var(--bw-muted); display: grid; gap: 4px; padding-left: 20px; margin: 0; list-style: decimal; }
</style>

<div class="mamias-bw">

    <header class="bw-stack">
        <span class="bw-eyebrow">IMO · REMPEC · SPA/RAC</span>
        <p class="bw-lede">Ships take on seawater as ballast to stay stable and safe, then release it at the next port. With it travel bacteria, plankton, eggs, cysts and larvae from the other side of the world. Some survive, settle and become invasive. The IMO Ballast Water Management Convention sets the global rules to stop this, and the Ballast Water Management Strategy for the Mediterranean Sea (2022–2027) organises how the Mediterranean countries apply them together.</p>
        <div class="bw-scroll">
            <ol class="bw-tl" aria-label="Ballast water management timeline">
                <li class="g"><span class="y">1991</span><b>First IMO guidelines</b><span class="d">MEPC.50(31)</span></li>
                <li class="g"><span class="y">1997</span><b>IMO Assembly guidelines</b><span class="d">A.868(20)</span></li>
                <li class="g"><span class="y">2004</span><b>BWM Convention adopted</b><span class="d">London · 13 February</span></li>
                <li><span class="y">2012</span><b>First Mediterranean Strategy</b><span class="d">COP 17 · Paris</span></li>
                <li class="g"><span class="y">2017</span><b>Convention in force</b><span class="d">8 September</span></li>
                <li><span class="y">2021</span><b>Strategy 2022–2027 adopted</b><span class="d">COP 22 · IG.25/17</span></li>
                <li><span class="y">2024</span><b>Exchange phased out</b><span class="d">D-2 standard only</span></li>
            </ol>
        </div>
    </header>

    <section class="bw-stack">
        <span class="bw-eyebrow">Diagram 1 · The pathway</span>
        <h2>How ballast water moves species between seas</h2>
        <p>Ballast water keeps a ship stable when it is empty or lightly loaded. It is pumped in where cargo is unloaded and pumped out where cargo is loaded, often thousands of kilometres away, together with everything living in it.</p>
        <ol class="bw-flow" aria-label="How ballast water moves species">
            <li class="st">
                <svg viewBox="0 0 120 80" aria-hidden="true">
                    <path d="M0 62 q10 -5 20 0 t20 0 t20 0 t20 0 t20 0 t20 0" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/>
                    <path d="M20 58 V20 H34 V58 M27 20 V8 H70 M66 8 V24" fill="none" stroke="var(--bw-ink-2)" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M62 24 h8 v8 h-8 z" fill="none" stroke="var(--bw-ink-2)" stroke-width="2"/>
                    <path d="M58 52 H104 L98 60 H62 Z" fill="var(--bw-surface)" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M86 70 v-10" stroke="var(--bw-teal-deco)" stroke-width="2"/><path d="M82 64 l4 -5 4 5" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/>
                    <circle cx="74" cy="72" r="2" fill="var(--status-invasive-text)"/><circle cx="96" cy="74" r="2.5" fill="var(--status-invasive-text)"/><circle cx="104" cy="70" r="1.6" fill="var(--status-invasive-text)"/>
                </svg>
                <span class="n">1</span>
                <div class="tx">
                    <h3>Uptake at the source port</h3>
                    <p>The ship unloads cargo and pumps in local seawater. Plankton, larvae, eggs, cysts and microbes come aboard with it.</p>
                </div>
            </li>
            <li class="ar" aria-hidden="true"><span></span></li>
            <li class="st">
                <svg viewBox="0 0 120 80" aria-hidden="true">
                    <path d="M0 58 q10 -5 20 0 t20 0 t20 0 t20 0 t20 0 t20 0" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/>
                    <path d="M10 36 H110 L100 56 H20 Z" fill="var(--bw-surface)" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/>
                    <rect x="76" y="16" width="20" height="20" fill="none" stroke="var(--bw-ink-2)" stroke-width="2"/>
                    <rect x="24" y="42" width="60" height="9" fill="var(--bw-tint)" stroke="var(--bw-teal-deco)" stroke-width="1.2"/>
                    <circle cx="34" cy="46.5" r="2" fill="var(--status-invasive-text)"/><circle cx="48" cy="46.5" r="1.6" fill="var(--status-invasive-text)"/><circle cx="62" cy="46.5" r="2" fill="var(--status-invasive-text)"/><circle cx="74" cy="46.5" r="1.4" fill="var(--status-invasive-text)"/>
                    <path d="M20 66 h80" stroke="var(--bw-muted)" stroke-width="1.2" stroke-dasharray="3 4"/>
                </svg>
                <span class="n">2</span>
                <div class="tx">
                    <h3>The voyage</h3>
                    <p>Hardy organisms survive days or weeks in the dark tanks and in the sediment that settles at their bottom.</p>
                </div>
            </li>
            <li class="ar" aria-hidden="true"><span></span></li>
            <li class="st end">
                <svg viewBox="0 0 120 80" aria-hidden="true">
                    <path d="M0 62 q10 -5 20 0 t20 0 t20 0 t20 0 t20 0 t20 0" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/>
                    <path d="M14 52 H60 L54 60 H20 Z" fill="var(--bw-surface)" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M36 60 v10" stroke="var(--bw-teal-deco)" stroke-width="2"/><path d="M32 66 l4 5 4 -5" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M86 58 V20 H100 V58 M93 20 V8" fill="none" stroke="var(--bw-ink-2)" stroke-width="2"/>
                    <circle cx="52" cy="73" r="2.5" fill="var(--status-invasive-text)"/><circle cx="64" cy="70" r="2" fill="var(--status-invasive-text)"/><circle cx="74" cy="74" r="3" fill="var(--status-invasive-text)"/><circle cx="84" cy="70" r="2" fill="var(--status-invasive-text)"/><circle cx="96" cy="74" r="2.5" fill="var(--status-invasive-text)"/>
                </svg>
                <span class="n">3</span>
                <div class="tx">
                    <h3>Discharge at the destination port</h3>
                    <p>The ship loads cargo and releases its ballast. Most organisms die, but some establish and a few become invasive.</p>
                </div>
            </li>
        </ol>
        <div class="bw-load">
            <div class="bw-load-k"><span class="bw-k">In one cubic metre of ballast water</span></div>
            <div class="bw-load-v"><b>50,000</b><span>zooplankton organisms, up to</span></div>
            <div class="bw-load-v"><b>10 million</b><span>phytoplankton cells, up to</span></div>
        </div>
        <div class="bw-also">
            <span class="bw-k">Ships carry species in two other ways</span>
            <div><b>Biofouling</b><span>Organisms attached to hulls, propellers and sea chests</span></div>
            <div><b>Tank sediments</b><span>A refuge for species such as dinoflagellate cysts</span></div>
        </div>
        <p class="bw-src">Organism figures from studies cited in the Mediterranean Strategy (2022–2027).</p>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">The Mediterranean context</span>
        <h2>A small sea with a large share of world shipping</h2>
        <p>The Mediterranean covers less than 1% of the world's oceans but carries just over 24% of global shipping, and most of that traffic is between Mediterranean ports. Corridors are the main entry route for non-indigenous species in the Eastern Mediterranean, while in the Western Mediterranean most introductions are linked to maritime transport, as stowaways in ballast water or on hulls.</p>
        <div class="bw-facts">
            <div class="bw-fact"><b>24%</b><span>of global shipping calls at or transits the Mediterranean</span></div>
            <div class="bw-fact"><b>453,000</b><span>port calls in 2019, by 14,403 ships</span></div>
            <div class="bw-fact"><b>5,251</b><span>ships transiting the area in 2019</span></div>
            <div class="bw-fact"><b>1,011</b><span>non-indigenous species recorded, 748 of them established</span></div>
        </div>
        <p class="bw-src">Shipping figures: REMPEC (2020), as cited in the Mediterranean Strategy. Non-indigenous species: 2023 MED QSR.</p>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">The global rules · IMO</span>
        <h2>The Ballast Water Management Convention</h2>
        <p>Concern about species carried in ballast water reached IMO's Marine Environment Protection Committee in the late 1980s, raised by Canada and Australia. After voluntary guidelines in 1991 and 1997, and more than 14 years of negotiation, the International Convention for the Control and Management of Ships' Ballast Water and Sediments was adopted by consensus in London on 13 February 2004. It entered into force on 8 September 2017, and its Parties now represent the vast majority of the world's fleet.</p>
        <div class="bw-reqs">
            <div class="bw-req">
                <svg viewBox="0 0 28 28" aria-hidden="true"><rect x="5" y="3" width="18" height="22" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/><path d="M9 9 H19 M9 14 H19 M9 19 H15" stroke="var(--bw-teal-deco)" stroke-width="1.6"/></svg>
                <b>Ballast Water Management Plan</b><span>Carried by every ship, specific to that ship</span>
            </div>
            <div class="bw-req">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M5 4 H20 L23 7 V24 H5 Z" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M9 11 l2 2 4 -4 M9 18 H19" fill="none" stroke="var(--bw-teal-deco)" stroke-width="1.6"/></svg>
                <b>Ballast Water Record Book</b><span>Every uptake, treatment and discharge logged</span>
            </div>
            <div class="bw-req">
                <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M3 18 H25 L22 24 H6 Z" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2" stroke-linejoin="round"/><path d="M9 18 V10 H19 V18" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/><path d="M12 14 l2 2 3 -4" fill="none" stroke="var(--bw-teal-deco)" stroke-width="1.6"/></svg>
                <b>Manage to a standard</b><span>Exchange (D-1), then treatment to the D-2 standard</span>
            </div>
            <div class="bw-req">
                <svg viewBox="0 0 28 28" aria-hidden="true"><circle cx="14" cy="14" r="10" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/><path d="M9 14 l3 3 7 -7" fill="none" stroke="var(--bw-teal-deco)" stroke-width="2"/></svg>
                <b>Type-approved systems</b><span>BWMS Code, mandatory since October 2019</span>
            </div>
        </div>
        <figure>
            <div class="bw-std">
                <div>
                    <div class="hd"><span class="tag">D-1</span><h3>Ballast water exchange</h3></div>
                    <p class="bw-small">An interim measure: ships swap coastal ballast for open-ocean water, where coastal organisms are less likely to survive.</p>
                    <ul>
                        <li>95% volumetric exchange</li>
                        <li>At least 200 nautical miles from land, in water at least 200 m deep</li>
                        <li>If not possible, at least 50 nautical miles from land, 200 m deep</li>
                        <li>Reduces the risk but does not remove it, and can compromise ship safety</li>
                        <li>No longer accepted from 2024</li>
                    </ul>
                </div>
                <div class="d2">
                    <div class="hd"><span class="tag">D-2</span><h3>Ballast water performance standard</h3></div>
                    <p class="bw-small">The standard every ship must now meet, usually with an approved treatment system. Discharged water must contain:</p>
                    <div class="bw-limits">
                        <div class="bw-limit"><b>&lt;10 / m³</b><span>viable organisms ≥ 50 µm</span></div>
                        <div class="bw-limit"><b>&lt;10 / mL</b><span>viable organisms 10–50 µm</span></div>
                        <div class="bw-limit"><b>&lt;1 cfu</b><span>toxicogenic <i>Vibrio cholerae</i> per 100 mL</span></div>
                        <div class="bw-limit"><b>&lt;250 cfu</b><span><i>Escherichia coli</i> per 100 mL</span></div>
                        <div class="bw-limit"><b>&lt;100 cfu</b><span>intestinal enterococci per 100 mL</span></div>
                    </div>
                </div>
            </div>
            <figcaption>The two standards of the Convention. Systems that use active substances also need a two-step IMO approval, reviewed by the GESAMP Ballast Water Working Group.</figcaption>
        </figure>
        <p class="bw-small">Article 13.3 of the Convention asks Parties that border enclosed and semi-enclosed seas, such as the Mediterranean, to cooperate regionally and develop harmonised procedures. The Mediterranean Strategy is that regional cooperation.</p>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">Infographic · Ratification in the Mediterranean</span>
        <h2>13 of 21 Mediterranean coastal States had ratified</h2>
        <div class="bw-ratio"><b>13 / 21</b><span class="bw-small">Mediterranean coastal States, Contracting Parties to the Barcelona Convention, party to the BWM Convention as of 21 April 2021</span></div>
        <div class="bw-dots" role="img" aria-label="13 of 21 Mediterranean coastal States had ratified the BWM Convention in April 2021.">
            <i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i class="y"></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
        </div>
        <p class="bw-small">A 2016 assessment of the first Mediterranean Strategy found that only five of the responding countries had national law in place. Supporting ratification, and the laws that give it effect, is therefore the Strategy's first action.</p>
        <p class="bw-src">Status as reported in the Mediterranean Strategy (2022–2027).</p>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">Diagram 2 · The Mediterranean Strategy 2022–2027</span>
        <h2>Three objectives, six priorities, twelve actions</h2>
        <p>The first Mediterranean Strategy was adopted at COP 17 in Paris in 2012 for 2011–2015, and its implementation continued after that. The entry into force of the Convention, its amendments, IMAP and the updated NIS Action Plan made it obsolete in several respects, and COP 22 adopted the updated Strategy for 2022–2027. Its focus remains ballast water, but its scope now extends to ship biofouling.</p>
        <div class="bw-obj">
            <div><span class="bw-k">Objective 1</span>A regional harmonised approach to ballast water control and management, consistent with the BWM Convention (Article 13.3)</div>
            <div><span class="bw-k">Objective 2</span>Start preliminary work on managing ships' biofouling in the Mediterranean</div>
            <div><span class="bw-k">Objective 3</span>Contribute to Good Environmental Status for non-indigenous species, as defined in IMAP</div>
        </div>
        <div class="bw-count"><span><b>6</b>strategic priorities</span><span><b>12</b>actions</span><span><b>39</b>activities, 2022–2027</span></div>
        <div class="bw-tree">
            <div class="bw-pri">
                <span class="bw-k">Priority 1</span>
                <h3>Support ratification and implementation of the BWM Convention</h3>
                <ul>
                    <li><span class="a">A1</span>Ratification of the BWM Convention<span class="n">4 activities</span></li>
                    <li><span class="a">A2</span>Harmonisation of BWM measures in the region<span class="n">8 activities</span></li>
                    <li class="on"><span class="a">A3</span>Port baseline surveys and biological monitoring<span class="n">4 activities</span></li>
                    <li><span class="a">A4</span>Risk assessment for ballast water decisions<span class="n">2 activities</span></li>
                    <li><span class="a">A5</span>Alignment with neighbouring regions<span class="n">1 activity</span></li>
                </ul>
            </div>
            <div class="bw-pri">
                <span class="bw-k">Priority 2</span>
                <h3>Contribute to Good Environmental Status</h3>
                <ul>
                    <li><span class="a">A6</span>Ratification of the SPA/BD Protocol<span class="n">2 activities</span></li>
                    <li><span class="a">A7</span>Preliminary work on ship biofouling<span class="n">3 activities</span></li>
                    <li><span class="a">A8</span>Web-based Regional Information System<span class="n">2 activities</span></li>
                </ul>
            </div>
            <div class="bw-pri">
                <span class="bw-k">Priorities 3 to 6</span>
                <h3>Expertise, political will, review and resources</h3>
                <ul>
                    <li><span class="a">A9</span>Capacity-building programme<span class="n">5 activities</span></li>
                    <li><span class="a">A10</span>Awareness among decision-makers and the public<span class="n">4 activities</span></li>
                    <li><span class="a">A11</span>Regular reviews of the Strategy<span class="n">3 activities</span></li>
                    <li><span class="a">A12</span>Resource mobilisation plan<span class="n">1 activity</span></li>
                </ul>
            </div>
        </div>
        <p class="bw-small">Action 3 connects the Strategy to IMAP: one of its activities adapts the guidance for Common Indicator 6 so that port survey data flow into the IMAP Info System.</p>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">Diagram 3 · Exchange in the Mediterranean until 2024</span>
        <h2>Where ships should exchange ballast water</h2>
        <p>The Mediterranean is a semi-enclosed sea, so it is often impossible to be 200 nautical miles from land. Until exchange was phased out in 2024, the Strategy proposed regional arrangements for where ships should exchange ballast water.</p>
        <ol class="bw-steps">
            <li><b>Entering or leaving the Mediterranean</b>Through Gibraltar or the Suez Canal: exchange outside the Mediterranean, at least 200 nautical miles from land in water 200 m deep, or at least 50 nautical miles if that is not possible.</li>
            <li><b>Sailing within the region</b>Between Mediterranean ports, or to and from the Black Sea and the Red Sea: exchange as far from land as possible, always at least 50 nautical miles out in water 200 m deep.</li>
            <li><b>When neither is possible</b>Use exchange areas designated by the port State under IMO Guidelines G14, after consulting neighbouring States and notifying IMO.</li>
        </ol>
        <p class="bw-src">If an exchange threatens the safety or stability of the ship, it is not carried out; the reason is recorded in the Ballast Water Record Book and reported to the port of destination.</p>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">Beyond ballast water</span>
        <h2>Biofouling: the other half of the shipping pathway</h2>
        <div class="bw-two">
            <div class="bw-stack">
                <p class="bw-small">Organisms that grow on hulls, niches and sea chests travel with every ship, whether it carries ballast or not. To reach Good Environmental Status, the Strategy recognises that the whole shipping pathway must be managed, not ballast water alone.</p>
                <p class="bw-small">The IMO's 2011 Biofouling Guidelines (resolution MEPC.207(62)) set the reference practice, and the GEF-UNDP-IMO GloFouling Partnerships project, launched in December 2018, supports countries in putting them into law and policy.</p>
            </div>
            <ol class="bw-steps" style="grid-template-columns: minmax(0, 1fr)">
                <li><b>Regional workshop</b>To start biofouling work in the Mediterranean</li>
                <li><b>National status assessments</b>Of biofouling in each country</li>
                <li><b>National strategies and action plans</b>To manage biofouling</li>
            </ol>
        </div>
    </section>

    <section class="bw-stack">
        <span class="bw-eyebrow">Who does what</span>
        <h2>A shared effort under the Barcelona Convention</h2>
        <div class="bw-two">
            <div class="bw-def"><h3>REMPEC and SPA/RAC</h3><p>REMPEC coordinates the Strategy, in cooperation with SPA/RAC, and both provide technical support to countries in synergy with IMO. Progress is reviewed at the meetings of the REMPEC Focal Points and the SPA/BD Focal Points, with a mid-term and a final review of the Strategy.</p></div>
            <div class="bw-def"><h3>Contracting Parties</h3><p>Countries ratify and apply the Convention, survey their ports, report ballast water data and build national capacity. Potential funding sources include the Mediterranean Trust Fund, IMO technical cooperation, the shipping and port industries and donors.</p></div>
        </div>
        <div class="bw-links">
            <a href="/pages/spa-bd-protocol"><b>SPA/BD Protocol</b><span>Article 13 on non-indigenous species</span></a>
            <a href="/pages/mediterranean-action-plan"><b>NIS Action Plan</b><span>Pathways beyond shipping, in tandem with this Strategy</span></a>
            <a href="/pages/imap"><b>IMAP</b><span>Common Indicator 6 and port monitoring</span></a>
            <a href="/pages/data"><b>Explore NIS data</b><span>Records in MAMIAS</span></a>
        </div>
    </section>

    <section class="bw-end bw-stack">
        <span class="bw-eyebrow">Sources</span>
        <ol class="bw-sources">
            <li>International Maritime Organization, Ballast Water Management. <a href="https://www.imo.org/en/ourwork/environment/pages/ballastwatermanagement.aspx" target="_blank" rel="noopener">imo.org</a></li>
            <li>REMPEC, Ballast Water Management Strategy. <a href="https://www.rempec.org/en/our-work/strategies-and-actions-plans/ballast-water-strategy" target="_blank" rel="noopener">rempec.org</a></li>
            <li>UNEP/MAP, Decision IG.25/17, Ballast Water Management Strategy for the Mediterranean Sea (2022–2027), COP 22, 2021. <a href="https://www.rempec.org/en/knowledge-centre/online-catalogue/med-bwm-strategy-2022-2027-1.pdf" target="_blank" rel="noopener">Document</a></li>
            <li>UNEP/MAP, 2023 Mediterranean Quality Status Report (MED QSR), for all figures on non-indigenous species. <a href="https://medqsr2023.info-rac.org/" target="_blank" rel="noopener">medqsr2023.info-rac.org</a></li>
        </ol>
    </section>

</div>
HTML;
    }
}
