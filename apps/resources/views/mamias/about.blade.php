@extends('app')

@section('title', 'About MAMIAS')

@section('breadcrumbs')
    {{ Breadcrumbs::render('about') }}
@endsection

@section('content')
    {{-- Hero --}}
    <section
        class="rounded-xl py-20"
        style="background: linear-gradient(135deg, #003d61 0%, #005f98 50%, #018d9a 100%)"
    >
        <div class="kt-container-fixed text-center">
            <h1 class="mb-4 text-4xl font-bold text-white md:text-5xl">About MAMIAS</h1>
            <p class="mx-auto max-w-2xl text-lg leading-relaxed text-white/70">
                Marine Mediterranean Invasive Alien Species — a science-driven platform for monitoring, reporting, and
                analysing Non-Indigenous Species data across the Mediterranean.
            </p>
        </div>
    </section>

    {{-- Mission & Stats --}}
    <section class="py-20">
        <div class="kt-container-fixed">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <span class="mb-4 inline-block rounded-full bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">Our Mission</span>
                    <h2 class="mb-4 text-3xl font-bold text-foreground">Monitoring Marine Biodiversity</h2>
                    <p class="mb-4 leading-relaxed text-muted-foreground">
                        MAMIAS is a regional platform developed by SPA/RAC to support Mediterranean countries in
                        monitoring and managing Non-Indigenous Species (NIS) introductions. Since 2012, it has been the
                        reference database for marine NIS in the region.
                    </p>
                    <p class="leading-relaxed text-muted-foreground">
                        The platform brings together researchers, institutions, and policymakers to share data, track
                        invasions, and inform evidence-based management decisions.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="text-3xl font-bold text-[#005f98]">1 200+</div>
                        <div class="mt-1 text-sm text-muted-foreground">Species</div>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="text-3xl font-bold text-[#005f98]">22</div>
                        <div class="mt-1 text-sm text-muted-foreground">Countries</div>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="text-3xl font-bold text-[#005f98]">300+</div>
                        <div class="mt-1 text-sm text-muted-foreground">Researchers</div>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="text-3xl font-bold text-[#005f98]">2012</div>
                        <div class="mt-1 text-sm text-muted-foreground">Since</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Timeline --}}
    <section class="bg-muted py-20">
        <div class="kt-container-fixed">
            <h2 class="mb-14 text-center text-3xl font-bold text-foreground">Our Journey</h2>
            <div class="mx-auto max-w-3xl space-y-8">
                <div class="relative border-l-2 border-[#4cafbf] pl-10">
                    <div class="absolute top-0 left-0 size-4 -translate-x-1/2 rounded-full border-2 border-white bg-[#4cafbf]"></div>
                    <span class="text-sm font-bold text-[#018d9a]">2012</span>
                    <h3 class="mt-1 text-lg font-semibold text-foreground">Launch of MAMIAS</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                        Establishment of the Mediterranean platform for monitoring Non-Indigenous Species, coordinated
                        by SPA/RAC.
                    </p>
                </div>
                <div class="relative border-l-2 border-[#4cafbf] pl-10">
                    <div class="absolute top-0 left-0 size-4 -translate-x-1/2 rounded-full border-2 border-white bg-[#4cafbf]"></div>
                    <span class="text-sm font-bold text-[#018d9a]">2016</span>
                    <h3 class="mt-1 text-lg font-semibold text-foreground">Regional Expansion</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                        Expansion to 22 Mediterranean countries with standardized data collection protocols.
                    </p>
                </div>
                <div class="relative border-l-2 border-[#4cafbf] pl-10">
                    <div class="absolute top-0 left-0 size-4 -translate-x-1/2 rounded-full border-2 border-white bg-[#4cafbf]"></div>
                    <span class="text-sm font-bold text-[#018d9a]">2024</span>
                    <h3 class="mt-1 text-lg font-semibold text-foreground">Platform Redesign</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                        Modern user interface, enhanced data visualisation, and improved reporting tools for researchers
                        and decision-makers.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Partners --}}
    <section class="py-20">
        <div class="kt-container-fixed">
            <h2 class="mb-14 text-center text-3xl font-bold text-foreground">Partners & Contributors</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-8 text-center transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#018d9a]/10">
                        <i class="ki-filled ki-building text-2xl text-[#018d9a]"></i>
                    </div>
                    <h3 class="text-lg font-bold text-foreground">SPA/RAC</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Regional Activity Centre for Specially Protected Areas</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-8 text-center transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#018d9a]/10">
                        <i class="ki-filled ki-globe text-2xl text-[#018d9a]"></i>
                    </div>
                    <h3 class="text-lg font-bold text-foreground">UNEP/MAP</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        United Nations Environment Programme / Mediterranean Action Plan
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-8 text-center transition-all duration-300 hover:border-[#4cafbf] hover:shadow-lg">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#018d9a]/10">
                        <i class="ki-filled ki-people text-2xl text-[#018d9a]"></i>
                    </div>
                    <h3 class="text-lg font-bold text-foreground">National Focal Points</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Researchers and institutions across 22 Mediterranean countries
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-muted py-20">
        <div class="kt-container-fixed mx-auto max-w-3xl">
            <h2 class="mb-14 text-center text-3xl font-bold text-foreground">Frequently Asked Questions</h2>
            <div class="space-y-4" data-kt-accordion="true" data-kt-accordion-expand="false">
                <div class="rounded-xl border border-border bg-card" data-kt-accordion-item="true">
                    <div
                        class="flex cursor-pointer items-center justify-between px-6 py-5 select-none"
                        data-kt-accordion-toggle="true"
                    >
                        <h3 class="text-base font-semibold text-foreground">What is MAMIAS?</h3>
                        <i class="ki-filled ki-plus kt-accordion-active:rotate-45 text-lg text-muted-foreground transition-transform duration-200"></i>
                    </div>
                    <div class="hidden px-6 pb-5" data-kt-accordion-content="true">
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            MAMIAS (Marine Mediterranean Invasive Alien Species) is a regional platform developed by
                            SPA/RAC for monitoring, reporting, and analysing Non-Indigenous Species data across the
                            Mediterranean Sea.
                        </p>
                    </div>
                </div>
                <div class="rounded-xl border border-border bg-card" data-kt-accordion-item="true">
                    <div
                        class="flex cursor-pointer items-center justify-between px-6 py-5 select-none"
                        data-kt-accordion-toggle="true"
                    >
                        <h3 class="text-base font-semibold text-foreground">Who can use MAMIAS?</h3>
                        <i class="ki-filled ki-plus kt-accordion-active:rotate-45 text-lg text-muted-foreground transition-transform duration-200"></i>
                    </div>
                    <div class="hidden px-6 pb-5" data-kt-accordion-content="true">
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            MAMIAS is open to researchers, institutions, and policymakers involved in marine
                            biodiversity monitoring and management across the Mediterranean region.
                        </p>
                    </div>
                </div>
                <div class="rounded-xl border border-border bg-card" data-kt-accordion-item="true">
                    <div
                        class="flex cursor-pointer items-center justify-between px-6 py-5 select-none"
                        data-kt-accordion-toggle="true"
                    >
                        <h3 class="text-base font-semibold text-foreground">How do I add an observation?</h3>
                        <i class="ki-filled ki-plus kt-accordion-active:rotate-45 text-lg text-muted-foreground transition-transform duration-200"></i>
                    </div>
                    <div class="hidden px-6 pb-5" data-kt-accordion-content="true">
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            Create a free account, then navigate to the dashboard and use the observation form to submit
                            your NIS record with species identification, location, and supporting references.
                        </p>
                    </div>
                </div>
                <div class="rounded-xl border border-border bg-card" data-kt-accordion-item="true">
                    <div
                        class="flex cursor-pointer items-center justify-between px-6 py-5 select-none"
                        data-kt-accordion-toggle="true"
                    >
                        <h3 class="text-base font-semibold text-foreground">Is my data publicly accessible?</h3>
                        <i class="ki-filled ki-plus kt-accordion-active:rotate-45 text-lg text-muted-foreground transition-transform duration-200"></i>
                    </div>
                    <div class="hidden px-6 pb-5" data-kt-accordion-content="true">
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            Submitted observations are reviewed before publication. Published data is accessible to all
                            registered users and contributes to the regional NIS knowledge base.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20">
        <div class="kt-container-fixed text-center">
            <span class="mb-4 inline-block rounded-full bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">Get Involved</span>
            <h2 class="mb-4 text-3xl font-bold text-foreground md:text-4xl">Join the MAMIAS Community</h2>
            <p class="mx-auto mb-8 max-w-lg text-base leading-relaxed text-muted-foreground">
                Contribute to the regional effort in monitoring and managing Non-Indigenous Species in the
                Mediterranean.
            </p>
            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <a
                        href="{{ route('filament.mamias.pages.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                        style="background: linear-gradient(135deg, #018d9a, #005f98)"
                    >
                        <i class="ki-filled ki-element-11 text-base"></i>
                        Go to Dashboard
                    </a>
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
                        class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-6 py-3 text-sm font-semibold text-foreground transition-all duration-300 hover:border-[#4cafbf] hover:text-[#018d9a]"
                    >
                        <i class="ki-filled ki-entrance-left text-base"></i>
                        Sign In
                    </a>
                @endauth
            </div>
        </div>
    </section>
@endsection
