{{-- The public user manual: resources/docs/user-manual.md, rendered like the screen guides. --}}
@extends('app')

@section('title', 'User manual')

@section('breadcrumbs')
    {{ Breadcrumbs::render('manual') }}
@endsection

@section('content')
    <div class="mb-6 flex justify-end">
        <x-filament::button tag="a" :href="route('manual.pdf')" icon="tabler-file-download" color="gray" outlined>
            Download PDF
        </x-filament::button>
    </div>

    <x-filament::section>
        @include('filament.literatures.guide', ['html' => \App\Filament\Resources\Literatures\LiteratureGuide::html('user-manual')])
    </x-filament::section>
@endsection
