---
name: rea-wp-abilities-verify
description: "Verify schemas, permissions, annotations and side effects at actual boundaries."
---

# Adversarial ability contract verification

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-abilities-verify at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-abilities-verify/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: mcp-01, mcp-04, mcp-05, mcp-06, completion-04, harness-09.

### Pinned REA source locators

- wp-source : wp-includes/abilities-api/class-wp-ability.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-run-controller.php
- wp-mcp : Domain/Utils/McpAnnotationMapper.php
- wp-mcp : Domain/Utils/McpValidator.php
- wp-mcp : Domain/Utils/AbilityPermissionChecker.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/AgentDraftService.php — function draft(
- app/Services/AgentToolRegistry.php — function completionDeclarations(
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes

## Trace the reference and current native behavior

- Read upstream annotation-correctness, permission-roundtrip, schema-lints, runtime-harness and error-vocabulary references. Trace all reachable service calls from the execute callback, including indirect writes, audit, cron and provider calls. Static grep produces candidates, not proof.
- Compare declared readonly/destructive/idempotent flags to durable business changes, external side effects and replay receipts. Document intentional operational telemetry separately; never let a misleading read annotation authorize a write.

## Native reconstruction contract

- Validate stable schemas and result/error shapes at both preflight and runtime; actor identities must be derived server-side. For mutations require existing authorized confirmation and transaction/revision/idempotency boundaries. No hidden permission bypass through direct call or adapter.

## Behavioral verification

- Test allowed/denied actors, another customer record, disabled modules, wrong shape, unknown names, empty vs unavailable, bounds, duplicate/reordered IDs, before/after durable records and notification counts. Use real loopback and isolated MariaDB; verify output schema and privacy. Fail if only static proof exists for runtime claims.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No fabricated verification, no provider challenge credited as business ability acceptance, no broad production destructive probes.
