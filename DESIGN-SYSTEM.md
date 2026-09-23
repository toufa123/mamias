# MAMIAS design system

One system for two surfaces: the public site (Metronic/Keenthemes shell) and the
Filament 5 panel at `/mamias`. Both read the same tokens, so a colour changes in
one place and lands in both.

Canvas (every board, full size): <https://claude.ai/artifact/M4vp9vwt8BrXobi58f5r9j>
Before/after preview over the real compiled CSS: <https://claude.ai/artifact/F9Y6NGqR8fokcDnEnJ41ti>

---

## The four rules

Everything below is a consequence of these. If a decision is not covered, work
it out from here rather than inventing a new pattern.

1. **Radius is zero.** Buttons, inputs, badges, avatars, checkboxes, cards,
   modals. `rounded-full` survives only where a thing is genuinely round —
   spinners, status dots.
2. **Structure is a 1px hairline, not a shadow.** One shadow exists, for
   overlays (dropdowns, modals, toasts). Cards never lift off the page.
3. **Two teals, two jobs.** `teal-500` is the logo colour and never carries
   text. `teal-600` is the fill under white text, and teal text on white.
4. **One filled button per view.** Everything else is outlined or hairline,
   destructive included. A second fill cancels the first.

---

## Colour

Brand values are sampled from `apps/public/images/Logoweb.png` and
`apps/public/images/logo.png`, not chosen by eye.

### Brand

| Token | Value | Use | Contrast |
|---|---|---|---|
| `teal-500` | `#078DA0` | Logo colour. Borders, focus rings, icons, active-tab rule, progress fill. | 3.1:1 vs white — graphic only, never text |
| `teal-600` | `#056273` | Filled buttons, links, teal text on white. | **7.08:1** under white |
| `teal-700` | `#044E5C` | Hover on a filled button. | 9.3:1 |
| `blue-500` | `#00558C` | Utility strip, CTA bands, the "Casual" status. | 7.8:1 under white |
| `navy` | `#2A2B74` | Barcelona Convention contexts only. | — |
| `cyan` | `#00A0C6` | UNEP / SPA-RAC contexts only. | — |

White on `teal-500` measures **4.15:1** and fails AA at 14px. That is the whole
reason rule 3 exists. Before putting white on a brand colour, check it.

### Neutrals

A zinc ramp pulled toward the logo's blue, so greys sit beside the teal without
going muddy.

| Token | Value | Use |
|---|---|---|
| `gray-50` | `#F7FAFB` | Sunken surfaces, table headers |
| `gray-100` | `#EDF3F5` | Soft rule — between table rows |
| `gray-200` | `#D8E3E8` | **The hairline.** Panels, inputs, table frames |
| `gray-500` | `#5F7783` | Muted text — 4.9:1 on white, the floor for captions |
| `gray-600` | `#47606B` | Secondary text |
| `gray-900` | `#0E2630` | Ink. Body text, headings, the footer ground |

### Status vocabulary

Fixed. A colour never means two things across the app.

| Status | Text | Fill | Border | Meaning |
|---|---|---|---|---|
| Invasive | `#B42318` | `#FEECEA` | `#F5C9C4` | Documented ecological or economic impact |
| Established | `#B45309` | `#FFEFD4` | `#F3DDB6` | Self-sustaining population in the basin |
| Casual | `#00558C` | `#E3EDF5` | `#BFD4E4` | Recorded, not yet reproducing |
| Verified | `#1F6B49` | `#EDF8F2` | `#C8E4D6` | Matched against WoRMS and reviewed |
| Unresolved | `#47606B` | `#EDF3F5` | `#D8E3E8` | Awaiting taxonomic or spatial review |
| Native | `#5B4B8A` | `#F0EDF7` | `#D9D2EA` | Not introduced — native range expanding on its own |

**Native** is the one addition outside the species ladder: `NisStatus` measures
whether a species was introduced at all, not how established it is, and Range
Expansion is its only value that means *not* introduced. Violet is used for
nothing else.

