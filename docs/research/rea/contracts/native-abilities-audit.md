---
type: doc
title: Native abilities audit and first reference implementation
description: Source-backed migration from existing agent tools into a typed shared capability contract.
category: docs
---

# Native abilities audit (issue #148)

## Baseline and reference evidence

Baseline Nebo main 954d892c5af4d246abb75dd485e351d0b1a775e7.
Native REA skills selected: rea-wp-abilities-audit, rea-wp-ability-development,
rea-wp-abilities-api, rea-abilities-api-implement, rea-wp-abilities-verify.

Read the exact archived WordPress 7.1.4 class-wp-ability.php and
class-wp-abilities-registry.php and WooCommerce 11.3.0 AbilitiesRegistry.php
and AbstractDomainAbility.php; all four selected source hashes match the
pinned REA inventories. WP_Ability::execute performs input normalization,
validation, permission checks, domain execution and output validation.
WooCommerce's domain ability base delegates to shared domain helpers;
the MCP adapter separately checks exposure and permission before execution.
The official skill audit/verify guides require actual backing-service,
side-effect, output and permission checks, not copying a REST controller.

## Current invocation boundary

SupportController::ask → SupportBotService::answer constructs the support
registry with context scoped to the authenticated user. AdminController
constructor enforces admin authentication and CSRF for POST; agentAsk
constructs the admin registry. Neither model chooses the registry scope.
AgentToolRegistry::declarations ranks the role/module-filtered catalog;
AiClient translates it to Google or Chat Completions. executeModelCall
validates arguments, dispatches run, bounds output and records the model-origin
trace. Previously it accepted any non-error array as a successful result,
without a typed output contract.

This is a static audit of the internal tool set. No HTTP/MCP exposure,
write authorization or runtime-read-only proof is granted merely by listing
a tool. Verify each migration against the actual backing path and data.

| Existing model tool | Scope / module gate | Backing service or operation | Output / risk to resolve |
|---|---|---|---|
| published_consultation_fees | support and admin; independent | ConsultationFeeService::published, canonical settings | Native v1; exact monetary units and duration, published-only; missing settings unavailable |
| published_practitioners | support and admin; independent | ClinicKnowledgeService::practitioners → ConsultantService::all | Native v1; visible-only typed fields, explicit malformed-source error and fixed source URL |
| published_programmes | support and admin; campaigns | ClinicKnowledgeService::programmes → CampaignPageService::published / homepagePrograms | Native v1; published and eligible homepage cards, bounded typed projection and module recheck |
| published_programme_detail | support and admin; campaigns | ClinicKnowledgeService::programmeDetail → CampaignPageService::findPublished | Native v1; exact slug, draft/ambiguous miss, bounded body and typed errors |
| search_clinic_knowledge | support and admin; source-specific module gates | ClinicKnowledgeService::search/rank | Lexical public-only result, source mix and per-source failure handling; bounded typed excerpts pending |
| search_articles | support and admin; blog | AgentToolRegistry::searchArticles → BlogService::all(false) | Stable list shape for empty/nonempty, Unicode search, URL validation pending |
| clinic_context | support only | SupportBotService::toolContext → scoped AgentContextService and current session cart | Mixed public and owned data; split scopes and prove field/record ownership before typed exposure |
| owner_dashboard_summary | admin only | AgentContextService::forAdmin | Free-text aggregate and hardcoded currency; use typed authoritative metrics and source dates, not persona text |
| appointment_summary | admin only | AgentToolRegistry::summariseAppointments | Historical only; stable count/status map and scope |
| recent_orders | admin; shop | OrderService::all | Bounded projection; preserve shop, not an expansion |
| catalog_categories | admin; shop | DatabaseService categories read | Bounded typed list; preserve existing shop |
| find_order | admin; shop | AgentToolRegistry::findOrder / summariseOrder | Customer email input and shipping city/tracking output; privacy/ownership review, stable empty shape |
| product_status | admin; shop | ProductService::all | Offer window, stock, hidden records; stable bounded result |
| sales_summary | admin; shop | AgentToolRegistry::salesSummary | Existing period/date and currency assumptions need correction before typed exposure |
| support_enquiries | admin only | SupportTicketService::all and topic aggregation | Empty shape differs, topic inference and IDs need bounded stable aggregate |
| campaign_pages | admin; campaigns | DatabaseService campaign_pages | Draft and published owner projection, strict slug/status/URL |
| therapy_enquiry_summary | admin; campaigns | AgentToolRegistry::summariseTherapyEnquiries | Aggregate only, explicit date range and valid slug |
| coupon_status | admin; shop | AgentToolRegistry::couponStatus | Preserve existing business rules and bounded result; not new commerce |

