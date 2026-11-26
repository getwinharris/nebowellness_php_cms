---
name: rea-wp-performance
description: "Measure actual route, query, cache, scheduled job and provider cost; adapt Site Kit reporting."
---

# Native performance measurement and optimization

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-performance at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-performance/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-13, cms-14, sitekit-02, sitekit-03, sitekit-05, sitekit-06, sitekit-07, completion-06, harness-07.

### Pinned REA source locators

- wp-source : wp-admin/includes/class-wp-site-health.php
- wp-source : wp-includes/cron.php
- archive-addendum : includes/Modules/PageSpeed_Insights.php
- sitekit-files : includes/Core/Email_Reporting/Cron_Health_Check.php
- deepseek : packages/session/session-telemetry/src/coordinator.ts

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- assets/admin-tables.js — document
- integrations/google-site-kit/GoogleSiteKitClient.php — function analytics(
- integrations/google-site-kit/GoogleSiteKitClient.php — function search(
- assets/measurement-consent.js — document
- app/Services/AiModelRouter.php — function recordFailure(
- app/Services/AiClient.php — function post(
- app/Services/AgentRunTrace.php — function provider(
- app/Services/AgentRunTrace.php — function usage(

## Trace the reference and current native behavior

- Read upstream measurement, database, autoload-options, object-cache, HTTP, cron and server-timing references. Trace real native route → DB reads → template/assets → external requests. Review request-local caching, indexes and provider trace timings before adding caches or retries.
- For Google reporting inspect pinned Site Kit service modules and report freshness, OAuth/property selection, PSI mobile/desktop and consent. Keep synthetic response measurements separate from real authorized data.

## Native reconstruction contract

- Record cold/warm identical scenario, data size, queries, HTTP status, latency and source revision; optimize measured bottleneck with bounded cache, indexes, batching or timeout. Cache keys include actor/filters and revision, writes invalidate; never cache private content publicly. Schedule only bounded native cron jobs with idempotency. No invented CrUX/analytics values.

## Behavioral verification

- Compare reproducible before/after runs and correctness for empty/error/stale/access-denied states. Check DB query counts, request deadlines, memory, layout, browser console and exact live source; document sample sizes and limitations rather than claiming speedup from one run.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No WP-CLI profiling plugin, Redis requirement, new background process or paid service; no synthetic analytics shown as live.
