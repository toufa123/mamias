<div>
    @section('title', 'My Species Reports')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('my-species-reports') }}
    @endsection

    <div class="space-y-8">
        <x-stat-tiles :stats="$stats" />

        {{ $this->table }}
    </div>
</div>