**Red means bad, once.** Filament's `danger` (rejected, destructive, low quality)
is the Invasive ramp, not a second rose red. There is one red in the system.

### Where each status lives

The five vocabulary colours plus `native` are registered as Filament colours in
`AppServiceProvider` (`FilamentColor::register`), which covers the panel and any
Filament table on a public page. Each ramp pins its shades to roles:

| Shade | Role |
|---|---|
| 50 | Fill |
| 200 | Border |
| 600 | Text — Filament's badge text lands here |
| 500 | Deliberately under 4.5:1 on the fill, so badge text never picks it |
| 300 | Dark-mode text |
| 950 | Dark-mode fill |

Unresolved has no ramp of its own: it *is* the gray ramp, so enums use `'gray'`.

The public site reads the same values as CSS variables in `app.css` —
`--status-{invasive,established,casual,verified,unresolved,native}-{text,fill,border}`
— defined in both theme blocks, so `text-[var(--status-verified-text)]` needs no
`dark:` twin.

Enum mapping — every badge colour in the app comes from this table:

| Enum | Case → colour |
|---|---|
| `EstablishmentStatus` | Invasive → invasive · Established → established · Casual, Vagrant → casual · Unknown, Data Deficient, Questionable, Excluded → gray |
| `NisStatus` | NIS → primary · Range Expansion → native · Cryptogenic, Questionable → gray (told apart by icon) |
| `Catalogue_Status` | accepted, manual entry → verified · not checked, no WoRMS data → gray · not accepted → danger |
| `Worms_Status` | accepted → verified · unaccepted → danger · doubtful names (nomen dubium, taxon inquirendum, …) → gray |
| `LiteratureStatus`, `OccurrenceStatus` | Pending → gray · Approved → verified · Rejected → danger |
| `DataQuality` | High → primary · Medium, N/A → gray · Low → danger |
| `CoverageMethod` | Measured → primary · Estimated → gray |
| `AcforScale`, `AbundanceCategory` | every step → primary; icon `antenna-bars-1…5` carries the step |
| `Habitat` | every case → gray; icon names it (plant-2, mountain, beach, help) |

**Scales and categories are not statuses — they don't get colour.** A scale
(ACFOR, abundance) is one hue with the step drawn as signal bars, so the same
step reads the same in both scales and nothing relies on colour alone
(WCAG 1.4.1). A category (habitat) is neutral with an identifying icon. Giving
either a hue borrows a meaning from the vocabulary above that it does not have.

Submission states are not species states, but they share the vocabulary *by
meaning*: pending is literally "awaiting review", approved is literally
"reviewed and accepted". They borrow those colours rather than amber/green,
which would collide with Established/Verified.

---

## Dark — public site

Same four rules, read from the dark end. Radius stays zero, structure stays a
hairline, type and spacing do not move. What changes is which teal does which
job, and that is the whole point of the section.

**Rule 3 inverts.** Light puts white text on a `teal-600` fill. Dark cannot:
white on `teal-400` measures **2.99:1**. So the dark fill is the *bright* teal
with **ink text on it** — a mirror of the light rule, not a dimmed copy. Teal
that carries text on a dark surface is `teal-300`.

| Token | Light | Dark | Measured |
|---|---|---|---|
| `--background` | `#FFFFFF` | `#08191F` | — |
| `--card` | `#FFFFFF` | `#0E2630` | the light ramp's ink, promoted to a surface |
| `--muted` | `#F7FAFB` | `#12303C` | — |
| `--secondary` | `#EDF3F5` | `#16394A` | — |
| `--accent` | `#F2F8F9` | `#12303C` | — |
| `--foreground` | `#0E2630` | `#E6F1F4` | **15.6:1** on the page |
| `--muted-foreground` | `#5F7783` | `#94AEB8` | **7.7** on page, **5.95** on `--muted` |
| `--border` | `#D8E3E8` | `#204653` | 1.76 — decoration, same relationship as the light hairline to white |
| `--input` | `#D8E3E8` | `#3C7E8D` | **3.90** vs page, **3.41** vs card |
| `--primary` | `teal-600` | `teal-400` `#29A3B7` | **5.64** under ink |
| `--primary-foreground` | white | `var(--background)` | `--mamias-ink` is the near miss — 4.45:1 |
| teal text / links | `teal-600` | `teal-300` `#6FC3D0` | **8.89** on the page |
| `--ring` | `teal-500` | `teal-400` | 3:1 graphic |

