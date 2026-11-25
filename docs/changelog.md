---
type: doc
title: Nebo changelog
description: Notable changes grouped by release.
category: docs
---

# Changelog

## Unreleased

- Customer Support and Enterprise Agent remember a bounded conversation in the current session. Both support reload, follow-up questions, retry and clearing. Owner diagnostics show provider failures and latency by model and request type.
- Therapy intake now has an owner queue, a customer status page, clinician assignments and conflict-checked scheduling. Paid requests use a distinct Razorpay order and require a verified capture before confirmation.
- Added owner GA4 and Search Console reports, separate opt-ins for analytics and advertising, and write-only provider credential fields. Google customers can explicitly link a verified identity during sign-in or registration.
- Added Markdown discovery, a public OpenAPI description, canonical metadata and clearer campaign receipts. Consolidated public styles, served fonts locally, and reduced the transfer size of clinic images.
- The media library can archive and restore managed uploads while preserving published URLs. The shop has a responsive heading and category controls, and service workers cache versioned scripts and styles only.

- Added 7- and 30-day dashboard views with daily agent charts, provider attempt counts, and shortcuts to clinic enquiries and trace details.

- Added New chat and 24-hour error rate and p95 timing to the Enterprise Agent.
- Added quick clinic guidance and a direct contact link to Nebo Support. The support page respects enabled site modules.

### Changed

- Aligned the homepage and About copy with the clinic brief, separated Focus Areas from Programs, displayed Team credentials, and replaced the public page finder with confirmed phone and WhatsApp shortcuts.
- Refined the public menu, Contact, Services and campaign pages. Improved the About introduction and restored image and category hierarchy in the Wellness Journal.
- Refined the home hero handoff, mobile Program filters, practitioner image fallbacks, and care layout. Added original Program artwork and a corporate wellness section for Nebo’s yoga and sound-healing sessions, with a preselected enquiry topic.
- Reordered the homepage around the Foundation, Programs, Team, and client stories. Added scroll-triggered clinic figures, an image-led care grid, and clinic resources above the footer.
- Refined the public menu and image-led program, service and shop cards using the SHA component reference while retaining Nebo imagery, native navigation, and purchase/enquiry behavior.
- Added a distinct Nebo hero entrance and direct consultation form link, increased mobile rail targets, and gave the customer Support panel a larger transcript and direct entry from the Support page.
- Restored the project CLI's environment loading on macOS system Bash so local maintainers can run project checks.

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
