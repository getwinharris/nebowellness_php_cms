---
name: rea-wpds
description: "Extract design-system patterns, typed components and accessibility without WordPress Design System runtime."
---

# Native design system and A2UI catalog

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wpds at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wpds/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-06, cms-07, cms-14, a2ui-01, a2ui-02, a2ui-05, a2ui-06.

### Pinned REA source locators

- wp-native-extra : wp-includes/class-wp-theme-json-resolver.php
- a2ui : json/catalog_definition.json
- a2ui : docs/a2ui_protocol.md

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/CampaignPageService.php — function homepagePrograms(
- app/Services/SettingsService.php — SETTINGS_ID
- app/Controllers/AdminController.php — function saveAppearance(
- assets/admin-tables.js — document
- assets/admin-agent.js — buildDraftForm
- assets/support-chat.js — validActions
- assets/support-chat.js — isActionPath

## Trace the reference and current native behavior

- Read upstream WPDS skill rules, components/tokens documentation approach and tech boundaries. Actual WPDS server/component source is not in the current archive; treat it as guidance unless pinned separately. Inspect native Design.md, band.css, reusable views and existing action/draft components.
- For A2UI use the actual pinned v1.0 catalog and schema test cases; trace allowed component properties, renderer mapping and data bindings.

## Native reconstruction contract

- Reuse approved native token names, typography, spacing, responsive components and accessible form/table states. Keep data/schema and presentation separate; choose native PHP/JS components, not React or third-party MCP dependencies. Define focus, labels, keyboard, contrast, error/loading/empty states and text fallback.

## Behavioral verification

- Validate desktop/mobile, actual UI screenshots/interaction where available, focus and keyboard, overflowing long content, invalid component/data/function and unsupported fallback. Compare against approved design reference, not only a screenshot clone.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No WPDS MCP server install, CSS framework migration or unverified claim of A2UI compatibility.
