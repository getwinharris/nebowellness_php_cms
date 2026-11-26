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
- Native PHP/MySQL/vanilla JS, shared hosting, isolated customer installs.
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
