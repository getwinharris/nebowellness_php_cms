---
name: rea-wp-playground
description: "Separate reference research, local execution, WebMCP and actual live acceptance."
---

# Isolated native execution and browser verification

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-playground at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-playground/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-13, harness-09, mcp-02, mcp-03, a2ui-06.

### Pinned REA source locators

- wp-source : wp-admin/includes/class-wp-site-health.php
- wp-mcp : Transport/HttpTransport.php
- a2ui : docs/sdks_spec.md

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes

## Trace the reference and current native behavior

- Read upstream Playground CLI, website, Sites API, MCP and WebMCP references according to intended operation. The Playground runtime is not part of the native product; inspect archived protocol/source for the selected boundary. Trace native PHP server, route, local fixture and actual deployed origin.
- Use official Browser for interactive checks when available, existing authorized local Chromium fixture for isolated evidence; do not claim that inspecting HTML means an action ran.

## Native reconstruction contract

- Use disposable PHP/MySQL and synthetic records to validate actual workflows, not a mock WordPress site. Route external tools through discovered authorized schema and native services. Record environment, revision, target origin, actor and proof of actual invocation; no public write unless explicitly authorized.

## Behavioral verification

- Run same scenario against fixture and real deployment where permitted; capture response/status and browser error/overflow, check source fingerprint, exact data state and role denials. Distinguish simulated renderer from actual tool execution.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No Playground installation, external site snapshot containing customer data, unapproved share links or new hosting service.
