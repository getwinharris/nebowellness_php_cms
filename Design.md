---
version: alpha
name: Nebo Wellness
description: Image-led editorial interface system for Nebo Lifestyle Clinic, its wellness campaigns, programs, and guidance content.
colors:
  primary: "#4472C4"
  on-primary: "#ffffff"
  primary-container: "#6b8dd6"
  on-primary-container: "#ffffff"
  secondary: "#70AD47"
  on-secondary: "#ffffff"
  secondary-container: "#8cc069"
  on-secondary-container: "#ffffff"
  tertiary: "#8cc069"
  on-tertiary: "#2f5aa0"
  tertiary-container: "#e7f2df"
  on-tertiary-container: "#3c6b2f"
  neutral: "#f7f9fa"
  neutral-variant: "#eef2f4"
  surface: "#f7f9fa"
  surface-container: "#edf3f6"
  outline: "#d7e1e6"
  outline-variant: "#e6ecef"
  ink: "#222222"
  ink-muted: "#5b6872"
  ink-soft: "#75838d"
  success: "#70AD47"
  warning: "#e8a317"
  error: "#d64045"
  info: "#4472C4"
typography:
  display:
    fontFamily: Inter
    fontSize: 4rem
    fontWeight: 300
    lineHeight: 1.05
  h2:
    fontFamily: Inter
    fontSize: 1.375rem
    fontWeight: 400
    lineHeight: 1.3
  body-md:
    fontFamily: Inter
    fontSize: 1rem
    fontWeight: 400
    lineHeight: 1.6
  body-sm:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: Inter
    fontSize: 0.8rem
    fontWeight: 600
    lineHeight: 1.4
  accent-italic:
    fontFamily: Playfair Display
    fontSize: 1.05rem
    fontWeight: 600
rounded:
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 20px
  pill: 999px
spacing:
  2xs: 2px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
  3xl: 64px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.md}"
    padding: 16px 40px
    height: 52px
  button-primary-hover:
    backgroundColor: "{colors.primary-container}"
  button-secondary:
    backgroundColor: "{colors.secondary}"
    textColor: "{colors.on-secondary}"
    rounded: "{rounded.md}"
    height: 52px
  card-program:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: 32px
  card-consultant:
    backgroundColor: "{colors.on-primary}"
    rounded: "{rounded.lg}"
  input:
    backgroundColor: "{colors.on-primary}"
    rounded: "{rounded.sm}"
    height: 48px
---

## Overview

Nebo Wellness is a naturopathy and functional medicine clinic platform. The primary customer journey is discover programs -> learn about consultants -> book consultation -> manage wellness journey. Blog content and health resources support that journey. This file is the canonical visual contract for everything customer-facing in `views/`, `assets/css/band.css` and `assets/css/scrollcraft-nebo.css`. Motion, scroll devices and public copy are specified in Motion and scroll language.

Design inspiration from SHA Wellness (shawellness.com) informs the use of:
- Clean, spacious card layouts with clear separation
- Professional medical team showcases with credentials
- Prominent program/service cards with clear CTAs
- Measured proof points and outcomes, displayed only when Nebo can substantiate them
- Testimonial sections with credibility markers
- Section rhythm: alternating grounds and contained content blocks
- Sophisticated use of whitespace and hierarchy

Keep the existing PHP templates, routes, forms, and hosted MySQL-backed behavior. Design changes must not scaffold a second frontend.

## Colors

