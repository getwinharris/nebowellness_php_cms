---
type: doc
title: bapXcms standalone product boundary
description: Service-first white-label scope and separation from the retail commerce platform.
category: docs
---

# bapXcms: service-first CMS, CRM and configurable operations

## Evidence and separation

Owner decision 2026-11-26. The two independent repositories have their own
backlogs, releases and deployment roots. No Git upstream/synchronization or
runtime dependency is intended.

| Pinned source | Role | Indexed inventory |
|---|---|---|
| [Nebo baseline](https://github.com/getwinharris/nebowellness_php_cms/tree/9b3f0c2c736ebdcd25c1e894634dc162e451766e) | bapXcms, service-first | 137 routes, 68 services, 26 collections including published MCP grants |
| [Commerce reference](https://github.com/bapxmediahub/bapXphpAiBackend/tree/ffb65050b8348f38357688d829772d2a8fe8b392) | Commerce-led CMS and CRM | 189 routes, 93 services, 25 collections |

Read the generated project inventories and original AGENTS.md, route maps,
domain services and canonical schema to compare implementation. The reference
has retail product/cart/order, shipping, tax, merchant feed and remote
checkout; it also has authored pages and campaign booking. Nebo has native
clinic programme/enquiry forms, therapy session triage/payment, owner CRM,
consultation fees, shared typed abilities and public MCP. Neither inventory
proves full white-label or production acceptance. Previous pinned comparison
and official REA references are historical source evidence only.

## Target product architecture

- CMS: canonical installation profile (name, logo, favicon, contacts, theme
  tokens), navigation and page/section/FAQ/form definitions. Draft, preview,
  publish, revision/restore, responsive rendering, media and SEO.
- Services: owner-defined service catalogues, enquiry/lead intake, assignments,
  appointments, availability, status transitions, verified service fees,
  payment receipts and approved communications. Preserve the actual Nebo
  service workflows while generalizing their labels and configuration.
- CRM: contacts, customer timelines, lead pipelines, tasks, follow-ups,
  support inbox and configurable typed fields; role and record ownership.
- Optional ERP: approved extensions such as resource allocation, projects,
  staff tasks, quotations, service invoices and operational reporting. These
  are design direction, not implemented features. No retail dependency.
- Agents: the shared typed ability registry and explicit actor scope; genuine
  model-led tools; A2UI renders catalog-constrained components. Owner-approved
  configuration/content proposals can change safe page sections, fields,
  layouts, theme tokens and workflows. Preview diffs, validate schemas,
  require current revision and confirmation, record receipts/audit, and
  support rollback. Never execute AI-generated PHP/JS or arbitrary SQL.
- Native PHP/vanilla JS, existing remoteDB JSON-compatible collection contract, shared hosting and isolated customer installs.
  Installer/provisioning follows verified native core. No paid runtime
  dependency, mandatory external orchestrator, vector database or vendor
  runtime. Existing customer appearance and service behavior are protected.

## Retirement boundary

Remove retail shop/product/cart/checkout and retail admin routes, modules,
UI/navigation, ability definitions, CLI commands, and unreachable implementation
in issue-scoped changes. Distinguish retail payment paths from verified therapy
payments and service receipts. Keep old collections/records private until an
explicit archival and migration decision with inventory, recoverable backup,
and owner authorization. Do not drop tables or delete customer data simply
because their runtime is retired. Keep original research evidence and record
new acceptance separately.

## CLI and acceptance

The repository owns only the executable ./bapXcms and cli/bapXcms. The
native skill is .agents/skills/bapxcms-cli. Generated developer navigation
remains under .agents/. GitHub issues, not this contract, are the live backlog.
Renaming is independent of actual retirement; the latter must be proven with
route/permission, API, agent and browser regressions, including preserved
service payment/booking/CRM behavior and exact deployed-source verification.

## Retail runtime retirement (#165)

The service application now registers 106 routes, down from 137. Retail public/API/admin routes, product drafting, product CLI executables and six retail abilities are removed from the active surface. A legacy shop setting is always false and is not configurable. The remaining twelve typed internal abilities and the six published MCP reads remain available according to their existing permission and module gates. Historic receipt access stays owner-scoped and read-only. Historical source files and schema collections remain for separately reviewed archival cleanup; no production table, credential or record is deleted by this change.

## Native site identity (#172)

The canonical settings record supports a versioned site identity with a
private saved draft, field preview, confirmed publication and prior-version
restore. The published projection supplies public/admin shells, contact
identity, default SEO, discovery and manifests. The Nebo reference profile
is deployment-specific; unconfigured independent installations remain
neutral. Existing appearance and phone/WhatsApp contracts remain in place.
General page content, navigation ordering, policies, form builder and
installation packaging are separate acceptance work.

## Storage and verification boundary (#186)

The existing remoteDB API owns all business records, including users, CMS,
CRM, settings and media metadata. Preserve the serving backend; do not
provision another database engine or copy production records into local
fixtures. Explicit BAPX_DB_MODE=remote bypasses the local SQL connection
probe and rejects a self-referential endpoint. JSON row envelopes must
decode as real records. Blogs use Markdown/YAML; agent instructions and
active state use private MD/YAML/JSON files and PHP sessions. The historic
durable agent transcript archive still uses remoteDB and requires a
separately reviewed preservation/migration contract.

Default CI uses PHP and JS, file fixtures and the existing client exercised
against a disposable loopback remoteDB contract. Historical direct-engine
integration suites remain in the repository and retain their earlier
evidence but are not rerun by this gate. This is a deliberate test
architecture change, not proof that the fixture reproduces server-side
SQL locks, payment processing or every business operation. Extend the
contract coverage issue by issue; use an authorized isolated endpoint for
serving-side acceptance. Never substitute a production customer record.

## Private agent archive boundary (#192)

Newly started conversations archive authorized visible turns in versioned
JSON files in a per-installation directory outside both the application
and document root. The current PHP-session prompt window remains separate.
Old active v1 conversations retain their original remoteDB archive until
reset or idle expiry; historical records remain readable and are not
copied or deleted by this change. Users, tickets and CRM remain remoteDB
records, linked by the stable conversation ID. Owner history merges
bounded metadata from both sources and reports partial availability.
