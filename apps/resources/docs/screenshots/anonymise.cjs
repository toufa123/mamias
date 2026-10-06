/**
 * Screenshot helpers shared by the guide and manual scripts in this folder.
 *
 * The developer logins are real people's accounts. Before any shot, `anonymise`
 * swaps their name, email, initials, avatar and phone for a demo identity in the
 * page, so no screenshot published in a guide or manual carries personal data.
 * Call `learnDeveloperNames(browser, BASE)` once at the start: names of accounts
 * that are not signed in still appear on screens (Reported By, Submitted By).
 */
const knownNames = [];

const learnName = async (page) => {
    const name = await page
        .locator('img')
        .evaluateAll((imgs) => imgs.map((i) => { try { return new URL(i.src, location.href).searchParams.get('name'); } catch (e) { return null; } }).find(Boolean));
    if (name && !knownNames.includes(name)) knownNames.push(name);
};

const anonymise = (page) =>
    page.evaluate((knownNames) => {
            const demo = { first: 'Leila', last: 'Haddad', email: 'leila.haddad@example.org' };
            const emailRe = /[\w.+-]+@[\w-]+(\.[\w-]+)+/;
            const texts = [];
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
            while (walker.nextNode()) texts.push(walker.currentNode);
            const emailNode = texts.find((t) => emailRe.test(t.textContent));
            const email = emailNode?.textContent.match(emailRe)[0] ?? document.querySelector('input[type="email"]')?.value;
            const first = document.querySelector('input[id$="first_name"]')?.value;
            const last = document.querySelector('input[id$="last_name"]')?.value;
            const name = first && last ? `${first} ${last}` : emailNode?.parentElement?.previousElementSibling?.textContent.trim();
            const names = [...new Set([name, ...knownNames].filter(Boolean))];
            const initials = name ? name.split(/\s+/).filter(Boolean).map((w) => w[0]).filter((c, i, a) => i === 0 || i === a.length - 1).join('').toUpperCase() : null;
            for (const t of texts) {
                let v = t.textContent;
                if (email) v = v.split(email).join(demo.email);
                for (const n of names) v = v.split(n).join(`${demo.first} ${demo.last}`);
                if (initials && v.trim() === initials) v = v.replace(initials, 'LH');
                t.textContent = v;
            }
            document.querySelectorAll('input').forEach((i) => {
                if (i.type === 'email' || emailRe.test(i.value)) i.value = demo.email;
                if (i.id.endsWith('first_name')) i.value = demo.first;
                if (i.id.endsWith('last_name')) i.value = demo.last;
                if (i.type === 'tel' || i.id.endsWith('phone')) i.value = '';
            });
            document.querySelectorAll('img').forEach((img) => {
                if (names.some((n) => img.alt.includes(n))) img.alt = `${demo.first} ${demo.last}`;
                // Avatars are drawn by UI Avatars from the name in the URL.
                try {
                    const url = new URL(img.src);
                    if (url.searchParams.has('name')) {
                        url.searchParams.set('name', `${demo.first} ${demo.last}`);
                        img.src = url.toString();
                    }
                } catch (e) {}
            });
        }, knownNames);

/** Signs in once with every "Login as" button, in throwaway contexts, to learn all their names. */
const learnDeveloperNames = async (browser, base) => {
    for (const button of [/Admin \(/, /Scientist \(/, /Public User \(/]) {
        const context = await browser.newContext({ ignoreHTTPSErrors: true });
        const page = await context.newPage();
        await page.goto(base + '/mamias/login', { waitUntil: 'networkidle' });
        await page.getByRole('button', { name: button }).click();
        await page.waitForURL((url) => !url.pathname.includes('/login'));
        await page.goto(base + '/', { waitUntil: 'networkidle' });
        await learnName(page);
        await context.close();
    }
};

module.exports = { anonymise, learnName, learnDeveloperNames };
