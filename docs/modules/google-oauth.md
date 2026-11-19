---
type: doc
title: Google OAuth Module
description: Owns optional customer sign-in through Google.
category: module
---
# Google customer sign-in

Enter the customer OAuth client ID and secret in Admin → Integrations. Copy the callback shown there into the web client's authorized redirect URIs in Google Cloud. The callback uses the configured `APP_URL` plus `/auth/google/callback`, including its exact scheme and hostname. When changing hosts, authorize the new callback before switching and allow ten minutes for outstanding sign-ins.

The login and registration pages offer Google when both credentials are configured. Authorization requests `openid email`. After the private code exchange the server checks the issuer, audience, verified email, nonce and expiry. A returning customer signs in using the stored Google subject.

For a first-time connection the verified Google email is held in the PHP session for ten minutes. An existing customer confirms the link with their password and the link checkbox. A new customer completes the registration form, delivery details and terms, sets a password for recovery, and selects the Google sign-in checkbox. The server saves the account and address before linking. An owner, consultant, ambiguous record or customer with outstanding account requirements cannot link through this flow.
