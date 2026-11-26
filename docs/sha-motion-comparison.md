---
title: SHA reference motion and component comparison
description: Evidence-backed mapping of the saved SHA Wellness homepage to native Nebo page sections and interactions.
category: design
---

# SHA reference motion and component comparison

Review date: 2026-11-26. Nebo baseline: `f664fab` (#131). Reference is evidence, not a production asset source.

## Capture and extraction

- Saved reference: `Luxury Wellness Destination _ SHA Health & Medical Programs.html` and its companion folder in the project owner's Downloads. Static inventory: 14 section elements, 68 buttons, 122 anchors, 101 images, 3 videos, 44 CSS files. The companion HTML files are saved resources/embedded frames, not independent complete internal-page captures.
- REA 6.8.0 `analyze-javascript-application`: exact selected external sources `header.js`, `modal-menu.js`, `home-hero-2026.js`, `gsap-config.js`, `kpis-home.js`, `experts.js`, `clinical-team-carousel.js`, `slideshow-sha.js`, `acordeon-vanilla.js`, `market-proof.js`, `main.js`. 11 parsed, 0 syntax failures, 10,992 AST nodes. Parent Evidence: `ev_72118ba9c95b5c01ecadf2a2ccb29b47cfa877ab4c3b087a20c4de2bc079fa7b`.
- REA on seven unique bounded relevant inline blocks extracted as inert text: 7 parsed, 0 syntax failures, 4,780 AST nodes. Parent Evidence: `ev_b37ed937a544934eae9608ee6f73f1942598e323d4a2d3e0653c97d2a9ff112d`.
- Both analyses are partial semantic coverage; static calls are not runtime proof. Unselected vendor bundles, tracking and dynamic relationships are not fully reconstructed. Evidence and inventory remain in a private temporary local directory; no third-party source, images, fonts or analytics are checked in.
- Live Chromium at 1440×900 reached the SHA home, Contact, Tailor-Made and Our Story. Measured the statement clipping boundary: 90.94%, 60.73%, 39.58%, then 60.73% after reverse scroll. The changing mask, not a time-based fade, is verified. Other effects below are source-derived unless separately measured.

## Homepage section ledger

| SHA section (saved HTML order) | Observed source behavior/component | Nebo adaptation and decision |
| --- | --- | --- |
| Slideshow | Full viewport, media, single primary action; slideshow controller has timed and video-driven advances | Existing pinned single-image hero and two genuine actions. No invented video, no autoplay. |
| Intro/KPI | Rule draw, four icon-led figures; count animation starts from observer; reference scripts use 2.5s | Foundation 4 figures from clinic setting and published data; existing one-time count and rule. Preserve exact data and no-JS text. |
| Programs carousel | Image-first filtered rail, arrow controls, optional preview video | Existing category filters and native rail; reversible media mask and heading rule. No invented video. |
| Advisors | Selected profile/card/dialog | Existing six practitioner profiles and stable selected biography. Add media settle and short copy transition; no invented board, portraits or biographies. |
| Dark story / big-one | Split image and copy, optional media modal and preview | Existing testimonials and labelled illustrative image; reversible photo reveal. No fake video/play CTA. |
| Scroll statement | Scroll-scrubbed color-boundary clip; live reverse confirmed | Existing single semantic heading with reversible gradient boundary. No duplicate text. |
| Clinical team | Native-like carousel, selected arrows; source duration 600ms | Existing accessible practitioner rail; retain focus, selection and enquiry prefill. |
| Locations | Desktop pinned wrapper with three panels, 300% travel; mobile/native scroll design | Clinic focus mosaic and workplace gallery use scroll-linked image depth/reveal; do not fabricate locations or pin for 300% when content is not equivalent. |
| Experts | Observer reveals, video modal and hover | Existing Why Choose and workplace content; image focal motion, existing icon entries. No endorsements or video without supplied assets. |
| KPIs second block | Observer counts and metric symbols | Existing labelled practitioner/steps/services figures; no medical outcome percentages. |
| Market proof heading | Rule and separate proof heading | Existing testimonials and confidence heading; no invented proof logos. |
| Market proof rail | Autoplay/continuous brand rail, mousewheel disabled | Not applicable: no verified logos or claim assets. Preserve native controlled rails. |
| Tab links | Multi-destination discovery actions | Existing Resources links and labelled image; scroll mask and rule. |
| Final CTA section | Large inquiry/book action | Existing consultation invitation, contact routes and complete charcoal footer. Exact owner-supplied credit. |

## Internal page comparison

| SHA page live | Measured sections and behavior | Nebo changes |
| --- | --- | --- |
| Contact | 3 sections (720px title, 507px intro, 1173px worldwide contacts at 1440×900); distinct routes and contact actions | Preserve one-clinic location, Call/WhatsApp/Email and existing enquiry form; ruled intro and reversible exit-linked image depth; do not copy multi-location controls. |
| Tailor-Made | 4 main sections: 783px hero, 1179px intro, 434px benefits, 731px audience | Existing published duration and owner-authored detail headings. Native heading-derived section index, scroll media, readable body and enquiry aside. No invented benefits, price or treatment claims. |
| Our Story | 6 sections with alternating image/copy and interstitial copy | Existing approved Foundation/vision/pillars and original artwork; reversible hero depth and rule, preserve native content. |
| Journal / other public families | Not independently captured in Downloads | Apply existing editorial components to native journal card and article media, not claim SHA route-by-route parity. |
| Admin | No SHA admin reference or permission | Keep existing task-first admin design; #131 provides shorter workspace and drawer animation. Do not transplant marketing scroll effects into operational forms/tables. |

## Acceptance

Verify a real scroll-linked media property at several scroll offsets, including reverse; test text and buttons remain stationary, images decode, no overflow, no JS and reduced-motion fallback. Verify program contents links are from actual published headings, click targets and inquiry form intact. Inspect rendered desktop and 375/390 screenshots. Preserve all current content and administrator settings. Review #101/#131 and record evidence in the next PR. Full internal-page visual parity, proprietary video/media, and owner acceptance are separate outstanding work.


## Candidate measured outcomes

Chromium measured scroll positions at entry, mid, exit and reverse. At desktop the testimonial image top mask changed 20.97% → 10.63% → 0.24% → 10.63%, workplace gallery 20.95% → 10.60% → 0.25% → 10.60%, and resources 21.00% → 10.63% → 0.27% → 10.63%. Program media masks changed 20.10% → 6.78% → 0% → 6.78%. All four reversed on 390px as well. Focus-card image scale changed 1.0846 → 1.0605 → 1.0437 → 1.0605 at desktop. Frame-named timelines are required where an overflow-hidden media wrapper or horizontal rail otherwise captures the timeline.

The About, Contact and Gut Restoration hero images preserve their full initial image and increase scale on exit, reversing at the same offset; verified at 1440, 375, 390. Contact desktop scale measured 1 → 1.03141 → 1.05748 → 1.03141. No horizontal page overflow. All five programme outlines matched actual published headings (4, 6, 5, 5, 4 in the test fixture), with enquiry forms preserved. Links landed below the sticky header by about 16px. Reduced-motion mode retained the outline and disabled effects; no-JS mode retained full content, form and exact credit. Practitioner selection passed using synthetic records. These observations prove specific effects, not an exhaustive SHA visual match.


## Matched composition follow-up

The owner rejected the previous motion-only result. Matched live 1440×900 captures of SHA and Nebo showed differences in the actual compositions: SHA's flat programme categories, artwork plates with pointer-triggered video, restrained adjacent discovery link, and bottom-positioned practitioner pagination. Nebo used pill filters, compressed images, top arrows and a clipped preview tile. The follow-up uses a white program canvas, larger care-category art plates with an owner-image crossfade (not video), flat filters, adjacent All programs link, exact three-slot portrait geometry, and actual selection indicators below the board. A one- or two-member team fills the available board. The header retains the owner-required fixed white surface with a smaller logo frame. Existing media, routes, published records, registration numbers, forms and clinical boundaries remain authoritative. Touch, reduced-motion and no-JS modes show the real programme photo. Internal contact/programme compositions use lighter titles, squared editorial actions and larger image panels. The exact credit is “Developed with ♥️ by @bapxmediahub”.

Local browser at 1440/375/390/743/744 verified five graphic covers, flat filters, two fertility cards, six selection indicators, sixth-profile selection, no horizontal overflow and exact footer. At 1440 the header measured 82px (previously 102px) and first card image 331px; at 375/390 the media measured 320px. Hover and keyboard focus crossfade the cover to zero opacity. The five programme routes, contact and about were checked at 390; form markup intact. Independent touch-emulated, reduced-motion and no-JS contexts checked. Matched images were inspected on the Mac. This is reference-informed production refinement, not a pixel clone, and does not claim unsupported videos or practitioner portraits.
