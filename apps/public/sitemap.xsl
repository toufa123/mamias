<?xml version="1.0" encoding="UTF-8"?>
<!--
    Renders sitemap.xml (and a sitemap index, once the site outgrows one file)
    as a readable page in a browser. Linked from the overridden spatie views in
    resources/views/vendor/sitemap; crawlers read the XML and ignore this.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    exclude-result-prefixes="s xhtml">

    <xsl:output method="html" encoding="UTF-8" indent="yes"/>

    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="utf-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
                <meta name="robots" content="noindex"/>
                <title>MAMIAS sitemap</title>
                <style>
                    :root { color-scheme: light; }
                    body { margin: 0; font-family: Geist, system-ui, sans-serif; color: #14232a; background: #f6f8f9; }
                    main { max-width: 1100px; margin: 0 auto; padding: 32px 16px 48px; }
                    h1 { margin: 0 0 4px; font-size: 24px; font-weight: 600; }
                    p { margin: 0 0 20px; color: #4a5b63; font-size: 14px; }
                    .frame { background: #fff; border: 1px solid #e1e7ea; border-radius: 10px; overflow-x: auto; }
                    table { width: 100%; border-collapse: collapse; font-size: 14px; }
                    th { text-align: left; font-weight: 600; background: #f3f6f7; padding: 10px 14px; border-bottom: 1px solid #e1e7ea; white-space: nowrap; }
                    td { padding: 8px 14px; border-bottom: 1px solid #eef2f4; vertical-align: top; }
                    tr:last-child td { border-bottom: 0; }
                    td.n { color: #6a7a82; width: 1%; text-align: right; font-variant-numeric: tabular-nums; }
                    td.d { white-space: nowrap; color: #4a5b63; font-variant-numeric: tabular-nums; }
                    a { color: #056273; text-decoration: none; word-break: break-all; }
                    a:hover { text-decoration: underline; }
                    .lang { display: inline-block; margin-right: 4px; padding: 0 6px; border-radius: 4px; background: #e6f1f2; color: #056273; font-size: 12px; }
                </style>
            </head>
            <body>
                <main>
                    <xsl:apply-templates select="s:urlset | s:sitemapindex"/>
                </main>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="s:urlset">
        <h1>MAMIAS sitemap</h1>
        <p>
            <xsl:value-of select="count(s:url)"/> pages, for search engines. This view is only for people reading it in a browser.
        </p>
        <div class="frame">
            <table>
                <thead><tr><th>#</th><th>Page</th><th>Languages</th><th>Last modified</th></tr></thead>
                <tbody>
                    <xsl:for-each select="s:url">
                        <tr>
                            <td class="n"><xsl:value-of select="position()"/></td>
                            <td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
                            <td>
                                <xsl:for-each select="xhtml:link[@hreflang != 'x-default']">
                                    <span class="lang"><xsl:value-of select="@hreflang"/></span>
                                </xsl:for-each>
                            </td>
                            <td class="d"><xsl:value-of select="substring(s:lastmod, 1, 10)"/></td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="s:sitemapindex">
        <h1>MAMIAS sitemap index</h1>
        <p>
            <xsl:value-of select="count(s:sitemap)"/> sitemap files, for search engines. Open one to see its pages.
        </p>
        <div class="frame">
            <table>
                <thead><tr><th>#</th><th>Sitemap</th><th>Last modified</th></tr></thead>
                <tbody>
                    <xsl:for-each select="s:sitemap">
                        <tr>
                            <td class="n"><xsl:value-of select="position()"/></td>
                            <td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
                            <td class="d"><xsl:value-of select="substring(s:lastmod, 1, 10)"/></td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>
    </xsl:template>
</xsl:stylesheet>
