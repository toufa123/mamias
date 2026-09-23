<div>
    @section('title', 'My Bibliographic References')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('references') }}
    @endsection

    <x-notify::notify />

    <div class="space-y-8">
        <div class="flex justify-end">
            <x-filament::button wire:click="mountAction('create')" icon="tabler-file-plus" size="lg">
                Add New Reference
            </x-filament::button>
        </div>

        {{ $this->table }}
    </div>
</div>
