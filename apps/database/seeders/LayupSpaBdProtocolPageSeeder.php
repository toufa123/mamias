<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes /pages/spa-bd-protocol (Resources › SPA/BD Protocol): non-indigenous
 * species under Article 13 of the SPA/BD Protocol and its relation to the CBD.
 *
 * One html widget. Its styles are scoped under `.mamias-spabd` and read the
 * site tokens from app.css (--border, --card, --mamias-teal-*, --status-invasive-*),
 * so the page follows DESIGN-SYSTEM.md and the light/dark switch without new
 * Tailwind utilities, which the build would purge from CMS content. No <h1>:
 * the site's page header already shows the title.
 *
 * Overwrites the page content. Run: php artisan db:seed --class=LayupSpaBdProtocolPageSeeder
 */
class LayupSpaBdProtocolPageSeeder extends Seeder
{
    public const SLUG = 'pages/spa-bd-protocol';

    public function run(): void
    {
        Page::withTrashed()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'deleted_at' => null,
                'title' => 'Non-indigenous species under the SPA/BD Protocol',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => [
                    'description' => 'Non-indigenous species under Article 13 of the SPA/BD Protocol of the Barcelona Convention, and how it relates to the Convention on Biological Diversity.',
                ],
                'content' => [
                    'rows' => [[
                        'id' => 'row_spabd',
                        'settings' => ['gap' => 'gap-0'],
                        'columns' => [[
                            'id' => 'col_spabd',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => 'widget_spabd',
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
.mamias-spabd {
    --sb-ink: var(--foreground);
    --sb-ink-2: #47606b;
    --sb-muted: var(--muted-foreground);
    --sb-line: var(--border);
    --sb-line-soft: var(--secondary);
    --sb-surface: var(--card);
    --sb-sunken: var(--muted);
    --sb-tint: var(--accent);
    --sb-teal-text: var(--mamias-teal-600);
    --sb-teal-deco: var(--mamias-teal-500);
    --sb-teal-fill: var(--mamias-teal-600);
    --sb-on-teal: #fff;
    --sb-navy: #2a2b74;
    display: grid; gap: 64px; padding-block: 24px 64px; color: var(--sb-ink);
    font-family: var(--font-sans); font-size: 16px; line-height: 26px;
}
html.dark .mamias-spabd {
    --sb-ink-2: #b3c7ce;
    --sb-teal-text: var(--mamias-teal-300);
    --sb-teal-deco: var(--mamias-teal-400);
    --sb-teal-fill: var(--mamias-teal-400);
    --sb-on-teal: var(--background);
    --sb-navy: #8f91d9;
}
.mamias-spabd * { box-sizing: border-box; }
.mamias-spabd h2, .mamias-spabd h3 { margin: 0; color: var(--sb-ink); text-wrap: balance; }
.mamias-spabd h2 { font-size: 20px; line-height: 28px; font-weight: 500; }
.mamias-spabd h3 { font-size: 16px; line-height: 24px; font-weight: 500; }
.mamias-spabd p { margin: 0; }
.mamias-spabd .sb-stack { display: grid; gap: 16px; }
.mamias-spabd .sb-col { max-width: none; }
.mamias-spabd .sb-eyebrow { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sb-muted); }
.mamias-spabd .sb-lede { color: var(--sb-ink-2); }
.mamias-spabd .sb-small { font-size: 14px; line-height: 22px; color: var(--sb-ink-2); }
.mamias-spabd em { font-style: italic; }
.mamias-spabd a { color: var(--sb-teal-text); text-underline-offset: 2px; }
.mamias-spabd a:focus-visible { outline: 2px solid var(--sb-teal-deco); outline-offset: 2px; }
.mamias-spabd .sb-art { font: 400 13px/20px var(--font-mono); color: var(--sb-teal-text); background: var(--sb-tint); border: 1px solid var(--sb-line); padding: 0 6px; white-space: nowrap; }
.mamias-spabd .sb-art.g { color: var(--sb-ink-2); background: var(--sb-line-soft); }

/* Timeline: teal-500 draws rule and markers only; teal-600 carries years and the filled band. */
.mamias-spabd .sb-tl-scroll { overflow-x: auto; }
.mamias-spabd .sb-tl { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(3, minmax(9rem, 1fr)) minmax(13rem, 1.5fr) minmax(9rem, 1fr); min-width: 54rem; }
.mamias-spabd .sb-tl b.and { margin-top: 8px; }
.mamias-spabd .sb-tl li { position: relative; padding: 28px 16px 0 0; display: grid; gap: 2px; align-content: start; }
.mamias-spabd .sb-tl li::before { content: ""; position: absolute; left: 0; right: 0; top: 7px; height: 2px; background: var(--sb-teal-deco); }
.mamias-spabd .sb-tl li::after { content: ""; position: absolute; left: 0; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--sb-surface); border: 2px solid var(--sb-teal-deco); }
.mamias-spabd .sb-tl li.g::after { border-color: var(--sb-muted); }
.mamias-spabd .sb-tl .y { font: 400 20px/28px var(--font-mono); letter-spacing: -.4px; color: var(--sb-teal-text); font-variant-numeric: tabular-nums; }
.mamias-spabd .sb-tl li.g .y { color: var(--sb-ink-2); }
.mamias-spabd .sb-tl b { font-weight: 500; font-size: 14px; line-height: 22px; }
.mamias-spabd .sb-tl .d { font: 400 12px/18px var(--font-mono); color: var(--sb-muted); }
@media (max-width: 640px) {
    .mamias-spabd .sb-tl { grid-template-columns: 1fr; min-width: 0; border-left: 2px solid var(--sb-teal-deco); margin-left: 5px; }
    .mamias-spabd .sb-tl li { padding: 0 0 16px 20px; }
    .mamias-spabd .sb-tl li::before { display: none; }
    .mamias-spabd .sb-tl li::after { left: -8px; top: 8px; }
}

.mamias-spabd .sb-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); gap: 24px; }
.mamias-spabd .sb-def { display: grid; gap: 4px; align-content: start; padding-top: 12px; border-top: 1px solid var(--sb-line); }
.mamias-spabd .sb-def p { font-size: 14px; line-height: 22px; color: var(--sb-ink-2); }

.mamias-spabd .sb-article { background: var(--sb-surface); border: 1px solid var(--sb-line); padding: 24px; display: grid; gap: 24px; }
.mamias-spabd .sb-paras { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr)); gap: 24px; }
.mamias-spabd .sb-para { display: grid; gap: 8px; align-content: start; }
.mamias-spabd .sb-num { font: 400 13px/20px var(--font-mono); color: var(--sb-teal-text); }
.mamias-spabd .sb-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.mamias-spabd .sb-chip { font: 500 11px/16px var(--font-sans); letter-spacing: .08em; text-transform: uppercase; padding: 2px 8px; background: var(--sb-tint); color: var(--sb-teal-text); border: 1px solid var(--sb-line); }
.mamias-spabd .sb-chip.a { background: var(--status-invasive-fill); color: var(--status-invasive-text); border-color: var(--status-invasive-border); }
.mamias-spabd .sb-note { font-size: 14px; line-height: 22px; color: var(--sb-ink-2); border-top: 1px solid var(--sb-line-soft); padding-top: 16px; }

.mamias-spabd figure { margin: 0; display: grid; gap: 12px; }
.mamias-spabd figcaption { font-size: 14px; line-height: 22px; color: var(--sb-muted); }
.mamias-spabd .sb-layer { border: 1px solid var(--sb-line); padding: 16px; display: grid; gap: 12px; background: var(--sb-surface); }
.mamias-spabd .sb-layer .h { display: flex; flex-wrap: wrap; gap: 4px 16px; align-items: baseline; justify-content: space-between; }
.mamias-spabd .sb-layer .s { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sb-muted); }
.mamias-spabd .sb-layer p { font-size: 14px; line-height: 22px; color: var(--sb-ink-2); }
.mamias-spabd .sb-layer.l1 { background: var(--sb-sunken); }
.mamias-spabd .sb-layer.l2 { border-top: 2px solid var(--sb-navy); }
.mamias-spabd .sb-layer.l2 .s { color: var(--sb-navy); }
.mamias-spabd .sb-layer.l3 { border-color: var(--sb-teal-deco); background: var(--sb-tint); }
.mamias-spabd .sb-layer.l3 .s { color: var(--sb-teal-text); }

