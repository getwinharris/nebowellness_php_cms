---
name: rea-blueprint
description: "Reconstruct portable setup recipes and isolated fixtures; do not install WordPress Playground."
---

# Declarative native environment blueprints

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / blueprint at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/blueprint/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-07, cms-13, service-08.

### Pinned REA source locators

- wp-source : wp-admin/includes/schema.php
- wp-source : wp-admin/options-general.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/SettingsService.php — SETTINGS_ID
- app/Controllers/AdminController.php — function saveAppearance(
- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- app/Services/CampaignPageService.php — function validateFields(

## Trace the reference and current native behavior

- Read the upstream Blueprint sections for preferredVersions, resources, steps, bundles and testing. Distinguish a declarative schema from an arbitrary command runner. The Playground implementation itself is not in the current REA archive; use its documented contract only, or acquire an exact source version before claiming equivalent behavior.
- Trace native bootstrap/config, storage/schema/collections.php and CLI test setup; identify installation-specific fields and existing seeded fixture ownership. Map dependencies and preconditions for ordered steps, including schema initialization and failure recovery.

## Native reconstruction contract

- Use versioned data-only fixture definitions for synthetic site profile, page sections, forms and settings; prohibit arbitrary PHP, SQL, shell, URLs and secret values. Dry-run validation precedes explicitly authorized initialization. Make steps deterministic and repeat-safe; record skipped/failed/complete outcomes.
- The eventual installer is deferred. Implement fixture recipes only within approved issues; do not add a competing deployment runtime or new setup endpoint.

## Behavioral verification

- Validate missing/unknown fields, unsupported versions, duplicate execution, interrupted step and rollback; compare two differently branded isolated fixtures without source edits. Never seed a real clinic database for a test.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No Playground bundle installer, wp:false runtime adoption or new /install feature until approved core acceptance.
