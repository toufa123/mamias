<div>
    @section('title', 'Data')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('data') }}
    @endsection

    {{ $this->table }}
</div>
