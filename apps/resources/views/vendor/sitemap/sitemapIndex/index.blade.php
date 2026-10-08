<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
@php
    // MAMIAS: asignua/filament-seo-files never sets one, so default to public/sitemap.xsl,
    // which renders the file as a readable page in a browser. Crawlers ignore it.
    $stylesheetUrl = $stylesheetUrl ?? '/sitemap.xsl';
@endphp
@if(!empty($stylesheetUrl))
<?= '<'.'?'.'xml-stylesheet type="text/xsl" href="'.e($stylesheetUrl).'"?'.">\n"; ?>
@endif
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($tags as $tag)
    @include('sitemap::sitemapIndex/' . $tag->getType())
@endforeach
</sitemapindex>
