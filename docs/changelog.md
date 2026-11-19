---
type: doc
title: Nebo changelog
description: Notable changes grouped by release.
category: docs
---

# Changelog

## Unreleased

- Added 7- and 30-day dashboard views with daily agent charts, provider attempt counts, and shortcuts to clinic enquiries and trace details.

- Added New chat and 24-hour error rate and p95 timing to the Enterprise Agent.
- Added quick clinic guidance and a direct contact link to Nebo Support. The support page respects enabled site modules.

### Changed

- Refined the homepage hero, Services layout, and footer. Support and Enterprise Agent now have clearer composer controls and feedback.

- Restored layered homepage chapter transitions on phones. Tall chapters scroll fully before settling, and the footer finishes in normal flow.

- Removed the fixed mobile page links and compacted the Foundation, featured therapy, testimonials, and footer. Missing therapy images now use a local fallback.

- Made the mobile menu compact and keyboard-accessible, kept floating controls behind it, centered the header logo, and moved bottom clearance into the footer.

- Added searchable page and module navigation, keyboard skips to the main content, labeled admin table filters with overflow hints, and accessible feedback messages across the public and admin layouts.

- Enterprise Agent run filters distinguish provider attempts from failed runs. Support preserves an unsent draft when a suggested question is clicked and explains how to retry a ticket when receipt is uncertain.

- Mobile navigation distributes the visible links evenly. The cart tray and support button clear the navigation and device safe area, and Programs reflects the homepage fragment.

- Therapy campaigns appear under Products, with the newest published offering featured on the homepage.
- Home chapters settle in short, staggered entrances while preserving native scrolling.
- Admin consultation analytics compares historical session counts and completion shares.
- Owner agent starters fill an empty composer for review and preserve unsent messages.

### Security

- Google sign-in validates the provider response and requires a previously linked customer account. Password registration remains available, and Admin Integrations shows the exact OAuth callback URL.

### Documentation

- Added targeted repository lookup examples and a release workflow.

The first tagged release will establish the baseline. Earlier changes remain
traceable through merged pull requests and Git history.
