/**
 * Per-page guided tours of the public site: Data, both dashboards, Map and
 * the three "My …" pages. On a visitor's first visit to one of them, a card
 * asks whether to take its tour; either answer is remembered in localStorage,
 * so it asks once per page. The navbar's [data-tour-start] replays the tour of
 * the page it is on (or opens Data's when the page has none). Styled in
 * app.css (.mamias-tour-*), DESIGN-SYSTEM.md: zero radius, hairline, the one
 * overlay shadow, one filled button.
 *
 * A step whose element is missing or hidden (a chart removed in the CMS, a
 * block folded away on a phone) is skipped, so editing a page cannot strand it.
 */

/** Steps per page path. el: the block to ring. */
const TOURS = {
    "/pages/data": {
        name: "the Data page",
        steps: [
            {
                el: '[data-tour="data-filters"]',
                title: "Filter the species",
                text: "Narrow the list by NIS status, establishment, country, year of first record and more. Reset clears every filter.",
            },
            {
                el: '[data-tour="data-table"]',
                title: "Non-indigenous species",
                text: "One row per species and first Mediterranean record. Search, sort, and open a row for the species page with its records and references.",
            },
            {
                el: '[data-tour="data-guide"]',
                title: "Help on this page",
                text: "This button explains every column and filter. Replay this tour any time from Explore MAMIAS › Guided tour.",
            },
        ],
    },
    "/pages/dashboard/mediterranean": {
        name: "the Mediterranean dashboard",
        steps: [
            {
                el: '[data-tour="headline"]',
                title: "Headline figures",
                text: "Reported NIS in the whole Mediterranean, and how many of them are established.",
            },
            {
                el: 'section:has([data-mamias-chart="trend"])',
                title: "Over time",
                text: "New reported NIS per decade, with the running total. Hover a bar for its figures; signed-in visitors can download any chart as a PNG.",
            },
            {
                el: 'section:has([data-mamias-chart="pathway-cloud"])',
                title: "How they arrive",
                text: "CBD pathways sized by number of species. Click a pathway to list its species.",
            },
            {
                el: 'section:has([data-mamias-chart="taxonomy"])',
                title: "Which groups",
                text: "Reported NIS per phylum, coloured by kingdom.",
            },
            {
                el: 'section:has([data-mamias-chart="spread-map"])',
                title: "Spread by sub-region",
                text: "Press Play, or drag the slider, to watch reported NIS build up in each EcAp sub-region decade by decade.",
            },
        ],
    },
    "/pages/dashboard/by-country": {
        name: "the dashboard by country",
        steps: [
            {
                el: "form:has(#mamias-country)",
                title: "Pick a country",
                text: "Choose a Barcelona Convention country. Every figure on the page follows the country you choose.",
            },
            {
                el: 'section:has([data-mamias-chart="country-ranking"])',
                title: "First records by country",
                text: "Where the chosen country stands among the others for first Mediterranean records.",
            },
            {
                el: 'section:has([data-mamias-chart="trend"])',
                title: "Over time",
                text: "New reported NIS per decade, with the running total. Hover a bar for its figures; signed-in visitors can download any chart as a PNG.",
            },
            {
                el: 'section:has([data-mamias-chart="pathways"])',
                title: "Introduction pathways",
                text: "How the species arrived, in the CBD pathway categories.",
            },
        ],
    },
    "/pages/map": {
        name: "the map",
        steps: [
            {
                el: '[data-tour="map-filters"]',
                title: "Map filters",
                text: "The same filters as the Data page. The map, the summary and the species list below all follow them.",
            },
            {
                el: '[data-tour="map-layer"]',
                title: "Subregions or countries",
                text: "Switch between EcAp subregions and countries of first record, and pin approved occurrences. Click an area to list its species.",
            },
            {
                el: '[data-tour="map-summary"]',
                title: "Summary",
                text: "The figures for the area you picked on the map.",
            },
            {
                el: '[data-tour="map-species"]',
                title: "Species of the area",
                text: "The species recorded in the selected area. Open one for its full page.",
            },
        ],
    },
    "/references": {
        name: "My Bibliographic References",
        steps: [
            {
                el: '[data-tour="references-actions"]',
                title: "Add a reference",
                text: "Add a reference you know of. A scientist reviews it, and accepted references join the MAMIAS bibliography. The other button explains how review works.",
            },
            {
                el: ".fi-ta-ctn",
                title: "Their review status",
                text: "Every reference you added, with its status and any reviewer comment.",
            },
        ],
    },
    "/my-species-reports": {
        name: "My Species Reports",
        steps: [
            {
                el: ".fi-ta-header",
                title: "Report a species",
                text: "Report an occurrence: where and when you saw a species. Approved reports appear as pins on the map.",
            },
            {
                el: ".fi-ta-ctn",
                title: "Follow your reports",
                text: "Pending, approved or rejected, with the moderator’s notes.",
            },
        ],
    },
    "/my-suggestions": {
        name: "My Species Suggestions",
        steps: [
            {
                el: ".fi-ta-header",
                title: "Suggest a species",
                text: "Suggest a species missing from the MAMIAS catalogue, with its references.",
            },
            {
                el: ".fi-ta-ctn",
                title: "Your suggestions",
                text: "Each suggestion and where it stands in the review.",
            },
        ],
    },
};

