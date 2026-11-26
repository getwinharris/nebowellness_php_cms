---
name: rea-wp-block-development
description: "Reconstruct typed reusable content sections and render contracts, not Gutenberg runtime."
---

# Native section/component development

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-block-development at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-block-development/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-02, cms-03, cms-04, cms-06, service-08, a2ui-02, a2ui-03, a2ui-05.

### Pinned REA source locators

- wp-native-extra : wp-includes/class-wp-block-type-registry.php
- wp-native-extra : wp-includes/class-wp-block-type.php
- wp-native-extra : wp-includes/class-wp-block.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-autosaves-controller.php
- a2ui : json/catalog_definition.json

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/MarkdownRenderer.php — function render(
- app/Services/BlogService.php — function save(
- app/Services/ResourceService.php — function revision(
- app/Services/CampaignPageService.php — function homepagePrograms(
- app/Services/CampaignPageService.php — function validateFields(
- assets/support-chat.js — isActionPath

## Trace the reference and current native behavior

- Read upstream block metadata, static vs dynamic render, attributes, inner blocks and migrations. Inspect original register, prepare_attributes_for_render and render symbols from the supplemental archive graph; follow metadata → validated attributes → render and saved content version.
- Locate existing CampaignPageService, safe Markdown renderer, views and editable form schema. A2UI catalog is a separate renderer protocol; reuse safe typed component data where appropriate rather than emitting block comments or executable JS.

## Native reconstruction contract

- Create data-only versioned section definitions (stable id/type, title/body/media, bounded typed attributes, allowed child composition, visibility, order). Render using allowlisted PHP partials and escaped output. Dynamic sections resolve authorized current records; static sections retain saved snapshot. Unknown/deprecated versions use explicit migrations or safe unsupported state.
- Editor works on draft/revision, preview cannot publish, optimistic conflict protection and server-side validation. No raw PHP/JS in authored content.

## Behavioral verification

- Round trip serialize/deserialize, validate unknown/nested fields, reorder stable IDs, migrate old fixtures, preview/publish/hide, stale revision and unsafe URL/HTML. Compare desktop/mobile actual UI and A2UI schema only where implemented.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No Gutenberg dependency, block-comment compatibility or npm production build.
