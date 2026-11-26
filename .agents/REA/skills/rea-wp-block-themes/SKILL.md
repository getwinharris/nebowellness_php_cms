---
name: rea-wp-block-themes
description: "Trace configuration precedence and template parts for reusable PHP layouts."
---

# Canonical site profile, templates and design tokens

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-block-themes at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-block-themes/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-06, cms-07, cms-11, service-08, a2ui-06.

### Pinned REA source locators

- wp-native-extra : wp-includes/class-wp-theme-json-resolver.php
- wp-native-extra : wp-includes/class-wp-block-patterns-registry.php
- wp-source : wp-admin/site-editor.php
- wp-source : wp-admin/options-general.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/CampaignPageService.php — function homepagePrograms(
- app/Services/SettingsService.php — SETTINGS_ID
- app/Controllers/AdminController.php — function saveAppearance(
- views/layouts/admin.php — admin-sidebar__nav
- app/Services/CampaignPageService.php — function validateFields(

## Trace the reference and current native behavior

- Inspect upstream theme.json settings/styles, templates, template parts and variations. Trace get_core_data/get_theme_data/get_user_data/get_merged_data to understand layered defaults versus owner overrides; do not assume copied theme JSON is native configuration.
- Follow native SettingsService, site appearance, header/footer and Design.md/band.css. Locate scattered identity, contact, colours, logo/favicon and menu data; identify canonical ownership and fallback semantics.

## Native reconstruction contract

- Separate allowed settings from renderer styles. Merge safe product defaults with owner-controlled site profile and per-page overrides using an explicit documented precedence. Reuse PHP layout parts, stable token names, media choices and versioned drafts; preserve clinic-approved content. Do not add arbitrary CSS/JS or theme installation.

## Behavioral verification

- Two differently branded fixture profiles render same templates; explicit clearing and absent values differ; header/footer/navigation and favicon match configuration; preview/publish/rollback, accessible desktop/mobile, no leaked defaults from another client.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No WordPress Site Editor, theme install or arbitrary theme code; installer remains deferred.
