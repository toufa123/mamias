@php
    $vis = \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
@endphp

@once
    @vite ('resources/js/mediterranean-dashboard.js')
@endonce

<div
    @if (! empty($data['id'])) data-block-id="{{ $data['id'] }}" @endif
    class="{{ $vis }} {{ $data['class'] ?? '' }} h-full"
    style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}"
>
    @if ($chart === 'country-selector')
        {{-- A plain GET form: every widget on the page reads ?country= on the next request. --}}
        <form method="get" class="border-border bg-card flex flex-wrap items-center gap-3 rounded-xl border p-5">
            <label for="mamias-country" class="text-mono text-lg font-semibold">{{ $title }}</label>
            <select id="mamias-country" name="country" class="kt-select w-auto min-w-56" onchange="this.form.submit()">
                @foreach (collect($payload['rows'])->sortBy('country') as $row)
                    <option value="{{ $row['country'] }}" @selected ($row['country'] === $payload['selected'])>
                        {{ $row['country'] }} ({{ $row['value'] }})
                    </option>
                @endforeach
            </select>
            <noscript><button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Show</button></noscript>
            <p class="text-secondary-foreground w-full text-sm">Counts are NIS first recorded in the Mediterranean in the chosen country, not its national inventory.</p>
        </form>
    @elseif ($chart === 'headline')
        <section aria-label="{{ $title }}">
            <h2 class="text-mono mb-4 text-xl font-semibold">{{ $title }}</h2>
            {{-- Four on one line from sm up; two on phones. Arbitrary base value: assets/css/styles.css re-declares .grid-cols-2 after app.css and would beat the sm: variant. --}}
            <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-4 sm:grid-cols-[repeat(4,minmax(0,1fr))]">
                @foreach (isset($payload['country']) ? [
                    ['First Mediterranean records', $payload['events'], "reported NIS first recorded in {$payload['country']}"],
                    ['Rank', $payload['rank'] > 0 ? '#'.$payload['rank'] : '–', "of {$payload['countries']} countries · {$payload['share']}% of all reported NIS"],
                    ['Established', $payload['established'], 'of them with established populations'],
                    ['Recent reports', $payload['recent'], "first recorded {$payload['recentFrom']}–{$payload['recentTo']}"],
                ] : [
                    ['Reported NIS', $payload['events'], 'reported in the Mediterranean'],
                    ['Established', $payload['established'], 'reported NIS with established populations'],
                    ['Casual / vagrant', $payload['casual'], 'reported NIS without establishment'],
                    ['Recent reports', $payload['recent'], "first reported {$payload['recentFrom']}–{$payload['recentTo']}"],
                ] as [$label, $value, $hint])
                    <div class="border-border bg-card rounded-xl border p-5">
                        <div class="text-secondary-foreground text-sm">{{ $label }}</div>
                        <div class="mt-1 text-3xl font-bold text-[#056273] tabular-nums">
                            {{ is_int($value) ? number_format($value) : $value }}
                        </div>
                        <div class="text-secondary-foreground mt-1 text-xs">{{ $hint }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section class="border-border bg-card flex h-full flex-col rounded-xl border p-5" aria-label="{{ $title }}">
            <header class="mb-3">
                <h2 class="text-mono text-lg font-semibold">{{ $title }}</h2>
                @if ($note)
                    <p class="text-secondary-foreground mt-1 text-sm">{{ $note }}</p>
                @endif
            </header>

            @if ($chart === 'spread-map')
                <div class="mb-3 flex items-center gap-3" data-spread-controls>
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-outline"
                        data-spread-play
                        aria-label="Play the spread over time"
                    >
                        Play
                    </button>
                    <input type="range" class="grow accent-[#056273]" data-spread-slider aria-label="Decade" />
                    <output
                        class="text-mono w-16 text-right text-sm font-semibold tabular-nums"
                        data-spread-label
                    ></output>
                </div>
            @endif

            {{-- Height per chart from MamiasChartWidget::HEIGHTS, inline: an arbitrary min-h-[…] class per value would each need compiling. --}}
            <div class="relative w-full grow" style="min-height: {{ $height }}px">
                <div class="absolute inset-0" data-mamias-chart="{{ $chart }}"></div>
            </div>
            <script type="application/json" data-mamias-payload>
                @json($payload)
            </script>
        </section>
    @endif
</div>
