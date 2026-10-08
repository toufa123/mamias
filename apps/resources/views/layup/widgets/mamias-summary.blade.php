@php
    use App\Services\MediterraneanDashboard;

    $vis = \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $dashboardUrl = url('pages/dashboard/mediterranean');
    $topSubregion = max([1, ...array_column($subregions, 'count')]);
    $establishmentTotal = max(1, array_sum(array_column($establishment, 'count')));
    $topPhylum = max([1, ...array_column($phyla, 'value')]);
    $statusColor = ['Established' => '#134f4c', 'Casual' => '#7fc0b7', 'Invasive' => '#b5452d'];
@endphp

<div
    @if (! empty($data['id'])) data-block-id="{{ $data['id'] }}" @endif
    class="{{ $vis }} {{ $data['class'] ?? '' }}"
    style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}"
>
    <section class="border-border bg-card overflow-hidden rounded-xl border" aria-labelledby="mamias-summary-title">
        <header class="flex flex-wrap items-baseline justify-between gap-3 px-6 pt-5">
            <h2 id="mamias-summary-title" class="text-mono text-xl font-semibold">{{ $title }}</h2>
            <p class="text-secondary-foreground text-sm">Validated non-indigenous species, as on the Mediterranean dashboard</p>
        </header>

        {{-- Four on one line from sm up; two on phones. Arbitrary base value: assets/css/styles.css re-declares .grid-cols-2 after app.css and would beat the sm: variant. --}}
        <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-3 px-6 py-4 sm:grid-cols-[repeat(4,minmax(0,1fr))]">
            @foreach ([
                [$headline['events'], 'reported NIS in the Mediterranean'],
                [$headline['established'], 'established'],
                [$headline['casual'], 'casual or vagrant'],
                [$headline['recent'], "new records in {$headline['recentFrom']}–{$headline['recentTo']}"],
            ] as [$value, $label])
                <a
                    href="{{ $dashboardUrl }}"
                    class="hover:border-primary rounded-lg border border-transparent bg-[#f3f7f8] px-4 py-3"
                >
                    <span
                        class="block text-3xl font-semibold text-[#056273] tabular-nums"
                        >{{ number_format($value) }}</span
                    >
                    <span class="text-secondary-foreground text-sm">{{ $label }}</span>
                </a>
            @endforeach
        </div>

        <div
            class="grid gap-7 px-6 pt-2 pb-6 md:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-[repeat(4,minmax(0,1fr))]"
        >
            <div class="min-w-0 space-y-2">
                <h3 class="text-mono text-sm font-semibold">Reported species by EcAp sub-region</h3>
                <ul class="space-y-1">
                    @foreach ($subregions as $subregion)
                        <li>
                            <a
                                href="{{ route('map', ['subregion' => $subregion['code']]) }}"
                                class="block rounded-md px-2 py-1.5 hover:bg-[#f3f7f8]"
                                title="See them on the map"
                            >
                                <span class="flex justify-between gap-2 text-sm">
                                    <span>{{ $subregion['label'] }}</span>
                                    <span
                                        class="font-semibold tabular-nums"
                                        >{{ number_format($subregion['count']) }}</span
                                    >
                                </span>
                                <span class="mt-1 block h-2 rounded bg-[#e8eef0]">
                                    <span
                                        class="block h-2 rounded bg-[#2f8a83]"
                                        style="width: {{ ($subregion['count'] / $topSubregion) * 100 }}%"
                                    ></span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <a href="{{ $dashboardUrl }}" class="block min-w-0 space-y-2 rounded-md">
                <h3 class="text-mono text-sm font-semibold">Establishment in the Mediterranean</h3>
                <span class="flex h-3.5 overflow-hidden rounded">
                    @foreach ($establishment as $status)
                        <span
                            style="width: {{ ($status['count'] / $establishmentTotal) * 100 }}%; background: {{ $statusColor[$status['label']] ?? '#b8c4c9' }}"
                        ></span>
                    @endforeach
                </span>
                <span class="block space-y-1 text-sm">
                    @foreach ($establishment as $status)
                        <span class="flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-sm"
                                style="background: {{ $statusColor[$status['label']] ?? '#b8c4c9' }}"
                            ></span>
                            <span class="grow">{{ $status['label'] }}</span>
                            <span class="tabular-nums">{{ number_format($status['count']) }}</span>
                            <span class="text-secondary-foreground w-10 text-right tabular-nums"
                                >{{ round($status['count'] / $establishmentTotal * 100) }}%</span
                            >
                        </span>
                    @endforeach
                </span>
            </a>

            <a href="{{ $dashboardUrl }}" class="block min-w-0 space-y-2 rounded-md">
                <h3 class="text-mono text-sm font-semibold">Top phyla</h3>
                <span class="block space-y-1.5 text-sm">
                    @foreach ($phyla as $phylum)
                        <span class="grid grid-cols-[6.5rem_minmax(0,1fr)_2.5rem] items-center gap-2">
                            <span class="truncate">{{ $phylum['name'] }}</span>
                            <span class="block h-2 rounded bg-[#e8eef0]">
                                <span
                                    class="block h-2 rounded bg-[#056273]"
                                    style="width: {{ ($phylum['value'] / $topPhylum) * 100 }}%"
                                ></span>
                            </span>
                            <span class="text-right tabular-nums">{{ number_format($phylum['value']) }}</span>
                        </span>
                    @endforeach
                </span>
            </a>

            <div class="min-w-0 space-y-2">
                <h3 class="text-mono text-sm font-semibold">Most recent arrivals</h3>
                <ul class="divide-border divide-y text-sm">
                    @foreach ($arrivals as $arrival)
                        <li>
                            <a
                                href="{{ route('data.species', $arrival) }}"
                                class="flex items-center justify-between gap-3 py-1.5"
                            >
                                <span class="min-w-0">
                                    <em class="block truncate">{{ $arrival->taxon->scientificname }}</em>
                                    <span class="text-secondary-foreground text-xs">
                                        {{ collect((array) $arrival->first_country)->filter()->map(MediterraneanDashboard::reportedCountry(...))->unique()->join(', ') }}
                                    </span>
                                </span>
                                <span
                                    class="text-secondary-foreground tabular-nums"
                                    >{{ $arrival->first_introduction_year }}</span
                                >
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- One way on: the navbar's own Explore MAMIAS menu, opened for the visitor. --}}
        <footer class="border-border flex justify-end border-t bg-[#fafcfc] px-6 py-3">
            <button type="button" class="kt-btn kt-btn-primary" data-open-explore-menu aria-controls="explore-menu">
                Explore MAMIAS
                <i class="ki-filled ki-arrow-up"></i>
            </button>
        </footer>
    </section>
</div>

@once
    <script>
        // Scroll up and open the navbar's Explore MAMIAS dropdown (KTUI's KTMenu).
        // Capture phase, stopped there: the menu closes on any click outside it.
        document.addEventListener(
            "click",
            (event) => {
                if (!event.target.closest("[data-open-explore-menu]")) return;
                event.preventDefault();
                event.stopPropagation();
                const item = document.getElementById("explore-menu");
                if (!item) return;
                window.scrollTo({ top: 0, behavior: "smooth" });
                setTimeout(() => {
                    window.KTMenu?.getInstance(item)?.show(item);
                    item.querySelector(".kt-menu-link")?.focus({ preventScroll: true });
                }, 450);
            },
            true,
        );
    </script>
@endonce
