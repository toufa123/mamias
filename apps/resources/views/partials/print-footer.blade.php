{{--
    Printed footer of the Explore MAMIAS pages (app.blade.php, print only): the
    partner logos beside three 12px lines (source, access date, print date), the
    logo exactly their 36px height. It sits in the print frame's <tfoot>, so it
    repeats on every sheet. No page number: that needs a page margin, where the
    browser would also print its own header and footer (app.css, @page).
    "Printed" is filled in by app.js when printing starts.
--}}
@php
    $today = now()->format('j F Y');
@endphp

<div class="print-footer">
    <img
        class="print-partners"
        src="{{ asset('images/sparac.png') }}"
        alt="UN Environment Programme · Mediterranean Action Plan, Barcelona Convention · SPA/RAC"
    />
    <div class="print-source">
        <span>Source: MAMIAS (SPA/RAC), www.mamias.org.</span>
        <span>Accessed on: {{ $today }}</span>
        <span>Printed: <span data-print-date>{{ $today }}</span></span>
    </div>
</div>
