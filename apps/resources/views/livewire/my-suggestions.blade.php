<div>
    @section('title', 'My Species Suggestions')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('suggestions') }}
    @endsection

    <div class="space-y-8">
        <x-stat-tiles :stats="$stats" />

        {{ $this->table }}
    </div>
</div>