- **Primary -- professional blue (`#4472C4`):** headers, primary buttons, active nav state, key actions. `on-primary` is white; `primary-container` (`#6b8dd6`) is the blue-active/pressed state. Inspired by medical/wellness trust colors.
- **Secondary -- wellness green (`#70AD47`):** supporting accent, success states, nature/health emphasis, secondary CTAs. Represents natural healing and vitality.
- **Tertiary -- soft green (`#8cc069`):** tertiary accents, active underline, eyebrow labels, subtle hairlines.
- **Surface (`#f7f9fa`) / surface-container (`#edf3f6`):** cool gray canvas and alternate-section background. White is reserved for cards, form surfaces, and deliberate image breaks.
- **Outline / outline-variant:** hairline borders only -- no heavy strokes.
- **Ink / ink-muted / ink-soft:** body text hierarchy from primary copy down to placeholders.
- Success uses green (#70AD47), warning, error, info are semantic exceptions for state communication.

## Typography

- Body: Inter (300-700) with system sans-serif fallbacks.
- Display headings: Inter, weight 300-400, with tight tracking for large editorial
  headings and predictable breakpoint sizes. Operational page headings may use 600.
- `accent-italic` (Playfair Display, 600 italic): decorative highlight only -- eyebrow labels and value-card titles. Never body text.
- Body copy: `14px`-`16px`, weight 400, line-height `1.45`-`1.6`.
- Labels/metadata: `12px`-`14px`, weight 500-600.
- Letter-spacing is `0` everywhere except short operational labels (table headers, filter labels, badges), where uppercase + slight tracking is acceptable. Buttons and headings are never uppercase.

## Layout

- Spacing scale: `2, 4, 8, 16, 24, 32, 48, 64px`.
- Container: centered, max `1300px` (`1440px` for wide layouts).
- `.section`: `64px` vertical padding by default. `.section--alt` uses `surface-container`; `.section--warm` uses the warmer `#edf3f6` for value sections.
- Responsive breakpoints: mobile below `744px` (one column, compact header, bottom nav), tablet `744-1128px` (reduced grid columns, same card geometry), desktop above `1128px` (centered container, `64px` section spacing).
- Text, buttons, images, and fixed controls must not overlap or reflow awkwardly as content length changes.
- Do not scale typography with viewport width. Use explicit breakpoint sizes so headings remain predictable and do not dominate short mobile screens.
- The first viewport must show the clinic's primary consultation action and a hint of the next section. Shop pages lead to products when enabled; account pages lead to the user's current task.
- Page sections are unframed full-width bands. Cards are reserved for repeated entities, forms, summaries, and genuinely bounded tools. Never place a card inside another decorative card.
- Desktop operational screens use compact density and stable columns. Mobile screens use one clear column with 16px page gutters and no horizontal scrolling.

## Elevation & Depth

Depth is used sparingly and tonally-first, the way Material 3 treats elevation: a surface reads as "raised" primarily because it's a different, lighter tone than the canvas (white card on warm surface), with a soft shadow as the secondary cue, never a glow.

Four shadow steps exist (`--shadow-sm/md/lg/xl` in `band.css`), each with exactly one job:

| Step | Use |
|---|---|
| `sm` | Resting state for cards and inputs |
| `md` | Raised buttons; hovered product cards |
| `lg` | Hovered consultant cards and feature/value cards |
| `xl` | Modals, drawers, popovers only |

Rules:
- Never stack two shadow steps on one element.
- A hover state may move exactly one step up the scale (`sm` → `md`, or `sm` → `lg`), never two.
- No glow, no colored shadows, no blur-heavy lighting effects.

## Shapes

The radius scale expresses a calm, rounded-but-not-playful geometry:

| Token | Value | Use |
|---|---|---|
| `rounded.xs` | 4px | Chips, tags, small badges |
| `rounded.sm` | 8px | Buttons, inputs, standard controls (48px tall) |
| `rounded.md` | 8px | Repeated photo cards, product cards |
| `rounded.lg` | 8px | Feature cards, panels |
| `rounded.xl` | 8px | Hero panels and large media when a radius is needed |
| `rounded.pill` | 999px | Search bars, filter pills, status badges |

Shape should stay consistent within a component family -- don't mix `sm` and `lg` radii on sibling elements of the same card.

## Components

- **Header:** soft white, sticky at the viewport top, with a hairline bottom border and the complete client-supplied Nebo logo centered. The drawer repeats this logo as a home link; account/cart actions remain right aligned.
- **Home hero (editorial):** Use `nebo-clinic-hero.png` as a full-width photo with a dark blue text scrim. Left-align the headline and consultation action; keep the people and clinic setting visible at the right. At 375px, crop toward the consultation and retain a readable text panel and full-width actions. The image is illustrative and must not be described as a photograph of Nebo staff.
- **Program rail:** a horizontal, equal-height scroll-snap editorial rail with three cards visible on
  wide screens and one partial next card visible on mobile. Each card uses an abstract
  colour field, duration label, program name, short factual description, and enquiry
  link. Each arrow action advances exactly one card plus its grid gap; keyboard and touch scrolling remain
  available. When the rail enters view, its cards use one short entry reveal with a stagger; only the current card receives a subtle lift. Nebo offers programs only: never introduce destinations, accommodation,
  room selection, stay length, or hotel booking concepts.
- **Campaign pages:** owner-edited pages use a full-width image with a blue text scrim, short headline and enquiry action, followed by an open reading column and one quiet side card. The campaign index uses 16:9 image cards. Drafts never have a public route or sitemap entry.
- **Proof points:** Display measured outcomes only when Nebo can substantiate the numbers and define the measurement period and source. Otherwise use concrete service details without invented percentages.
- **Team/Consultant rail:** portrait-first, equal-height cards with name, credentials,
  speciality, concise bio, and one consultation enquiry action. Until actual portraits
  are supplied, use the approved male silhouette for male practitioners and approved
  female silhouette for female practitioners, label them as portrait placeholders,
  and keep `photo_url` editable through Admin → Consultants and the media picker. Use a large selected profile beside small portrait previews, with the same single-card arrow advance and entry reveal as the program rail.
- **Testimonial Cards:** Use the client-approved testimonial copy from the project source document. Present it as a quiet two-column quote grid with the supplied attribution; do not invent outcome claims or missing attribution details.
- **Section rhythm:** Use generous whitespace (`64px` - `96px`) between major chapters. Alternate between canvas and `surface-container` grounds for visual rhythm, inspired by SHA's clean sectioning.
- **Content Cards:** More rounded (`12px-16px`) than the old design. Generous internal padding. Clear hierarchy with headings, body text, and CTAs.
- **Navigation:** the linked brand mark and name are the sole home control. Do not repeat a separate Home item in desktop or mobile navigation.
- **Mobile commerce tray:** after the cart becomes non-empty, show one fixed blue tray above the bottom navigation with item count and a direct View cart action. Use an 8px radius and stable 56px minimum height; update it without page reload.
- **Floating support stack:** on desktop, align the support circle directly below the cart tray at the same right edge. On mobile, keep the cart tray above the bottom navigation and keep support clear of both controls.
- **Editorial media:** every blog post uses one intentional 16:9 image for both its listing thumbnail and article lead image. UI guides use a legible screenshot of the exact page, cropped around the relevant interface rather than a decorative stock image, and link the represented page below the article.
- **Buttons (`button-primary` / `button-secondary`):** `48px` minimum height, `8px` radius, no uppercase, no letter-spacing. Primary is solid blue with a darker-blue hover; hover moves from `shadow-md` to `shadow-lg` and lifts 2px, no more. Secondary is solid green with white text. Hover states never shift layout.
- **Forms:** white fields (`on-primary`), `8px` radius, `48px` height, clear labels, a single-value focus ring (`--shadow-focus`) -- no glow.
- **Search/filter:** one rounded (`pill`) search control, or a quiet grouped filter row. No nested cards for filters.
- **Product cards (`product-card`):** linked image/title, short description, price, Buy Now and one stateful cart control. Initially show Add to Cart; adding one unit replaces it in place with `− quantity +`. The number is the actual cart quantity. Each press updates the cart immediately; decrementing the final unit restores Add to Cart. Keep 44px targets, native form fallback, visible focus and disabled controls during requests. Do not show a separate Quantity row or redundant in-cart link.
- **Cart quantity:** provide `−` and `+` controls beside every product, update line totals and the cart count, and remove the line when decreased to zero. Repeated additions of the same product merge into one line. Distinguish selecting a quantity to add from editing the quantity already in the cart.
- **Commerce responsiveness:** use `minmax(0, 1fr)` for purchase-button columns, wrap quantity labels when space is tight, and keep keyboard focus visible. Verify home, shop, product and cart at 375px and desktop in the built-in Browser. Do not shrink purchase controls to 30px to force them onto one row.
- **Consultant cards (`card-consultant`):** white, `8px` radius, face-forward portrait, name, speciality, language/experience metadata, review summary when present, and one clear profile/booking action. Every card uses equal media and content tracks so rows align.
- **Section rhythm:** Alternate an image-led introduction, clean focus-area cards, a three-column why-Nebo band, and five program cards. About uses a wide tropical-garden image before the story cards. Generated images are illustrative and never presented as actual Nebo staff, premises, or clinical outcomes. The primary action opens the contact form.
- **Authentication:** login and registration are task pages, not marketing pages. Use a centered form surface, suppress the public footer, and keep the complete form visible on common mobile heights.
- **Product details:** keep the short introduction, feature bullets, detailed description, and labelled specification rows in separate readable sections. Admin-created products use the same fields and layout as existing products; never paste table markup into a plain-text description. Omit empty sections instead of fabricating claims.
- **Account:** use a persistent internal menu and one unframed content region. Orders, addresses, and installation are tasks, not promotional cards.
- **Admin:** optimize for scanning and repeated action: compact sidebar, clear tables, consistent forms, explicit save state, and no marketing-style hero composition.
- **Admin platform guidance:** describe the product-led public journey consistently. Historical service records may remain owner-accessible, but integration help must not advertise retired public booking, text sessions, or direct calls.
- **Admin form sizing:** use zero-minimum grid tracks and shrinkable labels/controls. Long integration endpoints or credentials must stay within the field, never widen the page. Check 375px with populated fields without exposing their values in screenshots or reports.
- **Admin product preview:** render saved images and structured content through the existing product-detail layout. Clearly label the view as an admin-only preview, disable purchase controls, and provide Back to products. Hidden products remain absent from public listings and ordinary URLs; previews are uncached and not indexed.
- **AI request status (both chat surfaces):** immediately show a keyboard-operable, collapsible Thinking… row and elapsed seconds while waiting. Expanded content reports request status only, never raw model reasoning or simulated stages. Use the shared white/blue component, 44px summary targets, tabular time digits, and an indeterminate progress control. Respect reduced motion, prevent duplicate submits, restore the composer on every completion path, and retain a collapsed Response ready or Request failed record beside the answer. Timing includes network/server time and must not imply model success or accuracy.
- **Waiting and loading:** only show a loading state while a real request is pending. Keep the user's submitted message visible; do not replace real catalog content with decorative skeletons. Errors and fallbacks must remain distinguishable from successful model responses in monitoring.
- **Value-proposition cards:** 4-column desktop / 2 tablet / 1 mobile, white card, warm icon circle, `accent-italic` heading, muted body, `4px` hover lift into `shadow-lg`.
- **Footer:** charcoal gray (`#2f3437`) field with white headings and links, soft white
  body text, direct clinic contact details, and verified Instagram, Facebook, and
  YouTube links. The credit links to `https://mediahub.bapx.in/`. Avoid duplicate
  navigation and retired devotional routes.
- **Mail:** transactional email uses the full clinic name, cool-gray surfaces, white
  type on blue or charcoal headers, an 8px blue action, and “Owner notification” for
  admin-only mail. It must not describe Nebo as a store or use legacy colour names in
  customer-visible copy.
- **Motion layer:** optional, home-page-only layer. The device vocabulary, the permitted
  and banned devices, the copy rules and the reduced-motion fallback live in Motion and
  scroll language. No component may depend on motion for legibility, and no page adds a
  second scroll engine.
- **Documents and guides:** Markdown-backed pages use the same warm canvas and a constrained reading column. The page header is centered and quiet; the content surface is white with a single soft border, 14px-20px radius, and `shadow-sm`. Use blue `h2` headings, muted body text at 1.6-1.7 line-height, generous section spacing, and green only for eyebrows, links, and small metadata. Documentation indexes use a two-column desktop grid and one-column mobile layout with clear titles, summaries, and a visible `Read guide` action. Do not render legal or customer documentation as long unstructured text or nested cards.

## Motion and scroll language

Scroll-craft is the motion vocabulary for new customer-facing work. The vendored skill is `.claude/skills/scroll-craft/` and is read-only; `NEBO.md` there is the project adapter. Where the skill and this file disagree, this file wins for brand surface (palette, type, card families, component geometry), and the skill wins for motion, copy rules and verification depth on new work. The rules below bind new work; existing components keep their documented geometry. The home page is the pilot.

Static first: establish the complete static layout before any device is added. Motion is optional enhancement, and no chapter may depend on it for legibility or reachability.

### Devices

A device is one way scroll changes the page. Choose a device per chapter, never per page, and never repeat a device family in consecutive chapters. Only three device families are permitted on Nebo public pages today: flow with in/stagger, reveal and kinetic.

| Device | What it does | Engine mechanism | Nebo rule today |
|---|---|---|---|
| scrub | Scroll drives the playhead of a pre-rendered clip. | A `<video data-sc-scrub>` with `data-sc-src` and a poster. | **Banned.** No video, no audio and no new media budget. |
| pin | A stage holds at the viewport while copy crosses inside it. | `data-sc-act="pin"`, a `[data-sc-stage]` child that computes `position: sticky`, `data-sc-span`, and optionally `data-sc-dwell`. | **Banned** unless the owner approves an amendment. `pin` names this device only; the shipped home behaviour is a sticky stack, not a pin. |
| pan | Vertical scroll moves a wide rail sideways. | `data-sc-pan` on a rail wider than the viewport, inside an act. | **Banned.** The Program rail is a native scroll-snap rail with arrow controls, not a pan act. |
| reveal | A clip-path wipe eats in from an edge; reads as transformation. | `data-sc-reveal` takes up, down, left, right or iris; `data-sc-reveal-at` sets the window. | **Permitted, at most one per page**, on a full-width band rather than on type. Today: the testimonial band. |
| kinetic | A heading splits into lines, words or characters and staggers across its cue window. | `data-sc-kinetic` takes lines, words or chars on a cued element. | **Permitted, one headline per page.** Today: the Programs heading. Lines only; character splitting is not used. |
| parallax | Depth planes move at different rates. | `data-sc-parallax` on a descendant of an act; the rate is in hundreds of pixels. | **Banned.** Nebo has no depth plates or clean foregrounds, and the hero is a title screen, not a layered hero. |
| count | A number blooms or ticks up. | `data-sc-count` with `data-sc-count-at` or `data-sc-count-ms`. | **Banned until verified figures exist.** A counter is a truth claim and needs a figure, a source and a measurement period. |
| flow with in/stagger | Ordinary full-width chapters with a one-time entry reveal. | `data-sc-act="flow"`; `data-sc-in` and `data-sc-stagger` on a container. | **Permitted and the default.** Fires once and never hides content when scripts fail. Today: the entry reveal on the new Question chapter; Programs and Testimonials are flow acts only as containers for kinetic and reveal. |
| tilt, magnet, spotlight (pointer devices) | Pointer-reactive tilt, drift and a light that follows the cursor. | `data-sc-tilt`, `data-sc-magnet`, `data-sc-spotlight`. | **Banned.** Mouse-only decoration that touch visitors never see. |
| drift | The page ground interpolates between colours as acts take over. | `data-sc-drift` on acts; the engine mixes `--sc-canvas` on `:root`. | **Banned.** Home uses hard changes of ground (canvas, alt and warm bands); chaptered editorial bans the drift gradient. |
| worldflight (page mode, not a device) | One fixed stage for the whole page, every leg mounted, a spacer as the only scroll track. | `data-sc-mode="worldflight"` with `data-sc-world`, `data-sc-spacer` and legs. | **Banned.** It needs footage and the continuous-world grammar. |

**Permitted on Nebo public pages today:** flow with in/stagger, reveal (one wipe per page), kinetic (one headline per page). **Banned today:** scrub, worldflight, pin without an owner-approved amendment, pan, parallax, count without verified figures, the pointer trio and drift.

`stage`, `span`, `dwell` and `clip time` exist only for pinned devices, so they are not used today; only `flow` acts are permitted. Common wrong spellings do not exist in the engine: there is no `data-sc-pin` (`pin` is a value of `data-sc-act`) and no `data-sc-scrim` (scrims are classes).

### Planning and quality vocabulary

The planning words below are the vocabulary for briefs, reviews and commit messages. Use the definition given, and do not invent a synonym for a word that already has one.

| Term | Definition | Nebo application |
|---|---|---|
| brief | The written plan made before any act or device is chosen: journey, feeling curve, engineered peak, tell-someone sentence and any authored silence. | New pages start with a brief, not with attributes; the home pilot follows the chaptered-editorial brief. |
| journey beat | A shift in what the visitor knows or feels; beats are the spine and chapters serve them. | The nine home chapters follow beats from assurance to resolve. |
| chapter (act) | A section as one unit of the visitor's journey; its engine form is an act, the `[data-sc-act]` element and its scroll time. | Only `flow` chapters are permitted today; `section` is the layout band and `chapter` is the journey unit. |
| page grammar | The page's organising logic; each of the skill's eight grammars is defined by what it forbids. | Home uses chaptered editorial: hard changes of ground, no pinned type acts, no drift gradient. |
| feeling curve | One line per chapter: the emotion first, then the thing on screen that causes it. | The home curve runs assurance, trust, recognition, intimacy, reassurance, possibility, credibility, proof and resolve. |
| engineered peak | The single deliberate memory moment, named as a sentence a visitor would say, with a before and an after. | The Question chapter is the peak: one authored silence, one question and four real answers. |
| scroll score | The written table that assigns each beat its device and says why. | The device budget below is the home pilot's score. |
| signature move | One bespoke page-local interaction that exists on this site alone; the engine is not touched. | `The question that follows`: the answer travels into the closing CTA and the contact form through `data-nebo-*` hooks, and the links work when scripts are off. |
| fingerprint gate | The pre-build check that a new build differs from every registry row on at least four of six dimensions. | The first scroll-craft build starts from an empty registry; append the home row after it ships, in committed documentation outside the deployable root. |
| dead scroll | Consecutive scroll positions where nothing on screen changes. | Home has no scrubbed progress to check; the gate is that the entry reveal completes before its chapter is centred and that the quiet chapters are authored silence. |
| cue | A window of act progress during which copy fades and rises. | One cued element only, the Programs heading. Entry reveals use `in`, not `cue`. |
| scrim | A gradient plate that restores contrast only where text sits; a full-frame overlay is banned. | The hero and campaign images keep a text scrim; no page adds a full-frame dark overlay. |
| contact anchor | The shared point that keeps planes physically connected when they move. | Not used: Nebo has no layered hero or moving planes. |
| plane/layer contract | The written table of planes, their independent movement and their contact rules, made before a layered hero is built. | Not attempted: the hero is a static title screen and no depth is faked from gradients. |
| reduced-motion fallback | What remains when the visitor asks for less motion: fewer and gentler, never zero comprehension, every chapter complete and reachable. | The engine drops translation and wipes and keeps opacity; the sticky stack returns to normal flow; `scroll-behavior` is `auto`. |
| entry reveal | A one-time fade and rise as an element enters the viewport, then stillness. | Shipped as the view-timeline copy-in on home chapter containers and the `.reveal` hook on cards; the new Question chapter uses the engine's `flow` with in/stagger. Never put two entry reveal hooks on one element. |

### Resolved naming conflicts

One word per concept. The table retires the earlier wording and records where it was reworded; do not reintroduce a retired word in a new rule.

| Retired wording | Canonical term | Where the old wording was removed or reworded |
|---|---|---|
| scroll motion, motion-on-scroll, scroll effects | **motion layer** | The Components bullet is renamed `Motion layer` and points here; the layer is named once, in this section. Individual motion is named by its device. |
| reveal animation, entrance animation, scroll entrance, staggered rise | **entry reveal** | The Program rail, Team rail, Components and Do's and Don'ts bullets. `reveal` alone never means an entrance. |
| scroll-linked reveal, viewport timeline reveal | **entry reveal** | The view-timeline copy-in is the shipped implementation of the one-time entry reveal on home chapter containers, not a second motion system. The Home chapter stack section is reworded. |
| section dividers, dividers | **section rhythm**; a line rule is a **hairline** | The Overview inspiration bullet, the Colors bullet and the Components bullet renamed and reworded. |
| pinned header, pinned navigation, pinning a short section, editorial overlays, full-viewport layers, homepage layers | **sticky** for CSS positioning; **sticky stack** for the home chapter cards; **pin** only for the engine device | The Header bullet and both tail sections, renamed from `Editorial section overlays` to `Home chapter stack` and from `Pinned navigation and portrait alignment` to `Sticky navigation and portrait alignment`. |
| hero, for the article top image | **article lead image**; **hero** stays the home title screen (the first chapter); **layered hero** is the scroll-craft plane pattern and is not attempted | The Editorial media bullet. |
| carousel, carousel-or-grid | **rail** for the horizontal scroll-snap component; **card grid** only for static card families | The Sticky navigation section now says rails; `carousel` is retired. |
| spotlight, as a lighting effect | **lighting effects**; **spotlight** is reserved for the banned pointer device | The Elevation rules. |
| section, for both the layout band and the journey unit | **section** is the layout band; **chapter** is the journey unit (engine act) | This section, the device budget and both tail chapters. |
| final consultation card, CTA card | the **close** chapter and its **consultation CTA card** | The device budget and the Sticky navigation section. |

### Device budget (home pilot)

The home page runs three engine device families and one page-level pattern. The signature move `The question that follows` is page-local JavaScript, not an engine device. Every chapter after the hero also carries the shipped entry reveal (the view-timeline copy-in on the card container, and the `.reveal` hook on cards); it is one concept, not an extra device family. Programs and Testimonials are `flow` acts only so their kinetic and reveal devices have an act to drive them; the flow device itself, an entry reveal with stagger, is used only on The Question chapter.

| Chapter or layer | Engine device | Markup or layer | Rule |
|---|---|---|---|
| Hero | none | none | Static title screen. No cue on the h1: a flow act above the fold starts with progress above zero, and copy is never hidden. |
| Foundation | none | shipped view-timeline copy-in | Static content; the shipped entry reveal is the only motion. |
| Focus Areas | none | shipped view-timeline copy-in | Static content; no eyebrow. |
| The Question (new) | flow with in/stagger | `data-sc-act="flow"`; `data-sc-in data-sc-stagger="80"` on the inner stack | The engineered peak and the signature move; four real answer links work when scripts are off; the optional serif accent label counts as the chapter's one eyebrow. |
| Why Nebo | none | none | Static; the quiet after the peak. |
| Programs | kinetic | The chapter is a `data-sc-act="flow"` act; `data-sc-cue="0.02" data-sc-kinetic="lines"` on the h2 | The page's only kinetic headline, lines only; no eyebrow. |
| Team | none | none | Static; no eyebrow. |
| Testimonials | reveal | The chapter is a `data-sc-act="flow"` act; `data-sc-reveal="up" data-sc-reveal-at="0.15 0.55"` on the testimonial band | The page's only wipe; no eyebrow. |
| Close | none | none | Holds. The only change is the signature-move state: chip, CTA label and href. One eyebrow permitted. |
| Every chapter card | sticky stack (page-level, not engine) | `.editorial-stack` with `.editorial-stack-card` and `data-stack-card`; the layout script adds `stack-ready` and appends the footer as the final card | Opaque full-height cards hold under the sticky header while the next climbs over; reduced motion returns them to normal flow; never re-implement with the pin device. |

The cap is at most three eyebrows per page. Before the scroll-craft pass the home page had eight; the copy pass leaves the hero and the close, with at most one serif accent label on The Question chapter as the remaining allowance.

**Documented deviation:** three engine device families (flow with in/stagger, kinetic, reveal), short of the skill's four-family variety law. Without video, photography or an owner-approved amendment a fourth family is not honest here, so the home page is a motion layer, not a full scroll-craft build. The sticky stack is page layout, not a device, and it is never re-implemented with `data-sc-act="pin"`.

## Do's and Don'ts

- **Do** keep the canvas cool and light (`surface` / `surface-container`) and use white cards, inputs, and modals for focus.
- **Do** let a hover state move exactly one elevation step and/or lift by a few pixels -- nothing more theatrical.
- **Do** use `accent-italic` (Playfair Display) sparingly, as a decorative highlight.
- **Do** keep shape consistent per component family (see Shapes).
- **Do** name the device (entry reveal, reveal, kinetic, flow) when proposing motion, not a generic word.
- **Don't** use uppercase or letter-spacing on buttons or headings -- reserve it for short operational labels only.
- **Don't** add glow, colored blur, gradients-as-decoration, or stacked shadow tiers.
- **Don't** introduce a second frontend, second routing scheme, or a component library that bypasses the existing PHP templates.
- **Don't** copy reference-product navigation labels or routes that don't exist in this app -- keep the real routes.
- **Don't** let hover/focus states shift layout or reflow siblings.
- **Don't** hide essential content behind an entry reveal. Motion is optional enhancement and must never control legibility.
- **Don't** use an em dash in visible copy. Use a period, comma, colon or parentheses.
- **Don't** add a scroll-to-explore cue, an arrow, or an animated mouse.
- **Don't** number chapters or add a section counter (`01 / 06`).
- **Don't** exceed three eyebrows on one page (the skill's one per three chapters).
- **Don't** use an invented statistic or a counter without a verified figure, its source and a measurement period.
- **Don't** use claymation or AI-generated imagery.
- **Don't** animate width, height, top or left, and don't use `transition: all`. Motion uses transform, opacity or clip-path.
- **Don't** fix contrast with a full-frame dark overlay. A scrim sits under the text only.
- **Don't** let a page depend on JavaScript. Every chapter is complete and legible with scripts off.
- **Don't** reduce motion to nothing. With reduced motion emulated, every chapter stays complete and every action reachable.
- **Don't** use decorative gradients, oversized pills, or large-radius containers to manufacture hierarchy. Use spacing, typography, borders, and real media.
- **Do** keep policy, legal, customer, and internal guide content in Markdown/YAML sources and render it through the shared document surface so copy changes do not require template rewrites.

