@extends('app')

@section('title', 'Home')


@section('content')
    {{-- Carousel --}}
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
    </div>

    {{-- Key Features --}}
    <section id="features" class="py-20 bg-white">
        <div class="kt-container-fixed">
            {{-- Section header --}}
            <div class="flex flex-col items-center text-center gap-3 mb-14">
                <a href="#features" class="text-sm font-medium text-primary hover:text-primary/80 border-b border-primary pb-0.5">Key Features</a>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">MAMIAS Key Features</h2>
                <p class="text-base text-gray-500 max-w-2xl">
                    Our platform provides all the tools you need to monitor, report, and analyse Non-Indigenous Species data across the Mediterranean.
                </p>
            </div>

            {{-- Cards grid --}}
            <div class="features-grid">

                {{-- Card 1 — Lightning Workflows --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-blue-50">
                            <i class="ki-filled ki-flash text-xl text-blue-500"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">10x faster</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Speed Increase</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Lightning Workflows</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Supercharge your daily operations with automation that not only saves time, but intelligently adapts to your evolving business routines.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

                {{-- Card 2 — Adaptive Safeguards --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-red-50">
                            <i class="ki-filled ki-shield-tick text-xl text-red-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">99.9%</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Uptime</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Adaptive Safeguards</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Protect your data and streamline processes with real-time AI security, adapting instantly to threats and keeping your operations resilient and confidential.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

                {{-- Card 3 — Smart Team Sync (featured/active) --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-[#4cafbf]/10">
                            <i class="ki-filled ki-people text-xl text-[#018d9a]"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">10k+</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Users</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Smart Team Sync</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Let AI handle the chaos of calendars and meetings — Smart Team Sync coordinates, schedules, and adapts to your team's needs, so you can focus on what matters most.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

                {{-- Card 4 — Predictive Insights --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:shadow-lg hover:border-[#4cafbf]">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-full flex items-center justify-center bg-amber-50">
                            <i class="ki-filled ki-graph-up text-xl text-amber-500"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">25%</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Growth Boost</div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Predictive Insights</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Reveal hidden trends and forecast outcomes with analytics that learn from your unique data, giving you a competitive edge and actionable clarity.</p>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                </div>

            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-gray-50">
        <div class="kt-container-fixed">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
                {{-- Text content --}}
                <div class="flex-1 text-center lg:text-left">
                    <span class="inline-block text-sm font-medium text-[#018d9a] bg-[#018d9a]/10 rounded-full px-4 py-1.5 mb-4">Get Started Today</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Add observation of Marine Non-Indigenous Species</h2>
                    <p class="text-base text-gray-500 leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
                        Join hundreds of researchers and institutions using MAMIAS to monitor, report, and analyse Non-Indigenous Species data — powered by science, built for collaboration.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center gap-3 justify-center lg:justify-start">
                        @auth
                            @if(auth()->user()->hasAnyRole(['super_admin', 'scientist', 'admin']))
                                <a href="{{ route('filament.mamias.pages.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                                    <i class="ki-filled ki-element-11 text-base"></i>
                                    Go to Admin Area
                                </a>
                            @else
                                <a href="{{ route('filament.mamias.pages.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                                    <i class="ki-filled ki-element-11 text-base"></i>
                                    Make a species report
                                </a>
                            @endif
                        @else
                            <a href="{{ route('filament.mamias.auth.register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white font-semibold text-sm transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, #018d9a, #005f98);">
                                <i class="ki-filled ki-user-plus text-base"></i>
                                Create Free Account
                            </a>
                            <a href="{{ route('filament.mamias.auth.login') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-semibold text-sm transition-all duration-300 hover:border-[#4cafbf] hover:text-[#018d9a] bg-white">
                                <i class="ki-filled ki-entrance-left text-base"></i>
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- App screenshot --}}
                <div class="flex-1 w-full max-w-xl">
                    <div class="rounded-xl shadow-2xl border border-gray-200 overflow-hidden bg-white">
                        {{-- Browser chrome --}}
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
                        {{-- Screenshot placeholder --}}
                        <div class="relative bg-gradient-to-br from-[#003d61] via-[#005f98] to-[#018d9a] aspect-video flex items-center justify-center">
                            <div class="text-center">
                                <img src="{{ asset('images/Logoweb.png') }}" alt="MAMIAS Platform" class="max-h-16 mx-auto mb-4 opacity-90">
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
@endsection

@push('styles')
<style>
.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 48rem) {
    .features-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
@endpush

@push('scripts')
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
@endpush
