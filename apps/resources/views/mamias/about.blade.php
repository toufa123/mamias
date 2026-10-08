@extends('app')

@section('title', 'About MAMIAS')

@section('breadcrumbs')
    {{ Breadcrumbs::render('about') }}
@endsection

@section('content')
    {{-- Hero --}}
    <section
        class="py-20"
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

    {{-- Mission & Timeline --}}
    <section class="py-8">
        <div class="kt-container-fixed">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <span class="mb-4 inline-block bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">Our Mission</span>
                    <h2 class="mb-4 text-3xl font-bold text-foreground">
                        A shared knowledge base on non-indigenous species in the Mediterranean
                    </h2>
                    <p class="mb-4 leading-relaxed text-muted-foreground">
                        MAMIAS is the regional database of marine Non-Indigenous Species (NIS) in the Mediterranean,
                        developed and coordinated by SPA/RAC, the Specially Protected Areas Regional Activity Centre of
                        UNEP/MAP – Barcelona Convention. Since 2012 it has brought together validated records of which
                        species have arrived, where, when and by which pathway.
                    </p>
                    <p class="mb-4 leading-relaxed text-muted-foreground">
                        Its mission is to give the Contracting Parties to the Barcelona Convention, their national focal
                        points, scientists and decision-makers a common evidence base: to detect new arrivals early,
                        follow how species spread, and measure progress towards Good Environmental Status.
                    </p>
                    <p class="mb-4 leading-relaxed text-muted-foreground">
                        In doing so, MAMIAS supports the commitments of the Mediterranean countries on non-indigenous
                        species:
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="/pages/spa-bd-protocol" class="inline-block bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">SPA/BD Protocol · Article 13</a>
                        <a href="/pages/mediterranean-action-plan" class="inline-block bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">NIS Action Plan</a>
                        <a href="/pages/imap" class="inline-block bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">IMAP · Common Indicator 6</a>
                        <a href="/pages/post-2020-sapbio" class="inline-block bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">Post-2020 SAPBIO</a>
                    </div>
                </div>
                <div>
                    <span class="mb-4 inline-block bg-[#018d9a]/10 px-4 py-1.5 text-sm font-medium text-[#018d9a]">Our Journey</span>
                    <div class="space-y-8">
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
                            <h3 class="mt-1 text-lg font-semibold text-foreground">
                                Validated Inventories of Non-Indigenous Species (NIS) for the Mediterranean Sea as Tools for
                                Regional Policy and Patterns of NIS Spread
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                                MAMIAS also becomes a data partner of EASIN, the European Alien Species Information Network.
                            </p>
                        </div>
                        <div class="relative border-l-2 border-[#4cafbf] pl-10">
                            <div class="absolute top-0 left-0 size-4 -translate-x-1/2 rounded-full border-2 border-white bg-[#4cafbf]"></div>
                            <span class="text-sm font-bold text-[#018d9a]">2026</span>
                            <h3 class="mt-1 text-lg font-semibold text-foreground">Platform Redesign</h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                                Modern user interface, enhanced data visualisation, and improved reporting tools for
                                researchers and decision-makers.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
