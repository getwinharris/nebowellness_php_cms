# NEBO: scroll-craft project adapter

Read first: `CLAUDE.md` (binding contract), `Design.md` (canonical UI),
`.claude/skills/frontend-php/SKILL.md` (PHP frontend rules). This file adapts the
vendored `scroll-craft` skill to this repository. Upstream `SKILL.md` and
`references/` are read-only; their provenance is in `UPSTREAM.md`.

Precedence: `Design.md` beats this file and upstream for anything a visitor
sees; this file beats upstream where Nebo's contract requires an exception;
`CLAUDE.md` beats all three for process.

## 1. Scope and non-negotiables

- **Pilot scope: the home page `/` only** (`views/public/home.php` and its
  `views/layouts/app.php` includes). No other route loads the engine CSS or JS.
- **No build step.** No npm, Node, Playwright or any browser runtime dependency.
  The upstream `scripts/` are not vendored (`UPSTREAM.md`); do not fetch or run them.
- **No new CDN, no new font, no second frontend, no new routes**, controllers,
  services or schema changes for the motion layer.
- **No paid asset generation and no generated imagery:** no AI images, no
  claymation or low-poly worlds, no new photographs. Existing real assets only.
- **No video or audio.** That puts the scrub, sequence and worldflight device
  families out of reach for this repository.
- **The vendored engine files are never edited per project.** Theme with tokens,
  write real markup, put bespoke behaviour in page-local JS.
- **One motion system.** For new sections use the engine's `data-sc-*` devices.
  Do not add a parallel reveal/IntersectionObserver implementation, and never put
  the existing `.reveal` class and `data-sc-in` on the same element.
- **Verification is the built-in Browser** per `CLAUDE.md` Testing (desktop and
  375px, computed styles). Terminal checks cover generators and tests only.

## 2. Vendored files and exact paths

| What | Path | Rule |
|---|---|---|
| Skill (reference) | `.claude/skills/scroll-craft/SKILL.md`, `references/` (12 files), `engine/`, `templates/FINGERPRINTS.md`, `CHANGELOG.md`, `LICENSE`, `UPSTREAM.md` | read-only, byte-identical to upstream |
| Project adapter | `.claude/skills/scroll-craft/NEBO.md` | the only editable file in the skill directory |
| Runtime CSS | `assets/vendor/scrollcraft/scrollcraft.css` | read-only engine |
| Runtime JS | `assets/vendor/scrollcraft/scrollcraft.js` | read-only engine |
| License | `assets/vendor/scrollcraft/LICENSE` | read-only MIT notice |
| Project overrides | `assets/css/scrollcraft-nebo.css` | the only project-edited stylesheet for this layer |

Engine version identity: skill release **0.3.1**; exact hashes are in
`UPSTREAM.md`. Do not copy the engine anywhere else and do not recreate
`assets/js/` (that directory is asserted absent by `tests/run.php`).

Version both runtime files the way `views/layouts/app.php` versions
`band.css`. `.htaccess` and `index.php` serve CSS/JS with a one-year immutable
cache, so the `?v=filemtime(...)` query is required:

```php
<link rel="stylesheet" href="/assets/vendor/scrollcraft/scrollcraft.css?v=<?= filemtime(__DIR__ . '/../../assets/vendor/scrollcraft/scrollcraft.css') ?>">
<script src="/assets/vendor/scrollcraft/scrollcraft.js?v=<?= filemtime(__DIR__ . '/../../assets/vendor/scrollcraft/scrollcraft.js') ?>" defer></script>
```

## 3. Precedence: who wins

