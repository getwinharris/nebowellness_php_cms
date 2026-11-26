---
type: doc
title: Admin Guide
description: Use the dashboard to manage products, categories, coupons, orders, historical service records, and more.
category: docs
---
# Admin Guide

Use the dashboard to manage products, categories, coupons, orders, practitioner profiles, campaign landing pages, historical service records, shipping, integrations, backups, audit logs, blog content, and the developer project map.

Public visitors send consultation enquiries through the contact form. Practitioner profiles, credentials, optional professional registration numbers, visibility, display order and images are editable under Consultants. Leave the registration field blank if no approved number is supplied; clearing it removes it from the public profile. Only visible MySQL profiles appear on the homepage. An intentionally empty visible collection hides the Team section; no named hardcoded practitioner is substituted. Historical appointment records remain under Sessions, and Consultation Analytics summarizes those retained records.

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

## Search performance

Open **Dashboard → View reports** for GA4 sessions, active users, engaged sessions and landing pages, plus Search Console clicks, impressions, CTR, position, queries and pages. Choose 7, 28 or 90 dates ending yesterday in UTC. Google groups Analytics data by property time zone and Search Console data by Pacific time, and may finish processing recent dates later. The owner session reuses results for 30 minutes. A transient failure displays the date of its last successful result, for up to seven days. A denied request clears the affected cached report.

In **Integrations → Google reporting**, enter the numeric GA4 property ID and the exact Search Console property. Enable the Analytics Data and Search Console APIs in Google Cloud, grant a dedicated service account GA4 Viewer and Search Console restricted access, and paste its JSON signing key. A blank key field keeps the saved credential. To rotate, save the replacement and remove the old key in Google Cloud. Disconnect removes the local credential. Customer Google sign-in uses its own client and scopes.

The GA4 measurement ID configures visitor measurement separately from report access. Eligible signed-out homepage and About visits require opt-in in **Privacy → Audience measurement preferences**. Advertising also requires the owner to enable the saved destinations. General landing links may include a single `utm_source=google`, `facebook` or `instagram` for Analytics. Other query parameters and fragments exclude the visit. Measurement runs in an isolated frame without access to forms or chat. Verify provider receipt with a designated test property before using results to assess campaigns.

[GA4 Data API](https://developers.google.com/analytics/devguides/reporting/data/v1) · [Search Console API](https://developers.google.com/webmaster-tools/v1/api_reference_index) · [Google site verification](https://support.google.com/webmasters/answer/9008080)
