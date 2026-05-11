@extends('app')

@section('title', 'About MAMIAS')

@section('breadcrumbs')
    {{ Breadcrumbs::render('about') }}
@endsection

@push('head')
<style>
    /* ── About / MAMIAS — page-scoped utilities, brand-aligned ──────────── */
    .about-hero {
        background:
            radial-gradient(60% 60% at 15% 10%, rgba(76,175,191,0.35), transparent 60%),
            radial-gradient(50% 60% at 90% 90%, rgba(0,95,152,0.45), transparent 65%),
            linear-gradient(135deg, #003d61 0%, #005f98 55%, #018d9a 100%);
    }
    .about-grain::before {
        content: "";
        position: absolute; inset: 0;
        pointer-events: none;
        background-image: url("data:image/svg+xml;utf8,<svg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' seed='3'/><feColorMatrix values='0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 0.05 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
        opacity: .55;
        mix-blend-mode: overlay;
    }
    .about-num {
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.04em;
        line-height: 1;
    }
    .about-pulse {
        transform-origin: center;
        transform-box: fill-box;
        animation: aboutPulse 2.6s ease-in-out infinite;
    }
    @keyframes aboutPulse {
        0%, 100% { opacity: .9;  transform: scale(1); }
        50%      { opacity: .35; transform: scale(1.7); }
    }
    .about-marquee {
        mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
    }
    .about-marquee-track {
        animation: aboutMarquee 38s linear infinite;
        width: max-content;
    }
    @keyframes aboutMarquee {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }
    .about-card {
        position: relative; overflow: hidden;
    }
    .about-card::after {
        content: "";
        position: absolute; left: 0; right: 0; bottom: 0; height: 3px;
        background: linear-gradient(90deg, #018d9a, #4cafbf, #005f98);
        transform: scaleX(0); transform-origin: left;
        transition: transform .5s cubic-bezier(.4,0,.2,1);
    }
    .about-card:hover::after { transform: scaleX(1); }
    .about-step { position: relative; }
    .about-step::before {
        content: "";
        position: absolute; left: -22px; top: 8px;
        width: 12px; height: 12px; border-radius: 9999px;
        background: #4cafbf; box-shadow: 0 0 0 4px #003d61, 0 0 0 5px rgba(76,175,191,.25);
    }
    .about-step.is-warm::before {
        background: #e26a53; box-shadow: 0 0 0 4px #003d61, 0 0 0 5px rgba(226,106,83,.3);
    }
    .about-reveal { opacity: 0; transform: translateY(14px); transition: opacity .7s ease, transform .7s ease; }
    .about-reveal.is-in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .about-reveal { opacity: 1; transform: none; transition: none; }
        .about-pulse, .about-marquee-track { animation: none; }
    }
    /* Accordion */
    details.about-acc > summary { list-style: none; cursor: pointer; }
    details.about-acc > summary::-webkit-details-marker { display: none; }
    details.about-acc[open] .about-acc-plus { transform: rotate(45deg); }
    .about-acc-plus { transition: transform .25s ease; display: inline-block; }
</style>
@endpush

@section('content')

    {{-- ───────────────────────── HERO ───────────────────────── --}}
    <section class="about-hero about-grain relative overflow-hidden rounded-2xl text-white">
        <div class="relative px-6 sm:px-10 lg:px-14 py-16 lg:py-24">

            <div class="flex flex-wrap items-center gap-2 mb-8">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/85 bg-white/10 border border-white/20 backdrop-blur rounded-full px-3 py-1">
                    <i class="ki-filled ki-calendar text-xs"></i> Since 2012
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/85 bg-white/10 border border-white/20 backdrop-blur rounded-full px-3 py-1">
                    <i class="ki-filled ki-shield text-xs"></i> UNEP/MAP · Barcelona Convention
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/85 bg-white/10 border border-white/20 backdrop-blur rounded-full px-3 py-1">
                    <i class="ki-filled ki-geolocation text-xs"></i> SPA/RAC coordinated
                </span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-[1.05] tracking-tight max-w-4xl">
                A shared <span class="text-[#4cafbf]">memory</span> of the Mediterranean's
                <span class="block">unwelcome arrivals.</span>
            </h1>

            <p class="mt-8 max-w-2xl text-base sm:text-lg text-white/80 leading-relaxed">
                <strong class="text-white">MAMIAS</strong> — Marine Mediterranean Invasive Alien Species —
                is the regional information platform that gathers, harmonises and shares
                what we know about non-indigenous and invasive marine life across the
                Mediterranean Sea. It serves science, policy and the public under the
                mandate of the Barcelona Convention.
            </p>

            <div class="mt-12 grid grid-cols-2 lg:grid-cols-4 gap-y-8 gap-x-6 max-w-4xl">
                <div>
                    <div class="about-num text-4xl lg:text-5xl font-bold text-[#4cafbf]">~1,000</div>
                    <div class="mt-2 text-[11px] uppercase tracking-[0.18em] text-white/60">marine alien species reported</div>
                </div>
                <div>
                    <div class="about-num text-4xl lg:text-5xl font-bold text-white">22</div>
                    <div class="mt-2 text-[11px] uppercase tracking-[0.18em] text-white/60">contracting parties (21 + EU)</div>
                </div>
                <div>
                    <div class="about-num text-4xl lg:text-5xl font-bold text-[#ffb199]">&gt; 50%</div>
                    <div class="mt-2 text-[11px] uppercase tracking-[0.18em] text-white/60">considered established</div>
                </div>
                <div>
                    <div class="about-num text-4xl lg:text-5xl font-bold text-white">7</div>
                    <div class="mt-2 text-[11px] uppercase tracking-[0.18em] text-white/60">main introduction pathways</div>
                </div>
            </div>

            <div class="mt-14 flex items-center gap-3 text-white/55 text-[11px] uppercase tracking-[0.25em]">
                <span class="inline-block w-10 h-px bg-white/30"></span>
                Scroll to read
            </div>
        </div>
    </section>

    {{-- ───────────────────── SPECIES MARQUEE ─────────────────── --}}
    <section class="rounded-2xl border border-gray-200 bg-white py-5 about-marquee overflow-hidden">
        <div class="flex about-marquee-track gap-10 whitespace-nowrap text-gray-500 text-sm tracking-wider uppercase">
            @php
                $species = [
                    'Caulerpa cylindracea', 'Siganus luridus', 'Rhopilema nomadica',
                    'Pterois miles', 'Fistularia commersonii', 'Callinectes sapidus',
                    'Caulerpa taxifolia', 'Lagocephalus sceleratus', 'Percnon gibbesi',
                    'Brachidontes pharaonis', 'Halophila stipulacea', 'Plotosus lineatus',
                ];
            @endphp
            @for ($i = 0; $i < 2; $i++)
                @foreach ($species as $sp)
                    <span class="shrink-0 italic">{{ $sp }}</span>
                    <span class="shrink-0 text-[#4cafbf]">·</span>
                @endforeach
            @endfor
        </div>
    </section>

    {{-- ──────────────── 01 — WHAT IS MAMIAS / BENTO ──────────── --}}
    <section id="mission" class="py-20 bg-white">
        <div class="flex flex-col items-center text-center gap-3 mb-14">
            <a href="#mission" class="text-sm font-medium text-primary hover:text-primary/80 border-b border-primary pb-0.5">01 — What is MAMIAS</a>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">A regional brain for a shared sea.</h2>
            <p class="text-base text-gray-500 max-w-2xl">
                The Mediterranean is one of the most biologically invaded seas on Earth.
                MAMIAS exists to bring scattered knowledge — journals, institutional
                databases, field notebooks — into a single, continuously updated,
                openly accessible reference, operated by SPA/RAC under UNEP/MAP.
            </p>
        </div>

        {{-- Bento grid --}}
        <div class="grid grid-cols-12 gap-5">

            {{-- Database --}}
            <article class="about-card col-span-12 md:col-span-7 rounded-2xl border border-gray-200 p-8 lg:p-10 text-white" style="background: linear-gradient(135deg, #003d61 0%, #005f98 100%);">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center">
                        <i class="ki-filled ki-data text-base text-[#4cafbf]"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-white/70 font-semibold">Database</span>
                </div>
                <h3 class="text-2xl md:text-3xl font-bold leading-tight">
                    A basin-wide registry of alien species with taxonomy, biology, distribution and impact.
                </h3>
                <p class="mt-4 text-white/75 max-w-xl">
                    Each species record carries year of first introduction, first record per country,
                    primary and secondary pathways, establishment status, and documented impacts on
                    biodiversity, human health and ecosystem services.
                </p>
                <div class="mt-7 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] font-mono text-white/75">
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">taxon_id</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">first_record</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">pathway</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">impact_score</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">established</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">geo_record</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">country</div>
                    <div class="px-3 py-2 rounded-md bg-white/5 border border-white/10">source_ref</div>
                </div>
            </article>

            {{-- Early warning --}}
            <article class="about-card col-span-12 md:col-span-5 rounded-2xl border border-gray-200 p-8 lg:p-10 text-white" style="background: linear-gradient(135deg, #e26a53 0%, #c9472f 100%);">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-white/15 border border-white/25 flex items-center justify-center">
                        <i class="ki-filled ki-notification-status text-base"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-white/85 font-semibold">Early warning</span>
                </div>
                <h3 class="text-2xl md:text-3xl font-bold leading-tight">
                    Alerts when a new invader is recorded.
                </h3>
                <p class="mt-4 text-white/85">
                    A notification system flags first detections of high-impact species
                    to National Focal Points — so rapid response can begin while
                    eradication is still feasible.
                </p>
                <div class="mt-7 inline-flex items-center gap-2 text-sm font-semibold bg-white/15 border border-white/25 rounded-full px-4 py-1.5">
                    <i class="ki-filled ki-flash text-sm"></i> Rapid response ready
                </div>
            </article>

            {{-- Mapping --}}
            <article class="about-card col-span-12 md:col-span-5 rounded-2xl border border-[#4cafbf]/30 bg-[#018d9a]/5 p-8 lg:p-10">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-[#018d9a]/10 flex items-center justify-center">
                        <i class="ki-filled ki-geolocation text-base text-[#018d9a]"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-[#005f98] font-semibold">Spatial</span>
                </div>
                <h3 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight">
                    Distribution maps for every species in the basin.
                </h3>
                <p class="mt-4 text-gray-600">
                    Interactive cartography with extractable spatial data — feeding the
                    EcAp Integrated Monitoring &amp; Assessment Programme's Common Indicator&nbsp;6.
                </p>
            </article>

            {{-- Factsheets --}}
            <article class="about-card col-span-12 md:col-span-7 rounded-2xl border border-gray-200 bg-white p-8 lg:p-10">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-amber-50 flex items-center justify-center">
                        <i class="ki-filled ki-document text-base text-amber-500"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-gray-500 font-semibold">Factsheets</span>
                </div>
                <h3 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight">
                    Field-ready dossiers for high-impact species.
                </h3>
                <p class="mt-4 text-gray-600 max-w-xl">
                    Diagnostic characters, native range, Mediterranean distribution, history of
                    introduction, population trends, impacts and existing management measures —
                    synthesised in a consistent, citable format.
                </p>
                <div class="mt-6 flex flex-wrap gap-2 text-xs">
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600">Identification</span>
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600">Native range</span>
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600">Introduction history</span>
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600">Impacts</span>
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600">Management</span>
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600">References</span>
                </div>
            </article>

            {{-- Indicators --}}
            <article class="about-card col-span-12 md:col-span-4 rounded-2xl border border-gray-200 bg-white p-8">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-green-50 flex items-center justify-center">
                        <i class="ki-filled ki-graph-up text-base text-green-600"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-gray-500 font-semibold">Indicators</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 leading-tight">
                    Statistics that turn records into evidence.
                </h3>
                <p class="mt-3 text-sm text-gray-500">
                    Trends in new introductions per pathway, per country, per decade —
                    built to support EcAp reporting and EU MSFD descriptors.
                </p>
            </article>

            {{-- Web services --}}
            <article class="about-card col-span-12 md:col-span-4 rounded-2xl border border-gray-200 bg-white p-8">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-blue-50 flex items-center justify-center">
                        <i class="ki-filled ki-code text-base text-blue-500"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-gray-500 font-semibold">Web services</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 leading-tight">
                    Open tools and APIs for searching and extracting data.
                </h3>
                <p class="mt-3 text-sm text-gray-500">
                    Designed for researchers, environmental agencies and downstream
                    platforms such as EASIN to consume Mediterranean alien-species data.
                </p>
            </article>

            {{-- Network --}}
            <article class="about-card col-span-12 md:col-span-4 rounded-2xl border border-gray-200 bg-white p-8">
                <div class="flex items-center gap-2 mb-5">
                    <span class="size-9 rounded-full bg-violet-50 flex items-center justify-center">
                        <i class="ki-filled ki-people text-base text-violet-500"></i>
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.18em] text-gray-500 font-semibold">Network</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 leading-tight">
                    An active community of National Focal Points.
                </h3>
                <p class="mt-3 text-sm text-gray-500">
                    Harmonised reporting and a directory of regional experts —
                    curated together with the 22 Contracting Parties.
                </p>
            </article>
        </div>
    </section>

    {{-- ──────────────────── 02 — PATHWAYS ───────────────────── --}}
    <section id="pathways" class="py-20 rounded-2xl text-white relative overflow-hidden about-grain" style="background: linear-gradient(135deg, #001a2c 0%, #003d61 60%, #005f98 100%);">
        <div class="relative px-6 sm:px-10 lg:px-14">

            <div class="grid lg:grid-cols-12 gap-10 mb-14">
                <div class="lg:col-span-5">
                    <a href="#pathways" class="inline-block text-sm font-medium text-[#4cafbf] hover:text-white border-b border-[#4cafbf] pb-0.5">02 — Pathways</a>
                    <h2 class="mt-5 text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">How they get in.</h2>
                </div>
                <div class="lg:col-span-7 lg:pt-4">
                    <p class="text-base lg:text-lg text-white/75 leading-relaxed">
                        New alien species reach the Mediterranean through a handful of
                        well-identified routes. Knowing the pathway is the first step
                        toward prevention — it tells us which sector, corridor or
                        behaviour to address. MAMIAS records the pathway for every
                        species, with a confidence level.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @php
                    $pathways = [
                        ['n' => '01', 'icon' => 'ki-ship', 'title' => 'Shipping', 'desc' => 'Ballast waters and hull fouling — the historic and still dominant vector.'],
                        ['n' => '02', 'icon' => 'ki-arrows-circle', 'title' => 'Corridors', 'desc' => 'Lessepsian migration via the Suez Canal — continuous influx of Indo-Pacific species.'],
                        ['n' => '03', 'icon' => 'ki-route', 'title' => 'Maritime transport & waterways', 'desc' => 'Channels, ports and inland connections that reshape natural barriers.'],
                        ['n' => '04', 'icon' => 'ki-tree', 'title' => 'Aquaculture', 'desc' => 'Intentional or accidental release of farmed species and associated organisms.'],
                        ['n' => '05', 'icon' => 'ki-bag', 'title' => 'Live trade', 'desc' => 'Aquarium trade and fishing bait — small flows with outsized consequences.'],
                        ['n' => '06', 'icon' => 'ki-magnifier', 'title' => 'Fishing & exhibits', 'desc' => 'Bycatch transfers, escapes from public aquariums and similar incidental routes.'],
                        ['n' => '07', 'icon' => 'ki-sun', 'title' => 'Climate change', 'desc' => 'A warming basin lowers the threshold for tropical species to settle and spread.'],
                        ['n' => '∞',  'icon' => 'ki-arrow-circle-right', 'title' => 'Secondary spread', 'desc' => 'Once established, species move along currents and human activity — the loop continues.', 'warm' => true],
                    ];
                @endphp
                @foreach ($pathways as $p)
                    <div class="group rounded-2xl border {{ ($p['warm'] ?? false) ? 'border-[#e26a53]/40 bg-[#e26a53]/10' : 'border-white/10 bg-white/5' }} backdrop-blur p-6 hover:bg-white/10 transition">
                        <div class="flex items-center justify-between">
                            <span class="size-9 rounded-full flex items-center justify-center {{ ($p['warm'] ?? false) ? 'bg-[#e26a53]/20 text-[#ffb199]' : 'bg-[#4cafbf]/15 text-[#4cafbf]' }}">
                                <i class="ki-filled {{ $p['icon'] }} text-base"></i>
                            </span>
                            <span class="text-xl font-bold {{ ($p['warm'] ?? false) ? 'text-[#ffb199]' : 'text-[#4cafbf]' }}">{{ $p['n'] }}</span>
                        </div>
                        <h4 class="mt-5 text-lg font-bold leading-snug">{{ $p['title'] }}</h4>
                        <p class="mt-2 text-sm text-white/70">{{ $p['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─────────── 03 — MAP / NETWORK MIRRORS THE SEA ────────── --}}
    <section id="platform" class="py-20 bg-white">
        <div class="grid lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-5 lg:sticky lg:top-24">
                <a href="#platform" class="inline-block text-sm font-medium text-primary hover:text-primary/80 border-b border-primary pb-0.5">03 — A basin, twenty-two parties</a>
                <h2 class="mt-5 text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">A network that mirrors the sea.</h2>
                <p class="mt-6 text-gray-600 text-base lg:text-lg">
                    Twenty-one Mediterranean countries and the European Union are
                    Contracting Parties to the Barcelona Convention. Each one contributes
                    national reports, baseline studies and annual updates — turning local
                    fieldwork into a regional picture.
                </p>
                <ul class="mt-8 space-y-3 text-sm text-gray-700">
                    <li class="flex gap-3"><i class="ki-filled ki-double-check-circle text-primary mt-1"></i> Each Party operates through a National Focal Point for SPAs.</li>
                    <li class="flex gap-3"><i class="ki-filled ki-double-check-circle text-primary mt-1"></i> SPA/RAC coordinates reporting and harmonises the data model.</li>
                    <li class="flex gap-3"><i class="ki-filled ki-double-check-circle text-primary mt-1"></i> MAMIAS is linked to EASIN and other international networks.</li>
                </ul>

                <div class="mt-8 flex items-center gap-3">
                    <img src="{{ asset('images/sparac.png') }}" class="h-10" alt="SPA/RAC">
                    <div class="text-xs text-gray-500 leading-snug">
                        <div class="font-semibold text-gray-700">SPA/RAC</div>
                        Specially Protected Areas Regional Activity Centre
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="rounded-2xl bg-white border border-gray-200 p-6 shadow-sm">
                    <svg viewBox="0 0 800 420" class="w-full h-auto" role="img" aria-label="Stylised map of the Mediterranean basin with indicative alien species record clusters">
                        <defs>
                            <linearGradient id="aboutSea" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%"  stop-color="#4cafbf" stop-opacity="0.28"/>
                                <stop offset="100%" stop-color="#005f98" stop-opacity="0.45"/>
                            </linearGradient>
                            <pattern id="aboutDots" width="6" height="6" patternUnits="userSpaceOnUse">
                                <circle cx="1" cy="1" r="0.6" fill="#003d61" opacity="0.18"/>
                            </pattern>
                        </defs>

                        <path d="
                            M30,180 C90,90 180,80 280,110
                            C360,80 460,90 540,110
                            C620,90 700,100 760,140
                            L770,250
                            C720,290 640,310 560,300
                            C480,330 360,320 280,300
                            C200,330 110,310 60,280 Z
                        " fill="url(#aboutSea)" stroke="#005f98" stroke-opacity=".35" stroke-width="1"/>

                        <path d="M30,180 C90,90 180,80 280,110 L290,30 L20,30 Z" fill="url(#aboutDots)"/>
                        <path d="M280,110 C360,80 460,90 540,110 L540,30 L280,30 Z" fill="url(#aboutDots)"/>
                        <path d="M540,110 C620,90 700,100 760,140 L770,30 L540,30 Z" fill="url(#aboutDots)"/>
                        <path d="M60,280 C110,310 200,330 280,300 C360,320 480,330 560,300 C640,310 720,290 770,250 L770,400 L20,400 Z" fill="url(#aboutDots)"/>

                        <line x1="755" y1="200" x2="780" y2="240" stroke="#e26a53" stroke-width="2" stroke-dasharray="4 3"/>
                        <text x="685" y="195" font-family="Roboto, sans-serif" font-size="11" fill="#e26a53" font-style="italic">Suez corridor</text>

                        <circle cx="55" cy="220" r="3" fill="#005f98"/>
                        <text x="40" y="245" font-family="Roboto, sans-serif" font-size="10" fill="#003d61" opacity=".6">Strait of Gibraltar</text>

                        @foreach ([[180,200],[320,220],[450,180],[560,230],[680,200],[720,240]] as $pt)
                            <g>
                                <circle class="about-pulse" cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="4" fill="#e26a53"/>
                                <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="2" fill="#003d61"/>
                            </g>
                        @endforeach

                        <text x="30" y="395" font-family="Roboto, sans-serif" font-size="11" fill="#003d61" opacity=".5">
                            Illustrative — pulsing points represent indicative record clusters.
                        </text>
                    </svg>

                    <div class="mt-6 grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="about-num text-2xl lg:text-3xl font-bold text-mono">22</div>
                            <div class="text-[11px] uppercase tracking-widest text-gray-500 mt-1">Parties</div>
                        </div>
                        <div>
                            <div class="about-num text-2xl lg:text-3xl font-bold text-mono">2.5M km²</div>
                            <div class="text-[11px] uppercase tracking-widest text-gray-500 mt-1">Basin area</div>
                        </div>
                        <div>
                            <div class="about-num text-2xl lg:text-3xl font-bold text-[#e26a53]">~1,000</div>
                            <div class="text-[11px] uppercase tracking-widest text-gray-500 mt-1">Alien records</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────── 04 — MANDATE ────────────────────── --}}
    <section id="mandate" class="py-20 rounded-2xl" style="background: linear-gradient(180deg, #f0f9fb 0%, #ffffff 100%);">
        <div class="px-6 sm:px-10 lg:px-14">
            <div class="grid lg:grid-cols-12 gap-10">
                <div class="lg:col-span-5">
                    <a href="#mandate" class="inline-block text-sm font-medium text-primary hover:text-primary/80 border-b border-primary pb-0.5">04 — Mandate</a>
                    <h2 class="mt-5 text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">The frame that holds us.</h2>
                    <p class="mt-6 text-gray-600 text-base lg:text-lg">
                        MAMIAS sits inside a layered legal and scientific architecture
                        that gives it both purpose and obligation.
                    </p>
                </div>

                <div class="lg:col-span-7 space-y-3">
                    @php
                        $mandates = [
                            [
                                'year' => '1976 / 1995',
                                'title' => 'Barcelona Convention & SPA/BD Protocol',
                                'body' => 'Article 13 of the SPA/BD Protocol invites Parties to regulate intentional and non-intentional introduction of non-indigenous species, prohibit those with harmful impacts, and endeavour to eradicate established invasive species.',
                                'open' => true,
                            ],
                            [
                                'year' => '1992 — Rio',
                                'title' => 'Convention on Biological Diversity, Art. 8(h)',
                                'body' => 'Each Party is to prevent the introduction of, control or eradicate alien species which threaten ecosystems, habitats or species — operationalised by Aichi Target 9.',
                            ],
                            [
                                'year' => '2014 — EU',
                                'title' => 'Regulation (EU) 1143/2014 on Invasive Alien Species',
                                'body' => 'Establishes prevention, early warning, rapid response and management obligations for species of EU concern — complemented by the Marine Strategy Framework Directive (2008/56/EC) and the EASIN network.',
                            ],
                            [
                                'year' => 'EcAp',
                                'title' => 'Ecosystem Approach & Common Indicator 6',
                                'body' => 'MAMIAS feeds the Integrated Monitoring & Assessment Programme of the Barcelona Convention — particularly the trends-in-non-indigenous-species indicator used to assess Good Environmental Status.',
                            ],
                            [
                                'year' => 'IMO',
                                'title' => 'Ballast Water Management Convention',
                                'body' => 'National legislation across the basin is encouraged to mirror the IMO Convention on ballast water, together with codes of practice from ICES, IUCN and FAO.',
                            ],
                        ];
                    @endphp

                    @foreach ($mandates as $m)
                        <details class="about-acc group rounded-2xl bg-white border border-gray-200 p-6" @if($m['open'] ?? false) open @endif>
                            <summary class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-[11px] uppercase tracking-widest text-gray-500">{{ $m['year'] }}</div>
                                    <div class="font-bold text-lg md:text-xl text-gray-900 mt-1">{{ $m['title'] }}</div>
                                </div>
                                <span class="about-acc-plus text-2xl text-gray-400">+</span>
                            </summary>
                            <p class="mt-4 text-gray-600 leading-relaxed">{{ $m['body'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────── 05 — TIMELINE ───────────────────── --}}
    <section id="timeline" class="py-20 rounded-2xl text-white relative overflow-hidden about-grain" style="background: linear-gradient(135deg, #003d61 0%, #001a2c 100%);">
        <div class="relative px-6 sm:px-10 lg:px-14">
            <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
                <div>
                    <a href="#timeline" class="inline-block text-sm font-medium text-[#4cafbf] hover:text-white border-b border-[#4cafbf] pb-0.5">05 — Implementation timetable</a>
                    <h2 class="mt-5 text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">A five-year rolling plan.</h2>
                </div>
                <p class="max-w-md text-white/70">
                    Drawn from the Annex of the Action Plan on Species Introductions and
                    Invasive Species — the steps that brought MAMIAS into being and now
                    keep it alive.
                </p>
            </div>

            <ol class="relative border-l border-white/15 ml-3 space-y-10">
                <li class="about-step pl-8">
                    <div class="text-white/55 text-xs uppercase tracking-widest">2016</div>
                    <h4 class="font-bold text-xl md:text-2xl mt-1">Launch of MAMIAS</h4>
                    <p class="mt-2 text-white/70 max-w-2xl">SPA/RAC initiates the platform; Contracting Parties prepare national reports and set up coordination mechanisms.</p>
                </li>
                <li class="about-step pl-8">
                    <div class="text-white/55 text-xs uppercase tracking-widest">2017</div>
                    <h4 class="font-bold text-xl md:text-2xl mt-1">Baseline studies &amp; monitoring</h4>
                    <p class="mt-2 text-white/70 max-w-2xl">Reporting forms, online search tools, directories of specialists, and the first national legislation programmes go live.</p>
                </li>
                <li class="about-step pl-8">
                    <div class="text-white/55 text-xs uppercase tracking-widest">2018</div>
                    <h4 class="font-bold text-xl md:text-2xl mt-1">Mapping &amp; risk assessment</h4>
                    <p class="mt-2 text-white/70 max-w-2xl">Online mapping is released; regional training sessions are organised; risk-assessment techniques are deployed nationally.</p>
                </li>
                <li class="about-step is-warm pl-8">
                    <div class="text-white/55 text-xs uppercase tracking-widest">2019</div>
                    <h4 class="font-bold text-xl md:text-2xl mt-1">Early warning &amp; National Plans</h4>
                    <p class="mt-2 text-white/70 max-w-2xl">The notification system is integrated; Parties adopt National Plans; MAMIAS connects with EASIN and other international systems.</p>
                </li>
                <li class="about-step is-warm pl-8">
                    <div class="text-white/55 text-xs uppercase tracking-widest">2020 →</div>
                    <h4 class="font-bold text-xl md:text-2xl mt-1">Statistics, indicators &amp; outreach</h4>
                    <p class="mt-2 text-white/70 max-w-2xl">Statistical tools for EcAp reporting; public education material; a triennial symposium gathering Mediterranean specialists.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- ──────────────── 06 — PARTNERS / NETWORK ─────────────── --}}
    <section id="network" class="py-20 bg-white">
        <div class="flex flex-col items-center text-center gap-3 mb-14">
            <a href="#network" class="text-sm font-medium text-primary hover:text-primary/80 border-b border-primary pb-0.5">06 — Partners &amp; networks</a>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Built with many hands.</h2>
            <p class="text-base text-gray-500 max-w-2xl">
                MAMIAS draws on, and contributes to, the broader information ecosystem
                on alien species — from European-scale registries to global maritime
                and fisheries bodies. Coordination is ensured by the UNEP/MAP
                Secretariat through SPA/RAC.
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @php
                $partners = [
                    ['name' => 'SPA/RAC',     'desc' => 'Specially Protected Areas Regional Activity Centre — host institution.'],
                    ['name' => 'UNEP/MAP',    'desc' => 'UN Environment Programme — Mediterranean Action Plan Secretariat.'],
                    ['name' => 'EASIN',       'desc' => 'European Alien Species Information Network — JRC, European Commission.'],
                    ['name' => 'CBD',         'desc' => 'Convention on Biological Diversity — global biodiversity framework.'],
                    ['name' => 'IMO',         'desc' => 'International Maritime Organization — ballast water and shipping vectors.'],
                    ['name' => 'FAO / GFCM',  'desc' => 'General Fisheries Commission for the Mediterranean.'],
                    ['name' => 'ICES',        'desc' => 'International Council for the Exploration of the Sea — Code of Practice.'],
                    ['name' => 'IUCN',        'desc' => 'Guidelines on the prevention of biodiversity loss caused by IAS.'],
                ];
            @endphp
            @foreach ($partners as $p)
                <div class="about-card rounded-2xl bg-white border border-gray-200 p-5 hover:shadow-lg transition">
                    <div class="font-bold text-lg text-gray-900">{{ $p['name'] }}</div>
                    <p class="text-sm text-gray-500 mt-1.5">{{ $p['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <p class="mt-10 text-sm text-gray-500 max-w-3xl">
            The original Action Plan was prepared with the financial support of the
            <strong class="text-gray-700">MAVA Foundation</strong>. Implementation invites any organisation,
            laboratory or NGO contributing concrete actions to apply for the status of
            <em>Action Plan Associate</em>.
        </p>
    </section>

    {{-- ──────────────────── 07 — CTA / GET INVOLVED ───────────── --}}
    <section id="contribute" class="py-20 rounded-2xl text-white relative overflow-hidden" style="background: linear-gradient(135deg, #018d9a 0%, #005f98 60%, #003d61 100%);">
        <div class="absolute inset-0 opacity-50 pointer-events-none"
             style="background:
                radial-gradient(50% 60% at 80% 20%, rgba(226,106,83,.25), transparent 60%),
                radial-gradient(50% 60% at 10% 90%, rgba(76,175,191,.35), transparent 60%);"></div>

        <div class="relative px-6 sm:px-10 lg:px-14">
            <div class="grid lg:grid-cols-12 gap-10 items-end">
                <div class="lg:col-span-7">
                    <span class="inline-block text-sm font-medium text-white bg-white/15 border border-white/25 rounded-full px-4 py-1.5 mb-5">07 — Get involved</span>
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight max-w-3xl">
                        Whatever you've seen at sea
                        <span class="block text-[#4cafbf]">probably belongs here.</span>
                    </h2>
                    <p class="mt-6 text-white/80 max-w-2xl text-base lg:text-lg">
                        MAMIAS thrives on the records, expertise and curiosity of the
                        people who work, fish, dive, teach and govern around the
                        Mediterranean. There is a way in for every audience.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        @auth
                            <a href="{{ route('filament.mamias.pages.dashboard') }}"
                               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-[#003d61] font-semibold text-sm transition-all duration-300 hover:shadow-lg">
                                <i class="ki-filled ki-element-11 text-base"></i>
                                Go to Dashboard
                            </a>
                            <a href="#mission" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg border border-white/30 text-white font-semibold text-sm transition hover:bg-white/10">
                                <i class="ki-filled ki-arrow-up text-base"></i>
                                Back to top
                            </a>
                        @else
                            <a href="{{ route('filament.mamias.auth.register') }}"
                               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white text-[#003d61] font-semibold text-sm transition-all duration-300 hover:shadow-lg">
                                <i class="ki-filled ki-user-plus text-base"></i>
                                Create Free Account
                            </a>
                            <a href="{{ route('filament.mamias.auth.login') }}"
                               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg border border-white/30 text-white font-semibold text-sm transition hover:bg-white/10">
                                <i class="ki-filled ki-entrance-left text-base"></i>
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="lg:col-span-5 grid gap-3">
                    @php
                        $audiences = [
                            ['for' => 'For National Focal Points', 'title' => 'Submit national reports & updates', 'icon' => 'ki-document-text', 'primary' => true],
                            ['for' => 'For researchers',           'title' => 'Access data & web services',       'icon' => 'ki-data'],
                            ['for' => 'For citizens & divers',     'title' => 'Report a sighting',                'icon' => 'ki-eye'],
                            ['for' => 'For institutions',          'title' => 'Become an Action Plan Associate',  'icon' => 'ki-medal-star'],
                        ];
                    @endphp
                    @foreach ($audiences as $a)
                        <a href="#"
                           class="group flex items-start justify-between gap-4 rounded-2xl p-5 transition {{ ($a['primary'] ?? false) ? 'bg-white text-[#003d61] hover:bg-[#4cafbf] hover:text-white' : 'bg-white/10 border border-white/20 text-white hover:bg-white/15' }}">
                            <div class="flex items-start gap-3">
                                <span class="size-9 rounded-full flex items-center justify-center shrink-0 {{ ($a['primary'] ?? false) ? 'bg-[#018d9a]/10 text-[#018d9a] group-hover:bg-white/20 group-hover:text-white' : 'bg-white/10 text-[#4cafbf]' }}">
                                    <i class="ki-filled {{ $a['icon'] }} text-base"></i>
                                </span>
                                <div>
                                    <div class="text-[11px] uppercase tracking-widest {{ ($a['primary'] ?? false) ? 'text-[#003d61]/60 group-hover:text-white/70' : 'text-white/60' }}">{{ $a['for'] }}</div>
                                    <div class="font-bold text-base mt-0.5">{{ $a['title'] }}</div>
                                </div>
                            </div>
                            <i class="ki-filled ki-arrow-right text-lg mt-1.5"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ─────────────────── CITATION / FOOTNOTE ────────────────── --}}
    <section class="py-10">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 lg:p-8 grid md:grid-cols-12 gap-6 items-start">
            <div class="md:col-span-3 flex items-center gap-3">
                <img src="{{ asset('images/Logoweb.png') }}" class="h-10" alt="MAMIAS">
                <div>
                    <div class="text-[11px] uppercase tracking-widest text-gray-500">Citation</div>
                    <div class="mt-0.5 text-sm font-semibold text-gray-700">Reference framework</div>
                </div>
            </div>
            <div class="md:col-span-9 text-sm text-gray-600 italic leading-relaxed">
                UNEP/MAP – SPA/RAC, 2016.
                <span class="not-italic font-medium text-gray-800">Action Plan concerning Species Introductions and Invasive Species in the Mediterranean Sea.</span>
                Ed. SPA/RAC, Tunis. Prepared with the financial support of the MAVA Foundation.
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
(function () {
    if (!('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('#mission h2, #pathways h2, #platform h2, #mandate h2, #timeline h2, #network h2, #contribute h2, .about-card, .about-step, details.about-acc').forEach(function (el) {
        el.classList.add('about-reveal');
        io.observe(el);
    });
})();
</script>
@endpush
