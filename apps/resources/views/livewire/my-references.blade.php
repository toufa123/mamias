<div>
    @section('title', 'My Bibliographic References')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('references') }}
    @endsection

    <x-notify::notify />

    <div class="space-y-8">
        {{ $this->table }}
    </div>
</div>
