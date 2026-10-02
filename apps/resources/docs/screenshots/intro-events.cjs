/**
 * Retakes the screenshots of the Introduction Events guide
 * (resources/docs/intro-events.md → public/images/docs/intro-events/).
 *
 * Run from the host against the running dev stack, logged in through the
 * "Admin" developer-login button (local environment only):
 *
 *   PLAYWRIGHT_CORE=/path/to/node_modules/playwright-core \
 *   CHROME=/path/to/chrome \
 *   node resources/docs/screenshots/intro-events.cjs [name...]
 *
 * - Playwright is not a project dependency: point PLAYWRIGHT_CORE at any
 *   installed playwright-core (e.g. ~/.npm/_npx/<hash>/node_modules/playwright-core).
 *   CHROME is optional when that install has its own browser.
 * - Names (e.g. `03-view 08-pathway-check`) retake only those shots.
 * - BASE overrides the site URL (default https://localhost:7443).
 *
 * Nothing is saved or imported: the EASIN confirmation and the import window
 * are cancelled. The records shown are #249 (Caulerpa cylindracea, view and
 * edit) and #428 (Epinephelus merra, EASIN actions); change them below if the
 * data moves. 07-needs-review needs at least one flagged event and is skipped
 * when the tab is empty.
 */
const { chromium } = require(process.env.PLAYWRIGHT_CORE || 'playwright-core');
const fs = require('fs');
const os = require('os');
const path = require('path');

const BASE = process.env.BASE || 'https://localhost:7443';
const OUT = path.resolve(__dirname, '../../../public/images/docs/intro-events');
const VIEW_SPECIES = 'Caulerpa cylindracea';
const EASIN_SPECIES = 'Epinephelus merra';
const only = process.argv.slice(2);
const want = (...names) => !only.length || names.some((n) => only.includes(n));

// A one-row file, only to show the column mapping; the import is never run.
const SAMPLE = path.join(os.tmpdir(), 'intro-events-sample.csv');
fs.writeFileSync(
    SAMPLE,
    'Scientific Name,First Introduction Year,Country,NIS Status,Establishment Status,' +
        'WMED – Establishment Status,WMED – First Arrival Year,CMED – Establishment Status,CMED – First Arrival Year,' +
        'Adriatic – Establishment Status,Adriatic – First Arrival Year,EMED – Establishment Status,EMED – First Arrival Year,Notes\n' +
        'Caulerpa cylindracea,1985,Tunisia,NIS,est,est,1993,est,1985,est,1993,est,1991,\n',
);