| Topic | Winner | Binding rule |
|---|---|---|
| Brand tokens: palette, canvas, surfaces, ink, fonts, type ramp, tracking, section rhythm, radius/elevation, focus ring | **Design.md** | Map `--sc-*` to Nebo variables. Never ship the engine's dark defaults. Never apply the engine type ramp or tracking to Nebo copy. |
| Card families, existing component geometry, header/nav/footer/forms/cart, breakpoints 375/744/1128 | **Design.md** | Do not restructure existing markup for a motion pass. Do not restyle engine devices to taste either. |
| New-section motion vocabulary and device mechanics | **scroll-craft** | Use the engine attributes in §7. Do not hand-roll scroll math or IntersectionObservers for new sections. |
| Copy rules (em dashes, eyebrows, counters, scroll cues, statistics, one label per intent) | **scroll-craft** | Cheap edits; the pilot fixes the existing page's defects (see §10). |
| Imagery and world | **Design.md + repo contract** | Real existing assets only; no generated world, no fake depth from gradients. |
| Hero and existing centred section copy | **Design.md** | Hero stays the documented centred title screen; no "layered hero" claim. |
| Verification tooling | **Nebo** | Built-in Browser, no Playwright/node harness. |

## 4. Token mapping plan (`assets/css/scrollcraft-nebo.css`)

The override file loads last so it can alias tokens and neutralise the engine's
base reset and pre-paint states. Map to live variables, never to hex, so an
admin palette change still cascades (`views/layouts/app.php` writes
`--color-primary`, `--color-bg`/`--color-canvas`, `--color-ink`/`--color-text-primary`
from settings after `band.css`).

```css
:root {
  /* Colour roles: aliases of the live, admin-editable palette. Never hex. */
  --sc-canvas: var(--color-bg);
  --sc-surface: var(--color-white);
  --sc-ink: var(--color-ink);
  --sc-ink-soft: var(--color-text-muted);
  --sc-accent: var(--color-primary);       /* blue only, one accent */
  --sc-accent-ink: var(--color-white);
  --sc-hairline: var(--color-border-light);
  --sc-hairline-strong: var(--color-border);

  /* Two families max: Nebo's one family carries both roles. No new font. */
  --sc-font-display: var(--font-main);
  --sc-font-text: var(--font-main);
}

/* The engine's base reset would otherwise change Design.md typography. */
body { font-size: 1rem; letter-spacing: normal; }
```

Rules:

- **One accent.** `--sc-accent` is blue (`--color-primary`). Green
  (`--color-secondary`/`--color-gold`) stays the semantic success/support colour
  and must never be wired to `--sc-accent` or a scroll device.
- **Do not use the engine type/space/layout classes** (`.sc-display`, `.sc-lede`,
  `.sc-body`, `.sc-label`, `.sc-wrap`, `.sc-section`, `.sc-stack`) or
  `--sc-t-*`/`--sc-track-*` on Nebo copy in phase 1. Design.md owns type and
  section rhythm; phase 1 uses engine devices only, not the engine floor.
- Leave `--sc-font-mono` at its engine default; no monospace content is provisioned.
- **Light-canvas inversions** (upstream `references/worlds.md`: scrim washes to
  the canvas, type over media stays ink, drop/invert `--sc-edge`, halve elevation
  alphas) are not applied in phase 1 because there is no text-over-media and no
  raised scroll surface. Apply them only if a later phase introduces either, with
  the owner's approval.
- **One focus ring.** The engine ships its own global `:focus-visible` outline.
  Re-declare it in the override so Design.md's single-value ring wins:
  ```css
  :focus-visible { outline: 0; box-shadow: var(--shadow-focus); }
  ```
- **CSS order matters:** engine CSS loads *before* `band.css` (so band.css keeps
  control of base elements), then the inline palette block, then this override
  file last. Re-assert in the override the two values `band.css` would
  otherwise decide last:
  ```css
  body { overflow-x: clip; }
  @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
  ```

## 5. Progressive enhancement via `html.sc-js` (mandatory)

Essential copy must be legible with JavaScript off. The engine's pre-paint
states hide cued and revealed elements, so the override must gate them on a
class the head sets synchronously. Only on the pilot route, in `<head>`:

```html
<script>document.documentElement.classList.add('sc-js');</script>
```

In `assets/css/scrollcraft-nebo.css`:

