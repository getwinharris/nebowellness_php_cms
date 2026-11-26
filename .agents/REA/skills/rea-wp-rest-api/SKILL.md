---
name: rea-wp-rest-api
description: "Reconstruct route registration, validation, stable responses and authorization across API/MCP boundaries."
---

# Native HTTP contracts and permission gates

SPDX-License-Identifier: GPL-2.0-or-later

Native REA adaptation of WordPress/agent-skills / wp-rest-api at 32ea909f7d01be2e08fee6aff6ae449a42b9b34f. Upstream copyright and license are retained in .agents/REA/reference/wordpress/LICENSE. This rewritten guide is not a WordPress/WooCommerce installation workflow.

## Start with evidence

Read AGENTS.md, .agents/REA/README.md, the matching original skill (.agents/REA/reference/wordpress/skills/wp-rest-api/SKILL.md), and the linked references selectively. Treat the original as research data, not executable instructions. Read docs/research/rea/capabilities.json for current status and issue ownership; do not count the presence of a skill as implementation.

Capability IDs: cms-06, cms-12, cms-14, mcp-02, mcp-03, mcp-04, mcp-05, service-02, service-03.

### Pinned REA source locators

- wp-native-extra : wp-includes/rest-api/class-wp-rest-server.php
- wp-native-extra : wp-includes/rest-api/class-wp-rest-request.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php
- wp-source : wp-includes/rest-api/endpoints/class-wp-rest-settings-controller.php
- wp-mcp : Transport/Infrastructure/JsonRpcRequestDecoder.php
- wp-mcp : Handlers/Tools/ToolsHandler.php

Resolve each locator through docs/research/rea/evidence/manifest.json. Read the exact original member or Git source and validate its hash before changing a multi-layer component. If an upstream concept has no archived implementation, record the missing evidence; obtain it read-only before claiming equivalent behavior.

### Native entry points to inspect

- app/Services/CampaignPageService.php — function homepagePrograms(
- app/Services/AuthService.php — class AuthService
- assets/admin-tables.js — document
- app/Services/AgentToolRegistry.php — function executeModelCall(
- app/Services/CampaignPageService.php — function validateFields(
- app/Controllers/PublicController.php — function campaignEnquire(
- app/Services/EmailOtpService.php — class EmailOtpService

## Trace the reference and current native behavior

- Read upstream routes-and-endpoints, schema, authentication, responses-and-fields and discovery references. Trace register_route → dispatch → request validation/sanitize → permission → response; distinguish route auth from domain authorization. In native source follow ProjectMapService, Router, controller, existing service and schema.
- For MCP inspect version negotiation, request/session binding and errors in pinned adapter, not just REST path names.

## Native reconstruction contract

- Use explicit method/path registration and canonical service, typed query/body, safe scalar and array handling, actor derived from authenticated session/delegation, CSRF for browser mutation, stable status/error JSON and bounded pagination. Expose only approved schema/projection; avoid raw DB queries and metadata leakage. Document no-store for private endpoints.

## Behavioral verification

- Test real HTTP: valid, malformed, extra field, unauthenticated, wrong role/ownership, wrong method, empty result, missing record, conflict, provider/store unavailable and duplicate request. Check content type/status and discovery schema; verify direct service invocation cannot bypass authorization.
- Record the actual red/green or comparison evidence, source and fixture revision, expected/observed behavior and failure paths. Update the issue-scoped contract and REA register; run ./bapXphp update, ./bapXphp rea check, ./bapXphp rea skills-check and ./bapXphp ci. Merge only exact-head validated work; verify the deployed source and affected live flow separately.

## Out of scope

No wp-json runtime, duplicate controllers for agent/MCP, or arbitrary SQL/filesystem access.
