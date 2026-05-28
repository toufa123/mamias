<div>
    @section('title', 'My Profile')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('profile') }}
    @endsection


        <div>
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap justify-end gap-4 pt-2">
                    @if ($isEditing)
                        <x-filament::button type="button" wire:click="toggleEdit" color="gray" variant="ghost">
                            Cancel
                        </x-filament::button>
                        <x-filament::button type="submit" size="lg" color="primary">
                            Save Data
                        </x-filament::button>
                    @else
                        <x-filament::button type="button" wire:click="toggleEdit" size="lg" color="primary">
                            Edit
                        </x-filament::button>
                    @endif
                </div>
            </form>

            <x-filament-actions::modals />
        </div>

</div>

