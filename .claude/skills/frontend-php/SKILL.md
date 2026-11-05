---
name: frontend-php
description: Use when editing public, account, shop, consultant, temple, cart, checkout, contact, support, or other customer-facing PHP templates.
---
# PHP Frontend

- Follow the root `AGENTS.md` repository contract and its view/asset area rules.
- Read and follow root `Design.md` before changing any customer-facing UI. Treat it as the canonical token, typography, component, responsive and motion contract.
- Motion and public copy follow the `Motion and scroll language` section of root `Design.md`: only flow with in/stagger, one reveal wipe per page and one kinetic headline are permitted today; pin, scrub, worldflight, pan, parallax, drift, the pointer trio and count without verified figures are banned.
- Read `.claude/skills/scroll-craft/NEBO.md` before adding motion to a public page. The skill and engine are vendored read-only; override only `assets/css/scrollcraft-nebo.css` on the home page, after `band.css` and the inline palette block.
- Capture the current desktop and mobile page before editing. Validate the complete customer path from first viewport through primary action and authenticated result, not only isolated markup.
- Before using a browser capture as blog/help media, wait until every represented image reports `complete` with a non-zero `naturalWidth`, content behind an entry reveal is visible, and no loading skeleton or empty data state remains. Inspect the saved bitmap before attaching it with `bapXphp blog:image`.
- Use `bapXphp dev:user` for repeatable authenticated browser checks. Keep `BAPX_TEST_USER_PASSWORD` only in ignored operator configuration.
- Keep UI as PHP-rendered templates plus existing CSS; do not add React, CDN React, SPA shells, or a second frontend.
- Templates consume controller-provided data and existing services; they never read databases, local YAML catalogues, or generated indexes directly.
- Reuse shared tokens and classes in `assets/css/band.css`; keep its tokens synchronized with the critical CSS in `views/layouts/app.php`.
- Preserve the product's real routes and content. Apply the design system surgically instead of copying reference-product labels or scaffolding parallel components.
- Essential content must be legible before JavaScript enhancement. Never make an entry reveal's opacity a loading dependency.
- Audit card families together. Product, consultant, account, authentication, and admin cards have different jobs but share the geometry and focus rules in `Design.md`.
- Use the Nebo editorial image layout on home and about surfaces: an image-led hero, a separated introduction band, clear program cards, and consultant profiles only when real portrait and credential data are available. Never present generated people as named staff or claim unverified outcomes.
- Keep public help guides in `content/blog/posts/*.md` with `category: help`. Render them through existing blog cards and article pages; `/docs` and `/help/{slug}` are compatibility redirects only. Public blog categories must match clinic topics in `content/blog/categories.yaml`.
- Public consultation actions create scheduled appointment requests only. Do not expose live calls, messaging, signaling, or wallet pricing. `ConsultationService` and the consultation message/signal collections are retained legacy code, not permission to surface those features; restore them only under an explicit product change with matching routes, authorization, UI, schema, and tests.
- Validate with `php -l` for changed templates, `php tests/run.php`, and the active coding client's browser workflow for changed pages. Do not add a browser runtime or browser dependency to the application repository.

## Testing

Verify in a browser at desktop **and** 375px mobile — `Design.md` is canonical for responsive behaviour. Check computed styles, not just the screenshot: an author rule such as `display:flex` silently overrides `[hidden]`, which breaks JS filters without any visible error. Then `./bapXphp test`.

## Keeping this skill current

Update when `Design.md` tokens, breakpoints, shared component classes or motion rules change, and when the scroll-craft adapter changes. Public pages are gated by `module_on()`; re-check with each module both on and off.
