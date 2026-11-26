---
name: rea-woocommerce-stale-pr-audit
description: "Adapt the fixed-cohort audit to the native repository and avoid replaying or discarding unique work."
---

# Evidence-backed issue and stale PR reconciliation

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of woocommerce/agent-skills / woocommerce-stale-pr-audit at 89cc969d2273ac9eb668c48b7284f77e0862bb21. Upstream copyright and license are retained in .agents/REA/reference/woocommerce/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/woocommerce/skills/woocommerce-stale-pr-audit/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-13, harness-09, completion-06, mcp-01.

### Pinned REA source locators

- wp-source : wp-admin/includes/class-wp-site-health.php
- deepseek : packages/core/session/src/repair.ts

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes
- app/Services/AiModelRouter.php — function recordFailure(
- app/Services/AiClient.php — function post(
- app/Services/AgentToolRegistry.php — function declarations(
- app/Services/AgentToolRegistry.php — function validateArguments(

## Trace the reference and current native behavior

- Read upstream workflow and audit-schema; distinguish a frozen cohort from ordinary single-PR review. Fetch canonical main, record exact SHA, freeze issue/PR membership with URLs and timestamps. Read issue intent, review threads, branch diff, merge/squash patch equivalence and actual current source. Do not infer from age or labels.
- Compare affected flow on current main against PR if safe and runnable; use existing single checkout, preserve unique uncommitted/ignored evidence and use Git ancestry/patch identity instead of destructive cleanup.

## Native reconstruction contract

- Produce structured records: intended outcome, current state, evidence links, baseline head, tests executed, remaining unique patch, risks, recommendation and uncertainty. Separate already delivered, superseded, still needed, needs owner decision and unable to verify. Keep recommendations distinct from actual closure/merge.

## Behavioral verification

- Recheck live PR state and head immediately before reporting or authorized mutation; inspect review dismissal/CI as needed, never claim stale branch test as main proof. Verify counts and no lost unique work. Reopen issues when acceptance is incomplete.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No automatic closure/merge of upstream PRs, no deleting dirty branches or worktrees; no use of upstream scripts, no expansion of WooCommerce product scope.
