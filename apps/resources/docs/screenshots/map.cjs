/**
 * Retakes the screenshots of the Map guide
 * (resources/docs/map.md → public/images/docs/map/).
 *
 *   PLAYWRIGHT_CORE=/path/to/node_modules/playwright-core \
 *   CHROME=/path/to/chrome \
 *   node resources/docs/screenshots/map.cjs [name...]
 *
 * Same conventions as intro-events.cjs; names look like `02-subregion`. The
 * map is public, so every shot is taken signed out. The approved occurrences
 * of the dev data are enough for the pins. Nothing is saved.
 */
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');
const fs = require('fs');
const path = require('path');

const { anonymise } = require('./anonymise.cjs');

const BASE = process.env.BASE || 'https://localhost:7443';
const OUT = path.resolve(__dirname, '../../../public/images/docs/map');
const only = process.argv.slice(2);
const want = (...names) => !only.length || names.some((n) => only.includes(n));

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1400, height: 900 }, deviceScaleFactor: 1.25 });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));

    const settle = (ms = 1800) => page.waitForTimeout(ms); // Livewire round trip, then the map redraws

    // Page coordinates of the box spanning these elements, so a full-page shot
    // can be clipped to them without the sticky navbar landing on top.
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

    // The page's sections, in order: filters, map, summary, species table.
    const FILTERS = '.fi-section >> nth=0';
    const MAP = '.fi-section >> nth=1';
    const SUMMARY = '.fi-section >> nth=2';
    const TABLE = '#species';

    const mapPoint = (lat, lng) =>
        page.evaluate(([lat, lng]) => {
            const el = document.querySelector('[x-data^="leafletMapWidget"]');
            const map = Alpine.raw(Alpine.$data(el).mapCore.map);
            const p = map.latLngToContainerPoint([lat, lng]);
            const r = el.getBoundingClientRect();
            return [r.x + p.x, r.y + p.y];
        }, [lat, lng]);

    const clickMap = async (lat, lng) => {
        await page.locator('[x-data^="leafletMapWidget"]').scrollIntoViewIfNeeded();
        const [x, y] = await mapPoint(lat, lng);
        await page.mouse.click(x, y);
        await page.mouse.move(1, 1); // off the map, so no hover tooltip covers it
        await settle();
    };

    await page.goto(`${BASE}/pages/map`, { waitUntil: 'networkidle' });
    const accept = page.getByRole('button', { name: 'Accept all' }).locator('visible=true');
    if (await accept.count()) {
        await accept.first().click();
        await page.waitForSelector('.cookie-consent-root.cookie-consent-hide', { state: 'attached' });
    }
    // Accepted, the banner only slides off-screen; a full-page shot would still catch it.
    await page.addStyleTag({ content: '.cookie-consent-root { display: none !important; }' });
    await settle();

    // 1. The whole working area on arrival: guide button, filters, map.
    await shot('01-page', 'button:has-text("Guide")', FILTERS, MAP);

    // 2. A subregion picked: the Western Mediterranean, then its summary.
    await clickMap(39.5, 4.5);
    await shot('02-subregion', MAP, SUMMARY);

    // 3. The species of that subregion.
    await shot('03-table', TABLE);

    // 4. Countries of first record, with the occurrence pins, France picked.
    await page.locator('.fi-tabs-item', { hasText: 'Countries' }).click();
    await settle();
    await page.getByLabel('Occurrences').check();
    await settle();
    await clickMap(43.6, 4.6); // France's anchor (NisAreaMap::COUNTRY_ANCHORS)
    await shot('04-countries', MAP, SUMMARY);

    // 5. The EcAp filter narrowing the map to the Adriatic.
    await page.locator('.fi-tabs-item', { hasText: 'Subregions' }).click();
    await settle();
    await page.getByLabel('Occurrences').uncheck();
    await settle();
    await page.locator('.fi-fo-field', { hasText: 'EcAp Subregion' }).locator('button').first().click();
    await page.getByRole('option', { name: 'Adriatic Sea' }).click();
    await page.keyboard.press('Escape');
    await settle(2500);
    await shot('05-filtered', FILTERS, MAP);

    await browser.close();
    if (errors.length) {
        console.error('page errors:', errors);
        process.exitCode = 1;
    }
})();
