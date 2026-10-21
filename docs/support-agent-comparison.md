---
title: Support agent comparison and model routing
description: Evidence from SHA live chat, Nebo source and Google model discovery, tracked in issue 29.
---

# Support agent comparison and model routing

Inspected 2026-10-17. Tracking: issue #29.

## SHA observed browser behavior

The homepage initially rendered contact, phone and country-specific WhatsApp choices. A HubSpot chat widget appeared after its delayed load. Opening it showed a named agent (Daniel), availability/avatar, conversation list with unread count, expand/close, message composer, attachment control (10 MB limit), screen capture and resize controls. This is evidence of its visible interface, not proof that the conversation is AI-powered. No private conversation, attachment or message was sent.

## Nebo observed source gaps

The public widget in `views/layouts/app.php` uses a generic Support heading, sessionStorage transcript, one text composer and basic close/send controls. It lacks the reference's visible agent identity/availability, conversation list, attachment intake, expansion/resizing and explicit human handoff. The transcript alone is stored in the browser while the posted request carries the current question, so apparent conversation continuity needs a server-context audit. Customer and admin requests share `AiClient`, with isolated persistent three-failure routing health for each surface as described below.

Do not claim all SHA features should be copied: file/screen intake requires deliberate privacy, storage, validation and owner handling. Immediate priorities are accurate response behavior, useful program/contact actions, clear status and reliable model routing.

## Current provider verification

Google's models endpoint returned HTTP 200 with the supplied replacement key and listed both `gemma-4-31b-it` and `gemma-4-26b-a4b-it`. The replacement key was saved through encrypted SecretService into the hosted secrets collection and read back without outputting its value. The configured model remains `gemma-4-31b-it`.

Both PHP agent surfaces route through `AiClient`, with separate persistent admin and public-support health records in the `ai_model_state` collection so public traffic cannot degrade owner lookups. Three consecutive failed 31B calls in a surface switch it to 26B A4B; three consecutive failed fallback calls switch it back to 31B. The request that reaches a switch retries once within the remaining request deadline, while earlier failures return promptly. A successful call resets that model's consecutive-failure count, and a changed provider endpoint or key retries the primary.

Google documents both hosted model IDs and `minimal` thinking for Gemma 4. Live probing also established an operational limitation: 26B A4B ordinary `generateContent` succeeds quickly, but the hosted endpoint returns HTTP 500 when function declarations are present. The admin fallback therefore keeps its grounded statistics and document context but skips optional live tool calls until the primary model recovers.
