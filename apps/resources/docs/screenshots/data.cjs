/**
 * Retakes the screenshots of the data explorer guide
 * (resources/docs/data.md → public/images/docs/data/).
 *
 *   PLAYWRIGHT_CORE=/path/to/node_modules/playwright-core \
 *   CHROME=/path/to/chrome \
 *   node resources/docs/screenshots/data.cjs [name...]
 *
 * Same conventions as map.cjs; names look like `02-filtered`. The data
 * explorer is public, so every shot is taken signed out. Nothing is saved.
 */
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');
const fs = require('fs');
const path = require('path');

const { anonymise } = require('./anonymise.cjs');

const BASE = process.env.BASE || 'https://localhost:7443';
const OUT = path.resolve(__dirname, '../../../public/images/docs/data');
const SPECIES = 'Pterois miles';
const only = process.argv.slice(2);
const want = (...names) => !only.length || names.some((n) => only.includes(n));

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1400, height: 900 }, deviceScaleFactor: 1.25 });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));

    const settle = (ms = 1800) => page.waitForTimeout(ms);

    // Page coordinates of the box around these elements, for a full-page shot
    // clipped to them without the sticky navbar landing on top.
    const box = async (...selectors) => {
        const rects = [];
        for (const selector of selectors) {
            rects.push(await page.locator(selector).first().evaluate((el) => {
                const r = el.getBoundingClientRect();
                return { top: r.top + window.scrollY, bottom: r.bottom + window.scrollY, left: r.left, right: r.right };
            }));
        }
        const top = Math.min(...rects.map((r) => r.top)) - 12;
        const left = Math.min(...rects.map((r) => r.left)) - 12;
        return {
            x: Math.max(0, left),
            y: Math.max(0, top),
            width: Math.max(...rects.map((r) => r.right)) - left + 12,
            height: Math.max(...rects.map((r) => r.bottom)) - top + 12,
        };
    };

    const shot = async (name, ...selectors) => {
        if (!want(name)) return;
        await anonymise(page);
        await page.evaluate(() => window.scrollTo(0, 0));
        await settle(600);
        await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: true, clip: await box(...selectors) });
        console.log('saved', name);
    };

    const dismissCookies = async () => {
        const accept = page.getByRole('button', { name: 'Accept all' }).locator('visible=true');
        if (await accept.count()) {
            await accept.first().click();
            await page.waitForSelector('.cookie-consent-root.cookie-consent-hide', { state: 'attached' });
        }
        // Accepted, the banner only slides off-screen; a full-page shot would still catch it.
        await page.addStyleTag({ content: '.cookie-consent-root { display: none !important; }' });
    };

    const FILTERS = '.fi-section >> nth=0';
    const TABLE = '.fi-section >> nth=1';

    await page.goto(`${BASE}/pages/data`, { waitUntil: 'networkidle' });
    await dismissCookies();
    await settle();

    // 1. The page on arrival: guide button, filters, the top of the table.
    if (want('01-page')) {
        await page.setViewportSize({ width: 1400, height: 1300 });
        await shot('01-page', 'button:has-text("Guide")', FILTERS, 'table tbody tr:nth-child(4)');
        await page.setViewportSize({ width: 1400, height: 900 });
    }

    // 2. Two filters applied: their chips above the table.
    await page.locator('.fi-fo-field', { hasText: 'Establishment Status' }).locator('button').first().click();
    await page.getByRole('option', { name: 'Established' }).click();
    await page.keyboard.press('Escape');
    await page.locator('.fi-fo-field', { hasText: 'EcAp Subregion' }).locator('button').first().click();
    await page.getByRole('option', { name: 'Adriatic Sea' }).click();
    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Apply filters' }).click();
    await settle(2500);
    await shot('02-filtered', TABLE);

    // 3. A species page.
    await page.goto(`${BASE}/pages/data`, { waitUntil: 'networkidle' });
    await page.locator('.fi-ta-search-field input').fill(SPECIES);
    await settle(2000);
    await page.getByRole('link', { name: SPECIES }).first().click();
    await page.waitForLoadState('networkidle');
    await settle();
    if (want('03-species')) {
        await page.setViewportSize({ width: 1400, height: 1600 });
        await page.addStyleTag({ content: '.cookie-consent-root { display: none !important; }' });
        await shot('03-species', '.fi-sc-tabs >> nth=0', '.fi-section >> nth=-1');
    }

    await browser.close();
    if (errors.length) {
        console.error('page errors:', errors);
        process.exitCode = 1;
    }
})();
