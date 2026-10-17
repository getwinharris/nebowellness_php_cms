---
title: Support agent comparison and model routing
description: Evidence from SHA live chat, Nebo source and Google model discovery, tracked in issue 29.
---

# Support agent comparison and model routing

Inspected 2026-10-17. Tracking: issue #29.

## SHA observed browser behavior

The homepage initially rendered contact, phone and country-specific WhatsApp choices. A HubSpot chat widget appeared after its delayed load. Opening it showed a named agent (Daniel), availability/avatar, conversation list with unread count, expand/close, message composer, attachment control (10 MB limit), screen capture and resize controls. This is evidence of its visible interface, not proof that the conversation is AI-powered. No private conversation, attachment or message was sent.

## Nebo observed source gaps

The public widget in `views/layouts/app.php` uses a generic Support heading, sessionStorage transcript, one text composer and basic close/send controls. It lacks the reference's visible agent identity/availability, conversation list, attachment intake, expansion/resizing and explicit human handoff. The transcript alone is stored in the browser while the posted request carries the current question, so apparent conversation continuity needs a server-context audit. Customer and admin requests share `AiClient`; its configured-model request has no persistent three-failure switch.

Do not claim all SHA features should be copied: file/screen intake requires deliberate privacy, storage, validation and owner handling. Immediate priorities are accurate response behavior, useful program/contact actions, clear status and reliable model routing.

## Current provider verification

Google's models endpoint returned HTTP 200 with the supplied replacement key and listed both `gemma-4-31b-it` and `gemma-4-26b-a4b-it`. The replacement key was saved through encrypted SecretService into the hosted secrets collection and read back without outputting its value. The configured model remains `gemma-4-31b-it`.

Requested routing is not implemented yet: after three failed API calls on the main model, use 26B A4B; after three failed calls there, return to main. This requires shared state, explicit failure classification and tests across both agent surfaces, without multiplying six long provider calls inside a single browser request.