```css
/* No JS: neutralise the engine pre-states. */
[data-sc-cue],
[data-sc-in],
[data-sc-stagger] > * { opacity: 1; }
[data-sc-in],
[data-sc-stagger] > * { transform: none; }

/* JS present: engine pre-states, plus an explicit revealed state that beats
   the engine's own [data-sc-in].sc-in rule on specificity. Do not rely on
   `html.sc-js [data-sc-in] { opacity: 0 }` alone: that out-specifies
   `.sc-in` and traps the content hidden after the reveal. */
html.sc-js [data-sc-cue] { opacity: 0; }
html.sc-js [data-sc-in],
html.sc-js [data-sc-stagger] > * {
  opacity: 0;
  transform: translate3d(0, 14px, 0);
  transition: opacity 620ms var(--sc-ease-out), transform 620ms var(--sc-ease-out);
}
html.sc-js [data-sc-in].sc-in,
html.sc-js [data-sc-stagger] > .sc-in { opacity: 1; transform: none; }
```

If the engine script fails to load, remove the class so nothing is trapped
hidden: put `onerror="document.documentElement.classList.remove('sc-js')"` on the
engine `<script>` tag. No-JS legibility is a merge gate (§13).

## 6. Load order and mount (pilot route only)

Compute the pilot route in the layout head, for example
`$scPilot = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') === '/';`
(`$currentPath` is only defined later, in `<body>`), and include only when true.

1. `assets/vendor/scrollcraft/scrollcraft.css` (before `band.css`)
2. `assets/css/band.css` (existing include)
3. inline admin palette block in `views/layouts/app.php` (existing)
4. `assets/css/scrollcraft-nebo.css` (last)
5. inline `<script>document.documentElement.classList.add('sc-js');</script>` in `<head>`
6. `assets/vendor/scrollcraft/scrollcraft.js` with `defer`, then mount once:

```html
<script>
window.addEventListener('DOMContentLoaded', function () {
  if (window.ScrollCraft) ScrollCraft.mount(document.body);
});
</script>
```

- Mount root is `document.body`; every act and device must be inside it.
- Mount exactly once. Do not double-mount; a second mount starts a second
  playhead loop. `ScrollCraft.destroy()` exists for client-side routers, which
  this PHP app does not have.
- Do not load the engine on any other route. Attributes are inert without the
  script, but other pages must not pay for it.

## 7. Engine API allowed in phase 1

The page uses **flow acts plus the no-video reveal family**. Pinned acts and
media devices are banned (§8).

| Attribute | Allowed on | Behaviour / rule |
|---|---|---|
| `data-sc-act="flow"` | a section | Marks the act and publishes `--sc-p`. Flow progress is already > 0 above the fold (engine formula), so never author a fade-from-nothing cue on a hero flow act. Hero copy stays static. |
| `data-sc-in` | a section's content container | One-time IntersectionObserver reveal; fires once, never re-hides. |
| `data-sc-stagger="60"` | parent of the reveal children | Per-child delay in ms; keep 30-80ms and total under 500ms. Reading order is reveal order. |
| `data-sc-kinetic="lines"` | exactly one heading on the page | Masked line stagger. Requires the `.sc-js` gate; the engine re-measures on `document.fonts.ready`. Fall back to no kinetic if it cannot read clean line boxes at 375px. |
| `data-sc-reveal="up"` + `data-sc-reveal-at="0.15 0.55"` | at most one full-width band | Clip-path wipe; use it big, never on body type. It reveals once and never re-hides. |
| `data-sc-lerp` | optional | Page-wide smoothing; the engine default 0.18 is fine. Do not tune per element unless a clip needs it (there are no clips). |

Device budget for the home page: **three families only** (flow + in/stagger,
kinetic, reveal), with the kinetic headline and the wipe each used once.
Everything else stays static. A fourth family needs an owner-approved
`Design.md` change; do not add one "for variety". This is documented deviation
D4 in §15.

Available but unused in phase 1 (needs a specific reason and Browser evidence):
`data-sc-cue`/`data-sc-rise` text windows. They are safe only inside flow acts
and after the `.sc-js` gate; do not put them on essential copy that is already
legible at rest.

## 8. Banned or deferred devices

