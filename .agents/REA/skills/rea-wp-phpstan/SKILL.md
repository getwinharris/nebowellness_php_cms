---
name: rea-wp-phpstan
description: "Adapt static typing and analysis to the existing PHP codebase without WordPress stubs."
---

# Native PHP type and static-contract review

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-phpstan at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-phpstan/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-02, cms-12, mcp-05, completion-04, harness-09.

### Pinned REA source locators

- wp-source : wp-includes/abilities-api/class-wp-ability.php
- wp-native-extra : wp-includes/rest-api/class-wp-rest-request.php
- wp-mcp : Domain/Utils/McpValidator.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/MarkdownRenderer.php — function render(
- app/Services/AuthService.php — class AuthService
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/AgentToolRegistry.php — function completionDeclarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes

## Trace the reference and current native behavior

- Read upstream PHPStan configuration, third-party class boundaries and baseline guidance. Inspect actual PHP version/extension support, autoload, function signatures and existing php -l and tests. Inspect input validation, nullable fields, JSON decode and error paths where type evidence matters.
- PHPStan is a possible developer-only analysis tool, not a runtime dependency. Do not install it or WordPress stubs just because the source skill lists them.

## Native reconstruction contract

- Prefer strict types for new native contracts, explicit types/shapes and bounded DTO-like arrays at external boundaries. Use actual provider payloads and declared storage fields; reject malformed values instead of casting away errors. Baselines/suppressions require specific justification and remediation plan.

## Behavioral verification

- Run php -l, affected behavioral/type-negative fixtures and full CI; if an existing static analyzer is available, record exact version/config and findings. Check null/array vs scalar/invalid encoding, unsupported schema keywords, exceptional returns. Do not call lint a comprehensive type proof.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No Composer or WordPress runtime requirement; no blanket ignoreErrors, generated fake stubs or claimed PHPStan pass without running.