`--border` and `--input` split in dark where light shares one value. A decorative
hairline may be quiet; the edge of a control the user has to find may not
(WCAG 1.4.11, 3:1).

### Status vocabulary, dark

Same five meanings, lifted. All text-on-fill clears 6.6:1.

| Status | Text | Fill |
|---|---|---|
| Invasive | `#FF9E93` | `#3A1512` |
| Established | `#FFC26B` | `#3A2610` |
| Casual | `#7FC1EF` | `#0E2C44` |
| Verified | `#79D3A6` | `#0F2E22` |
| Unresolved | `#9FB6C0` | `#1B2E36` |
| Native | `#B9A9E8` | `#231B3A` |

These are tokenised: shade 300 / 950 of each Filament ramp, and the
`html:root.dark` values of `--status-*` in `app.css`. Dark borders are not
separately documented; `app.css` mixes them from text and fill.

### The specificity trap

The theme blocks are `html:root:not(.dark)` and `html:root.dark`, not a plain
block plus a `.dark` override. Keenthemes ships its own `.dark { --background:
… }`. `.dark` scores (0,1,0); a plain `html:root` scores (0,1,1) and therefore
beat it **in dark mode too** — the site shipped a Dark Mode switch in the user
menu that moved nothing but the handful of `dark:` utilities hand-written in
the markup, leaving light text on white surfaces. Both selectors now score
(0,2,1). Do not drop the `:not(.dark)`.

The theme class is written onto `<html>` by the boot script in
`app.blade.php`. `<body>` carries no theme class; one pinned there only
contradicts it.

### Not yet converted

82 literal hex values, 53 of them in arbitrary `bg-[#…]` / `text-[#…]` utilities,
remain in public Blade — nearly all in `mamias/home` and `mamias/about`. They read as light-mode islands in dark and are a separate
pass.

---

## Type

| Role | Size / line | Weight |
|---|---|---|
| Display | 56 / 60, tracking −1.4 | 400 |
| Page title | 32 / 40, tracking −0.6 | 400 |
| Section heading | 20 / 28 | 500 |
| Body | 16 / 26 | 400 |
| Interface — cells, labels, buttons | 14 / 22 | 400–500 |
| Eyebrow | 11 / 16, tracking .16em, uppercase | 500 |
| Mono | 13 / 20 | 400 |

Mono (`Geist Mono`, `ui-monospace`) carries anything a scientist copies or
compares: AphiaIDs, coordinates, counts, dates, file sizes. Not decoration.

**Species names are always italic**, at whatever size their context uses.

### The families, and why not Cloudflare's

`try.cloudflare.com` runs on **STK Bureau** (weights 300 and 400 only) and
**Paper Mono** (variable 100–800), both self-hosted woff2. Both are commercial
licences we do not hold, so they are not used here. **Geist** and **Geist Mono**
stand in — the same low-contrast, wide-aperture grotesque plus a variable mono.
If STK Bureau is ever licensed, only the `@font-face` source changes; the
tokens and every rule built on them stay as they are.

Note what Cloudflare does *not* ship: no 500, 600 or 700. The whole page runs on
two weights, and hierarchy comes from size and negative tracking rather than
bolding. That restraint is the part worth copying — which is why the display
sizes above are weight 400.

Both surfaces load from **Bunny** (the GDPR-friendly Google Fonts mirror):

- Public site — one `<link>` in `app.blade.php`, weights `300,400,500,600` plus
  mono `400,500`.
- Panel — `->font('Geist', provider: BunnyFontProvider::class)` and
  `->monoFont('Geist Mono', provider: BunnyFontProvider::class)` in
  `MamiasPanelProvider`.

