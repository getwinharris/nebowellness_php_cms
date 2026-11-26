---
name: rea-wp-abilities-audit
description: "Derive a structured audit of semantic domain operations and actual gates before scaffolding."
---

# Audit the native route-to-capability inventory

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-abilities-audit at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-abilities-audit/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: mcp-01, mcp-02, mcp-05, mcp-06, harness-03.

### Pinned REA source locators

- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-list-controller.php
- wp-mcp : Abilities/DiscoverAbilitiesAbility.php
- wp-mcp : Abilities/GetAbilityInfoAbility.php
- wp-mcp : Abilities/ExecuteAbilityAbility.php
- woo-source : src/Internal/Abilities/Domain/AbstractDomainAbility.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/AgentDraftService.php — function draft(

## Trace the reference and current native behavior

- Read upstream controller-enumeration, capability-gate-tracing and audit-schema references. Enumerate existing routes/services using project-index, actual route definitions, controller methods, schema collections and current tool declarations. Follow inherited guards and domain permission checks, including row ownership and module state.
- Group by caller intent, not one ability per endpoint. Record semantic operation, source symbol, backing service, input/output projection, exact authorization, side effects (DB, file, mail, payment, cache, analytics), replay behavior, data classification and evidence SHA. Flag unbacked candidates and exclusions.

## Native reconstruction contract

- Produce a machine-readable JSON audit in docs/research/rea/contracts with stable ability ID, current source anchor, owner, intended caller, scope, permission source, schema shapes, annotation rationale, dependencies and fixture cases. Include expected empty versus unavailable and explicit approval where needed. Link capability IDs and issues; never scaffold a null-backing operation as a fake answer.

## Behavioral verification

- Cross-check all audited candidates against current route/registry at implementation time, confirm actual gates, reject missing source/permission fields and count missing/denied separately. Representative tests must use isolated data, not production customer records.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No general WooCommerce parity; no auto-exposing every admin endpoint, no guessing from REST names or static counts.