## Verification

- Check `/`, `/shop`, one product, `/login`, and authenticated account pages at 1440x1000 and 390x844 in a real browser.
- Confirm image crops, active navigation, focus states, card alignment, no content hidden behind an entry reveal, no horizontal overflow, and that the next section is hinted in the first mobile viewport.
- For the home page, also check with JavaScript off, with reduced motion emulated, and at 744px and 860px; the 860px engine fallback sits between the tablet breakpoints.
- Use the fixed development customer created by `bapXphp dev:user`; its password must come from `BAPX_TEST_USER_PASSWORD` and must never be committed.
- Run the repo's PHP tests, project-map validation, and local smoke test before commit or push.

## Implementation Notes

- Tokens above are expressed as CSS custom properties in `assets/css/band.css` (`:root`) and critical CSS in `views/layouts/app.php`. Prefer the semantic `--color-primary`, `--color-secondary`, and surface tokens in new code; legacy hue names in older components currently resolve to Nebo colors during migration.
- Elevation tokens: `--shadow-sm/md/lg/xl` plus `--shadow-focus` for the single focus-ring definition.
- Motion: `--transition-spring` (a restrained expressive-motion curve, `cubic-bezier(0.34, 1.4, 0.64, 1)`) is used for hover lifts on buttons and cards; `--transition-base` still governs color/shadow fades. New scroll devices use the engine's `--sc-ease-out`; existing hover lifts keep `--transition-spring`.
- Scroll-craft engine: `assets/vendor/scrollcraft/scrollcraft.js` and `assets/vendor/scrollcraft/scrollcraft.css` are vendored unmodified, with the upstream `LICENSE` beside them. Never edit them; theme through tokens only. The project override is `assets/css/scrollcraft-nebo.css`, the only scroll-craft stylesheet edited. The vendored skill is `.claude/skills/scroll-craft/`, with `NEBO.md` as the adapter.
- Load order: `scrollcraft.css`, then `band.css`, then the inline palette block, then `scrollcraft-nebo.css`. The override aliases the engine roles to live tokens: `--sc-canvas` to `var(--color-bg)`, `--sc-surface` to `var(--color-white)`, `--sc-ink` to `var(--color-ink)`, `--sc-ink-soft` to `var(--color-text-muted)`, `--sc-accent` to `var(--color-primary)`, `--sc-accent-ink` to `var(--color-white)`, and `--sc-font-display` and `--sc-font-text` to `var(--font-main)`. Never fork a value.
- Progressive enhancement: an inline head script adds `sc-js` to `<html>` before first paint; engine pre-states apply only under `html.sc-js`. The override restates `:focus-visible` as `box-shadow: var(--shadow-focus)`, and pages with engine acts or sticky elements use `overflow-x: clip` rather than `hidden`.
- Scope: the engine and the page-local signature-move script `assets/nebo-focus.js` load on the home page only; every other page ships neither. Version each asset with `?v=<?= filemtime(...) ?>`.
- No build step: the vendored files are static. Do not add Node, Playwright, ffmpeg, a CDN or an asset pipeline; verification stays in the built-in Browser.
- This file can be linted against the open spec with `npx @google/design.md lint Design.md` if you want machine validation of token references and section order.

