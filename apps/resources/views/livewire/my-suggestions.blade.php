<div>
    @section('title', 'My Species Suggestions')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('suggestions') }}
    @endsection

    <div class="space-y-8">
        {{ $this->table }}

        <x-filament-actions::modals />
    </div>
</div>
