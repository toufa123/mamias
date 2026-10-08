<div>
    @section ('title', trim($taxon->scientificname . ' ' . $taxon->authority))
    @section ('description', $this->previewDescription())

    @section ('breadcrumbs')
        {{ Breadcrumbs::render('data.species', $introEventRecord) }}
    @endsection

    <div class="space-y-8">
        {{ $this->catalogueInfolist }}

        <x-block heading="Introduction event" icon="tabler-calendar-event" :compact="false">
            {{ $this->introEventInfolist }}
        </x-block>

        <x-block
            :compact="false"
            heading="Occurrences"
            icon="tabler-map-pin"
            :description="$occurrenceCount ? trans_choice(':count approved occurrence|:count approved occurrences', $occurrenceCount) . ' · click a pin for details' : null"
        >
            @if ($occurrenceCount)
                {{ $this->occurrencesInfolist }}
            @else
                <p class="text-sm text-gray-500">No occurrence has been recorded for this species yet.</p>
            @endif
        </x-block>
    </div>
</div>
