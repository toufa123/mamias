<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes the MAMIAS "About" page as a Layup page.
 *
 * Mirrors resources/views/mamias/about.blade.php:
 * Every section — hero, mission & stats, timeline, partners, FAQ and CTA — is
 * static markup stored in `html` widgets, so the whole page is editable from the
 * page builder and no bespoke widget class exists for it. (The FAQ accordion is
 * driven by KTUI's data-kt-accordion attributes, initialised by the shared app
 * layout.)
 *
 * The CTA buttons still follow the visitor: Layup echoes stored HTML raw rather
 * than compiling it, so @auth cannot run inside page content. Instead both
 * button variants ship and the auth state class on <body> (see
 * resources/views/app.blade.php) reveals the right one.
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
                        self::htmlRow('row_timeline', 'col_timeline', 'widget_about_timeline', self::timelineHtml()),
                        self::htmlRow('row_partners', 'col_partners', 'widget_about_partners', self::partnersHtml()),
                        self::htmlRow('row_faq', 'col_faq', 'widget_about_faq', self::faqHtml()),
                        self::htmlRow('row_about_cta', 'col_about_cta', 'widget_about_cta', self::ctaHtml()),
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
<section class="py-20 rounded-xl" style="background: linear-gradient(135deg, #003d61 0%, #005f98 50%, #018d9a 100%);">
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
<section class="py-20">
    <div class="kt-container-fixed">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div>
                <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 rounded-full px-4 py-1.5 mb-4">Our Mission</span>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Monitoring Marine Biodiversity</h2>
                <p class="text-gray-500 leading-relaxed mb-4">
                    MAMIAS is a regional platform developed by SPA/RAC to support Mediterranean countries in monitoring and managing Non-Indigenous Species (NIS) introductions. Since 2012, it has been the reference database for marine NIS in the region.
                </p>
                <p class="text-gray-500 leading-relaxed">
                    The platform brings together researchers, institutions, and policymakers to share data, track invasions, and inform evidence-based management decisions.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div class="rounded-xl border border-gray-200 p-6 text-center bg-white">
                    <div class="text-3xl font-bold text-[#005f98]">1 200+</div>
                    <div class="text-sm text-gray-500 mt-1">Species</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-6 text-center bg-white">
                    <div class="text-3xl font-bold text-[#005f98]">22</div>
                    <div class="text-sm text-gray-500 mt-1">Countries</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-6 text-center bg-white">
                    <div class="text-3xl font-bold text-[#005f98]">300+</div>
                    <div class="text-sm text-gray-500 mt-1">Researchers</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-6 text-center bg-white">
                    <div class="text-3xl font-bold text-[#005f98]">2012</div>
                    <div class="text-sm text-gray-500 mt-1">Since</div>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    private static function timelineHtml(): string
    {
        return <<<'HTML'
<section class="py-20 bg-gray-50">
    <div class="kt-container-fixed">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-14">Our Journey</h2>
        <div class="space-y-8 max-w-3xl mx-auto">
            <div class="relative pl-10 border-l-2 border-[#4cafbf]">
                <div class="absolute left-0 top-0 -translate-x-1/2 size-4 rounded-full bg-[#4cafbf] border-2 border-white"></div>
                <span class="text-sm font-bold text-[#018d9a]">2012</span>
                <h3 class="text-lg font-semibold text-gray-900 mt-1">Launch of MAMIAS</h3>
                <p class="text-gray-500 text-sm leading-relaxed mt-1">Establishment of the Mediterranean platform for monitoring Non-Indigenous Species, coordinated by SPA/RAC.</p>
            </div>
            <div class="relative pl-10 border-l-2 border-[#4cafbf]">
                <div class="absolute left-0 top-0 -translate-x-1/2 size-4 rounded-full bg-[#4cafbf] border-2 border-white"></div>
                <span class="text-sm font-bold text-[#018d9a]">2016</span>
                <h3 class="text-lg font-semibold text-gray-900 mt-1">Regional Expansion</h3>
                <p class="text-gray-500 text-sm leading-relaxed mt-1">Expansion to 22 Mediterranean countries with standardized data collection protocols.</p>
            </div>
            <div class="relative pl-10 border-l-2 border-[#4cafbf]">
                <div class="absolute left-0 top-0 -translate-x-1/2 size-4 rounded-full bg-[#4cafbf] border-2 border-white"></div>
                <span class="text-sm font-bold text-[#018d9a]">2024</span>
                <h3 class="text-lg font-semibold text-gray-900 mt-1">Platform Redesign</h3>
                <p class="text-gray-500 text-sm leading-relaxed mt-1">Modern user interface, enhanced data visualisation, and improved reporting tools for researchers and decision-makers.</p>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    private static function partnersHtml(): string
    {
        return <<<'HTML'
<section class="py-20">
    <div class="kt-container-fixed">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-14">Partners & Contributors</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-xl border border-gray-200 p-8 text-center bg-white transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                <div class="size-16 rounded-full bg-[#018d9a]/10 flex items-center justify-center mx-auto mb-4">
                    <i class="ki-filled ki-building text-2xl text-[#018d9a]"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">SPA/RAC</h3>
                <p class="text-sm text-gray-500 mt-1">Regional Activity Centre for Specially Protected Areas</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-8 text-center bg-white transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                <div class="size-16 rounded-full bg-[#018d9a]/10 flex items-center justify-center mx-auto mb-4">
                    <i class="ki-filled ki-globe text-2xl text-[#018d9a]"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">UNEP/MAP</h3>
                <p class="text-sm text-gray-500 mt-1">United Nations Environment Programme / Mediterranean Action Plan</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-8 text-center bg-white transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                <div class="size-16 rounded-full bg-[#018d9a]/10 flex items-center justify-center mx-auto mb-4">
                    <i class="ki-filled ki-people text-2xl text-[#018d9a]"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">National Focal Points</h3>
                <p class="text-sm text-gray-500 mt-1">Researchers and institutions across 22 Mediterranean countries</p>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    private static function faqHtml(): string
    {
        return <<<'HTML'
<section class="py-20 bg-gray-50">
    <div class="kt-container-fixed max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-14">Frequently Asked Questions</h2>
        <div class="space-y-4" data-kt-accordion="true" data-kt-accordion-expand="false">
            <div class="rounded-xl border border-gray-200 bg-white" data-kt-accordion-item="true">
                <div class="flex items-center justify-between px-6 py-5 cursor-pointer select-none" data-kt-accordion-toggle="true">
                    <h3 class="text-base font-semibold text-gray-900">What is MAMIAS?</h3>
                    <i class="ki-filled ki-plus text-gray-400 text-lg transition-transform duration-200 kt-accordion-active:rotate-45"></i>
                </div>
                <div class="px-6 pb-5 hidden" data-kt-accordion-content="true">
                    <p class="text-sm text-gray-500 leading-relaxed">MAMIAS (Marine Mediterranean Invasive Alien Species) is a regional platform developed by SPA/RAC for monitoring, reporting, and analysing Non-Indigenous Species data across the Mediterranean Sea.</p>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white" data-kt-accordion-item="true">
                <div class="flex items-center justify-between px-6 py-5 cursor-pointer select-none" data-kt-accordion-toggle="true">
                    <h3 class="text-base font-semibold text-gray-900">Who can use MAMIAS?</h3>
                    <i class="ki-filled ki-plus text-gray-400 text-lg transition-transform duration-200 kt-accordion-active:rotate-45"></i>
                </div>
                <div class="px-6 pb-5 hidden" data-kt-accordion-content="true">
                    <p class="text-sm text-gray-500 leading-relaxed">MAMIAS is open to researchers, institutions, and policymakers involved in marine biodiversity monitoring and management across the Mediterranean region.</p>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white" data-kt-accordion-item="true">
                <div class="flex items-center justify-between px-6 py-5 cursor-pointer select-none" data-kt-accordion-toggle="true">
                    <h3 class="text-base font-semibold text-gray-900">How do I add an observation?</h3>
                    <i class="ki-filled ki-plus text-gray-400 text-lg transition-transform duration-200 kt-accordion-active:rotate-45"></i>
                </div>
                <div class="px-6 pb-5 hidden" data-kt-accordion-content="true">
                    <p class="text-sm text-gray-500 leading-relaxed">Create a free account, then navigate to the dashboard and use the observation form to submit your NIS record with species identification, location, and supporting references.</p>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white" data-kt-accordion-item="true">
                <div class="flex items-center justify-between px-6 py-5 cursor-pointer select-none" data-kt-accordion-toggle="true">
                    <h3 class="text-base font-semibold text-gray-900">Is my data publicly accessible?</h3>
                    <i class="ki-filled ki-plus text-gray-400 text-lg transition-transform duration-200 kt-accordion-active:rotate-45"></i>
                </div>
                <div class="px-6 pb-5 hidden" data-kt-accordion-content="true">
                    <p class="text-sm text-gray-500 leading-relaxed">Submitted observations are reviewed before publication. Published data is accessible to all registered users and contributes to the regional NIS knowledge base.</p>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    /**
     * Call-to-action section.
     *
     * Both button variants are rendered and the body's auth state class picks
     * one: signed-in visitors get the dashboard, guests get register / sign in.
     * Links are root-relative on purpose — the stack answers on any hostname,
     * and an absolute URL baked in at seed time would pin the page to whichever
     * APP_URL seeded it.
     */
    private static function ctaHtml(): string
    {
        $dashboard = route('filament.mamias.pages.dashboard', absolute: false);
        $register = route('filament.mamias.auth.register', absolute: false);
        $login = route('filament.mamias.auth.login', absolute: false);

        return <<<HTML
<style>
    .mamias-cta-auth { display: none; }
    body.is-authenticated .mamias-cta-guest { display: none; }
    body.is-authenticated .mamias-cta-auth { display: inline-flex; }
</style>
<section class="py-20">
    <div class="kt-container-fixed text-center">
        <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 rounded-full px-4 py-1.5 mb-4">Get Involved</span>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Join the MAMIAS Community</h2>
        <p class="text-base text-gray-500 max-w-lg mx-auto leading-relaxed mb-8">
            Contribute to the regional effort in monitoring and managing Non-Indigenous Species in the Mediterranean.
        </p>
        <div class="flex flex-col sm:flex-row items-center gap-3 justify-center">
            <a href="{$dashboard}" class="mamias-cta-auth items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                <i class="ki-filled ki-element-11 text-base"></i>
                Go to Dashboard
            </a>
            <a href="{$register}" class="mamias-cta-guest inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                <i class="ki-filled ki-user-plus text-base"></i>
                Create Free Account
            </a>
            <a href="{$login}" class="mamias-cta-guest inline-flex items-center gap-2 px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-semibold text-sm transition-all duration-300 hover:border-[#4cafbf] hover:text-[#018d9a] bg-white">
                <i class="ki-filled ki-entrance-left text-base"></i>
                Sign In
            </a>
        </div>
    </div>
</section>
HTML;
    }
}
