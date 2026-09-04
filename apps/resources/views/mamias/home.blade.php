@extends('app')

@section('title', 'Home')

@section('content')
    {{-- Carousel --}}
    <div class="mamias-carousel-wrap pt-0 pb-4">
        <div class="mamias-carousel overflow-hidden" id="mamiasCarousel">
            <div class="mamias-carousel-track" id="mamiasTrack">
                <div class="mamias-slide" style="background: linear-gradient(135deg, #003d61 0%, #005f98 100%)">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture mb-3 text-5xl text-white opacity-30"></i>
                        <span class="text-sm font-medium tracking-widest text-white/60 uppercase">Slide 1 — Mediterranean</span>
                    </div>
                </div>
                <div class="mamias-slide" style="background: linear-gradient(135deg, #005f98 0%, #018d9a 100%)">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture mb-3 text-5xl text-white opacity-30"></i>
                        <span class="text-sm font-medium tracking-widest text-white/60 uppercase">Slide 2 — Marine Biodiversity</span>
                    </div>
                </div>
                <div class="mamias-slide" style="background: linear-gradient(135deg, #018d9a 0%, #4cafbf 100%)">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture mb-3 text-5xl text-white opacity-30"></i>
                        <span class="text-sm font-medium tracking-widest text-white/60 uppercase">Slide 3 — Invasive Species</span>
                    </div>
                </div>
                <div class="mamias-slide" style="background: linear-gradient(135deg, #4cafbf 0%, #003d61 100%)">
                    <div class="mamias-slide-inner">
                        <i class="ki-filled ki-picture mb-3 text-5xl text-white opacity-30"></i>
                        <span class="text-sm font-medium tracking-widest text-white/60 uppercase">Slide 4 — SPA/RAC</span>
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
    <section id="features" class="bg-white py-20">
        <div class="kt-container-fixed">
            {{-- Section header --}}
            <div class="mb-14 flex flex-col items-center gap-3 text-center">
                <a
                    href="#features"
                    class="text-primary hover:text-primary/80 border-primary border-b pb-0.5 text-sm font-medium"
                >Key Features</a>
                <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">MAMIAS Key Features</h2>
                <p class="max-w-2xl text-base text-gray-500">
                    Our platform provides all the tools you need to monitor, report, and analyse Non-Indigenous Species
                    data across the Mediterranean.
                </p>
            </div>

            {{-- Cards grid --}}
            <div class="features-grid">
                {{-- Card 1 — Lightning Workflows --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex size-12 items-center justify-center rounded-full bg-blue-50">
                            <i class="ki-filled ki-flash text-xl text-blue-500"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">10x faster</div>
                            <div class="text-xs font-semibold tracking-wider text-gray-400 uppercase">
                                Speed Increase
                            </div>
                        </div>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-900">Lightning Workflows</h3>
                    <p class="text-sm leading-relaxed text-gray-500">
                        Supercharge your daily operations with automation that not only saves time, but intelligently
                        adapts to your evolving business routines.
                    </p>
                    <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] transition-transform duration-500 group-hover:scale-x-100"></div>
                </div>

                {{-- Card 2 — Adaptive Safeguards --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex size-12 items-center justify-center rounded-full bg-red-50">
                            <i class="ki-filled ki-shield-tick text-xl text-red-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">99.9%</div>
                            <div class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Uptime</div>
                        </div>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-900">Adaptive Safeguards</h3>
                    <p class="text-sm leading-relaxed text-gray-500">
                        Protect your data and streamline processes with real-time AI security, adapting instantly to
                        threats and keeping your operations resilient and confidential.
                    </p>
                    <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] transition-transform duration-500 group-hover:scale-x-100"></div>
                </div>

                {{-- Card 3 — Smart Team Sync (featured/active) --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex size-12 items-center justify-center rounded-full bg-[#4cafbf]/10">
                            <i class="ki-filled ki-people text-xl text-[#018d9a]"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">10k+</div>
                            <div class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Active Users</div>
                        </div>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-900">Smart Team Sync</h3>
                    <p class="text-sm leading-relaxed text-gray-500">
                        Let AI handle the chaos of calendars and meetings — Smart Team Sync coordinates, schedules, and
                        adapts to your team's needs, so you can focus on what matters most.
                    </p>
                    <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] transition-transform duration-500 group-hover:scale-x-100"></div>
                </div>

                {{-- Card 4 — Predictive Insights --}}
                <div class="group relative overflow-hidden rounded-none border border-gray-200 bg-white p-8 transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex size-12 items-center justify-center rounded-full bg-amber-50">
                            <i class="ki-filled ki-graph-up text-xl text-amber-500"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">25%</div>
                            <div class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Growth Boost</div>
                        </div>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-900">Predictive Insights</h3>
                    <p class="text-sm leading-relaxed text-gray-500">
                        Reveal hidden trends and forecast outcomes with analytics that learn from your unique data,
                        giving you a competitive edge and actionable clarity.
                    </p>
                    <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 bg-gradient-to-r from-[#018d9a] via-[#4cafbf] to-[#005f98] transition-transform duration-500 group-hover:scale-x-100"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="bg-gray-50 py-20">
        <div class="kt-container-fixed">
            <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-16">
                {{-- Text content --}}
                <div class="flex-1 text-center lg:text-left">
                    <span class="mb-4 inline-block rounded-full bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">Get Started Today</span>
                    <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                        Add observation of Marine Non-Indigenous Species
                    </h2>
                    <p class="mx-auto mb-8 max-w-lg text-base leading-relaxed text-gray-500 lg:mx-0">
                        Join hundreds of researchers and institutions using MAMIAS to monitor, report, and analyse
                        Non-Indigenous Species data — powered by science, built for collaboration.
                    </p>
                    <div class="flex flex-col items-center justify-center gap-3 sm:flex-row lg:justify-start">
                        @auth
                            @if (auth()->user()->hasAnyRole(['super_admin', 'scientist', 'admin']))
                                <a
                                    href="{{ route('filament.mamias.pages.dashboard') }}"
                                    class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                                    style="background: linear-gradient(135deg, #018d9a, #005f98)"
                                >
                                    <i class="ki-filled ki-element-11 text-base"></i>
                                    Go to Admin Area
                                </a>
                            @else
                                <a
                                    href="{{ route('filament.mamias.pages.dashboard') }}"
                                    class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                                    style="background: linear-gradient(135deg, #018d9a, #005f98)"
                                >
                                    <i class="ki-filled ki-element-11 text-base"></i>
                                    Make a species report
                                </a>
                            @endif
                        @else
                            <a
                                href="{{ route('filament.mamias.auth.register') }}"
                                class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                                style="background: linear-gradient(135deg, #018d9a, #005f98)"
                            >
                                <i class="ki-filled ki-user-plus text-base"></i>
                                Create Free Account
                            </a>
                            <a
                                href="{{ route('filament.mamias.auth.login') }}"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition-all duration-300 hover:border-[#4cafbf] hover:text-[#018d9a]"
                            >
                                <i class="ki-filled ki-entrance-left text-base"></i>
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- App screenshot --}}
                <div class="w-full max-w-xl flex-1">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
                        {{-- Browser chrome --}}
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-100 px-4 py-3">
                            <div class="flex gap-1.5">
                                <span class="block size-3 rounded-full bg-red-400"></span>
                                <span class="block size-3 rounded-full bg-yellow-400"></span>
                                <span class="block size-3 rounded-full bg-green-400"></span>
                            </div>
                            <div class="mx-2 flex-1">
                                <div class="truncate rounded-md border border-gray-200 bg-white px-3 py-1 text-center text-xs text-gray-400">
                                    mamias.org/mamias
                                </div>
                            </div>
                        </div>
                        {{-- Screenshot placeholder --}}
                        <div class="relative flex aspect-video items-center justify-center bg-gradient-to-br from-[#003d61] via-[#005f98] to-[#018d9a]">
                            <div class="text-center">
                                <img
                                    src="{{ asset('images/Logoweb.png') }}"
                                    alt="MAMIAS Platform"
                                    class="mx-auto mb-4 max-h-16 opacity-90"
                                />
                                <div class="mt-4 flex items-center justify-center gap-6">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-white">1 200+</div>
                                        <div class="text-xs tracking-wide text-white/60 uppercase">Species</div>
                                    </div>
                                    <div class="h-8 w-px bg-white/20"></div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-white">22</div>
                                        <div class="text-xs tracking-wide text-white/60 uppercase">Countries</div>
                                    </div>
                                    <div class="h-8 w-px bg-white/20"></div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-white">300+</div>
                                        <div class="text-xs tracking-wide text-white/60 uppercase">Researchers</div>
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

            function next() {
                goTo(current + 1);
            }
            function prev() {
                goTo(current - 1);
            }

            document.getElementById('mamiasCarouselNext')?.addEventListener('click', () => {
                clearInterval(timer);
                next();
                resetTimer();
            });
            document.getElementById('mamiasCarouselPrev')?.addEventListener('click', () => {
                clearInterval(timer);
                prev();
                resetTimer();
            });
            dots.forEach((d) =>
                d.addEventListener('click', () => {
                    clearInterval(timer);
                    goTo(+d.dataset.slide);
                    resetTimer();
                }),
            );

            function resetTimer() {
                timer = setInterval(next, 5000);
            }
            resetTimer();
        })();
    </script>
@endpush
