<div>
    @section('title', $taxon->scientificname)

    @section('breadcrumbs')
        {{ Breadcrumbs::render('data.species', $introEventRecord) }}
    @endsection

    <div class="space-y-8">
        <h1 class="text-2xl font-semibold tracking-tight">
            <em>{{ $taxon->scientificname }}</em>
            @if ($taxon->authority)
                <span class="font-normal text-gray-500">{{ $taxon->authority }}</span>
            @endif
        </h1>

        <x-filament::section heading="MAMIAS catalogue" icon="tabler-book">
            {{ $this->catalogueInfolist }}
        </x-filament::section>

        <x-filament::section heading="Introduction event" icon="tabler-calendar-event">
            {{ $this->introEventInfolist }}
        </x-filament::section>

        <x-filament::section
            heading="Occurrences"
            icon="tabler-map-pin"
            :description="$occurrenceCount ? trans_choice(':count approved occurrence|:count approved occurrences', $occurrenceCount) . ' · click a pin for details' : null"
        >
            @if ($occurrenceCount)
                {{ $this->occurrencesInfolist }}
            @else
                <p class="text-sm text-gray-500">No occurrence has been recorded for this species yet.</p>
            @endif
        </x-filament::section>
    </div>
</div>
