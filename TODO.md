---
title: Work Tracking
description: GitHub issues are the authoritative backlog.
category: root
---

# Work Tracking

Use the open issues in `getwinharris/nebowellness_php_cms` as the backlog. Before acting:

1. read the full issue;
2. cross-check the current source and route/service ownership;
3. reproduce with the smallest safe command or browser workflow;
4. mark the claim confirmed, fixed, stale, duplicate, live-only, or credential-blocked;
5. implement only a confirmed problem and record its acceptance evidence.

Do not maintain a second feature backlog here. This file exists only to route humans
and agents to the verified GitHub workflow.

## Current Nebo conversion

- [x] Recheck current `main`, generated map/schema, project index, and live homepage before editing.
- [x] Open evidence-backed public rebrand issue #18.
- [x] Replace devotional logo, blog taxonomy/fallbacks, and empty-gradient hero with Nebo-specific assets and content; update `Design.md` and frontend skill.
- [x] Verify public routes at desktop and 375px with built-in Browser; correct crop, overflow, CTA, navigation, and one-card rail behavior.
- [x] Complete issue #4: consultant schema, admin routes, media context, live profiles, and historical appointment terminology are migrated without losing records.
- [x] Close stale issue #6: current home template has no shop product section; remove unused product/temple fetching.
- [x] Issue #19: export and remove the backed-up old orders, users, products, categories, and temples. Live after-counts are zero.
- [x] Issue #19: clear old AI, Razorpay, Google OAuth, and legacy mailbox credentials. Preserve Hostinger SMTP host, port, and encryption for the new client mailbox.
- [ ] Client configuration: add the new Razorpay, Google OAuth/analytics, AI, and Nebo Hostinger mailbox credentials when supplied; revoke superseded provider keys from the respective provider consoles.
- [x] Run `./bapXphp update` and green `./bapXphp ci`; merge PR #20, confirm Hostinger deployment, repeat live Browser checks, and attach evidence.
