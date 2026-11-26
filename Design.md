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

Nebo Wellness is a naturopathy and functional medicine clinic platform. The primary customer journey is discover programs -> learn about consultants -> book consultation -> manage wellness journey. Blog content and health resources support that journey. This file is the canonical visual contract for public and admin UI. `assets/css/band.css` is the stylesheet for shared tokens, layout, component states, and public art direction. Public and admin layouts share this file and serve their Inter and Playfair Display fonts locally.

SHA Wellness is the primary reference for the homepage's editorial hierarchy, section transitions, and horizontal program and practitioner layouts. Use secondary references to evaluate individual principles such as the placement of a primary action, a phone crop, or the spacing between a heading and its cards. Apply those principles to Nebo's services, media, content, and controls. The frontend skill defines how to compare and verify the result.

Keep the existing PHP templates, routes, forms, and hosted MySQL-backed behavior. Design changes must not scaffold a second frontend.

## Colors

- **Primary blue (`#4472C4`)** is the shared action and navigation color. Use white on primary blue and the lighter blue container for its active state. The homepage hero and closing consultation actions use charcoal.
- **Secondary -- wellness green (`#70AD47`):** supporting accent, success states, nature/health emphasis, secondary CTAs. Represents natural healing and vitality.
- **Tertiary -- soft green (`#8cc069`):** tertiary accents, active underline, eyebrow labels, subtle dividers.
- **Surface (`#f7f9fa`) / surface-container (`#edf3f6`):** cool gray canvas and alternate-section background. White is reserved for cards, form surfaces, and deliberate image breaks.
- **Outline / outline-variant:** hairline borders only -- no heavy strokes.
- **Ink / ink-muted / ink-soft:** body text hierarchy from primary copy down to placeholders.
- Success uses green (#70AD47), warning, error, info are semantic exceptions for state communication.

## Typography

- Body: Inter (300-700) with system sans-serif fallbacks.
- Display headings: Inter, weight 300-400, with tight tracking for large editorial
  headings and predictable breakpoint sizes. Operational page headings may use 600.
- Use Playfair Display at 600 italic for short accents in editorial headings, eyebrows, and value-card titles. Use Inter for body text.
- Body copy: `14px`-`16px`, weight 400, line-height `1.45`-`1.6`.
- Labels/metadata: `12px`-`14px`, weight 500-600.
- Keep default letter spacing in body copy and controls. Large editorial headings may use tighter tracking. Reserve tracked uppercase for short metadata and compact header labels.

## Layout

- Spacing scale: `2, 4, 8, 16, 24, 32, 48, 64px`.
- The standard centered container caps at `1300px`. Wide layouts can use `1440px`. Homepage chapter containers can expand to `1720px`, including their gutters. Use the same content edge across chapters, with `20px` gutters on phones.
- `.section`: `64px` vertical padding by default. `.section--alt` uses `surface-container`; `.section--warm` uses the warmer `#edf3f6` for value sections.
- Responsive breakpoints: mobile below `744px` (one column, compact header, navigation in Menu), tablet `744-1128px` (reduced grid columns, same card geometry), desktop above `1128px` (centered container, `64px` section spacing).
- Text, buttons, images, and fixed controls must not overlap or reflow awkwardly as content length changes.
- Use explicit breakpoint sizes for controls and body text. Editorial headings may scale within bounded sizes. Check their wrap and the primary action on short mobile screens.
- On the homepage, keep the primary consultation action visible in the first viewport. Make the transition into the Foundation clear on the first scroll. Shop pages lead to products when enabled and account pages lead to the user's current task.
- Page sections are unframed full-width bands. Cards are reserved for repeated entities, forms, summaries, and genuinely bounded tools. Never place a card inside another decorative card.
- Desktop operational screens use compact density and stable columns. Mobile screens use one clear column with 16px page gutters and no horizontal scrolling.

## Elevation & Depth

Depth is used sparingly and tonally-first, the way Material 3 treats elevation: a surface reads as "raised" primarily because it's a different, lighter tone than the canvas (white card on warm surface), with a soft shadow as the secondary cue -- not a spotlight effect.

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
- No glow, no colored shadows, no blur-heavy "spotlight" effects.

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

- **Header:** soft white, pinned to the viewport top, with a hairline bottom border and the complete client-supplied Nebo logo centered. The drawer repeats this logo as a home link; account/cart actions remain right aligned.
- **Home hero (editorial)** uses the Nebo veranda illustration with an Inter headline and a short Playfair italic accent. Use a charcoal reading surface and keep the consultation action prominent. Present Programs as a quieter text link. On phones, carry the artwork behind the heading with a scrim and retain the focal point. Use the client’s full specialty-led subheadline and two actions. Keep the complete composition still and readable with reduced motion.
- **Program rail** uses equal-height scroll-snap cards with subject-specific illustrations, duration, program name, factual description, and an enquiry link. Show four complete cards at 1440px when unfiltered and part of the next card on phones. Each arrow has a minimum 44px target and advances one card plus the gap. Keep keyboard and touch navigation available. Use one short staggered entrance and a subtle lift on the current card. Keep content specific to Nebo’s programs.
- **Therapy products:** published campaign pages are the public Products catalogue. The homepage gives the newest record a large image and session-request action, followed by two quieter older entries. The catalogue uses the same hierarchy for all published offerings. On phones the featured therapy comes first in a single column. Preserve existing campaign URLs. Drafts never have a public route or sitemap entry. A request does not confirm a session.
- **Proof points:** Display measured outcomes only when Nebo can substantiate the numbers and define the measurement period and source. Otherwise use concrete service details without invented percentages.
- **Team/Consultant rail.** Use photographs when supplied and typographic initials for
  homepage profiles with a missing or default photo. Keep names, credentials, a concise
  biography, and one consultation action visible in the active profile. Manage photos
  through Admin → Consultants and the media picker. Align the bottom of the selected
  profile and smaller previews.
- **Testimonial Cards:** Use the client-approved testimonial copy from the project source document. Present it as a quiet two-column quote grid with the supplied attribution; do not invent outcome claims or missing attribution details.
- **Section Dividers:** Use generous whitespace (`64px` - `96px`) between major sections. Alternate between canvas and `surface-container` backgrounds for visual rhythm, inspired by SHA's clean sectioning.
- **Content Cards:** More rounded (`12px-16px`) than the old design. Generous internal padding. Clear hierarchy with headings, body text, and CTAs.
- **Navigation:** the linked brand mark and name are the home control. Keep mobile page links in Menu. The public header uses a compact Phone and WhatsApp disclosure with the two confirmed clinic numbers and 44px link targets. Use JavaScript for fragment-based active state and synchronize it on initial load, hash changes, and browser history navigation.
- **Mobile commerce tray:** after the cart becomes non-empty, show one fixed blue tray above the device safe area with item count and a direct View cart action. Use an 8px radius and stable 56px minimum height; update it without page reload.
- **Floating support stack:** on desktop, align the support circle directly below the cart tray at the same right edge. On mobile, keep Support and the cart tray side by side above the device safe area. Reserve enough space to reach the final page action and keep the open support panel clear of the navigation.
- **Customer Support chat:** use a deep blue identity bar, a separate scrollable transcript and a compact composer with Send next to the message field. Keep suggested questions, clinic contact and the signed-in staff handoff available. The Support page launches the same panel and restores focus on close. Keep interactive targets at least 44px and keep the composer above the phone keyboard.
- **Editorial media:** every blog post uses one intentional 16:9 image for both its listing thumbnail and article hero. UI guides use a legible screenshot of the exact page, cropped around the relevant interface rather than a decorative stock image, and link the represented page below the article. In the Journal, give the lead image approximately half the card on desktop and place it above the text on phones. Give each entry one article link, with its category and date visible.
- **Buttons (`button-primary` / `button-secondary`):** `48px` minimum height, `8px` radius, no uppercase, no letter-spacing. Primary is solid blue with a darker-blue hover; hover moves from `shadow-md` to `shadow-lg` and lifts 2px, no more. Secondary is solid green with white text. Hover states never shift layout.
- **Forms:** white fields (`on-primary`), `8px` radius, `48px` height, clear labels, a single-value focus ring (`--shadow-focus`) -- no glow.
- **Contact and consultation enquiries:** introduce the page with a short editorial heading, labelled Nebo imagery, and links to the form, phone, WhatsApp and email. Use the confirmed clinic contact destinations; the direct actions remain available before the mobile image. Pair the form with open ruled clinic details on wide screens and put the details after the form on phones. Keep the form as the only elevated surface, with a visible keyboard focus outline. Preserve subject and practitioner prefill, field order, and verification. Keep success and error messages visible at the form after submission.
- **Search/filter:** one rounded (`pill`) search control, or a quiet grouped filter row. No nested cards for filters.
- **Product cards (`product-card`):** linked image/title, short description, price, Buy Now and one stateful cart control. Initially show Add to Cart; adding one unit replaces it in place with `− quantity +`. The number is the actual cart quantity. Each press updates the cart immediately; decrementing the final unit restores Add to Cart. Keep 44px targets, native form fallback, visible focus and disabled controls during requests. Do not show a separate Quantity row or redundant in-cart link.
- **Cart quantity:** provide `−` and `+` controls beside every product, update line totals and the cart count, and remove the line when decreased to zero. Repeated additions of the same product merge into one line. Distinguish selecting a quantity to add from editing the quantity already in the cart.
- **Commerce responsiveness:** use `minmax(0, 1fr)` for purchase-button columns, wrap quantity labels when space is tight, and keep keyboard focus visible. Verify home, shop, product and cart at 375px and desktop in the built-in Browser. Do not shrink purchase controls to 30px to force them onto one row.
- **Consultant cards (`card-consultant`):** white, `8px` radius, face-forward portrait, name, speciality, language/experience metadata, review summary when present, and one clear profile/booking action. Every card uses equal media and content tracks so rows align.
- **Section rhythm** begins with the Foundation introduction, figures, vision, and pillars. Programs follow. Use the same Foundation, vision and pillars copy on About. Present Our Focus Areas as four care topics with links into the separate Programs catalogue. Use the [home motion score](#home-motion-score) for the later chapters, including the image-led care areas. On phones, keep each care principle's copy below its title. About pairs the story heading and consultation action with a labelled tropical-garden illustration. Keep the action before the image on phones.
- **Authentication:** login and registration are task pages, not marketing pages. Use a centered form surface, suppress the public footer, and keep the complete form visible on common mobile heights.
- **Product details:** keep the short introduction, feature bullets, detailed description, and labelled specification rows in separate readable sections. Admin-created products use the same fields and layout as existing products; never paste table markup into a plain-text description. Omit empty sections instead of fabricating claims.
- **Account:** use a persistent internal menu and one unframed content region. Orders, addresses, and installation are tasks, not promotional cards.

- **Admin:** optimize for scanning and repeated action: compact sidebar, clear tables, consistent forms, explicit save state, and no marketing-style hero composition.
- **Enterprise Agent:** place the conversation and task starters beside the operations rail on desktop. Stack the rail after the composer on phones. New chat clears the current conversation and restores starters. Report recorded runs, error rate and p95 duration over 24 hours. Links open existing admin workflows. Agent summaries distinguish requests from confirmed bookings and exclude identities and form answers.
- **Admin platform guidance:** describe the product-led public journey consistently. Historical service records may remain owner-accessible, but integration help must not advertise retired public booking, text sessions, or direct calls.
- **Admin form sizing:** use zero-minimum grid tracks and shrinkable labels/controls. Long integration endpoints or credentials must stay within the field, never widen the page. Check 375px with populated fields without exposing their values in screenshots or reports.
- **Admin product preview:** render saved images and structured content through the existing product-detail layout. Clearly label the view as an admin-only preview, disable purchase controls, and provide Back to products. Hidden products remain absent from public listings and ordinary URLs; previews are uncached and not indexed.
- **AI request status (both chat surfaces):** immediately show a keyboard-operable, collapsible Thinking… row and elapsed seconds while waiting. Expanded content reports request status only, never raw model reasoning or simulated stages. Use the shared white/blue component, 44px summary targets, tabular time digits, and an indeterminate progress control. Respect reduced motion, prevent duplicate submits, restore the composer on every completion path, and retain a collapsed Response ready or Request failed record beside the answer. Timing includes network/server time and must not imply model success or accuracy.
- **Waiting and loading:** only show a loading state while a real request is pending. Keep the user's submitted message visible; do not replace real catalog content with decorative skeletons. Errors and fallbacks must remain distinguishable from successful model responses in monitoring.
- **Value-proposition cards outside the homepage editorial chapters:** 4-column desktop / 2 tablet / 1 mobile, white card, warm icon circle, `accent-italic` heading, muted body, `4px` hover lift into `shadow-lg`.
- **Footer:** charcoal gray (`#2f3437`) field with white headings and links, soft white
  body text, direct clinic contact details, and verified Instagram, Facebook, and
  YouTube links. The credit links to `https://mediahub.bapx.in/`. Avoid duplicate
  navigation and retired devotional routes.
- **Mail:** transactional email uses the full clinic name, cool-gray surfaces, white
  type on blue or charcoal headers, an 8px blue action, and “Owner notification” for
  admin-only mail. It must not describe Nebo as a store or use legacy colour names in
  customer-visible copy.
- **Scroll motion:** establish the complete static layout first. The Nebo home page
  lets the Foundation introduction cover the briefly pinned hero, then uses
  content-led sections in document order. Copy uses a quiet copy-in, not alternating sideways,
  scaling, bouncing, or looping effects. A card can have one entrance and one selected
  state only. The important item moves first, total stagger stays below 500ms, and
  `prefers-reduced-motion` removes transforms and smooth scrolling. Never animate
  layout dimensions, loop ambient movement, or hide content when script execution
  fails.
- **Documents and guides:** Markdown-backed pages use the same warm canvas and a constrained reading column. The page header is centered and quiet; the content surface is white with a single soft border, 14px-20px radius, and `shadow-sm`. Use blue `h2` headings, muted body text at 1.6-1.7 line-height, generous section spacing, and green only for eyebrows, links, and small metadata. Documentation indexes use a two-column desktop grid and one-column mobile layout with clear titles, summaries, and a visible `Read guide` action. Do not render legal or customer documentation as long unstructured text or nested cards.

### Nebo editorial treatment

Use the Scroll Craft guidance by Nate Herk for composition and interaction states. The homepage takes visitors from arrival and orientation through program comparison and practitioner selection to a consultation. Let visitors compare programs through duration, title, and copy at a glance. Reserve the strongest selection change for the featured practitioner and their consultation link.

Use cool paper and deep blue ink across these surfaces. Blue remains the shared action color, with charcoal for the homepage hero and closing consultation actions. The public art-direction rules derive paper and rules from the configured primary palette where `color-mix()` is supported. Ink, action, and focus colors use fixed contrast-safe blues. Hairlines and spacing group related content. Give program titles more scale than their duration and description, and remove decorative numbers and gradient panels. Maintain a visible offset focus outline on rail controls and form fields. Without JavaScript, show native scroll and consultation links without enhanced arrow controls. Preserve the reduced-motion rules in `band.css`.

## Do's and Don'ts

- **Do** keep the canvas cool and light (`surface` / `surface-container`) and use white cards, inputs, and modals for focus.
- **Do** let a hover state move exactly one elevation step and/or lift by a few pixels -- nothing more theatrical.
- **Do** use `accent-italic` (Playfair Display) sparingly, as a decorative highlight.
- **Do** keep shape consistent per component family (see Shapes).
- **Do** use sentence case and default tracking for routine buttons and headings. Keep tracked uppercase for short metadata and compact header labels.
- **Don't** add glow, colored blur, gradients-as-decoration, or stacked shadow tiers.
- **Don't** introduce a second frontend, second routing scheme, or a component library that bypasses the existing PHP templates.
- **Don't** copy reference-product navigation labels or routes that don't exist in this app -- keep the real routes.
- **Don't** let hover/focus states shift layout or reflow siblings.
- **Don't** hide essential content behind entrance animations. Motion is optional enhancement and must never control legibility.
- **Don't** use decorative gradients, oversized pills, or large-radius containers to manufacture hierarchy. Use spacing, typography, borders, and real media.
- **Do** keep policy, legal, customer, and internal guide content in Markdown/YAML sources and render it through the shared document surface so copy changes do not require template rewrites.

## Verification

- Check the affected flow in the built-in Browser before and after editing. Use 1440×1000, 375px wide, and both sides of any affected breakpoint. Check public pages at 390×844 as well. Include `/`, `/shop`, a product, `/cart`, `/login`, and authenticated account pages when changes touch the shared public layout.
- Confirm image crops, active navigation, focus states, card alignment, and visibility of essential content during reveals. Check that the page stays within the viewport, the primary action is visible on mobile, and the first scroll clearly leads to the next chapter.
- Use the fixed development customer created by `bapXphp dev:user`; its password must come from `BAPX_TEST_USER_PASSWORD` and must never be committed.
- Include applicable empty, populated, disabled-module, and error states. Check keyboard focus, touch targets, safe-area spacing, reduced motion, and core content and form fallbacks with JavaScript disabled. Use synthetic data. Redact credentials and personal data from evidence before attaching it to the issue. Identify any unrun checks. Run `./bapXphp ci` before review.

## Implementation Notes

- Define static tokens, responsive rules, and visual states in `assets/css/band.css`. Migrate existing rules from inline styles in cascade order, remove superseded rules, and verify computed styles in public and admin layouts. Keep server-configured palette values as validated CSS custom-property values.
- PHP supplies semantic markup, initial state, and native links and forms. JavaScript handles fragment state, cart updates, dialogs, and motion where browser behavior requires it. Reuse existing JavaScript assets or add a focused deferred script under `assets/`. Use CSS for breakpoint behavior and preserve the native path when enhancement is unavailable.
- Elevation tokens: `--shadow-sm/md/lg/xl` plus `--shadow-focus` for the single focus-ring definition.
- Motion: `--transition-spring` (a restrained expressive-motion curve, `cubic-bezier(0.34, 1.4, 0.64, 1)`) is used for hover lifts on buttons and cards; `--transition-base` still governs color/shadow fades.
- This file can be linted against the open spec with `npx @google/design.md lint Design.md` if you want machine validation of token references and section order.

## Promotion enquiry forms

Each campaign has an on-page enquiry band with a charcoal surface, white copy,
clear field labels and a two-column editorial composition. At 375px it becomes a
single column. Admin controls form heading, introduction, submission button,
confirmation copy and phone/message visibility and required state. Name and email
remain required. Enquiries retain campaign attribution in the existing inbox.

## Team selection composition

The team module uses a clipped profile track on the left and a selected biography
column on the right. Previews show practitioner names and their configured photographs.
Use typographic initials for empty and default photo values. Render an optional owner-supplied professional registration number with the selected credentials and role; never manufacture a portrait or registration detail. Exactly one profile is
large, with its description and consultation action in the detail column. Size that
column for the longest current biography at each width so profile selection keeps
the section height steady.
Arrows cycle one practitioner at a time; portrait buttons and left/right keys also
select profiles. On mobile the detail column follows the portraits. Without script
enhancement, every profile renders with its own readable biography.

## Editorial scroll and hero handoff

Keep native scrolling and a short hero hold under the header so the charcoal
Foundation introduction arrives on the first scroll. The homepage order is hero,
Foundation, Programs, Team, client stories, scroll statement, care areas, clinic figures,
and the closing chapter. End that chapter with the resources split directly above
the footer. Show featured Services above the resource split when published campaigns
are available.

Set the statement as two deliberate lines in one accessible heading. The scroll
effect moves a shared boundary through the background and the ink while the text
stays in place. It reverses on upward scroll. Keyboard flow, reduced motion, forced
colours and unsupported browsers show the complete heading in normal ink on pale
paper. The statement adds no focus target.

### Home motion vocabulary

Use these terms consistently in product copy, implementation notes, and code review:

- **Arrival:** the hero at the start of the visit. It establishes the clinic and
  its primary action before the next chapter scrolls over it.
- **Act:** one meaningful visitor state in the home sequence. Nebo stages the hero handoff and lets later chapters flow in document order.
- **Copy-in:** a short upward entrance triggered once as a chapter enters view.
  Essential copy remains opaque and ends in a still composition.
- **Content composition:** the complete group of heading, media, supporting copy, and
  action within an act. Center the hero copy within its stage and let later
  compositions follow the content and reading order.
- **Selection rail:** the horizontal program or practitioner control. Arrow actions
  advance exactly one item. The rail never auto-advances.
- **Featured profile.** The selected practitioner card is larger than the previews.
  Previews retain the practitioner name while the biography and
  action live in the separate detail column.
- **Handoff:** a useful, explicit movement from a selected program or practitioner to
  a consultation action. It must arise from a user selection, not an automatic prompt
  or a generic quiz.
- **Resolve:** the final consultation act and the full footer act. Both are calm,
  complete end states rather than banners floating in empty space.
- **Motion brief:** the written statement of audience, journey, intent, feeling curve,
  peak, visitor action, owned assets, content priority, visual world, and accessibility
  constraints before a new scroll sequence is built. Identify a self-authored brief.
- **Feeling curve:** the planned emotional sequence of arrival, grounding, clarity,
  confidence, trust, evidence, and resolve. It guides emphasis; it is not text that
  appears on the site. Give each act a distinct emotion and on-screen cause; document
  any reason for repeating a feeling across adjacent acts.
- **Grammar:** the reusable structural rule for a page, including navigation, sequence,
  ending, and explicit bans. Nebo home uses its own editorial scroll rhythm
  because the product requires persistent navigation.
- **Device:** one restrained scroll mechanism, such as a copy-in, media crop, selection
  rail, or sticky stack. Use only the devices needed by the story; the clinic brief
  overrides a cinematic four-device quota.
- **Signature move:** one Nebo-specific, purposeful interaction that connects a real
  user choice to the next step. It must be designed and tested before it is added.
- **Fingerprint:** the combination of hierarchy, type, colour, motion, and interaction
  that makes the implementation recognisably Nebo rather than a cloned reference.
- **Motion score:** the act-by-act table of feeling, visitor learning, device, pacing,
  and purpose. It is reviewed for hierarchy, continuity, restraint, accessibility, and
  cross-device behaviour before a motion change is approved.

The vocabulary is a Nebo-specific adaptation of the Scroll Craft method, not a copied
vendor page or a requirement to use its engine. Keep the sticky white navigation as a
product requirement. It is deliberately above the editorial stack and its measured
height defines their safe top offset.

### Scroll Craft planning terms

Use the following source-method terms when they clarify the work. They are planning
and review language, not permission to import a vendor runtime or copy another site's
visual system.

- **Journey:** the ordered shifts in what a visitor understands. An act that produces
  neither a new understanding nor a new feeling is removed rather than animated.
- **Peak:** the single memorable contrast moment. Nebo's peak is the selected
  practitioner becoming the featured portrait and revealing a direct consultation
  handoff; it is preceded by quieter programme choice.
- **World:** the genuine visual material and art direction used by an act. Nebo uses
  supplied clinic photography, portraits, and its blue/green identity; generated clay,
  low-poly scenes, fabricated outcomes, and generic wellness imagery are prohibited.
- **Layer contract:** a written account of the necessary visual planes, their depth,
  movement, and copy-safe area. Only add a plane when genuine source media supports
  it; never duplicate a subject merely to imitate parallax.
- **Anchor:** the intended copy or focal-media placement in an act. Re-anchor mobile
  deliberately; do not uniformly shrink desktop composition.
- **Cue:** semantic, selectable heading, copy, or action that remains readable at
  every supported scroll position. A cue never relies on a timed opacity state.
- **Close pattern:** the way the final act resolves. Nebo resolves through one
  consultation action and the complete charcoal footer, never an empty fade-out.
- **Feel check:** a cold scroll with one felt word recorded for each act, compared to
  the planned feeling curve before approval.
- **Fingerprint gate:** compare grammar, navigation, hero/world treatment, act
  sequence, close pattern, and signature move against relevant prior work. Change the
  plan when it feels derivative; do not rename a pattern to pass the gate.

### Home motion score

- **Arrival.** Set the hero in Inter 300 with a short Playfair italic accent. The image settles behind the headline, the headline and supporting copy rise in sequence, and the actions fade in place. In browsers with scroll-driven animation support, dim the outgoing artwork as the Foundation enters. The actions remain available throughout.
- **Foundation.** Pair the clinic introduction with a two-by-two ruled proof grid: optional owner-confirmed historical customer minimum (with +), published program pathways, four displayed focus areas, and visible practitioners. Omit unavailable record counts rather than showing zero. Use original decorative line icons and one count-up per figure as it enters view; keep the suffix visible during animation and in the accessible final value. Vision and three shared icon-led pillars follow, aligned to the same columns; stack on narrow screens. About reuses these pillar icons.
- **Programs.** Align the first image with the heading and use distinct artwork for each program.
  Give copy 16px desktop and 12px phone insets. Keep filters and arrows on one row.
  On phones, let the filters scroll independently while the arrow controls remain visible
  and at least 44px. Use 32px desktop and 16px phone gaps.
- **Team.** The selected profile grows relative to named previews, then the biography
  updates beside it. This is the signature move: the practitioner's consultation link
  opens an enquiry with their name in the editable message. The same link works in
  each profile without JavaScript.
- **Stories.** Place attributed quotes on a dark surface beside an illustrative scene. On phones, the image precedes the quotes. The scroll statement follows the stories.
- **Care areas and clinic figures.** Use a large photographic lead and three supporting entries on wide screens. Keep each heading, description, and filtered program link together. Stack them in the same reading order on phones. Follow the care entries with a workplace-wellness section for corporate teams. Use original editorial artwork that shows yoga, sound healing and group sound baths in a work setting. Link to the clinic’s published campaigns and preselect Corporate Wellness in the existing enquiry form. Follow with labelled counts for practitioner profiles, consultation steps, and featured services when present.
- **Resolve.** Show the consultation invitation, then pair a short list of clinic resources with an illustrative image directly above the charcoal footer. On phones the footer stays in normal flow so contact and legal links are fully visible.

### Home motion quality gate

- Start with semantic PHP content that is readable with JavaScript and CSS motion
  disabled. Enhancement may add classes, never remove information.
- Use scroll-linked opacity and transforms for artwork. The statement clips its
  ink gradient to one heading. Keep layout dimensions and grid tracks steady during scroll.
- Use one directional copy-in per act. Do not alternate card directions simply to
  create movement, and do not add decorative scroll counters, fake progress, or
  autoplaying rails.
- Count up from zero once for labelled figures based on Nebo's own lists. Leave the
  final values readable with JavaScript off or reduced motion on. Keep an identical
  static number available to screen readers throughout the animation.
- Keep every horizontal card's text in its own card with `min-width: 0`, wrapping and
  clipping of decorative art only. Copy, labels, controls, and touch targets must not
  be clipped.
- Respect reduced motion by disabling the sticky stack and all entrance transforms.
  Keyboard, touch, arrows, and manual horizontal scrolling must continue to work.
- Verify the whole sequence at desktop and 375px: header stays sticky, the statement sweeps in both directions, one arrow click changes one rail item, the featured
  portrait remains distinct, no text overflows, and the footer becomes the final layer.
- Run the feel check after visual verification. Confirm that the selected portrait is
  the one engineered peak, the quieter programs act precedes it, and the resolve holds
  as a complete end state rather than trailing into unused page space.


## Sticky navigation and portrait alignment

- **Menu:** align the close control and logo with the closed header. Use a compact primary pane with a separate panel for program filters and blog topics on desktop. On phones the second level slides in from the right, with Back above its links. Keep consultation visible below both levels. Use native disclosures when JavaScript is unavailable.

The homepage hero holds for a short handoff. The hold is shorter on phones and absent with
reduced motion. The measured header reserves space for anchors. Later sections
remain in document flow and the opaque header stays above the content.
Team preview portraits align with the foot of the featured portrait. The biography
starts at the top of the rail. Keep centered image crops and visible names. Details move below the portrait rail at
tablet widths as well as mobile, so the preview names retain useful space.

Later sections size to their content, with separate vertical padding at each
breakpoint. Desktop program artwork and portraits retain their own responsive
geometry. Let sections expand for longer text or phone layouts. Section boundaries contain decorative
motion, while horizontal rails keep their own scrolling region.

The final consultation card uses a generous editorial heading, one clear request
button and three concise next-step explanations. The shared charcoal footer closes the desktop stack. On phones and without JavaScript, it stays in normal flow. The footer retains its navigation, social, contact, and legal links.

### Therapy campaign detail

Use the published campaign's title, summary and media in a split hero with one
prominent enquiry action. On phones the title and action precede the image. Keep
the configured program details distinct from the next steps, and repeat the form
link there after the visitor has read the details. The enquiry band pairs brief
guidance with a light form card, stacked on phones. The in-page links use the
shared header offset and the configured form fields stay in their saved order.
The Services index opens each program at its overview. Its latest published
program leads in a wide feature, followed by smaller program cards below. Use
the configured campaign image when supplied, with subject-specific Nebo artwork
for programs that still use the legacy shared image.

### Mobile homepage reading order

At widths below 744px, use a shorter hero hold and allow the statement to wrap
without reducing its readable area. Later chapters grow with their content.
Without JavaScript, with reduced motion, or during keyboard navigation, the
hero scrolls in normal flow. Programs uses a stationary anchor so the menu can jump there from below the chapter. Use a two-column grid for labelled figures, stack the care and resource imagery, and keep compact therapy and testimonial cards. Use a two-column footer for Clinic and Explore. The menu keeps Programs and Blog reachable. Footer contact and legal links stay above the cart and Support controls.

### Homepage hero, services and footer

Use the illustrative veranda as a wide backdrop on desktop and carry it behind the heading on phones. Frame the crop around the people and keep the headline and actions readable over the charcoal scrim. The optional Services block places the featured image on the left and additional image-led service cards on the right at desktop widths. On phones the feature comes first and the additional cards can be swiped or reached with arrows. Keep the campaign links and the section anchor. The charcoal footer begins with a contact action and groups clinic, reading, and contact links, with legal links last.