.mamias-spabd .sb-split { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 32px; align-items: start; }
@media (max-width: 760px) { .mamias-spabd .sb-split { grid-template-columns: minmax(0, 1fr); } }
.mamias-spabd .sb-fig { overflow-x: auto; }
.mamias-spabd svg text { font-family: var(--font-sans); fill: var(--sb-ink); }
.mamias-spabd svg .lbl { font-size: 13px; font-weight: 500; }
.mamias-spabd svg .sub { font-size: 11px; fill: var(--sb-muted); }
.mamias-spabd svg .mono { font-family: var(--font-mono); font-size: 10.5px; letter-spacing: .06em; fill: var(--sb-teal-text); }

.mamias-spabd .sb-paths { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 19rem), 1fr)); gap: 1px; background: var(--sb-line); border: 1px solid var(--sb-line); }
.mamias-spabd .sb-path { background: var(--sb-surface); padding: 16px; display: grid; grid-template-rows: auto auto auto 1fr auto; gap: 8px; }
.mamias-spabd .sb-path svg { width: 32px; height: 32px; }
.mamias-spabd .sb-path .c { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sb-muted); }
.mamias-spabd .sb-path p { font-size: 14px; line-height: 22px; color: var(--sb-ink-2); }
.mamias-spabd .sb-path .ex { color: var(--sb-ink); border-top: 1px solid var(--sb-line-soft); padding-top: 8px; }