| Pattern | Why it is banned here | Unlock condition |
|---|---|---|
| `data-sc-act` pin/scrub/pan, `data-sc-span`, `data-sc-stage`, `data-sc-dwell`, `data-sc-clip-map` | Chaptered-editorial grammar and Design.md motion rules; a pin needs a sticky stage and is the silent failure mode | Owner-approved `Design.md` amendment, a sticky-stage contract, Browser evidence at all gates |
| `data-sc-scrub`, `data-sc-src`, `data-sc-src-mobile`, `data-sc-sequence`, `data-sc-poster`, `<video>`/`<audio>` | No video assets, no paid generation, no autoplay | Real footage supplied and `Design.md` amended for it |
| `data-sc-mode="worldflight"`, `data-sc-world*`, `data-sc-segment`, `data-sc-w`, `data-sc-copy`, `data-sc-window`, `data-sc-spacer`, `data-sc-waypoint`, `--sc-seg`, `sc:waypoint` | Needs footage and the continuous-world grammar, which bans every act device | Not for this repository |
| `data-sc-drift` | Ground changes are hard cuts between canvas, surface and warm bands, not animated colour; drift is not in the approved pilot device set | Not for this repository |
| `data-sc-tilt`, `data-sc-magnet`, `data-sc-spotlight` | Design.md bans glow/coloured shadows; comprehension must not need a pointer | Owner decision plus `Design.md` evidence |
| `data-sc-parallax` | Phase 1 is one-time reveals that settle into stillness; parallax is continuous motion and not in the approved pilot device set | Owner decision plus verification |
| `data-sc-count`, `data-sc-count-at`, `data-sc-count-ms` | No invented statistics; a counter is a truth claim | Owner supplies the verified number and its source |
| `data-sc-progress` | New fixed chrome that Design.md does not define | `Design.md` amendment |
| Custom scroll cue ("scroll to explore", arrow, mouse icon), section counters ("01 / 06") | Upstream hard rules; Nebo has none | Never |
| Nesting `[data-sc-act]` inside another act | The engine collects all acts globally and each act's devices with `querySelectorAll`, so nested cues are driven twice | Never nest acts |

Also: do not let the engine and page-local JS animate the same element, and do
not put engine attributes on an element that page JS already animates.

## 9. Sticky stages, `overflow-x`, and the existing editorial stack

- `body`'s computed `overflow-x` must be **`clip`** on any page carrying sticky
  stages or pinned content. `overflow: hidden` on an ancestor makes that ancestor
  the scroll container for `position: sticky` descendants and can silently stop a
  pin (the engine logs a warning when a stage does not compute sticky). The engine
  CSS sets clip; `band.css` has both declarations; re-assert clip in the override
  and verify the **computed** value in the Browser, not by reading CSS order.
- The home page already runs a **native sticky editorial stack**:
  `views/public/home.php` marks cards with `data-stack-card` inside
  `[data-editorial-stack]`, `views/layouts/app.php` adds `.stack-ready` and the
  footer card, and `band.css` gives the cards `position: sticky`. Consequences:
  - Keep `body` at clip; the stack depends on sticky as well.
  - Do not add engine pin/stage machinery to a stack card or the stack container.
  - Flow acts are safe on a stack card, but the engine measures **document
    flow**, not the card's visually pinned position. Verify cue/reveal timing at
    real scroll positions; if a device reads at the wrong moment because the card
    is pinned, change the device or markup, not the engine.
- Do not set `position` on any `[data-sc-stage]` (phase 1 has no stages; see §8).

## 10. Copy rules (scroll-craft wins; fix the pilot page while editing it)

- **No em dash anywhere visible**, including new copy. Use a period, comma,
  colon or parentheses.
- **At most one eyebrow per three sections**, and never above every heading. The
  page ships at most three eyebrow labels in total; the heading carries itself.
- **No section counters** ("01 / 06") and **no scroll cue** ("scroll to explore",
  arrow, animated mouse icon). Nebo has neither; do not add them.
- **No invented statistics or numbers.** Do not add a statistics section and do
  not use counters until the owner supplies a verified figure and its source.
  Design.md's example statistics are illustrations, not claims.
- **One label per intent.** Design.md defines the intent (the primary action
  opens the contact form) but not the wording; the page currently uses
  "Book Your Consultation" for it. Standardise on one label for the intent across
  the page; do not add a second label or a header CTA.
- **No claymation, low-poly, AI imagery or new decorative gradients** to fake
  depth; no text baked into images; no clinical outcome claims.
