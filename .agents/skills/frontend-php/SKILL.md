---
name: frontend-php
description: Use when editing public, account, shop, consultant, temple, cart, checkout, contact, support, or other customer-facing PHP templates.
---
# PHP Templates, JavaScript, and CSS

- Follow the UI workflow in `AGENTS.md`. Capture the issue, owning files, baseline, and acceptance checks before editing.
- Read and follow root `Design.md` before changing any customer-facing UI. Treat it as the canonical token, typography, component, and responsive contract.
- Capture the current desktop and mobile page before editing. Validate the complete customer path from first viewport through primary action and authenticated result, not only isolated markup.
- Before using a browser capture as blog/help media, wait until every represented image reports `complete` with a non-zero `naturalWidth`, reveal content is visible, and no loading skeleton or empty data state remains. Inspect the saved bitmap before attaching it with `bapXphp blog:image`.
- Use `bapXphp dev:user` for repeatable authenticated browser checks. Keep `BAPX_TEST_USER_PASSWORD` only in ignored operator configuration.
- Render landmarks, links, initial values, and native forms in PHP. Use JavaScript for fragment navigation, client-side filtering, asynchronous cart updates, and interactive controls when they need browser state. Reuse existing scripts or add a focused deferred script under `assets/`. Keep essential content and ordinary navigation and form submissions usable without enhancement.
- Templates consume controller-provided data and existing services; they never read databases, local YAML catalogues, or generated indexes directly.
- Use `assets/css/band.css` for shared and component styles. When moving affected rules from inline CSS, preserve the intended cascade and remove the superseded declarations. Use semantic classes and data attributes as JavaScript hooks.
- Preserve the product's real routes and content. Apply the design system surgically instead of copying reference-product labels or scaffolding parallel components.
- Essential content must be legible before JavaScript enhancement. Never make reveal-animation opacity a loading dependency.
- For a scroll effect that changes text colour across a boundary, paint both states on a single semantic heading. Check the glyph positions and contrast at the moving boundary in both directions, at the reported width and on phones.
- Audit card families together. Product, consultant, account, authentication, and admin cards have different jobs but share the geometry and focus rules in `Design.md`.
- For layout feedback on a marked element, locate the owning markup and inspect computed padding, alignment, and size at the marked width and at the relevant breakpoints. Remove declarations that the cascade overrides. Check adjacent cards for equal heights, readable wraps, and stable action placement.
- For rails with mixed-size portraits, compare the rendered image edges before and after selection. Inspect both the rail's alignment and the individual card's alignment when the edges differ.
- For homepage sequence or spacing changes, compare the full chapter order against `Design.md`. Measure the Programs heading against the first card, check the rail controls and the consultation link, and verify that direct and filtered URL fragments land below the sticky header.
- For count-up figures, render the final values and descriptive labels in PHP using the underlying list or published data. Keep animated digits hidden from assistive technology and supply identical static values for screen readers. Verify that the figures start at zero when scrolled into view, play once, and show the final values with reduced motion or without JavaScript.
- Use Nebo's own media and copy for the hero, program cards, and care-area imagery. Mark illustrative scenes clearly when a visitor could mistake them for the clinic. Never present generated people as named staff or claim unverified outcomes. Check clinic-approved copy and official campaign channels as well as the site catalogue before describing services. Use published clinic footage or artwork for actual events when supplied. Label past events and direct future enquiries to an existing route.
- Keep public help guides in `content/blog/posts/*.md` with `category: help`. Render them through existing blog cards and article pages; `/docs` and `/help/{slug}` are compatibility redirects only. Public blog categories must match clinic topics in `content/blog/categories.yaml`.
- Public consultation actions create scheduled appointment requests only. Do not expose live calls, messaging, signaling, or wallet pricing. `ConsultationService` and the consultation message/signal collections are retained legacy code, not permission to surface those features; restore them only under an explicit product change with matching routes, authorization, UI, schema, and tests.
- Validate with `php -l` for changed templates, `php tests/run.php`, and the active coding client's browser workflow for changed pages. Do not add a browser runtime or browser dependency to the application repository.

## Reference comparison and visual review

1. Identify the primary reference, its source, viewport, and visible state. Use a secondary reference when it clarifies a particular design principle such as action hierarchy or a phone crop. Record how each principle applies to Nebo's content and assets.
2. Before editing, record the priority content and action at each relevant fold. Compare the reference, Nebo baseline, and candidate at matched widths and equivalent landmarks. Include the hero-to-introduction transition, heading-to-first-card alignment, rail controls, and any scroll effect touched by the change. Check the static layout before evaluating motion.
3. Inspect computed gutters, section padding, media dimensions, readable line wraps, and control sizes. For touched media, confirm that images load and decode, the subject fits the adjacent copy, and the focal point and text contrast survive desktop and phone crops. Confirm that the media is approved for the role and identify illustrative scenes when visitors could mistake them for the clinic.
4. Attach matched screenshots and key computed measurements to the issue or PR. Record interaction and fallback results, including any unverified case. Keep reusable requirements in `Design.md` and this skill.

## Assigning section work and independent review

Give each section agent the route and section order, its own files or selectors,
the relevant reference and baseline screenshots, the viewport and scroll state,
and the behaviors and content to preserve. Ask for one result per section with
the observed problem, its evidence, the fix, and the Browser check that proves
the change. Keep shared styles, data changes, and issue writes under named ownership.

Review the integrated diff and rendered page with an agent that did not implement
the changes. Run a separate gap pass against the original brief and its reference
states. Have the gap agent look for missing sections, assets, interactions, and
unverified mobile or fallback states. Reproduce findings before fixing them or
updating an existing issue. Open a scoped issue when a confirmed finding needs
approved assets, content, or work outside the current change.

## Testing

Verify in the built-in Browser at desktop, 375px, 390×844, and both sides of each affected
breakpoint. For shared navigation, test empty and populated carts, enabled and disabled
module combinations, support open and closed, direct fragment links, and Back/Forward.
Check computed visibility and geometry, keyboard focus, touch targets, and native
fallbacks. If browser controls or data are unavailable, record the missing checks.
Run `./bapXphp ci` before review.

## Keeping this skill current

When work changes a layout contract or reveals a reusable UI check, update this skill and the affected part of `Design.md` in the same PR. Store one-off measurements and screenshots in the issue. Public pages are gated by `module_on()`; re-check affected conditional layouts with the module on and off.
