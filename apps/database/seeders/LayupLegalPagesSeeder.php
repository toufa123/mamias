<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Publishes the three legal pages as Layup pages: /legal-notice,
 * /terms-of-use and /cookies-policy (served by Layup's root catch-all).
 *
 * Each page is one html widget of plain semantic markup inside
 * `.mamias-legal`, styled in resources/css/app.css, so editors change the
 * text in the page builder without handling utility classes. No <h1>: the
 * site's page header already shows the title (one title per view).
 *
 * Institutional matters (UN privileges and immunities, the UN Secretariat
 * privacy policy) are referenced on spa-rac.org rather than copied, so they
 * never drift from SPA/RAC's own wording.
 *
 * Run: php artisan db:seed --class=LayupLegalPagesSeeder
 */
class LayupLegalPagesSeeder extends Seeder
{
    private const UPDATED = '24 September 2026';

    private const CONTACT = 'atef.ouerghi@spa-rac.org';

    public function run(): void
    {
        self::publish('legal-notice', 'Legal notice', 'Publisher, hosting, intellectual property and liability for the MAMIAS platform.', self::legalNotice());
        self::publish('terms-of-use', 'Terms of use', 'Conditions for using MAMIAS, contributing records and re-using its data under CC BY 4.0.', self::termsOfUse());
        self::publish('cookies-policy', 'Cookies policy', 'The cookies MAMIAS sets, why, for how long, and how to change your choice.', self::cookiesPolicy());
    }

    private static function publish(string $slug, string $title, string $description, string $body): void
    {
        $contact = self::CONTACT;
        $updated = self::UPDATED;

        $html = <<<HTML
<article class="mamias-legal">
    <p class="mamias-legal-updated">Last updated {$updated}</p>
{$body}
    <h2>Contact</h2>
    <p>Questions about this page: <a href="mailto:{$contact}">{$contact}</a>.</p>
</article>
HTML;

        Page::updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $title,
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => ['description' => $description],
                'content' => [
                    'rows' => [[
                        'id' => "row_{$slug}",
                        'settings' => ['gap' => 'gap-0'],
                        'columns' => [[
                            'id' => "col_{$slug}",
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => "widget_{$slug}",
                                'type' => 'html',
                                'data' => ['content' => $html],
                            ]],
                        ]],
                    ]],
                ],
            ]
        );
    }

    private static function legalNotice(): string
    {
        return <<<'HTML'
    <h2>Publisher</h2>
    <p>MAMIAS (Marine Mediterranean Invasive Alien Species) is published by the Specially Protected Areas Regional Activity Centre (SPA/RAC), a Regional Activity Centre of the United Nations Environment Programme / Mediterranean Action Plan (UNEP/MAP) — Barcelona Convention.</p>
    <p>SPA/RAC, Boulevard du Leader Yasser Arafat, B.P. 337, 1080 Tunis Cedex, Tunisia.</p>

    <h2>Publication responsibility</h2>
    <p>The SPA/RAC Management is responsible for the content published on MAMIAS, within the Centre's institutional mandate.</p>

    <h2>Hosting</h2>
    <p>MAMIAS is hosted by SPA/RAC.</p>

    <h2>Intellectual property</h2>
    <p>The MAMIAS database — species records, introduction events, pathways and their compilation — is released under the <a href="https://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International licence (CC BY 4.0)</a>. See the <a href="/terms-of-use">Terms of use</a> for how to cite it.</p>
    <p>The SPA/RAC and UNEP/MAP names and logos, and the site's design and software, are not covered by that licence and may not be reused without written permission.</p>

    <h2>Hyperlinks</h2>
    <p>MAMIAS links to external sites, such as WoRMS, EASIN and GBIF, for information only. SPA/RAC has no control over them and accepts no responsibility for their content.</p>

    <h2>Limitation of liability</h2>
    <p>Information on MAMIAS is provided for scientific and informational purposes. SPA/RAC makes every effort to keep it accurate and current but gives no warranty of completeness or fitness for a particular purpose, and is not liable for any damage arising from its use or from the site being unavailable.</p>

    <h2>SPA/RAC legal notice</h2>
    <p class="mamias-legal-note">Institutional matters not covered here — including the privileges and immunities of the United Nations — are governed by the <a href="https://spa-rac.org/en/legal-notice/">SPA/RAC legal notice</a>.</p>
HTML;
    }

    private static function termsOfUse(): string
    {
        return <<<'HTML'
    <h2>Acceptance</h2>
    <p>By using MAMIAS you accept these terms. If you do not accept them, please do not use the site.</p>

    <h2>Purpose of MAMIAS</h2>
    <p>MAMIAS is the regional database of marine non-indigenous species (NIS) in the Mediterranean. It maintains a validated inventory of introduction events at national and sub-regional level in support of the Ecosystem Approach (EcAp), the Integrated Monitoring and Assessment Programme (IMAP) and the Mediterranean Quality Status Report.</p>

    <h2>Accounts</h2>
    <ul>
        <li>An account is personal. Keep your password confidential and tell us if you suspect it has been misused.</li>
        <li>Provide accurate registration details. Accounts created with false details, or used to disrupt the service, may be suspended.</li>
        <li>Access to the management panel is granted by SPA/RAC according to your role.</li>
    </ul>

    <h2>Contributions</h2>
    <p>Occurrence records, photographs, species suggestions and references you submit are reviewed by the MAMIAS team before publication and may be edited, merged or declined.</p>
    <ul>
        <li>You confirm that you have the right to submit the material, including any photographs.</li>
        <li>You keep your rights in what you submit, and grant SPA/RAC a licence to publish it under CC BY 4.0, credited to you.</li>
    </ul>

    <h2>Re-using MAMIAS data</h2>
    <p>MAMIAS data are published under <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>: you may share and adapt them, including commercially, provided you give appropriate credit. Please cite:</p>
    <p class="mamias-legal-note">SPA/RAC (year). MAMIAS — Marine Mediterranean Invasive Alien Species database. https://mamias.org, accessed on (date).</p>

    <h2>No warranty</h2>
    <p>Data are provided as they stand. Taxonomy follows WoRMS and is revised as the literature evolves; always check the current record before relying on it.</p>

    <h2>Geographic designations</h2>
    <p>The designations employed and the presentation of material on MAMIAS do not imply the expression of any opinion whatsoever on the part of SPA/RAC or UNEP concerning the legal status of any country, territory or area, or of its authorities, or concerning the delimitation of its frontiers or boundaries.</p>

    <h2>Personal data</h2>
    <p>Personal data are processed in accordance with the UN Secretariat Data Protection and Privacy Policy. Registration collects your name, email address, country, and optionally your phone number, taxonomic areas and sub-regions of interest. If you give a phone number, it is sent to GreenAPI, a third-party service, only to check whether it is registered on WhatsApp. See the <a href="/cookies-policy">Cookies policy</a> for cookies.</p>

    <h2>Changes to these terms</h2>
    <p>SPA/RAC may update these terms at any time; the date above shows the latest version.</p>

    <h2>SPA/RAC terms</h2>
    <p class="mamias-legal-note">Matters not covered here, including the privileges and immunities of the United Nations, are governed by the <a href="https://spa-rac.org/en/terms-of-use/">SPA/RAC terms of use</a>.</p>
HTML;
    }

    private static function cookiesPolicy(): string
    {
        return <<<'HTML'
    <h2>What cookies are</h2>
    <p>Cookies are small text files your browser stores and sends back to the site on later visits.</p>

    <h2>Cookies MAMIAS sets</h2>
    <p>MAMIAS uses only the cookies it needs to work and to remember your cookie choice. It sets no analytics, advertising or tracking cookies.</p>
    <table>
        <thead>
            <tr><th>Cookie</th><th>Purpose</th><th>Duration</th></tr>
        </thead>
        <tbody>
            <tr><td><code>mamias-session</code></td><td>Keeps you signed in and holds your session.</td><td>2 hours of inactivity</td></tr>
            <tr><td><code>XSRF-TOKEN</code></td><td>Protects forms against cross-site request forgery.</td><td>2 hours of inactivity</td></tr>
            <tr><td><code>remember_web_…</code></td><td>Keeps you signed in when you tick “Remember me”.</td><td>400 days</td></tr>
            <tr><td><code>mamias_…</code> (with the year)</td><td>Records whether you accepted or rejected cookies, and your preferences.</td><td>365 days if accepted, 7 days if rejected</td></tr>
        </tbody>
    </table>

    <h2>Other storage</h2>
    <p>Your light or dark theme choice is kept in your browser's local storage, not in a cookie, and never leaves your device.</p>
    <p>Fonts are served by Bunny Fonts, which does not set cookies or log visitors' IP addresses. The anti-spam check on forms is hosted by SPA/RAC itself.</p>

    <h2>Your choices</h2>
    <p>Use “Change Cookie Preferences” in the site footer at any time. You can also delete or block cookies in your browser settings; blocking the session cookie will prevent you from signing in.</p>

    <h2>Your rights</h2>
    <p>You may ask to access, correct or delete the personal data MAMIAS holds about you, or object to its processing, using the contact below. Processing follows the UN Secretariat Data Protection and Privacy Policy.</p>

    <h2>SPA/RAC cookies policy</h2>
    <p class="mamias-legal-note">The main SPA/RAC website has its own <a href="https://spa-rac.org/en/cookies-policy/">cookies policy</a>.</p>
HTML;
    }
}
