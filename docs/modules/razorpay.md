---
type: doc
title: Razorpay Module
description: Owns payment order creation and signature verification.
category: module
---
# Razorpay Module

Owns payment order creation and signature verification.

Main files: `RazorpayClient.php`, `PaymentService.php`, `CommerceController.php`, `views/public/checkout.php`.

Checkout endpoints: `/checkout/create-order` and `/payment/verify`, with `/api/create-order` and `/api/verify-payment` available as JSON API aliases.

Key checks: missing keys block checkout clearly. Admin Integrations stores `razorpay_mode`, test keys, and live keys separately in the remote MySQL `secrets` table, then exposes the selected mode as the active key pair for product checkout. System environment variables remain a hosting fallback for critical credentials. The browser receives only the active key id. The key secret stays server-side for order creation and HMAC-SHA256 signature verification.

## Approved therapy requests

Signed-in campaign enquiries appear under **Account → Therapy requests**. Owners can review guest and account enquiries under **Enquiries → Therapy requests**, record private follow-up notes, assign a team member, set a proposed time and duration in the clinic timezone, and offer an INR fee with a description. Guest submissions are handled directly by the clinic. A matching email alone never grants account access.

Before first use, run `./bapXcms db init` on the deployment host to create the additive `therapy_sessions` collection. In Admin → Integrations, set the API keys and the separate therapy webhook signing secret for the active Razorpay mode. Configure `payment.captured` in the Razorpay Dashboard using the site's full URL ending in `/payment/therapy/webhook/test` or `/payment/therapy/webhook/live`. Test keys belong on an isolated deployment. The public clinic domain requires live mode.

The same approved request always uses its opaque request ID as the Razorpay receipt. If an unpaid checkout outlives its proposed start, the owner can offer another future time on the same request and order. A late capture records payment, and the replacement time requires owner confirmation unless the customer has reopened checkout for that time. The customer pays the approved amount in paise with no delivery or intake fields in the order. Both a signed Checkout return with a server-fetched capture and a signed capture notification use the same durable transition. An occupied or elapsed time leaves the payment recorded and the request approved. The owner can filter **Paid, awaiting a time**, agree on another time and confirm it. The calendar allows independent clinicians to see different customers at the same time. It blocks overlaps for a clinician or a signed-in customer. The shared Clinic team counts as one scheduling resource. A late capture on a cancelled request records the payment and keeps the cancellation visible. The clinic reconciles refunds in Razorpay.

Use an isolated MySQL database ending in `_fixture` or `_test` to run `php tests/therapy-integration.php`. It exercises overlap under concurrent connections, duplicate notifications, stale edits and recovery. Merchant acceptance still requires a checkout against the clinic's authorized Razorpay test merchant.

[Razorpay Orders API](https://razorpay.com/docs/api/orders/create/) · [Order lookup by receipt](https://razorpay.com/docs/api/orders/fetch-all/) · [Webhook signature verification](https://razorpay.com/docs/webhooks/validate-test/)