(async () => {
    const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1540, height: 1500 },
        deviceScaleFactor: 1.25,
    });

    // Toasts (new WoRMS names, comments) would cover the screens.
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
        await page.waitForTimeout(800);
    };

    // A page-coordinate crop: boundingBox() is relative to the viewport.
    const shotClip = async (name, clip) => {
        if (!want(name)) return;
        await page.waitForTimeout(500);
        const scrollY = await page.evaluate(() => window.scrollY);
        await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: true, clip: { ...clip, y: clip.y + scrollY } });
        console.log('saved', name);
    };

    // An element with a margin, so nothing at its edge (slider tooltips) is cut.
    const shot = async (name, locator, pad = 12) => {
        if (!want(name)) return;
        await locator.scrollIntoViewIfNeeded();
        const box = await locator.boundingBox();
        await shotClip(name, { x: Math.max(0, box.x - pad), y: box.y - pad, width: box.width + 2 * pad, height: box.height + 2 * pad });
    };

    const tableTop = async (name, height) => {
        const box = await page.locator('.fi-ta').first().boundingBox();
        await shotClip(name, { x: box.x - 12, y: box.y - 12, width: box.width + 24, height: Math.min(box.height, height) + 24 });
    };

    const search = async (text) => {
        await page.locator('.fi-ta-search-field input, input[type="search"]').first().fill(text);
        await page.waitForTimeout(1500);
    };

    const openRowMenu = async () => {
        const row = page.locator('.fi-ta-row').first();
        await row.locator('.fi-dropdown-trigger button, button.fi-icon-btn').last().click();
        await page.waitForTimeout(700);

        return { row, panel: page.locator('.fi-dropdown-panel').locator('visible=true').first() };
    };

    const modal = () => page.locator('.fi-modal-window').locator('visible=true').first();

    await go('/mamias/login');
    await page.getByRole('button', { name: /Admin \(/ }).click();
    await page.waitForURL('**/mamias');

    // 01 list · 02 filters · 03 view window
    if (want('01-list', '02-filters', '03-view')) {
        await go('/mamias/intro-event-records');
        const main = await page.locator('.fi-main').first().boundingBox();
        await shotClip('01-list', { x: main.x, y: main.y, width: main.width, height: 620 });

        if (want('02-filters')) {
            await page.getByRole('button', { name: /filter/i }).first().click();
            await page.waitForTimeout(1200);
            await shot('02-filters', page.locator('.fi-ta-filters').first(), 24);
            await go('/mamias/intro-event-records');
        }

        if (want('03-view')) {
            await search(VIEW_SPECIES);
            const { panel } = await openRowMenu();
            await panel.getByText('View', { exact: true }).click();
            await page.waitForTimeout(1500);
            await shot('03-view', modal());
        }
    }

    // 04 new event, species picker open
    if (want('04-create')) {
        await go('/mamias/intro-event-records/create');
        const section = page.locator('.fi-section').first();
        await section.locator('.fi-select-input-btn, button[aria-haspopup]').first().click();
        await page.waitForTimeout(1200);
        const box = await section.boundingBox();
        const dropdown = page.locator('.fi-dropdown-panel, [role="listbox"]').locator('visible=true').first();
        const drop = (await dropdown.count()) ? await dropdown.boundingBox() : null;
        const bottom = Math.max(box.y + box.height, drop ? drop.y + drop.height : 0);
        await shotClip('04-create', { x: box.x - 12, y: box.y - 12, width: box.width + 24, height: bottom - box.y + 24 });
    }

    // 05 / 06 edit page tabs
    if (want('05-edit-subregions', '06-edit-pathways')) {
        await go('/mamias/intro-event-records');
        await search(VIEW_SPECIES);
        const { panel } = await openRowMenu();
        await panel.getByText('Edit', { exact: true }).click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(800);
        const tabs = page.locator('.fi-sc-tabs').last();
        await shot('05-edit-subregions', tabs);
        await tabs.getByRole('tab', { name: /Pathways/ }).click();
        await page.waitForTimeout(800);
        await shot('06-edit-pathways', tabs);
    }

    // 07 Needs review
    if (want('07-needs-review')) {
        await go('/mamias/intro-event-records');
        await page.getByRole('tab', { name: /Needs review/ }).click();
        await page.waitForTimeout(1500);

        if (await page.locator('.fi-ta-row').count()) {
            await tableTop('07-needs-review', 330);
        } else {
            console.log('skipped 07-needs-review: no flagged event to show');
        }
    }

    // 08 Pathway check · 09 its row menu · 10 EASIN confirmation (cancelled)
    if (want('08-pathway-check', '09-pathway-menu', '10-easin-confirm')) {
        await go('/mamias/intro-event-records');
        await page.getByRole('tab', { name: /Pathway check/ }).click();
        await page.waitForTimeout(2500);
        await tableTop('08-pathway-check', 560);

        await search(EASIN_SPECIES);
        const { row, panel } = await openRowMenu();
        const rowBox = await row.boundingBox();
        const panelBox = await panel.boundingBox();
        await shotClip('09-pathway-menu', { x: rowBox.x - 8, y: rowBox.y - 8, width: rowBox.width + 16, height: panelBox.y + panelBox.height - rowBox.y + 16 });

        await panel.getByText('Apply EASIN decision', { exact: true }).click();
        await page.waitForTimeout(1200);
        await shot('10-easin-confirm', modal());
        await modal().getByRole('button', { name: 'Cancel' }).click();
    }

    // 11 import window · 12 column mapping (never submitted)
    if (want('11-import-modal', '12-import-mapping')) {
        await go('/mamias/intro-event-records');
        await page.getByRole('button', { name: 'Import Intro Events' }).click();
        await page.waitForTimeout(1200);
        await shot('11-import-modal', modal());
        await modal().locator('input[type="file"]').setInputFiles(SAMPLE);
        await page.waitForTimeout(5000);
        const box = await modal().boundingBox();
        await shotClip('12-import-mapping', { x: box.x, y: box.y, width: box.width, height: 640 });
        await modal().getByRole('button', { name: 'Cancel' }).click();
    }

    fs.unlinkSync(SAMPLE);
    await browser.close();

    if (errors.length) {
        console.error('page errors:', errors);
        process.exitCode = 1;
    }
})();