- **One signature move.** Bespoke interaction is page-local JS keyed with
  `data-nebo-*` names (never `data-sc-*`), an enhancement over working static
  markup and real links. The engine stays untouched.

## 11. Motion mechanics (new CSS and JS)

- New CSS animates only `transform`, `opacity` and `clip-path`. Never
  `transition: all`; never animate `width`/`height`/`top`/`left`.
- New scroll motion uses the engine's easing and durations (`--sc-ease-out`,
  160/240/420ms). The existing hover spring `--transition-spring` stays for
  existing hover/focus affordances only.
- Stagger 30-80ms, total entrance under 500ms, the important item moves first,
  then stillness (`Design.md` "Scroll motion"). No overshoot on scroll cues.
- Do not `transition: transform` on a parallaxed element (no parallax in phase 1).
- No layout shift: pre-states are opacity/transform only.

## 12. Reduced motion

- The engine handles its own devices: no clip fetch, no translation, opacity
  only, `scroll-behavior: auto`, pointer devices off. It reads the media query
  **once at script evaluation**, so a live OS-setting change needs a page reload.
  That is an engine limitation, not a page bug.
- The project must also: remove any hover/reveal motion it adds, keep every
  chapter complete and every link reachable, and add a reduced-motion rule for
  any infinite animation visible on the touched page (the engine only covers its
  own devices).
- No content may exist only behind a transform. Verify with emulated
  `prefers-reduced-motion: reduce` that nothing is left invisible.

## 13. Verification gates (Browser, not terminal)

Per `CLAUDE.md` Testing and `Design.md` Verification. No upstream harness.

1. **No-JS:** the whole home page is readable, every link works, and computed
   `opacity` is 1 for cued/revealed content.
2. **JS on, console clean:** no engine warnings, no 404s, one mount.
3. **Scroll sweep at 1440x1000:** every chapter at entry, middle and exit; the
   reveals fire once and never re-hide; the kinetic heading is fully legible when
   centred; the wipe completes before its band is centred; the close holds and
   nothing fades out. Check computed `opacity`, `clip-path` and `transform`, not
   just screenshots.
4. **Reduced motion:** every chapter complete, no translation motion, every
   link reachable, `scroll-behavior` computed as `auto`.
5. **Responsive:** 375px, 744px, 860px (engine breakpoint) and desktop. No
   horizontal overflow, no overlap with header/bottom nav/support stack.
6. **Keyboard:** tab order follows reading order, one Nebo focus ring visible on
   every ground, and no focusable element inside a hidden pre-state.
7. **Copy audit on rendered text:** no em dash, at most three eyebrows, one
   booking label, no counters, no invented statistics.
8. **Network:** one engine CSS, one engine JS, one mount, engine deferred, no
   new blocking requests.
9. Record what was actually inspected; a screenshot alone does not prove a
   computed style.

## 14. Fingerprint registry and build artifacts (optional, not phase 1)

Upstream's BRIEF/score/fingerprint process is design process, not a code
dependency. If a later phase runs it:

- **Registry:** `docs/scrollcraft/FINGERPRINTS.md` (committed documentation).
  Never create a `scrollcraft/` build folder at the app root, which is deployable.
  The read-only seed is `templates/FINGERPRINTS.md`.
- **Workspace:** keep builds outside the deployable tree or use
  `SCROLLCRAFT_HOME`; do not commit a build workspace.
- Append exactly one row after a build ships; never edit the template or an
  existing row.

## 15. Documented deviations (repeat them in any build report)

1. **Centred hero copy:** Design.md wins over the chaptered-editorial ban.
2. **Close is the existing CTA card**, not a running-text colophon: Design.md wins.
3. **Existing card families keep their documented geometry:** Design.md wins
   over the taste floor's card-grid ban.
4. **Three device families, not four:** no video, photography or layout freedom
   is available; the variety law is waived. Call the result a motion layer, not
   a full scroll-craft build.
5. **Header unchanged:** Design.md defines it; do not add scroll chrome or a
   header CTA.

## 16. Updating the engine or this adapter

See `UPSTREAM.md` **Upgrade procedure**. Never edit a vendored file, re-vendor
byte-identical copies, update the hashes there, then adapt this file only.
