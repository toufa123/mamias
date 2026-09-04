@extends('app')

@section('title')
    {{ $title ?? $layupPage?->title ?? '' }}
@endsection

@section('breadcrumbs')
    @if (isset($layupPage))
        {{ Breadcrumbs::render('layup.page.show', $layupPage) }}
    @else
        {{ Breadcrumbs::render('home') }}
    @endif
@endsection

@section('content')
    {{ $slot }}
@endsection
