---
name: rea-wp-abilities-api
description: "Reconstruct versioned capability registration, category, discovery, permissions and adapters."
---

# Native ability registry and transport projections

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-abilities-api at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-abilities-api/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: mcp-01, mcp-02, mcp-03, mcp-04, mcp-05, mcp-06, harness-03, completion-04.

### Pinned REA source locators

- wp-source : wp-includes/abilities-api/class-wp-abilities-registry.php
- wp-source : wp-includes/abilities-api/class-wp-ability.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-list-controller.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-run-controller.php
- wp-mcp : Domain/Utils/SchemaTransformer.php
- wp-mcp : Domain/Utils/AbilityPermissionChecker.php
- wp-mcp : Domain/Tools/RegisterAbilityAsMcpTool.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/AgentDraftService.php — function draft(
- app/Services/AgentToolRegistry.php — function completionDeclarations(

## Trace the reference and current native behavior

- Trace registry registration → ability constructor/schema → list filtering → permission check → execute/result → transport projection; inspect version-pinned source and annotation references. In Nebo start with AgentToolRegistry::declarations/validateArguments/run/executeModelCall and ModelToolSet, not a second business registry.
- Separate canonical capability definitions from Chat Completions/Google schema projection, MCP tools/list/call, WebMCP and A2UI action binding. Keep domain-service results independent of HTTP wrappers; trace module and actor gates through invocation.

## Native reconstruction contract

- Define stable versioned names, category, input and output schemas, read/write/destructive/idempotent annotations, actor/module gate, domain handler, provenance, budgets and stable error codes. Discovery filters by permitted actor; recheck at execution, including row/field ownership. Do not expose arbitrary SQL, filesystem or private records.
- Migrate one existing read-only capability first, preserving current provider names and behavior. Keep diagnostics synthetic and independent. Use the audit and verify adaptations before broad registration.

## Behavioral verification

- Enumerate allowed names for guest/customer/admin and disabled modules; reject unknown/extra/malformed nested input and invalid output; compare native, Google and compatible projections; prove read-only via actual isolated DB before/after and trace successful source-grounded model round trip.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No WordPress functions, plugin loader, parallel duplicate registry, broad external MCP transport before authorization is proven.