.mamias-spabd .sb-table { overflow-x: auto; border: 1px solid var(--sb-line); background: var(--sb-surface); }
.mamias-spabd table { border-collapse: collapse; width: 100%; min-width: 36rem; font-size: 14px; line-height: 22px; }
.mamias-spabd th, .mamias-spabd td { text-align: left; vertical-align: top; padding: 12px 16px; border-bottom: 1px solid var(--sb-line-soft); }
.mamias-spabd thead th { font: 500 11px/16px var(--font-sans); letter-spacing: .16em; text-transform: uppercase; color: var(--sb-muted); background: var(--sb-sunken); border-bottom: 1px solid var(--sb-line); }
.mamias-spabd tbody tr:last-child td { border-bottom: 0; }
.mamias-spabd td:first-child { font-weight: 500; width: 24%; }

.mamias-spabd .sb-cta { display: flex; flex-wrap: wrap; gap: 12px; }
.mamias-spabd .sb-btn { display: inline-flex; align-items: center; height: 40px; padding: 0 16px; border: 1px solid var(--sb-line); color: var(--sb-teal-text); background: var(--sb-surface); text-decoration: none; font: 500 14px/22px var(--font-sans); }
.mamias-spabd .sb-btn:hover { border-color: var(--sb-teal-deco); }
.mamias-spabd .sb-btn.p { background: var(--primary); border-color: var(--primary); color: var(--primary-foreground); }
.mamias-spabd .sb-btn.p:hover { background: var(--mamias-teal-700); border-color: var(--mamias-teal-700); }
html.dark .mamias-spabd .sb-btn.p:hover { background: var(--mamias-teal-300); border-color: var(--mamias-teal-300); }
.mamias-spabd .sb-sources { font-size: 14px; line-height: 22px; color: var(--sb-muted); display: grid; gap: 4px; padding-left: 20px; margin: 0; }
.mamias-spabd .sb-end { border-top: 1px solid var(--border); padding-top: 32px; }
</style>

