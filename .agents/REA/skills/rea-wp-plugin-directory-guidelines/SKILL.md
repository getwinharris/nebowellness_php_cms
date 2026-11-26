---
name: rea-wp-plugin-directory-guidelines
description: "Use directory-guideline lessons for product release review, not WordPress directory submission."
---

# Native release policy, licensing and security review

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-plugin-directory-guidelines at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-plugin-directory-guidelines/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-12, cms-13, mcp-04, mcp-06, harness-09.

### Pinned REA source locators

- wp-source : wp-includes/capabilities.php
- wp-source : wp-admin/includes/privacy-tools.php
- wp-source : wp-admin/includes/class-wp-site-health.php
- wp-mcp : Domain/Utils/AbilityPermissionChecker.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/AuthService.php — class AuthService
- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- app/Services/AgentDraftService.php — function draft(
- tests/agent-harness.test.cjs — completion harness correlates native calls
- tests/agent-session.test.cjs — both chat routes

## Trace the reference and current native behavior

- Read upstream GPL compliance, naming, review checklist and plugin-check references; distinguish legally required notices from WordPress-directory-specific rules. Inspect native dependency/license inventory, privacy boundaries, source changes and release contract. Trace any external calls, executable content and user data handling.
- Reference archives retain their upstream GPL notices; do not transplant vendor runtime code into native product. Do not claim compliance certification from a checklist or copy another brand.

## Native reconstruction contract

- Record distribution/license provenance, source attribution, no hidden tracking or opaque execution, explicit user consent, least privilege, safe secret handling and bounded inputs. Verify scope and effect of writes, source review and deployment checks. Treat policy findings by impact and evidence; do not change license of unrelated native code.

## Behavioral verification

- Check exact diff and dependency inventory, permission/CSRF and privacy tests, no secret-bearing sample data or new executable vendor scripts, nonmutating CI and deployed fingerprint. Mark review-only findings vs repaired issues.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No directory submission, Plugin Check dependency, or generic legal compliance guarantee.
