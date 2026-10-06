/**
 * Retakes the screenshots of the two manuals
 * (resources/docs/user-manual.md, admin-manual.md → public/images/docs/manuals/).
 * The manuals also reuse the screen guides' own shots; retake those with the
 * guide scripts beside this one.
 *
 * Run with the demo data loaded (php artisan guides:demo-data --contributor=<public user email>):
 *
 *   PLAYWRIGHT_CORE=/path/to/node_modules/playwright-core \
 *   CHROME=/path/to/chrome \
 *   node resources/docs/screenshots/manuals.cjs [name...]
 *
 * Same conventions as intro-events.cjs; names look like `user-03-data-explorer`.
 * The public shots are taken signed out (then as the "Public User" developer
 * login), the panel shots as "Admin". Nothing is saved.
 */
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');
const fs = require('fs');
const path = require('path');

const { anonymise, learnName, learnDeveloperNames } = require('./anonymise.cjs');

const BASE = process.env.BASE || 'https://localhost:7443';
const OUT = path.resolve(__dirname, '../../../public/images/docs/manuals');
const SPECIES = 'Pterois miles';
const only = process.argv.slice(2);
const want = (...names) => !only.length || names.some((n) => only.includes(n));

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
    await learnDeveloperNames(browser, BASE);
    const errors = [];

    const session = async ({ hideToasts = true } = {}) => {
        const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1400, height: 900 }, deviceScaleFactor: 1.25 });
        if (hideToasts) {
            await context.addInitScript(() =>
                document.addEventListener('DOMContentLoaded', () => {
                    const style = document.createElement('style');
                    style.textContent = '.fi-no-notification, .fi-no { display: none !important; }';
                    document.head.appendChild(style);
                }),
            );
        }
        const page = await context.newPage();
        page.on('pageerror', (e) => errors.push(e.message));
        return page;
    };

    const go = async (page, url) => {
        await page.goto(BASE + url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1500); // maps and charts
    };

    // Every shot is anonymised first (anonymise.cjs).
    const shot = async (page, name, clip) => {
        if (!want(name)) return;
        await anonymise(page);
        await page.waitForTimeout(1200); // the rewritten avatars load
        await page.screenshot({ path: `${OUT}/${name}.png`, ...(clip ? { clip } : {}) });
        console.log('saved', name);
    };

    const acceptCookies = async (page) => {
        const accept = page.locator('.cookie-consent-accept').locator('visible=true').first();
        if (await accept.count()) {
            await accept.click();
            await page.waitForTimeout(500);
        }
    };

    const login = async (page, button) => {
        await go(page, '/mamias/login');
        await page.getByRole('button', { name: button }).click();
        await page.waitForURL((url) => !url.pathname.includes('/login'));
        // The public home page shows every account's avatar, panel user or not.
        await go(page, '/');
        await learnName(page);
    };

    // Public site, signed out
    const visitor = await session();
    await go(visitor, '/');
    await shot(visitor, 'user-01-cookies');
    await acceptCookies(visitor);
    await go(visitor, '/');
    await shot(visitor, 'user-02-home');
    await go(visitor, '/pages/data');
    await shot(visitor, 'user-03-data-explorer');
    if (want('user-04-species')) {
        await visitor.locator('.fi-ta-search-field input, input[type="search"]').first().fill(SPECIES);
        await visitor.waitForTimeout(1500);
        await visitor.locator('.fi-ta-row').first().click();
        await visitor.waitForLoadState('networkidle');
        await visitor.waitForTimeout(2000);
        await shot(visitor, 'user-04-species');
    }
    await go(visitor, '/pages/dashboard/mediterranean');
    await visitor.waitForTimeout(2000);
    await shot(visitor, 'user-05-dashboard');
    await go(visitor, '/mamias/register');
    await shot(visitor, 'user-06-sign-up');

    // Public site, signed in
    const contributor = await session();
    await login(contributor, /Public User \(/);
    await go(contributor, '/');
    await acceptCookies(contributor);
    if (want('user-07-user-menu')) {
        await contributor.locator('[data-kt-dropdown-toggle="true"]').last().click();
        await contributor.waitForTimeout(800);
        await shot(contributor, 'user-07-user-menu', { x: 900, y: 0, width: 500, height: 560 });
    }
    await go(contributor, '/profile');
    await shot(contributor, 'user-08-profile');

    // Panel
    const admin = await session();
    await login(admin, /Admin \(/);
    await go(admin, '/mamias');
    await shot(admin, 'admin-01-panel');
    if (want('admin-02-notifications')) {
        const withToasts = await session({ hideToasts: false });
        await login(withToasts, /Admin \(/);
        await go(withToasts, '/mamias/taxons/taxa');
        await shot(withToasts, 'admin-02-notifications', { x: 760, y: 0, width: 640, height: 420 });
    }
    await go(admin, '/mamias/nis-suggestions');
    if (want('admin-03-suggestion-review')) {
        await admin.locator('.fi-ta-row', { hasText: 'Penaeus aztecus' }).first().getByRole('link', { name: 'View' }).first().click();
        await admin.waitForLoadState('networkidle');
        await admin.waitForTimeout(2000);
        await shot(admin, 'admin-03-suggestion-review');
    }
    await go(admin, '/mamias/pages');
    await shot(admin, 'admin-04-pages');
    await go(admin, '/mamias/seo-files');
    await shot(admin, 'admin-05-seo-files');
    await go(admin, '/mamias/users');
    if (want('admin-06-user-edit')) {
        await admin.locator('.fi-ta-search-field input, input[type="search"]').first().fill('gmail');
        await admin.waitForTimeout(1500);
        await admin.locator('.fi-ta-row').first().locator('.fi-ta-cell').nth(1).click();
        await admin.waitForLoadState('networkidle');
        await admin.waitForTimeout(1500);
        await shot(admin, 'admin-06-user-edit');
    }
    await go(admin, '/mamias/shield/roles');
    await shot(admin, 'admin-07-roles');

    await browser.close();

    if (errors.length) {
        console.error('page errors:', errors);
        process.exitCode = 1;
    }
})();
