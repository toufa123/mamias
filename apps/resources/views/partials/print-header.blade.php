{{--
    Printed header of the Explore MAMIAS pages (app.blade.php, print only):
    the logo, the page title and its breadcrumb, and what the printout covers.
    It sits in the print frame's <thead>, so it repeats on every sheet.
--}}
@php
    use App\Layup\Widgets\MamiasChartWidget;

    $country = request()->is('pages/dashboard/by-country') ? MamiasChartWidget::selectedCountry() : null;
@endphp

<div class="print-header">
    <img class="print-logo" src="{{ asset('images/mamias-logo-print.png') }}" alt="MAMIAS" />
    <div class="print-heading">
        <div class="print-title">@yield ('title')</div>
        <div class="print-crumb">
            Explore MAMIAS ›
            @yield ('title')
        </div>
    </div>
</div>

@if ($country)
    <div class="print-scope">
        <b>Country</b> {{ $country }} <b>Counts</b> NIS first recorded in the Mediterranean in {{ $country }}
    </div>
@elseif (request()->is('pages/dashboard/*'))
    <div class="print-scope"><b>Scope</b> Whole Mediterranean <b>Counts</b> validated baseline of reported NIS</div>
@endif