**Pass the provider explicitly on both calls.** `Panel::font()` only overwrites
the provider when that argument is non-null, so an earlier
`->font(..., provider: GoogleFontProvider::class)` stays sticky: changing just
the family leaves the sans coming from Google while the mono comes from Bunny —
two CDNs for one typeface, and a silent hole in the GDPR posture.

Filament still emits its own Inter stylesheet through `@filamentStyles`. It is a
registered core asset, unused once `--font-family` is Geist; deregistering it is
more fragile than the request is expensive.

---

## Spacing

`4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 · 96`, on an 8px grid. Touch targets are
44px minimum; the 32px small button is legal only inside a table row, where the
row itself is the target.

---

## Components

Buttons — medium is the default.

| | Height | Padding | Font |
|---|---|---|---|
| Small (table rows only) | 32 | `0 12` | 13 |
| Medium | 40 | `0 16` | 14 |
| Large | 48 | `0 22` | 15 |

States: hover `teal-700`, pressed `teal-800`, focus is a 2px `teal-500` ring
with a 2px gap, disabled is `gray-100` on a `gray-200` hairline with `gray-500`
text (4.9:1 — a disabled control still has to be readable).

Inputs take the 1px hairline and switch the border to `teal-500` on focus. No
glow, no ring.

Tables: `gray-200` frame, `gray-50` header, `gray-100` between rows, accent edge
on the selected row. Overlays (dropdown, modal, toast) get the one shadow:
`0 12px 28px rgba(14, 38, 48, 0.12)`.

---

## Where it lives

| File | Owns |
|---|---|
| `apps/resources/css/app.css` | Public site. Brand ramp, Keenthemes token overrides (`--primary`, `--background`, `--border`, `--radius`…), carousel and CMS-content rules. |
| `apps/resources/css/filament/mamias/theme.css` | Panel + `fi-*` components. Radius scale, grey ramp, hairlines, button and table rules. |
| `apps/app/Providers/AppServiceProvider.php` | The status ramps (`invasive`, `established`, `casual`, `verified`, `native`) and `danger`, via `FilamentColor::register` — global, so they reach public Filament tables too. Also a copy of the `primary` ramp for components outside a panel. |
| `apps/app/Providers/Filament/MamiasPanelProvider.php` | The panel's `primary`, `gray` and `info` ramps. Filament injects these as `--primary-*` / `--gray-*` at runtime, so **the ramps here win over any `@theme` block.** `--color-gray-200` is only an alias for `--gray-200`; redefining the alias repaints the utilities and leaves every `var(--gray-*)` rule on the old value. |

Changing a brand colour means editing the ramp in the panel provider *and*
`--mamias-teal-*` in `app.css`. They are two copies on purpose — Filament only
injects its variables inside a panel, so public pages need their own — but they
must move together.

The public site's token overrides use `html:root` rather than `:root`.
Keenthemes' `styles.css` loads *after* `app.css` (deliberately — see the comment
in `app.blade.php`), so a plain `:root` block would lose the cascade on equal
specificity. Do not "fix" that by reordering the stylesheets.

After any change here: `npm run build`, or `make dev-cache` if the panel looks
stale.

---

## Open items

- **Panel dark mode.** The public site now has a dark ramp (above). The panel
  does not: `MamiasPanelProvider` sets `->darkMode(false)`, so `/mamias` —
  login and registration included — is light-only, and the `.dark` block in
  `theme.css` is unreachable. Enabling it means giving that block the same
  bright-fill/ink-text treatment the public ramp got.
- **`success` / `warning`.** Still `Color::Emerald` / `Color::Amber`, for actions
  and notifications only. No enum uses them.
- **Public-site layout.** Three shells were drawn (demo9 restyled / condensed
  command bar / facet rail). Nothing here depends on that choice — the tokens
  apply to all three. The current `app.blade.php` shell is the demo9 one.
- **Mobile.** `app.blade.php` hides all content below 768px behind an
  "Optimized for Larger Screens" notice. The designs work at phone width; that
  notice is the next thing to remove.
