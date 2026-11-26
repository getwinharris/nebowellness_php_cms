---
name: rea-wp-plugin-development
description: "Reconstruct component boundaries, lifecycle, settings, security and scheduled operations without plugins."
---

# Native service/module lifecycle and migration

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-plugin-development at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-plugin-development/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-07, cms-12, cms-13, cms-14, service-08, harness-06.

### Pinned REA source locators

- wp-source : wp-admin/options.php
- wp-source : wp-admin/includes/schema.php
- wp-source : wp-includes/capabilities.php
- wp-source : wp-includes/cron.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/SettingsService.php — SETTINGS_ID
- app/Controllers/AdminController.php — function saveAppearance(
- app/Services/AuthService.php — class AuthService
- app/Services/MailQueueService.php — class MailQueueService
- app/Services/ReleaseIdentityService.php — function inspect(
- cli/release-identity.php — source_matched_revision_unknown
- docs/research/rea/contracts/release-identity.md — Reference evidence before design
- assets/admin-tables.js — document
- app/Services/CampaignPageService.php — function validateFields(
- app/Services/SupportTicketService.php — function create(
- app/Services/AgentConversation.php — function recordEvent(

## Trace the reference and current native behavior

- Inspect upstream structure, lifecycle, settings-api, data-and-cron and security references. Trace original settings and schema entry points, permissions and scheduled callbacks. In Nebo locate the existing route registry, domain service, MySQL collection, module gate and admin form; inspect side effects and legacy migrations.
- Distinguish initialization/migration, enabled/disabled, decommissioning and retention. Module disabled is not data deletion. Never introduce a second lifecycle or per-client hardcoded plugin.

## Native reconstruction contract

- Add native PHP service only when existing service cannot own the behavior. Use explicit schema version and idempotent migration, owner-managed configuration, CSRF and actor permission, scoped reads and transactional revision checks. Add bounded scheduled job receipts and retry semantics; leave destructive cleanup separate and confirmed.

## Behavioral verification

- Test new/old fixture migration, repeated migration, partial failure, disabled route/tool, reenable retaining data, unauthorized/CSRF, failed read does not wipe settings, scheduled duplicate/retry and rollback; document host constraints.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No plugin activation hooks, arbitrary extension installer, vendor PHP execution, or uninstall wiping customer data.
