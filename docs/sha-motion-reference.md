---
type: doc
title: SHA editorial motion reference
description: Evidence and acceptance checks for team portraits, native scroll overlays and campaign forms.
---
# SHA motion reference investigation

Tracked in issue #27. Inspected 2026-10-16 using REA 6.5.0 and the built-in Browser.

## Evidence

Public reference: https://shawellness.com/en/

REA operation: `analyze-javascript-application /tmp/nebo-sha-reference --artifact-format directory --analysis-timeout-ms 30000 --format json`.
Evidence ID: `ev_2b5689b3863f7ea5b70f6616a07375a6cbd07985e33f453d5a632b29f18e3c0b`.
Input digest: `881c61aa32cae29e572fd5784ce912706d1edd9c2879f362f8cb262bb9870e1b`.
The input contained the public homepage and four public theme scripts. Raw reference assets and the REA analysis remain temporary investigation artifacts, not application dependencies.

## Observed implementation

- `design-system/modulos/advisors/advisors.js`: active portrait and information states are synchronized; large and small widths are measured separately. The active desktop portrait sits at the right edge of its portrait track. Movement uses a 500ms transform with `cubic-bezier(0.25, 0.1, 0.25, 1)`. Portrait order rotates after the movement to maintain continuity. The details and pagination reflect the selected identity, rather than the scroll offset alone.
- `design-system/modulos/clinical-team-carousel-only/clinical-team-carousel.js`: adjacent navigation is bounded and handles the final visible group explicitly. Mobile has separate sizing/navigation behavior.
- `js/gsap-config.js`: the reference registers ScrollTrigger, applies mobile-specific resizing rules, and integrates Lenis where enabled. This establishes library use, not a requirement to import those libraries into Nebo.
- Homepage markup includes viewport entrance attributes and pinned/scrubbed sections. The board screenshots show small portrait previews, one large portrait and a separate biography column.

These are static source observations combined with rendered-page inspection. No claim is made that REA reproduced a complete runtime animation trace.

## Nebo implementation requirements

1. Programs retain equal-size cards and single-step arrow navigation.
2. Team uses small labelled portrait previews, one large selected portrait, and a separate selected biography region. Name remains visible on every preview. Selection works by keyboard, pointer and arrows, with one identity change per action.
3. Reveal motion uses a short upward fade, with content visible before enhancement and reduced-motion support. Sticky overlays use opaque surfaces and native document scrolling; long mobile content remains reachable.
4. Promotion pages keep their own enquiry form on the page. Admin controls its title, introductory copy, button, confirmation copy and optional phone/message fields per campaign. Server submission validates the published campaign and stores its identity with the existing contact record.
5. Verify desktop and 375px layouts, campaign create/edit/publish, enquiry validation/persistence and owner visibility, then CI and live deployment. Provider-dependent payment, Google and SMTP activation remain subject to the previously stated client setup.

## Local verification

- Built-in Browser: created `promotion-form-qa-27` in Admin, confirmed draft route unavailable, published, verified custom copy and required phone/hidden message, submitted synthetic contact data, saw custom confirmation and corresponding owner Contacts row. Returned the QA campaign to draft afterward.
- At 375px: campaign form uses one column and document overflow is zero. Team arrows change selected identity and names remain in portrait buttons.
- Desktop: selected team biography updates one identity per arrow; document overflow is zero.
- These checks verify local PHP connected to hosted data. Live deployment verification is still required.
