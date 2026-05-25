@section('title', 'Add Bibliographic Reference')

@section('breadcrumbs')
    {{ Breadcrumbs::for('add-literature', function ($trail) {
        $trail->parent('home');
        $trail->push('Add Bibliographic Reference', route('add-literature'));
    }) }}
    {{ Breadcrumbs::render('add-literature') }}
@endsection

<div>
    <form wire:submit="create" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-x-3 pt-2">
            <x-filament::button type="button" tag="a" href="{{ route('profile') }}" color="gray" variant="ghost">
                Cancel
            </x-filament::button>
            <x-filament::button type="submit" size="lg" color="primary">
                Add Reference
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
