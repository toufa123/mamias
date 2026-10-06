/**
 * Retakes the screenshots of the Occurrences guide
 * (resources/docs/occurrences.md → public/images/docs/occurrences/).
 *
 * Seed the demo reports first, and remove them afterwards:
 *
 *   docker compose --profile dev exec -u www-data app php artisan guides:demo-data
 *   PLAYWRIGHT_CORE=/path/to/node_modules/playwright-core \
 *   CHROME=/path/to/chrome \
 *   node resources/docs/screenshots/occurrences.cjs [name...]
 *   docker compose --profile dev exec app php artisan guides:demo-data --remove
 *
 * Same conventions as intro-events.cjs: logged in through the "Admin"
 * developer-login button, names retake only those shots, BASE overrides the
 * site URL. Nothing is saved: the reject and create windows are cancelled.
 */
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');
const fs = require('fs');
const path = require('path');

const { anonymise, learnDeveloperNames } = require('./anonymise.cjs');

const BASE = process.env.BASE || 'https://localhost:7443';
const OUT = path.resolve(__dirname, '../../../public/images/docs/occurrences');
const only = process.argv.slice(2);
const want = (...names) => !only.length || names.some((n) => only.includes(n));

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
    await learnDeveloperNames(browser, BASE);
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1540, height: 1500 },
        deviceScaleFactor: 1.25,
    });

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
        await page.waitForTimeout(1500); // basemap tiles
    };

    const shotClip = async (name, clip) => {
        if (!want(name)) return;
        await anonymise(page);
        await page.waitForTimeout(1200); // the rewritten avatars load
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

    await go('/mamias/login');
    await page.getByRole('button', { name: /Admin \(/ }).click();
    await page.waitForURL('**/mamias');

    // 01 map and list
    if (want('01-list')) {
        await go('/mamias/occurrences');
        const main = await page.locator('.fi-main').first().boundingBox();
        await shotClip('01-list', { x: main.x, y: main.y, width: main.width, height: 1180 });
    }

    // 02 the resubmitted note on a status badge (newest row)
    if (want('02-resubmitted')) {
        await go('/mamias/occurrences');
        const row = page.locator('.fi-ta-row').first();
        await row.locator('.fi-badge').first().hover();
        await page.waitForTimeout(1000);
        const box = await row.boundingBox();
        await shotClip('02-resubmitted', { x: box.x - 8, y: box.y - 90, width: Math.min(box.width, 900) + 16, height: box.height + 100 });
    }

    // 03 details window · 04 reject window (cancelled)
    if (want('03-view', '04-reject')) {
        await go('/mamias/occurrences');
        await page.locator('.fi-ta-row').first().locator('.fi-dropdown-trigger button').last().click();
        await page.waitForTimeout(700);
        await page.locator('.fi-dropdown-panel').locator('visible=true').first().getByText('View', { exact: true }).click();
        await page.waitForTimeout(2500);
        await shot('03-view', modal());

        if (want('04-reject')) {
            await modal().locator('.fi-modal-footer').getByRole('button', { name: 'Reject' }).click();
            await page.waitForTimeout(1200);
            await shot('04-reject', modal());
        }
    }

    // 05 filters
    if (want('05-filters')) {
        await go('/mamias/occurrences');
        await page.getByRole('button', { name: /filter/i }).first().click();
        await page.waitForTimeout(1200);
        await shot('05-filters', page.locator('.fi-ta-filters').first(), 24);
    }

    // 06 new occurrence window (cancelled)
    if (want('06-create')) {
        await go('/mamias/occurrences');
        await page.getByRole('button', { name: 'New occurrence' }).click();
        await page.waitForTimeout(2500);
        await shot('06-create', modal());
    }

    await browser.close();

    if (errors.length) {
        console.error('page errors:', errors);
        process.exitCode = 1;
    }
})();