All legacy tool names stay unchanged until migrated, and no legacy tool
is presented as a completed typed native ability. ProbeTools remains an
isolated synthetic diagnostic, not a business capability. No arbitrary SQL,
runtime scripts, file paths, external action or writes are registered.

## First implementation: published_consultation_fees v1

The canonical service is already used by the public Contact view and the
owner settings flow. NativeAbilityRegistry defines version, category,
existing provider name, input/output schema, read-only/destructive/idempotent
annotations and trusted surface/module scope. Discover/inspect filter the
native catalog; invoke rechecks scope and any required module, validates
arguments, calls the domain service directly, validates output and enforces
the existing 48,000-byte result budget. Return stable safe error codes
alongside an error message; never use a successful empty result for a
failed or missing settings read. An existing configured empty catalogue
is valid; drafts and internal fields are never returned. No provider tool
name or successful payload is changed.

AbilitySchema is an intentionally limited, bounded native subset, not a
complete JSON Schema implementation. Only the types/keywords needed by
these audited native definitions are accepted. Provider projections keep
the existing compatibility behavior. The domain fee normalizer enforces
cross-field currency, minor-unit and duration rules. The metadata
inspection methods are internal PHP methods; external authorization and
MCP remain future issues.

## Acceptance and evidence

- RED: the old actual internal fee call in test mode returned a successful
  empty result when the canonical settings row was absent.
- GREEN: tests/native-ability.test.cjs verifies unavailable result and
  error trace without credited evidence, exact version/annotations,
  invalid input, unknown scope/name and invalid extra output fields.
- tests/consultation-fee-integration.php: real disposable MariaDB
  draft/publish/unpublish, exact USD amount and inclusions, stale revision,
  independent scopes, unchanged database before/after each read,
  strict schema, and missing settings versus configured empty.
- Existing native provider fixture, editorial and agent regression suites
  must pass. Test the actual production Contact and Support routes after
  exact deployed-source verification. A provider outage is not a tool
  contract failure or proof of successful final inference.

This first reference ability established the migration pattern. Issue #150
migrates three further public read capabilities with the same contract.
mcp-01 and mcp-05 remain partial; external discovery/transport and full
catalogue migration are separate obligations.

## Second implementation: published clinic reads (issue #150)

- Use existing ClinicKnowledgeService and CampaignPageService for the
  practitioner list, homepage programme list and exact published detail.
  Move the old detail implementation out of the agent registry; preserve
  the provider function names and existing success keys.
- Public field types and bounds are checked before conversion; missing
  optional text is empty, malformed source is unavailable. Hidden
  practitioners and draft programmes stay excluded. Unknown/draft/ambiguous
  exact detail returns ability_not_found, without guessing a record.
- Campaign availability is checked on discovery and again at invocation
  against canonical settings. The published list uses the existing homepage
  eligibility and ordering rules. The body remains the existing bounded
  text projection (up to 12,000 characters), not an unbounded raw document.
- Native input constraints are authoritative. The provider declaration
  adapter projects only the established supported input vocabulary;
  server validation rejects malformed slug/extra fields regardless of
  the provider's schema support.
- RED/GREEN: tests/native-ability.test.cjs verifies catalog membership,
  disabled discovery and execution, invalid slug, no record, empty
  list, Google and compatible declarations. CI executes seeded MariaDB
  tests/native-public-ability-integration.php for both surfaces, publication,
  visibility, exact schema, missing/draft/ambiguous record, malformed source,
  stale module gate and unchanged durable state.

Next migrate public lexical search and blog lookup, then separate public
clinic context from owned account data and type the owner aggregate
capabilities. External MCP remains gated on verified catalogue/actor
contracts. Do not expose legacy untyped functions to external clients.