/** localStorage: pages whose tour offer was answered. sessionStorage: replay Data's tour after the jump there. */
const ANSWERED = "mamias-tour-answered";
const START = "mamias-tour-start";

const storage = (area) => ({
    get(key) {
        try {
            return area.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            value === null ? area.removeItem(key) : area.setItem(key, value);
        } catch {}
    },
});
const local = storage(window.localStorage);
const session = storage(window.sessionStorage);

function answered() {
    try {
        return JSON.parse(local.get(ANSWERED)) ?? [];
    } catch {
        return [];
    }
}

function remember(path) {
    local.set(ANSWERED, JSON.stringify([...new Set([...answered(), path])]));
}

const visible = (el) => el && el.getClientRects().length > 0 && el.getBoundingClientRect().width > 0;

/** Livewire and the dashboards draw late: look for the element for up to 4s. */
async function find(selector) {
    for (let waited = 0; waited < 4000; waited += 100) {
        const el = document.querySelector(selector);
        if (visible(el)) return el;
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    return null;
}

let ui = null;

function close() {
    ui?.cleanup();
    ui = null;
}

/** A tour card. el: ring it and sit beside it; null: no ring, the card docks bottom-right. */
function card({ el, count, title, text, buttons, onKey }) {
    close();

    const spot = el ? Object.assign(document.createElement("div"), { className: "mamias-tour-spot" }) : null;
    const box = Object.assign(document.createElement("div"), { className: "mamias-tour-card" });
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-labelledby", "mamias-tour-title");
    box.tabIndex = -1;
    box.innerHTML = `
        <div class="mamias-tour-head">
            <p class="mamias-tour-count"></p>
            <button type="button" class="mamias-tour-close" aria-label="Close" data-tour-action="close">&times;</button>
        </div>
        <h2 id="mamias-tour-title" class="mamias-tour-title"></h2>
        <p class="mamias-tour-text"></p>
        <div class="mamias-tour-actions"></div>`;
    box.querySelector(".mamias-tour-count").textContent = count;
    box.querySelector(".mamias-tour-title").textContent = title;
    box.querySelector(".mamias-tour-text").textContent = text;

    const actions = box.querySelector(".mamias-tour-actions");
    // variant: "primary" (the one fill), "outline", or "skip" (a plain text button, pushed left).
    for (const [label, variant, run] of buttons) {
        const button = Object.assign(document.createElement("button"), {
            type: "button",
            className: variant === "skip" ? "mamias-tour-skip" : `kt-btn kt-btn-${variant}`,
            textContent: label,
        });
        button.addEventListener("click", run);
        actions.append(button);
    }
    box.querySelector('[data-tour-action="close"]').addEventListener("click", () => onKey({ key: "Escape" }));
    document.body.append(...[spot, box].filter(Boolean));

    const place = () => {
        const gutter = 16;
        const width = Math.min(360, innerWidth - gutter * 2);
        box.style.width = `${width}px`;

        if (!el) {
            Object.assign(box.style, {
                top: `${innerHeight - box.offsetHeight - gutter}px`,
                left: `${innerWidth - width - gutter}px`,
            });
            return;
        }

        const rect = el.getBoundingClientRect();
        const pad = 6;
        Object.assign(spot.style, {
            top: `${rect.top - pad}px`,
            left: `${rect.left - pad}px`,
            width: `${rect.width + pad * 2}px`,
            height: `${rect.height + pad * 2}px`,
        });

        const below = rect.bottom + pad + 12;
        const above = rect.top - pad - 12 - box.offsetHeight;
        const top =
            below + box.offsetHeight <= innerHeight - gutter
                ? below
                : above >= gutter
                  ? above
                  : innerHeight - box.offsetHeight - gutter;
        const left = Math.min(Math.max(gutter, rect.left), innerWidth - width - gutter);
        Object.assign(box.style, { top: `${top}px`, left: `${left}px` });
    };

    addEventListener("resize", place);
    addEventListener("scroll", place, true);
    document.addEventListener("keydown", onKey);

    ui = {
        cleanup() {
            removeEventListener("resize", place);
            removeEventListener("scroll", place, true);
            document.removeEventListener("keydown", onKey);
            spot?.remove();
            box.remove();
        },
    };

    el?.scrollIntoView({ block: "center", behavior: "instant" });
    place();
    box.focus({ preventScroll: true });
}

async function step(steps, index, direction = 1) {
    if (index < 0 || index >= steps.length) return close();

    const el = await find(steps[index].el);
    if (!el) return step(steps, index + direction, direction);

    const last = index === steps.length - 1;
    card({
        el,
        count: `${index + 1} of ${steps.length}`,
        title: steps[index].title,
        text: steps[index].text,
        buttons: [
            ...(last ? [] : [["Skip tour", "skip", close]]),
            ...(index > 0 ? [["Back", "outline", () => step(steps, index - 1, -1)]] : []),
            [last ? "Finish" : "Next", "primary", () => (last ? close() : step(steps, index + 1))],
        ],
        onKey(event) {
            if (event.key === "Escape") close();
            else if (event.key === "ArrowRight") last ? close() : step(steps, index + 1);
            else if (event.key === "ArrowLeft" && index > 0) step(steps, index - 1, -1);
        },
    });
}

/** First visit to a toured page: ask, and remember the answer either way. */
function offer(path, tour) {
    const answer = (start) => {
        remember(path);
        start ? step(tour.steps, 0) : close();
    };

    card({
        el: null,
        count: "Guided tour",
        title: `New to ${tour.name}?`,
        text: `Take a short tour of ${tour.name}: ${tour.steps.length} steps, under a minute. You can replay it any time from Explore MAMIAS › Guided tour.`,
        buttons: [
            ["Not now", "outline", () => answer(false)],
            ["Start the tour", "primary", () => answer(true)],
        ],
        onKey(event) {
            if (event.key === "Escape") answer(false);
        },
    });
}

document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-tour-start]")) return;

    event.preventDefault();
    const tour = TOURS[location.pathname];
    if (tour) {
        remember(location.pathname);
        step(tour.steps, 0);
    } else {
        session.set(START, "1");
        location.href = "/pages/data";
    }
});

const current = TOURS[location.pathname];
if (current && session.get(START)) {
    session.set(START, null);
    remember(location.pathname);
    step(current.steps, 0);
} else if (current && !answered().includes(location.pathname)) {
    offer(location.pathname, current);
}
