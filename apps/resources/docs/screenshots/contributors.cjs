/**
 * Retakes the screenshots of the contributor guides on the public site:
 * resources/docs/species-reports.md and suggestions.md
 * → public/images/docs/{species-reports,suggestions}/.
 *
 * Seed the demo records onto the "Public User" developer login first, and
 * remove them afterwards:
 *
 *   docker compose --profile dev exec -u www-data app php artisan guides:demo-data --contributor=<public user email>
 *   PLAYWRIGHT_CORE=/path/to/node_modules/playwright-core \
 *   CHROME=/path/to/chrome \
 *   node resources/docs/screenshots/contributors.cjs [name...]
 *   docker compose --profile dev exec app php artisan guides:demo-data --remove
 *
 * Same conventions as intro-events.cjs; names look like `species-reports/03-view`.
 * Nothing is saved: every form is cancelled.
 */
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');
const fs = require('fs');
const path = require('path');

const { anonymise, learnDeveloperNames } = require('./anonymise.cjs');

const BASE = process.env.BASE || 'https://localhost:7443';
const OUT = path.resolve(__dirname, '../../../public/images/docs');
const only = process.argv.slice(2);
const want = (...names) => !only.length || names.some((n) => only.includes(n));

(async () => {
    const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
    await learnDeveloperNames(browser, BASE);
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1540, height: 1300 },
        deviceScaleFactor: 1.25,
    });

    // Toasts would cover the screens.
    await context.addInitScript(() =>
        document.addEventListener('DOMContentLoaded', () => {
            const style = document.createElement('style');
            style.textContent = '.fi-no-notification, .fi-no { display: none !important; }';
            document.head.appendChild(style);
        }),
    );

    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));

    const go = async (url) => {
        await page.goto(BASE + url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1500); // map tiles
    };

    const shotClip = async (name, clip) => {
        if (!want(name)) return;
        await anonymise(page);
        await page.waitForTimeout(1200); // the rewritten avatars load
        fs.mkdirSync(path.dirname(`${OUT}/${name}.png`), { recursive: true });
        const scrollY = await page.evaluate(() => window.scrollY);
        await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: true, clip: { ...clip, y: clip.y + scrollY } });
        console.log('saved', name);
    };

    const shot = async (name, locator, pad = 12) => {
        if (!want(name)) return;
        await locator.scrollIntoViewIfNeeded();
        const box = await locator.boundingBox();
        await shotClip(name, { x: Math.max(0, box.x - pad), y: box.y - pad, width: box.width + 2 * pad, height: box.height + 2 * pad });
    };

    const modal = () => page.locator('.fi-modal-window').locator('visible=true').last();
    const row = (text) => page.locator('.fi-ta-row', { hasText: text }).first();

    // The page content: the counters down to the bottom of the table.
    const content = async (name) => {
        const top = await page.locator('main#content').first().boundingBox();
        const table = await page.locator('.fi-ta').first().boundingBox();
        await shotClip(name, { x: top.x, y: top.y, width: top.width, height: table.y + table.height - top.y + 16 });
    };

    // The reason tooltip over a rejected row's status badge.
    const rejectedHover = async (name, text) => {
        const r = row(text);
        await r.scrollIntoViewIfNeeded();
        await r.locator('.fi-badge').first().hover();
        await page.waitForTimeout(1000);
        const box = await r.boundingBox();
        await shotClip(name, { x: box.x - 8, y: box.y - 110, width: Math.min(box.width, 1000) + 16, height: box.height + 120 });
    };

    await go('/mamias/login');
    await page.getByRole('button', { name: /Public User \(/ }).click();
    await page.waitForURL((url) => !url.pathname.includes('/login'));

    // The public site blocks every click (html.cookie-disable-interaction) until
    // the cookie dialog is answered; the panel, where the login lands, has none.
    await go('/');
    await page.locator('.cookie-consent-accept').locator('visible=true').first().click();
    await page.waitForTimeout(500);

    // My Species Reports
    if (want('species-reports/01-page', 'species-reports/02-form', 'species-reports/03-view', 'species-reports/04-rejected', 'species-reports/05-resubmit')) {
        await go('/my-species-reports');
        await content('species-reports/01-page');

        if (want('species-reports/02-form')) {
            await page.getByRole('button', { name: 'Report New Occurrence' }).click();
            await page.waitForTimeout(2500);
            await shot('species-reports/02-form', modal());
            await go('/my-species-reports');
        }

        if (want('species-reports/03-view')) {
            await row('Lagocephalus sceleratus').getByRole('button', { name: 'View' }).first().click();
            await page.waitForTimeout(2500);
            await shot('species-reports/03-view', modal());
            await go('/my-species-reports');
        }

        if (want('species-reports/04-rejected')) await rejectedHover('species-reports/04-rejected', 'Siganus rivulatus');

        if (want('species-reports/05-resubmit')) {
            await go('/my-species-reports');
            await row('Siganus rivulatus').getByRole('button', { name: 'Revise & resubmit' }).first().click();
            await page.waitForTimeout(2500);
            const box = await modal().boundingBox();
            await shotClip('species-reports/05-resubmit', { x: box.x, y: box.y, width: box.width, height: Math.min(box.height, 420) });
        }
    }

    // My Species Suggestions
    if (want('suggestions/01-page', 'suggestions/02-form', 'suggestions/03-view', 'suggestions/04-rejected')) {
        await go('/my-suggestions');
        await content('suggestions/01-page');

        if (want('suggestions/02-form')) {
            await page.getByRole('button', { name: 'Suggest New NIS' }).click();
            await page.waitForTimeout(2500);
            await shot('suggestions/02-form', modal());
            await go('/my-suggestions');
        }

        if (want('suggestions/03-view')) {
            await row('Portunus segnis').getByRole('button', { name: 'View' }).first().click();
            await page.waitForTimeout(2500);
            await shot('suggestions/03-view', modal());
            await go('/my-suggestions');
        }

        if (want('suggestions/04-rejected')) await rejectedHover('suggestions/04-rejected', 'Carcinus aestuarii');
    }

    await browser.close();

    if (errors.length) {
        console.error('page errors:', errors);
        process.exitCode = 1;
    }
})();
