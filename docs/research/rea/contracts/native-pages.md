# Native typed page documents

Issue #176; part of #125/#121. REA skills: rea-wp-block-development and
rea-wp-patterns. Read-only WordPress 7.1.4 reference archive, SHA-256
a147146c159362778ecbbbfb07bb20ddf4e85b130f971c824e656a8f938c5f87.
Inspected block type registry, block type, block and pattern registry
members; SHA-256 respectively:
- a68ae144da84c63ce6ca91475348a5f9b772e2eabf2a6d01a487186b93085d3c
- bcb3456bb186462ebc804f70998bf276c46d22a2b6433428f5c83f06370be09a
- b0b563352170d509b09bca49632f263bfdeb21697d835e203b2e22525d15c588
- 5e6bdde7d5f9031f33434544bd5d5e3a74026e5c729b96e0adb37d8146abf645

## Contract

Canonical MySQL collection cms_pages; immutable slug (primary key) reserves
the /pages/{slug} namespace without replacing existing clinic routes.
Create is insert-only, including bridge callers. Each record has a version,
private draft, published document, and preceding publication for rollback.
A document has a title, description, and at most twenty ordered sections.
Hero, rich text, image/text, FAQ, and CTA have exact allowlisted content
fields. Section IDs are generated server-side; native editing actions
reorder, duplicate, remove and hide sections without permitting a client
to forge identity or type. Local raster media paths and public HTTPS
destinations are validated. Markdown uses the existing sanitizing renderer;
raw HTML, arbitrary JS/PHP and custom executable templates are unsupported.

The owner-only admin editor uses existing CSRF, locked revision comparison,
and audit metadata. Publish acts on saved content only. Unpublish removes
public access; restore swaps the prior publication and clears any draft.
The same PHP partial renders visible saved sections for preview and public
pages. Sitemap includes published pages only. A confirmed absent page collection contains no authored pages: existing
sitemap and media reference scanning stay available and unknown authored
pages return 404. Uncertain storage failure still reports temporary
unavailability and blocks unsafe archive decisions.

## Verification and scope

Unit tests exercise lifecycle, section identity and order, shape/path
validation, safe rendering and visibility. Six local Chromium fixture
scenarios at 1440, 375 and 390 px exercise both shared document views,
private preview, native submitter serialization, FAQ expansion, overflow
and browser errors. Disposable MariaDB CI exercises
actual owner and public routes, duplicate create, stale edits, confirmation,
authorization and sitemap. Schema installation is an explicit idempotent
migration, not an anonymous page request. On a hosted installation the
owner can initialize only cms_pages via Pages, with CSRF and confirmation;
setup never replaces existing records. Do not create synthetic content
in production. No homepage conversion, dynamic service list, reusable
shared forms, navigation editor, scheduled publish, autosave or agent
mutation is claimed here. General CMS and revision coverage remain partial.

## Rollout and media boundary hardening (#180)

Readiness uses an actual schema-bound SELECT and recognizes only the
MySQL missing-table SQLSTATE 42S02 / error 1146 as absence. Other read
failures remain unavailable. The authenticated bridge projects an explicit
boolean from the same direct check. Owner-confirmed initialization uses
the configured database path and the existing canonical initializer.
No production initialization was attempted by this engineering follow-up.

Native page edit/publication/restore and reversible media archive use
the same database-scoped advisory lock on the serving database; bridge
calls dispatch the operation there rather than trying to carry a lock
across HTTP requests. Newly referenced managed paths and new public
references must have a unique active inventory record. Reference scans
retain published, draft and preceding-publication paths. Legacy non-CMS
writers and explicitly privileged raw DB operators do not participate
in this lock, so archived files remain at their original URL.
The loopback bridge and concurrent isolated MariaDB fixtures exercise
absence, malformed schema, authorization, setup, competing edits and
archive, and deterministic archive-first/draft-first protection.

## Live published service sections (#184)

The page vocabulary adds programmes, team, and fee_table. An authored
section stores a heading, optional introduction and a display limit of
1–12 (default six), not copied business records or arbitrary queries.
CmsDynamicSectionService resolves only the existing public
published_programmes, published_practitioners and
published_consultation_fees native abilities under the support scope.
Output schema is validated again before bounded rendering; repeated
sections reuse only request-local results. Current module gates apply.
Disabled programme sections are omitted publicly and labelled in owner
preview; empty records and unavailable/invalid data have distinct states.
Fee values use the canonical display amounts, durations, inclusions and
enquiry-only scope, never a payment or booking action. The common preview
and public renderer uses escaped fields and native validated source links.

Source-derived block render reference: WordPress 7.1.4 class-wp-block-type.php
and class-wp-block.php rechecked against the exact pinned hashes in the
original native-pages contract. Static authored content and live record
projection stay separate. No existing page or installation data is
migrated. Database-backed route fixtures exercise current records, limits,
draft/hidden exclusions, module disable, source edits without republish,
empty and malformed sources. Responsive no-DB fixtures exercise the
shared preview/public states; no production page was created to test.
