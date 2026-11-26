---
name: rea-wp-env
description: "Adapt reproducible environment setup and validation to the native stack."
---

# Isolated PHP/MySQL fixture environments

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-env at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-env/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-13, harness-09, completion-08.

### Pinned REA source locators

- wp-source : wp-admin/includes/schema.php
- wp-source : wp-admin/includes/class-wp-site-health.php
- deepseek : docs/subsystems/core.md

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes
- app/Services/AiClient.php — function probe(
- app/Services/ModelToolSet.php — interface ModelToolSet
- app/Services/ProviderProbeTools.php — function executeModelCall(
- app/Controllers/AdminController.php — function testAi(
- cli/ai-probe.php — ->probe(
- docs/research/rea/contracts/provider-diagnostics.md — ## Ownership and execution

## Trace the reference and current native behavior

- Read upstream wp-env configuration, local overrides, start/stop, test command and debugging sections as environment isolation patterns. Its Docker/Node workflow is reference-only. Trace native tests fixtures, schema migration, BAPX_TEST_MODE, local smoke server and MariaDB CI.
- Distinguish disposable DB, loopback provider, local browser, production configuration and hosting runtime. Record exact source commit, fixture version, PHP extensions and execution target.

## Native reconstruction contract

- Use existing PHP CLI, disposable database/schema and synthetic seed data; use explicit environment overrides scoped to subprocess. Keep production secrets out of fixtures and reports. Ensure deterministic cleanup of test-only resources; no new production runtime or global service install.

## Behavioral verification

- Run native full CI, actual HTTP boundaries, isolated DB concurrency and provider fixtures; verify test writes cannot reach remote DB. Record environment and skip reasons when tools unavailable.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No wp-env, Docker or WordPress installation requirement. Do not alter shared-host production configuration to satisfy a fixture.
