---
name: rea-wp-interactivity-api
description: "Reconstruct server/client state and safe interaction boundaries for A2UI and existing UI."
---

# Native reactive state, events and hydration

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-interactivity-api at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-interactivity-api/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: a2ui-01, a2ui-02, a2ui-03, a2ui-04, a2ui-05, a2ui-06, service-02.

### Pinned REA source locators

- wp-native-extra : wp-includes/interactivity-api/class-wp-interactivity-api.php
- a2ui : docs/a2ui_protocol.md
- a2ui : docs/a2ui_custom_functions.md
- a2ui : json/renderer_to_agent.json

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- assets/admin-agent.js — buildDraftForm
- assets/support-chat.js — validActions
- assets/support-chat.js — isActionPath
- app/Services/CampaignPageService.php — function validateFields(

## Trace the reference and current native behavior

- Read upstream stores, directives, server processing and debugging. Follow process_directives and scoped state/context; distinguish initial server-rendered state, derived data, user event and async result. In native JS trace existing focused scripts (support-chat, admin-agent, blog-editorial, form builder) and server endpoints.
- Map A2UI surface lifecycle and renderer-to-agent event/function RPC to actual permission-checked abilities; separate UI state from durable application state.

## Native reconstruction contract

- Initialize minimal escaped server state, maintain component-local state and stable IDs, use explicit event handlers and bounded validated data updates. Allowlisted component catalog only; use request/turn correlation, stale response protection, clear loading/error/empty states and text fallback. Consequential actions require visible confirmation and server receipt.

## Behavioral verification

- Test initial render and subsequent state, stale/out-of-order response, duplicate events, hidden/unknown component, invalid data path, unsafe URL, denied operation, keyboard/focus and mobile overflow. Verify real backend mutation once, not just renderer behavior.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXcms update, ./bapXcms rea check, ./bapXcms rea skills-check and ./bapXcms ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No @wordpress/interactivity package, data-wp runtime or arbitrary model-generated executable markup.
