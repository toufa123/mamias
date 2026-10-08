<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes the MAMIAS landing page as a Layup page.
 *
 * Mirrors resources/views/mamias/home.blade.php, less its Key Features, which
 * now sit on the About page under Our Mission (LayupAboutPageSeeder). The
 * carousel and CTA are static markup stored in `html` widgets, so the whole
 * page is editable from the page builder; the one live section, "MAMIAS at a
 * glance" under the carousel, is the mamias-summary widget
 * (App\Layup\Widgets\MamiasSummaryWidget). Their <style>/<script> are inlined because Layup renders widget HTML
 * outside the @push('styles')/@push('scripts') stacks.
 *
 * The CTA buttons still follow the visitor: Layup echoes stored HTML raw rather
 * than compiling it, so @auth cannot run inside page content. Instead the three
 * button variants all ship and the auth state class on <body> (see
 * resources/views/app.blade.php) reveals the right one.
 *
 * The page slug is `home`; config('layup.pages.default_slug') = 'home' makes
 * it serve at the site root `/`.
 */
class LayupHomePageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'content' => [
                    'rows' => [
                        [
                            'id' => 'row_carousel',
                            'settings' => ['gap' => 'gap-0'],
                            'columns' => [
                                [
                                    'id' => 'col_carousel',
                                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                                    'settings' => [],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_carousel',
                                            'type' => 'html',
                                            'data' => ['content' => self::carouselHtml()],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'row_summary',
                            'settings' => ['gap' => 'gap-0'],
                            'columns' => [
                                [
                                    'id' => 'col_summary',
                                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                                    'settings' => [],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_summary',
                                            'type' => 'mamias-summary',
                                            'data' => ['title' => null],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'row_cta',
                            'settings' => ['gap' => 'gap-0'],
                            'columns' => [
                                [
                                    'id' => 'col_cta',
                                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                                    'settings' => [],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_cta',
                                            'type' => 'html',
                                            'data' => ['content' => self::ctaHtml()],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    private static function carouselHtml(): string
    {
        return <<<'HTML'
<div class="mamias-carousel-wrap pt-0 pb-4">
    <div class="mamias-carousel overflow-hidden" id="mamiasCarousel">
        <div class="mamias-carousel-track" id="mamiasTrack">
            <div class="mamias-slide" style="background: linear-gradient(135deg, #003d61 0%, #005f98 100%);">
                <div class="mamias-slide-inner">
                    <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                    <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 1 — Mediterranean</span>
                </div>
            </div>
            <div class="mamias-slide" style="background: linear-gradient(135deg, #005f98 0%, #018d9a 100%);">
                <div class="mamias-slide-inner">
                    <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                    <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 2 — Marine Biodiversity</span>
                </div>
            </div>
            <div class="mamias-slide" style="background: linear-gradient(135deg, #018d9a 0%, #4cafbf 100%);">
                <div class="mamias-slide-inner">
                    <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                    <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 3 — Invasive Species</span>
                </div>
            </div>
            <div class="mamias-slide" style="background: linear-gradient(135deg, #4cafbf 0%, #003d61 100%);">
                <div class="mamias-slide-inner">
                    <i class="ki-filled ki-picture text-5xl opacity-30 text-white mb-3"></i>
                    <span class="text-white/60 text-sm font-medium tracking-widest uppercase">Slide 4 — SPA/RAC</span>
                </div>
            </div>
        </div>

        <button class="mamias-carousel-btn mamias-carousel-prev" id="mamiasCarouselPrev" aria-label="Previous">
            <i class="ki-filled ki-left"></i>
        </button>
        <button class="mamias-carousel-btn mamias-carousel-next" id="mamiasCarouselNext" aria-label="Next">
            <i class="ki-filled ki-right"></i>
        </button>

        <div class="mamias-carousel-dots" id="mamiasCarouselDots">
            <button class="mamias-dot active" data-slide="0" aria-label="Slide 1"></button>
            <button class="mamias-dot" data-slide="1" aria-label="Slide 2"></button>
            <button class="mamias-dot" data-slide="2" aria-label="Slide 3"></button>
            <button class="mamias-dot" data-slide="3" aria-label="Slide 4"></button>
        </div>
    </div>
</div>
<script>
(function () {
    const track = document.getElementById('mamiasTrack');
    const dots = document.querySelectorAll('#mamiasCarouselDots .mamias-dot');
    if (!track) return;
    let current = 0;
    const total = track.children.length;
    let timer;

    function goTo(index) {
        current = (index + total) % total;
        track.style.transform = `translateX(-${current * 100}%)`;
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    document.getElementById('mamiasCarouselNext')?.addEventListener('click', () => { clearInterval(timer); next(); resetTimer(); });
    document.getElementById('mamiasCarouselPrev')?.addEventListener('click', () => { clearInterval(timer); prev(); resetTimer(); });
    dots.forEach(d => d.addEventListener('click', () => { clearInterval(timer); goTo(+d.dataset.slide); resetTimer(); }));

    function resetTimer() { timer = setInterval(next, 5000); }
    resetTimer();
})();
</script>
HTML;
    }

    /**
     * Call-to-action section.
     *
     * All three button variants are rendered and the body's auth state class
     * picks one: staff go to the admin area, other signed-in users to the
     * report form, guests to register / sign in. Links are root-relative on
     * purpose — the stack answers on any hostname, and an absolute URL baked in
     * at seed time would pin the page to whichever APP_URL seeded it.
     */
    private static function ctaHtml(): string
    {
        $dashboard = route('filament.mamias.pages.dashboard', absolute: false);
        $reports = route('my-species-reports', absolute: false);
        $register = route('filament.mamias.auth.register', absolute: false);
        $login = route('filament.mamias.auth.login', absolute: false);
        // Not asset(): that returns an absolute URL built from the seeding
        // environment's APP_URL, which would pin the stored markup to one host.
        $logo = '/images/Logoweb.png';

        return <<<HTML
<style>
    .mamias-cta-auth, .mamias-cta-staff { display: none; }
    body.is-authenticated .mamias-cta-guest { display: none; }
    body.is-authenticated .mamias-cta-auth { display: inline-flex; }
    body.is-staff .mamias-cta-auth { display: none; }
    body.is-staff .mamias-cta-staff { display: inline-flex; }
</style>
<section class="py-20 bg-gray-50">
    <div class="kt-container-fixed">
        <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
            <div class="flex-1 text-center lg:text-left">
                <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 rounded-full px-4 py-1.5 mb-4">Get Started Today</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Add observation of Marine Non-Indigenous Species</h2>
                <p class="text-base text-gray-500 leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
                    Join hundreds of researchers and institutions using MAMIAS to monitor, report, and analyse Non-Indigenous Species data — powered by science, built for collaboration.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-3 justify-center lg:justify-start">
                    <a href="{$dashboard}" class="mamias-cta-staff items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                        <i class="ki-filled ki-element-11 text-base"></i>
                        Go to Admin Area
                    </a>
                    <a href="{$reports}" class="mamias-cta-auth items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                        <i class="ki-filled ki-element-11 text-base"></i>
                        Make a species report
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

            <div class="flex-1 w-full max-w-xl">
                <div class="rounded-xl shadow-2xl border border-gray-200 overflow-hidden bg-white">
                    <div class="flex items-center gap-2 px-4 py-3 bg-gray-100 border-b border-gray-200">
                        <div class="flex gap-1.5">
                            <span class="block size-3 rounded-full bg-red-400"></span>
                            <span class="block size-3 rounded-full bg-yellow-400"></span>
                            <span class="block size-3 rounded-full bg-green-400"></span>
                        </div>
                        <div class="flex-1 mx-2">
                            <div class="bg-white rounded-md px-3 py-1 text-xs text-gray-400 border border-gray-200 text-center truncate">mamias.org/mamias</div>
                        </div>
                    </div>
                    <div class="relative bg-gradient-to-br from-[#003d61] via-[#005f98] to-[#018d9a] aspect-video flex items-center justify-center">
                        <div class="text-center">
                            <img src="{$logo}" alt="MAMIAS Platform" class="max-h-16 mx-auto mb-4 opacity-90">
                            <div class="flex items-center justify-center gap-6 mt-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white">1 200+</div>
                                    <div class="text-xs text-white/60 uppercase tracking-wide">Species</div>
                                </div>
                                <div class="w-px h-8 bg-white/20"></div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white">22</div>
                                    <div class="text-xs text-white/60 uppercase tracking-wide">Countries</div>
                                </div>
                                <div class="w-px h-8 bg-white/20"></div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white">300+</div>
                                    <div class="text-xs text-white/60 uppercase tracking-wide">Researchers</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
HTML;
    }
}