<div class="mamias-spabd">

    <header class="sb-stack">
        <span class="sb-eyebrow">Non-indigenous species · Article 13</span>
        <p class="sb-lede">The Mediterranean is one of the seas most affected by marine biological invasions. The SPA/BD Protocol of the Barcelona Convention commits its Parties to regulate new introductions and to eradicate harmful ones. The obligation mirrors Article 8(h) of the global Convention on Biological Diversity, and MAMIAS is one of the tools that helps put it into practice.</p>
        <div class="sb-tl-scroll">
            <ol class="sb-tl" aria-label="SPA/BD Protocol timeline">
                <li><span class="y">1976</span><b>Barcelona Convention adopted</b><span class="d">Protection against pollution</span></li>
                <li><span class="y">1982</span><b>SPA Protocol</b><span class="d">Specially protected areas</span></li>
                <li class="g"><span class="y">1992</span><b>Convention on Biological Diversity</b><span class="d">Global · Art. 8(h)</span></li>
                <li class="w"><span class="y">1995</span><b>Barcelona Convention amended</b><span class="d">Marine environment and coastal region</span><b class="and">SPA/BD Protocol adopted</b><span class="d">Replaces the 1982 Protocol</span></li>
                <li><span class="y">1999</span><b>SPA/BD Protocol in force</b><span class="d">Legally binding</span></li>
            </ol>
        </div>
    </header>

    <section class="sb-stack">
        <span class="sb-eyebrow">Key terms</span>
        <h2>What counts as a non-indigenous species?</h2>
        <div class="sb-grid">
            <div class="sb-def"><h3>Non-indigenous species (NIS)</h3><p>A species, subspecies or lower taxon introduced outside its natural past or present range by human activity, directly or indirectly. Also called alien or exotic species.</p></div>
            <div class="sb-def"><h3>Invasive NIS</h3><p>A non-indigenous species that spreads and harms biodiversity, ecosystem services, the economy or human health. Only a fraction of NIS become invasive.</p></div>
            <div class="sb-def"><h3>Cryptogenic species</h3><p>A species whose origin cannot be established with certainty as native or introduced. MAMIAS records these separately so they are not over- or under-counted.</p></div>
            <div class="sb-def"><h3>Lessepsian migrant</h3><p>A Red Sea or Indo-Pacific species that entered the Mediterranean through the Suez Canal, opened in 1869. This corridor is the largest single source of Mediterranean NIS.</p></div>
        </div>
    </section>

    <section class="sb-stack">
        <span class="sb-eyebrow">The legal core</span>
        <h2>Article 13 in two obligations</h2>
        <div class="sb-article">
            <div class="sb-paras">
                <div class="sb-para">
                    <span class="sb-num">Art. 13 · paragraph 1</span>
                    <h3>Regulate and prohibit introductions</h3>
                    <p class="sb-small">Parties take all appropriate measures to regulate the intentional or accidental introduction of non-indigenous or genetically modified species into the wild, and prohibit those that may harm ecosystems, habitats or species in the Protocol area.</p>
                    <div class="sb-chips"><span class="sb-chip">Prevent</span><span class="sb-chip">Regulate</span><span class="sb-chip">Prohibit</span></div>
                </div>
                <div class="sb-para">
                    <span class="sb-num">Art. 13 · paragraph 2</span>
                    <h3>Eradicate harmful species already present</h3>
                    <p class="sb-small">Parties endeavour to implement all possible measures to eradicate species already introduced when a scientific assessment shows they cause, or are likely to cause, damage to ecosystems, habitats or species.</p>
                    <div class="sb-chips"><span class="sb-chip a">Assess</span><span class="sb-chip a">Eradicate</span></div>
                </div>
            </div>
            <p class="sb-note">Inside Specially Protected Areas, <span class="sb-art">Art. 6</span> adds a site-level duty: Parties regulate the introduction of any species not indigenous to the protected area, as well as genetically modified species. <span class="sb-art">Art. 3</span> sets the general obligation to protect, preserve and manage biological diversity, which frames both.</p>
        </div>
    </section>

    <section class="sb-stack">
        <span class="sb-eyebrow">Diagram 1 · From global to regional</span>
        <h2>How the SPA/BD Protocol fits within the CBD</h2>
        <p class="sb-col">The CBD sets the global commitment. The Barcelona Convention and its Protocols turn it into regional, legally binding obligations for the Mediterranean, and the SPA/BD Protocol is where the duties on non-indigenous species are written down.</p>
        <figure>
            <div class="sb-layer l1">
                <div class="h"><h3>Convention on Biological Diversity (1992)</h3><span class="s">Global</span></div>
                <p><span class="sb-art g">Art. 8(h)</span> prevent the introduction of, control or eradicate alien species that threaten ecosystems, habitats or species. Operationalised by the 2002 Guiding Principles on invasive alien species.</p>
                <div class="sb-layer l2">
                    <div class="h"><h3>Barcelona Convention and the Mediterranean Action Plan (UNEP/MAP)</h3><span class="s">Regional framework</span></div>
                    <p>The 1976 Convention, amended in 1995, and its seven Protocols. UNEP/MAP coordinates the system, with SPA/RAC in Tunis supporting biodiversity work.</p>
                    <div class="sb-layer l3">
                        <div class="h"><h3>SPA/BD Protocol (1995)</h3><span class="s">Regional legal obligation</span></div>
                        <p><span class="sb-art">Art. 13</span> regulate, prohibit and eradicate · <span class="sb-art">Art. 6</span> controls inside protected areas</p>
                    </div>
                </div>
            </div>
            <figcaption>Each layer sits inside the one above it: the global commitment, the regional framework, and the Protocol that sets the Mediterranean obligations.</figcaption>
        </figure>
    </section>

    <section class="sb-stack">
        <span class="sb-eyebrow">Diagram 2 · The management hierarchy</span>
        <h2>Prevention first, eradication second, control last</h2>
        <div class="sb-split">
            <figure>
                <div class="sb-fig">
                    <svg viewBox="0 0 440 330" width="100%" role="img" aria-labelledby="sb-h-title">
                        <title id="sb-h-title">Three-stage hierarchy: prevention, early detection and eradication, containment and control. Cost rises and success falls at each stage.</title>
                        <polygon points="20,20 360,20 312,110 68,110" fill="var(--sb-tint)" stroke="var(--sb-teal-deco)" stroke-width="1.5"/>
                        <polygon points="72,118 308,118 260,208 120,208" fill="var(--sb-surface)" stroke="var(--sb-teal-deco)" stroke-width="1.5"/>
                        <polygon points="124,216 256,216 220,300 160,300" fill="var(--status-invasive-fill)" stroke="var(--status-invasive-border)" stroke-width="1.5"/>
                        <text x="190" y="56" text-anchor="middle" class="lbl">1 · Prevention</text>
                        <text x="190" y="75" text-anchor="middle" class="sub">Pathway and vector controls</text>
                        <text x="190" y="91" text-anchor="middle" class="mono">ART. 13(1)</text>
                        <text x="190" y="152" text-anchor="middle" class="lbl">2 · Early detection</text>
                        <text x="190" y="169" text-anchor="middle" class="sub">and rapid eradication</text>
                        <text x="190" y="187" text-anchor="middle" class="mono">ART. 13(2)</text>
                        <text x="190" y="248" text-anchor="middle" class="lbl">3 · Control</text>
                        <text x="190" y="265" text-anchor="middle" class="sub">Containment</text>
                        <text x="190" y="282" text-anchor="middle" class="mono">ART. 13(2)</text>
                        <line x1="392" y1="30" x2="392" y2="290" stroke="var(--sb-muted)" stroke-width="1.5"/>
                        <polygon points="386,288 398,288 392,302" fill="var(--sb-muted)"/>
                        <text x="404" y="120" class="sub" transform="rotate(90 404 120)">Cost rises · success falls</text>
                    </svg>
                </div>
                <figcaption>The three-stage approach comes from the CBD Guiding Principles (Decision VI/23, 2002). Article 13 of the SPA/BD Protocol follows the same order.</figcaption>
            </figure>
            <div class="sb-stack">
                <div class="sb-def"><h3>1 · Prevention</h3><p>Stopping a species before it arrives is the cheapest and most effective option. It covers ballast water treatment, hull-fouling management, checks on aquaculture stock and controls on the ornamental trade.</p></div>
                <div class="sb-def"><h3>2 · Early detection and rapid response</h3><p>Eradication is only realistic while a population is small and local. That makes early records essential: one confirmed sighting reported to MAMIAS can trigger a response.</p></div>
                <div class="sb-def"><h3>3 · Containment and long-term control</h3><p>Once a species is established at sea, eradication is rarely possible. Management then aims to limit spread and impacts, for example through targeted removal in protected areas or commercial use of species such as lionfish.</p></div>
            </div>
        </div>
    </section>

    <section class="sb-stack">
        <span class="sb-eyebrow">Infographic · Pathways of introduction</span>
        <h2>How species reach the Mediterranean</h2>
        <p class="sb-col">Preventing introductions under Article 13(1) means acting on pathways. The CBD groups them into six categories, from deliberate introduction to natural spread. IMAP and MAMIAS use the same classification.</p>
        <div class="sb-paths">
            <article class="sb-path">
                <svg viewBox="0 0 36 36" aria-hidden="true"><path d="M18 5 V22" stroke="var(--sb-teal-deco)" stroke-width="2.2" stroke-linecap="round"/><path d="M11 16 L18 23 L25 16" fill="none" stroke="var(--sb-teal-deco)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 29 q4 -4 7 0 t7 0 t7 0 t7 0" fill="none" stroke="var(--status-invasive-text)" stroke-width="1.8"/></svg>
                <span class="c">Intentional</span><h3>Release in nature</h3>
                <p>Deliberate introduction of a species into the wild, for example to create or support a fishery.</p>
                <p class="ex">e.g. <em>Ruditapes philippinarum</em>, introduced for clam harvesting</p>
            </article>
            <article class="sb-path">
                <svg viewBox="0 0 36 36" aria-hidden="true"><rect x="5" y="8" width="20" height="20" fill="var(--sb-tint)" stroke="var(--sb-teal-deco)" stroke-width="2"/><path d="M22 18 H32 M28 14 L32 18 L28 22" fill="none" stroke="var(--status-invasive-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="c">Intentional, then unintended</span><h3>Escape from confinement</h3>
                <p>A species kept in captivity, in aquaculture farms or aquaria, escapes into the wild.</p>
                <p class="ex">e.g. <em>Caulerpa taxifolia</em>, first recorded off Monaco in 1984</p>
            </article>
            <article class="sb-path">
                <svg viewBox="0 0 36 36" aria-hidden="true"><rect x="5" y="11" width="22" height="16" fill="none" stroke="var(--sb-teal-deco)" stroke-width="2"/><circle cx="28" cy="10" r="4" fill="var(--status-invasive-text)"/><path d="M9 19 H23" stroke="var(--sb-teal-deco)" stroke-width="1.5"/></svg>
                <span class="c">Unintentional</span><h3>Transport · contaminant</h3>
                <p>A species carried along with a traded commodity, such as parasites or algae moved with live shellfish stock.</p>
                <p class="ex">e.g. <em>Sargassum muticum</em>, arrived with oyster imports</p>
            </article>
            <article class="sb-path">
                <svg viewBox="0 0 36 36" aria-hidden="true"><path d="M5 12 H31 V26 H5 Z" fill="var(--sb-tint)" stroke="var(--sb-teal-deco)" stroke-width="2"/><circle cx="13" cy="19" r="2.5" fill="var(--status-invasive-text)"/><circle cx="22" cy="21" r="2" fill="var(--status-invasive-text)"/></svg>
                <span class="c">Unintentional</span><h3>Transport · stowaway</h3>
                <p>A species travelling inside or attached to a vector, for example in ballast water or as hull fouling.</p>
                <p class="ex">e.g. <em>Mnemiopsis leidyi</em></p>
            </article>
            <article class="sb-path">
                <svg viewBox="0 0 36 36" aria-hidden="true"><path d="M4 30 C10 22, 14 22, 18 18 S26 10, 32 6" fill="none" stroke="var(--sb-teal-deco)" stroke-width="2.5" stroke-linecap="round"/><circle cx="4" cy="30" r="3" fill="var(--sb-teal-deco)"/><circle cx="32" cy="6" r="3" fill="var(--sb-teal-deco)"/></svg>
                <span class="c">Unintentional</span><h3>Corridor</h3>
                <p>A species moves on its own through an artificial waterway that links previously separate seas.</p>
                <p class="ex">e.g. <em>Lagocephalus sceleratus</em>, <em>Siganus luridus</em></p>
            </article>
            <article class="sb-path">
                <svg viewBox="0 0 36 36" aria-hidden="true"><path d="M4 18 q4 -6 8 0 t8 0 t8 0 t8 0" fill="none" stroke="var(--sb-teal-deco)" stroke-width="2"/><path d="M18 26 l6 -4 -6 -4" fill="none" stroke="var(--status-invasive-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="c">Unintentional</span><h3>Unaided</h3>
                <p>Natural spread from a neighbouring region where the species was itself introduced by one of the other pathways.</p>
                <p class="ex">e.g. westward spread of <em>Pterois miles</em></p>
            </article>
        </div>
    </section>

    <section class="sb-stack">
        <span class="sb-eyebrow">Relation with the CBD</span>
        <h2>SPA/BD provisions and their global counterparts</h2>
        <p class="sb-col">The Protocol was adopted three years after the CBD and follows its logic closely. The table pairs each Mediterranean provision with the matching CBD text.</p>
        <div class="sb-table">
            <table>
                <thead><tr><th>Theme</th><th>SPA/BD Protocol</th><th>CBD text</th></tr></thead>
                <tbody>
                    <tr><td>Preventing introductions</td><td><span class="sb-art">Art. 13(1)</span> regulate intentional and accidental introductions, prohibit harmful ones</td><td><span class="sb-art g">Art. 8(h)</span> prevent the introduction of threatening alien species</td></tr>
                    <tr><td>Eradication and control</td><td><span class="sb-art">Art. 13(2)</span> eradicate damaging species after scientific assessment</td><td><span class="sb-art g">Art. 8(h)</span> control or eradicate; Guiding Principles 12 to 15</td></tr>
                    <tr><td>Protected areas</td><td><span class="sb-art">Art. 6</span> regulate non-indigenous species inside SPAs and SPAMIs</td><td><span class="sb-art g">Art. 8(a)–(c)</span> establish and manage protected areas</td></tr>
                    <tr><td>Knowledge and monitoring</td><td><span class="sb-art">Art. 15</span> inventories · <span class="sb-art">Art. 20</span> scientific research</td><td><span class="sb-art g">Art. 7</span> identification and monitoring</td></tr>
                    <tr><td>Precaution</td><td>Risk-based prohibition in Art. 13(1)</td><td>Guiding Principle 1, precautionary approach</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="sb-stack">
        <span class="sb-eyebrow">Diagram 3 · From a sighting to a decision</span>
        <h2>Where MAMIAS sits in the policy cycle</h2>
        <div class="sb-split">
            <figure>
                <div class="sb-fig">
                    <svg viewBox="0 0 420 380" width="100%" role="img" aria-labelledby="sb-c-title">
                        <title id="sb-c-title">Policy cycle: field records, MAMIAS database, national IMAP reporting, regional assessment, COP decisions, national measures, back to field records.</title>
                        <defs><marker id="sb-arr" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 Z" fill="var(--sb-muted)"/></marker></defs>
                        <circle cx="210" cy="190" r="128" fill="none" stroke="var(--sb-line)" stroke-width="2"/>
                        <path d="M 248 68 A 128 128 0 0 1 318 120" fill="none" stroke="var(--sb-muted)" stroke-width="1.5" marker-end="url(#sb-arr)"/>
                        <path d="M 336 170 A 128 128 0 0 1 320 245" fill="none" stroke="var(--sb-muted)" stroke-width="1.5" marker-end="url(#sb-arr)"/>
                        <path d="M 280 297 A 128 128 0 0 1 200 318" fill="none" stroke="var(--sb-muted)" stroke-width="1.5" marker-end="url(#sb-arr)"/>
                        <path d="M 140 300 A 128 128 0 0 1 92 245" fill="none" stroke="var(--sb-muted)" stroke-width="1.5" marker-end="url(#sb-arr)"/>
                        <path d="M 84 170 A 128 128 0 0 1 102 118" fill="none" stroke="var(--sb-muted)" stroke-width="1.5" marker-end="url(#sb-arr)"/>
                        <path d="M 140 82 A 128 128 0 0 1 170 68" fill="none" stroke="var(--sb-muted)" stroke-width="1.5" marker-end="url(#sb-arr)"/>
                        <rect x="150" y="38" width="120" height="44" fill="var(--sb-surface)" stroke="var(--sb-line)"/><text x="210" y="57" text-anchor="middle" class="lbl">Field records</text><text x="210" y="73" text-anchor="middle" class="sub">scientists · citizens</text>
                        <rect x="292" y="110" width="118" height="50" fill="var(--sb-tint)" stroke="var(--sb-teal-deco)" stroke-width="1.5"/><text x="351" y="131" text-anchor="middle" class="lbl">MAMIAS</text><text x="351" y="148" text-anchor="middle" class="sub">validated regional data</text>
                        <rect x="292" y="232" width="118" height="50" fill="var(--sb-surface)" stroke="var(--sb-line)"/><text x="351" y="253" text-anchor="middle" class="lbl">IMAP reporting</text><text x="351" y="270" text-anchor="middle" class="sub">EO2 · Common Ind. 6</text>
                        <rect x="150" y="300" width="120" height="50" fill="var(--sb-surface)" stroke="var(--sb-line)"/><text x="210" y="321" text-anchor="middle" class="lbl">Assessment</text><text x="210" y="338" text-anchor="middle" class="sub">Quality Status Report</text>
                        <rect x="10" y="232" width="118" height="50" fill="var(--sb-surface)" stroke="var(--sb-navy)" stroke-width="1.5"/><text x="69" y="253" text-anchor="middle" class="lbl">COP decisions</text><text x="69" y="270" text-anchor="middle" class="sub">Barcelona · CBD</text>
                        <rect x="10" y="110" width="118" height="50" fill="var(--sb-surface)" stroke="var(--sb-line)"/><text x="69" y="131" text-anchor="middle" class="lbl">National action</text><text x="69" y="148" text-anchor="middle" class="sub">Art. 13 measures</text>
                        <text x="210" y="186" text-anchor="middle" class="mono">ADAPTIVE</text>
                        <text x="210" y="202" text-anchor="middle" class="mono">MANAGEMENT</text>
                    </svg>
                </div>
                <figcaption>Every validated record strengthens the evidence used to judge progress towards Good Environmental Status.</figcaption>
            </figure>
            <div class="sb-stack">
                <p>Article 13 can only be applied to species that are known to be present. The cycle starts with a record: a diver, fisher or researcher spots a species outside its native range.</p>
                <p>MAMIAS validates and stores the record with its location, date, pathway and literature source. Countries draw on this data for their IMAP reports under Ecological Objective 2, which asks whether new introductions are being kept to a minimum.</p>
                <p>Regional assessments, such as the Mediterranean Quality Status Report, then feed the decisions taken by the Contracting Parties. Those decisions update the action plans and national measures, and monitoring checks whether they work.</p>
                <div class="sb-cta">
                    <a class="sb-btn p" href="/pages/data">Explore NIS data</a>
                    <a class="sb-btn" href="/pages/map">Open the map</a>
                </div>
            </div>
        </div>
    </section>

    <section class="sb-end sb-stack sb-col">
        <span class="sb-eyebrow">Sources</span>
        <ol class="sb-sources">
            <li>Protocol concerning Specially Protected Areas and Biological Diversity in the Mediterranean (1995), Articles 3, 6, 13, 15 and 20. UNEP/MAP.</li>
            <li>Convention on Biological Diversity (1992), Articles 7 and 8(h).</li>
            <li>CBD Decision VI/23, Guiding Principles for the prevention, introduction and mitigation of impacts of alien species (2002).</li>
            <li>UNEP/MAP Integrated Monitoring and Assessment Programme (IMAP), Ecological Objective 2, Common Indicator 6.</li>
        </ol>
    </section>

</div>
HTML;
    }
}
