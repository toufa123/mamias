{{--
    Body of a guide popup (LiteratureGuide). The modal body scrolls; the
    contents links jump within it. fi-prose gives Filament's type scale, and
    theme.css (panel) / app.css (public) keep it square and hairline-ruled
    (DESIGN-SYSTEM.md).

    In-page links are scrolled here rather than followed: the public layout
    carries <base href="../../"> from the Metronic template, which resolves
    "#section" against the site root and navigated away from the page.
--}}
<div
    class="fi-prose mamias-guide"
    x-data
    x-on:click="
        const link = $event.target.closest('a[href^=\'#\']')
        if (link) {
            $event.preventDefault()
            document.getElementById(link.getAttribute('href').slice(1))?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
    "
>
    {!! $html !!}
</div>
