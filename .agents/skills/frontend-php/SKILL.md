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
- Audit card families together. Product, consultant, account, authentication, and admin cards have different jobs but share the geometry and focus rules in `Design.md`.
- For layout feedback on a marked element, locate the owning markup and inspect computed padding, alignment, and size at the marked width and at the relevant breakpoints. Remove declarations that the cascade overrides. Check adjacent cards for equal heights, readable wraps, and stable action placement.
- For homepage sequence or spacing changes, compare section order and the first visible heading after the hero. Measure the Programs heading against the first card, check the rail controls and the consultation link, and verify that direct and filtered URL fragments land below the sticky header. Use the saved reference to derive layout behavior and keep Nebo's own copy, assets, and design tokens.
- Use the Nebo editorial image layout on home and about surfaces: an image-led hero, a separated introduction band, clear program cards, and consultant profiles only when real portrait and credential data are available. Never present generated people as named staff or claim unverified outcomes.
- Keep public help guides in `content/blog/posts/*.md` with `category: help`. Render them through existing blog cards and article pages; `/docs` and `/help/{slug}` are compatibility redirects only. Public blog categories must match clinic topics in `content/blog/categories.yaml`.
- Public consultation actions create scheduled appointment requests only. Do not expose live calls, messaging, signaling, or wallet pricing. `ConsultationService` and the consultation message/signal collections are retained legacy code, not permission to surface those features; restore them only under an explicit product change with matching routes, authorization, UI, schema, and tests.
- Validate with `php -l` for changed templates, `php tests/run.php`, and the active coding client's browser workflow for changed pages. Do not add a browser runtime or browser dependency to the application repository.

## Testing

Verify in the built-in Browser at desktop, 375px, 390×844, and both sides of each affected
breakpoint. For shared navigation, test empty and populated carts, enabled and disabled
module combinations, support open and closed, direct fragment links, and Back/Forward.
Check computed visibility and geometry, keyboard focus, touch targets, and native
fallbacks. If browser controls or data are unavailable, record the missing checks.
Run `./bapXphp ci` before review.

## Keeping this skill current

When work changes a layout contract or reveals a reusable UI check, update this skill and the affected part of `Design.md` in the same PR. Store one-off measurements and screenshots in the issue. Public pages are gated by `module_on()`; re-check affected conditional layouts with the module on and off.
