---
name: rea-wp-ability-development
description: "Define stable caller-oriented input/output and recovery semantics before registration."
---

# Design typed domain operations

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-ability-development at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-ability-development/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: mcp-01, mcp-05, mcp-06, service-05, service-06, harness-06.

### Pinned REA source locators

- wp-source : wp-includes/abilities-api/class-wp-ability.php
- woo-source : src/Abilities/AbilityDefinition.php
- woo-source : src/Internal/Abilities/Domain/OrderUpdateStatus.php
- wp-mcp : Domain/Utils/AbilityArgumentNormalizer.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/AgentDraftService.php — function draft(
- app/Services/TherapySessionService.php — function update(
- app/Controllers/TherapyController.php — function triage(
- app/Services/TherapySessionService.php — function matchesCapture(
- app/Controllers/TherapyController.php — function webhook(
- app/Services/SupportTicketService.php — function create(
- app/Services/AgentConversation.php — function recordEvent(

## Trace the reference and current native behavior

- Read upstream task-oriented schema, success shapes, partial update, error and side-effect sections. Trace existing domain service and actor boundary. Identify known inputs (stable record reference) versus server-derived values (fees/status), and scope read fields.

## Native reconstruction contract

- Prefer one clear input contract with bounded nested objects, explicit required/optional/null semantics, additionalProperties false, and stable success/error envelopes. Empty result is successful only if the source read succeeded. Distinguish omitted field from explicit clearing. Return authoritative computed facts and revision, never arbitrary data dumps.
- Write proposals and receipts are separate from execution; recheck permissions and current revision on execution. Report partial/unknown side effects honestly, use idempotency keys for repeat-safe actions.

## Behavioral verification

- Exercise zero/one/many results, bad nested properties, null versus omission, stale edits, exact currency/minor units, same receipt on retry, denied access and unavailable data. Check Google/compatible schema support and MCP/A2UI projections without broadening the contract.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No direct model writes or price guessing, no unsupported schema keywords hidden by adapter coercion.
