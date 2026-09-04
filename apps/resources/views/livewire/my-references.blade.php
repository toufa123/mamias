<div>
    @section('title', 'My Bibliographic References')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('references') }}
    @endsection

    <x-notify::notify />

    <div class="space-y-8">
        <div class="flex justify-end">
            <button
                type="button"
                wire:click="mountAction('create')"
                class="inline-flex items-center gap-2 rounded-lg bg-[#00899d] px-4 py-2.5 text-sm font-medium text-white shadow-xs transition hover:bg-[#007080]"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                Add New Reference
            </button>
        </div>

        {{ $this->table }}
    </div>
</div>
