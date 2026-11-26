---
name: rea-wp-wpcli-and-ops
description: "Adapt safe targeting, backups, dry runs, cron and release verification to bapXcms."
---

# Native CLI and deployment operations

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-wpcli-and-ops at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-wpcli-and-ops/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-07, cms-13, sitekit-07, completion-08, harness-09.

### Pinned REA source locators

- wp-source : wp-includes/cron.php
- wp-source : wp-admin/includes/class-wp-site-health.php
- wp-source : wp-admin/includes/schema.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/SettingsService.php — SETTINGS_ID
- app/Controllers/AdminController.php — function saveAppearance(
- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- app/Services/AiClient.php — function probe(
- app/Services/ModelToolSet.php — interface ModelToolSet
- app/Services/ProviderProbeTools.php — function executeModelCall(
- app/Controllers/AdminController.php — function testAi(
- cli/ai-probe.php — ->probe(
- docs/research/rea/contracts/provider-diagnostics.md — ## Ownership and execution
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes

## Trace the reference and current native behavior

- Read upstream targeting, automation, safety, search-replace and cron/cache guidance. Trace native cli/bapXcms, schema migration, release verifier, audit events and mail queue. Confirm canonical checkout and deployed origin before running a command.
- Separate harmless local reads from production state mutations. Preserve exact source and backup/rollback evidence; do not use copied credentials or raw SQL from a reference skill.

## Native reconstruction contract

- Use existing bapXcms commands for map/schema/CI/release:verify, provider diagnostics and authorized domain operations. Plan dry-run and affected fields for migrations; recheck schema and revision. Never flush caches or replace arbitrary text on production by default.

## Behavioral verification

- Verify CLI exit codes and actual output (not HTTP 2xx alone), repeated execution, wrong environment, unavailable source/DB, and post-deploy revision/fingerprint and affected flow. Document no-op/failure honestly.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No WP-CLI, multisite conversion, arbitrary command runner, production bulk search-replace or installer until approved.
