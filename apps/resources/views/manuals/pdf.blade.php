<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>{{ $title }}</title>
        {{-- Dompdf: one flat stylesheet, DejaVu Sans for the arrows and symbols the manuals use. --}}
        <style>
            @page {
                margin: 20mm 18mm 18mm;
            }
            body {
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 10pt;
                line-height: 1.45;
                color: #0e2630;
            }
            .cover {
                border-bottom: 2px solid #078da0;
                padding-bottom: 10pt;
                margin-bottom: 14pt;
            }
            .cover h1 {
                font-size: 20pt;
                margin: 0 0 4pt;
                color: #056273;
            }
            .cover p {
                margin: 0;
                color: #47606b;
            }
            h2 {
                font-size: 14pt;
                color: #056273;
                margin: 18pt 0 6pt;
                padding-bottom: 3pt;
                border-bottom: 1px solid #d8e3e8;
                page-break-after: avoid;
            }
            h3 {
                font-size: 11.5pt;
                margin: 12pt 0 4pt;
                page-break-after: avoid;
            }
            a {
                color: #056273;
                text-decoration: none;
            }
            /* The heading permalinks (#) are noise on paper. */
            .heading-permalink {
                display: none;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 6pt 0 10pt;
                font-size: 9pt;
            }
            th,
            td {
                border: 1px solid #d8e3e8;
                padding: 4pt 6pt;
                text-align: left;
                vertical-align: top;
            }
            th {
                background: #f7fafb;
            }
            tr {
                page-break-inside: avoid;
            }
            img {
                border: 1px solid #d8e3e8;
                margin: 6pt 0 10pt;
            }
            p img {
                display: block;
            }
            ul.table-of-contents {
                background: #f7fafb;
                border: 1px solid #d8e3e8;
                padding: 8pt 8pt 8pt 22pt;
            }
            code {
                font-family: 'DejaVu Sans Mono', monospace;
                font-size: 9pt;
            }
        </style>
    </head>
    <body>
        <div class="cover">
            <h1>{{ $title }}</h1>
            <p>Marine Mediterranean Invasive Alien Species (MAMIAS) · {{ $date }}</p>
        </div>
        {!! $html !!}
    </body>
</html>