## Promotion enquiry forms

Each campaign has an on-page enquiry band with a charcoal surface, white copy,
clear field labels and a two-column editorial composition. At 375px it becomes a
single column. Admin controls form heading, introduction, submission button,
confirmation copy and phone/message visibility and required state. Name and email
remain required. Enquiries retain campaign attribution in the existing inbox.

## Team selection composition

The team module uses a clipped portrait track on the left and a selected biography
column on the right. Portrait previews always show names. Exactly one portrait is
large; its description and consultation action appear in the detail column.
Arrows cycle one practitioner at a time; portrait buttons and left/right keys also
select profiles. On mobile the detail column follows the portraits. Without script
enhancement, every profile renders with its own readable biography.

## Home chapter stack

Home chapters ship as a **sticky stack**: opaque full-height cards in increasing
stacking order. A short chapter holds at the viewport top while the next card climbs
over it. A tall chapter scrolls to its bottom before holding, so all text and actions
remain reachable on mobile. The stack is page-level CSS (`position: sticky` on
`.editorial-stack-card` under `.stack-ready`, with the measured header height), not a
scroll-craft engine device: it carries no `data-sc-act="pin"`, no `[data-sc-stage]` and
no `data-sc-span`, and it is never re-implemented with the pin device. Each chapter
container uses the shipped entry reveal (the view-timeline copy-in) where supported;
unsupported browsers and reduced motion keep the copy visible. Reduced motion returns
the stack to normal flow.

## Sticky navigation and portrait alignment

The white main navigation bar is sticky at the viewport top during scrolling. The
hero remains in normal document flow and scrolls away. The header's measured height
reserves space for anchor targets and short stacked chapters; tall chapters still
scroll fully before holding. The opaque header sits above the stack.

Team preview portraits align around the featured portrait's vertical center, with
centered image crops and visible names. Details move below the portrait rail at
tablet widths as well as mobile, so the preview names retain useful space.

Every non-hero chapter centers its complete content composition vertically within
the available viewport below navigation. Desktop program artwork, spacing, and
portraits scale with viewport height so copy and actions stay inside the card.
Chapters expand naturally for longer text and mobile; do not impose a fixed height
or crop readable text to manufacture a fit. The stack confines its hold to chapter
boundaries, while horizontal rails keep their own scrolling region.

The final consultation card uses a generous editorial heading, one clear request
button and three concise next-step explanations. When enhanced, the shared gray
footer becomes the final full-height card of the home sticky stack, retaining a
single footer instance and its navigation, social and legal links. Without
JavaScript it remains the normal page footer. Footer copy stays white on charcoal.
