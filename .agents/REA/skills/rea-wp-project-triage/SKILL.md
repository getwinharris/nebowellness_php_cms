---
name: rea-wp-project-triage
description: "Classify source, runtime surfaces, data ownership and gaps before engineering."
---

# Native repo triage and evidence inventory

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-project-triage at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-project-triage/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-06, cms-13, mcp-01, harness-03, harness-09.

### Pinned REA source locators

- wp-source : wp-admin/includes/schema.php
- wp-source : wp-includes/abilities-api/class-wp-abilities-registry.php
- deepseek : docs/subsystems/core.md

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/CampaignPageService.php — function homepagePrograms(
- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes

## Trace the reference and current native behavior

- Read upstream triage output schema and detector approach. Use generated index/map/schema list and Git status/worktree/issue history, then inspect exact originals. Identify repo identity, runtime PHP/JS, routes, modules, DB collections, provider transport and UI assets; do not run upstream detector scripts.
- For unfamiliar reference areas use installed REA import-reference-source on bounded pinned source, record hashes and limitations, and only then inspect symbols and dependencies.

## Native reconstruction contract

- Produce structured inventory: repo/head, working tree status, actor/surface, source locators, current native behavior, missing dependencies, scope/issue, needed reference skill and acceptance tests. Differentiate static findings, unknowns, and real failures.

## Behavioral verification

- Check index pointers resolve and source aligns, no stale branch patch replay, no duplicate worktrees; compare current source with original reference before coding. Run ./bapXcms rea skills-check and capability register validation.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No WordPress project classification assumptions, automatic code scaffold, or broad scans/rewrites of unrelated customer projects.
