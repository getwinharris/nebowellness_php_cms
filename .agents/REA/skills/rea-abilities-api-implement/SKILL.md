---
name: rea-abilities-api-implement
description: "Adapt the WooCommerce implementation playbook to clinic service booking and shared native abilities."
---

# Native ability implementation from audited domain services

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of woocommerce/agent-skills / abilities-api-implement at 89cc969d2273ac9eb668c48b7284f77e0862bb21. Upstream copyright and license are retained in .agents/REA/reference/woocommerce/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/woocommerce/skills/abilities-api-implement/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: mcp-01, mcp-02, mcp-05, mcp-06, service-02, service-05, service-06, service-07, harness-06.

### Pinned REA source locators

- woo-source : src/Internal/Abilities/AbilitiesRegistry.php
- woo-source : src/Internal/Abilities/AbilitiesLoader.php
- woo-source : src/Internal/Abilities/Domain/AbstractDomainAbility.php
- woo-source : src/Internal/Abilities/Domain/OrderUpdateStatus.php
- woo-source : includes/class-wc-order.php
- wp-mcp : Domain/Tools/McpTool.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/AgentDraftService.php — function draft(
- app/Services/CampaignPageService.php — function validateFields(
- app/Services/TherapySessionService.php — function update(
- app/Controllers/TherapyController.php — function triage(
- app/Services/TherapySessionService.php — function matchesCapture(
- app/Controllers/TherapyController.php — function webhook(
- app/Services/TherapySessionService.php — function change(
- app/Services/SupportTicketService.php — function create(
- app/Services/AgentConversation.php — function recordEvent(

## Trace the reference and current native behavior

- Read upstream audit-precondition, first-ability-template, paginated-output-envelope, write-ability-template, test-public-contract and token-budget sections. Trace reference loader/registry/domain/permission and result projection. Require a current native ability audit and reverify backing service and actor gate; null backing is a gap, not an implementation.
- Compare three shapes: direct existing domain service (preferred), extracted shared service when logic lives in controller, reviewed low-risk read delegation only if no request-context/telemetry/side effects. Never route agent writes through HTTP controller wrappers.

## Native reconstruction contract

- Build one verified read capability first with typed output, explicit errors and bounded list envelope; migrate existing names safely. Add remaining read capabilities only with actual evidence and token/response budget measurements. Service booking/enquiry/landing/receipt writes require real domain state transition, actor/revision/confirmation, verified payment evidence and idempotent receipt.

## Behavioral verification

- Verify actual callable public contract with seeded representative data; zero/one/many pagination, schema, deny/allow/disabled module, cross-account, side-effect comparison, stale/duplicate writes, no hidden private fields. Measure real schema/result size and preserve prior flows. Run wp-abilities-verify adaptation before issue PR.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No general WooCommerce parity, extension scaffold, plugin lifecycle, cart/inventory/shipping/marketing expansion or vendor installation.
