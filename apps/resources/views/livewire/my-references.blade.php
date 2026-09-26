<div>
    @section('title', 'My Bibliographic References')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('references') }}
    @endsection

    <x-notify::notify />

    <div class="space-y-8">
        @if ($rejected = $this->rejectedCount())
            {{-- Rejected is the danger (Invasive) ramp, not amber; the button is
                 an outline because "Add New Reference" is this view's one fill. --}}
            <x-filament::callout
                color="danger"
                icon="tabler-alert-triangle"
                :heading="trans_choice(':count reference was not accepted.|:count references were not accepted.', $rejected)"
                description="See the reviewer's reason on each one. To discuss a decision, click its Discussion badge."
            >
                <x-slot name="controls">
                    <x-filament::button size="sm" color="gray" outlined wire:click="$set('tableFilters.status.value', 'rejected')">
                        Show them
                    </x-filament::button>
                </x-slot>
            </x-filament::callout>
        @endif

        @if (($unanswered = $this->unansweredReferences())->isNotEmpty())
            @php
                $unansweredHeading = trans_choice('A reviewer commented on :count reference.|Reviewers commented on :count references.', $unanswered->count());
                $unansweredList = $unanswered->map(fn ($reference) => "{$reference->code} — {$reference->short_ref}")->join(' · ');
            @endphp
            <x-filament::callout
                color="warning"
                icon="tabler-message-circle"
                :heading="$unansweredHeading"
                :description="$unansweredList.'. Click the Discussion badge on each to reply.'"
            />
        @endif

        <div class="flex justify-end gap-3">
            {{ $this->guideAction }}

            <x-filament::button wire:click="mountAction('create')" icon="tabler-file-plus" size="lg">
                Add New Reference
            </x-filament::button>
        </div>

        {{ $this->table }}
    </div>
</div>
