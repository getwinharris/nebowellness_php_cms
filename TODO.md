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
- [ ] Replace devotional logo, blog taxonomy/fallbacks, and empty-gradient hero with Nebo-specific assets and content; update `Design.md` and frontend skill.
- [ ] Verify public routes at desktop and 375px with built-in Browser; correct any crop, overflow, CTA, or navigation defect.
- [ ] Revisit issue #4 with a backward-compatible `astrologers` to `consultants` data migration and admin route update; inventory live collection first.
- [x] Close stale issue #6: current home template has no shop product section; remove unused product/temple fetching.
- [x] Issue #19: export and remove the backed-up old orders, users, products, categories, and temples. Live after-counts are zero.
- [ ] Issue #19: inspect the remaining encrypted integration row through the production Admin/server-side path, clear old AI/Razorpay/Google credentials while preserving clinic SMTP, revoke provider keys, and verify the resulting login/payment configuration.
- [ ] Run `./bapXphp update` and green `./bapXphp ci`; open PR, verify GitHub checks, merge, confirm Hostinger revision, and repeat live browser checks. Attach evidence to issues.
