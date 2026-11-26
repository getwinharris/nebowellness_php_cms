---
name: rea-wp-patterns
description: "Reverse engineer reusable page composition and safe editor insertions."
---

# Reusable native content patterns

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-patterns at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-patterns/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-06, cms-07, cms-11, service-01, service-08, a2ui-06.

### Pinned REA source locators

- wp-native-extra : wp-includes/class-wp-block-patterns-registry.php
- wp-native-extra : wp-includes/class-wp-block.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/CampaignPageService.php — function homepagePrograms(
- app/Services/SettingsService.php — SETTINGS_ID
- app/Controllers/AdminController.php — function saveAppearance(
- views/layouts/admin.php — admin-sidebar__nav
- app/Services/CampaignPageService.php — function findPublished(
- app/Services/CampaignPageService.php — function validateFields(

## Trace the reference and current native behavior

- Read upstream patterns guardrails, registration/category, visual composition, tokens and anti-pattern references; inspect register/get_registered source. Distinguish reusable static template data, runtime record-driven sections, and an inserted independent draft.
- Follow current homepage and campaign templates, content owner controls and Design.md. Identify which native section types actually exist; do not label a static screenshot a reusable pattern.

## Native reconstruction contract

- Provide versioned data-only section presets (hero, image/text, stats, cards, FAQ, CTA, enquiry form) using allowed section types, tokens and local managed media. Clone preset data into a draft with fresh stable IDs; preserve references, ordering, accessible headings and visibility. Bind dynamic lists through approved services.

## Behavioral verification

- Verify registration/selection, insert/edit/duplicate/reorder, preview/publish, responsive composition, alt text/contrast/keyboard and empty/disabled dynamic sections; reject invalid types and unsafe raw markup.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No block markup, plugin registration or new commerce/catalog features; patterns cannot execute arbitrary scripts or payment actions.
