---
type: doc
title: Admin Guide
description: Use the dashboard to manage products, categories, coupons, orders, historical service records, and more.
category: docs
---
# Admin Guide

Use the dashboard to manage products, categories, coupons, orders, practitioner profiles, campaign landing pages, historical service records, shipping, integrations, backups, audit logs, blog content, and the developer project map.

Public visitors send consultation enquiries through the contact form. Practitioner profiles and their temporary images are editable under Consultants. Historical appointment records remain under Sessions, and Consultation Analytics summarizes those retained records.

## Agent operations

Enterprise Agent → Recent agent runs shows up to 12 recorded runs across customer support and the Enterprise Agent. Filter by agent and distinguish failed provider attempts from failed runs. A retry or fallback can produce an answer after a provider attempt fails. Use the trace ID to correlate an event with the dashboard. The explorer shows operational metadata and excludes chat content, tool inputs, and tool results.

### Backend adaptation sequence

Reference revision [bapXphpAiBackend `2392570`](https://github.com/bapxmediahub/bapXphpAiBackend/commit/2392570522c140345e4710b583f92e64f0ec8f40).

| Priority | Reference capability | Nebo adaptation and verification |
| --- | --- | --- |
| 1. Provider triage ([#82](https://github.com/getwinharris/nebowellness_php_cms/issues/82)) | Correlated HTTP responses and observed provider spans | Reuse Nebo's existing trace IDs and model-routing health. Validate retry, fallback, timeout, and response production independently with synthetic provider responses. Add first-byte duration and token counts only when observed. |
| 2. Owner monitoring ([#85](https://github.com/getwinharris/nebowellness_php_cms/issues/85)) | Separate monitoring and chat views | Keep dashboard trends, chat and operational diagnostics distinct. Use bounded server-side time and surface filters when expanding beyond the recent-runs panel. Verify admin authentication and avoid exporting chat content as telemetry. |
| 3. Tool policy and evaluation | Separate Support and Enterprise allowlists, runtime instruction manifests and golden evaluations | Map existing Support routing and Enterprise tools to Nebo's public, customer-owned, and admin scopes. Use fixed manifests only if policy moves out of PHP. Test guest and customer denials, module gating, source selection, and draft confirmation. Use clinic-approved copy and synthetic data. |
| 4. Chat history and feedback | Private conversations, pending-turn checkpoints, replay handling, and explicit feedback | Choose retention and deletion rules before storing new message content. Keep current MySQL ownership and audit boundaries. Validate durable idempotency and interruption handling before adding history, exports, or feedback. Treat an explicit feedback signal separately from the number of replies produced. |

The reference implementation describes its role policy in [Runtime Agents](https://github.com/bapxmediahub/bapXphpAiBackend/blob/2392570522c140345e4710b583f92e64f0ec8f40/docs/runtime-agents.md) and trace and evaluation semantics in [Agent Observability](https://github.com/bapxmediahub/bapXphpAiBackend/blob/2392570522c140345e4710b583f92e64f0ec8f40/docs/agent-observability.md).
