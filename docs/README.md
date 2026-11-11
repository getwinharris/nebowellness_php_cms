---
type: doc
title: Documentation Index
description: Navigation for the verified architecture, deployment, modules, and pages.
category: docs
---

# Documentation Index

Start with the root [`README.md`](../README.md) for human navigation and
[`CLAUDE.md`](../CLAUDE.md) for the binding agent contract.

## Generated navigation

- [`project-index.json`](project-index.json): exact routes, controllers, services,
  views, integrations, and schema collections.
- [`systematic-map.mmd`](systematic-map.mmd): generated systematic inventory.
- [`map.mmd`](map.mmd): documentation/content mindmap.
- [`../map.mmd`](../map.mmd): route-to-controller-to-service dependency graph.
- [`../index.yaml`](../index.yaml): query router for blogs, images, skills, code,
  schema, and hosted `/remotedb` concepts.

Generated files point to original sources. Do not hand-edit them.

## Feature lookup

Start with an exact path and inspect its GET and POST entries independently:

```sh
jq --arg path '/campaigns/{slug}' '[.routes[] | select(.path == $path)]' docs/project-index.json
rg -n -A12 'id: "schema:campaign_pages"' index.yaml
```

| Task | Search the route inventory for | Next source |
|---|---|---|
| Home, motion, logo | `GET /` | Home view, shared layout, then `Design.md` and `assets/css/band.css` |
| Campaign forms | `/campaigns/{slug}`, `/campaigns/{slug}/enquire` | Campaign view/service, then the `campaign_pages` schema |
| Email verification | `/contact`, `/forms/email-otp` | `EmailOtpService`, `SmtpMailer`, [email setup](email-setup.md) |
| AI agents | `/admin/agent`, `/support/ask` | `AgentContextService`, `AiModelRouter`, [agent comparison](support-agent-comparison.md) |
| Hosted database | `/remotedb` | `RemoteDbController`, `DatabaseService`, [remote DB guide](modules/remote-db.md) |

Use the route record's controller, services, and view to open the original code.
Check [release history](changelog.md) for shipped scope and the [release guide](releases.md)
for tagging and production verification.

## Maintainer guides

- [`architecture.md`](architecture.md)
- [`deployment-hostinger.md`](deployment-hostinger.md)
- [`schema.md`](schema.md)
- [`json-storage.md`](json-storage.md) — historical filename; content must describe
  the current hosted-MySQL boundary
- [`admin-guide.md`](admin-guide.md)
- [`email-setup.md`](email-setup.md)

## Modules

- [`modules/admin.md`](modules/admin.md)
- [`modules/auth.md`](modules/auth.md)
- [`modules/catalog.md`](modules/catalog.md)
- [`modules/booking.md`](modules/booking.md)
- [`modules/consultations.md`](modules/consultations.md)
- [`modules/orders.md`](modules/orders.md)
- [`modules/remote-db.md`](modules/remote-db.md)
- [`modules/razorpay.md`](modules/razorpay.md)
- [`modules/google-oauth.md`](modules/google-oauth.md)
- [`modules/pwa.md`](modules/pwa.md)

## Page notes

Page-specific notes live in [`pages/`](pages/). Verify every claim against the route,
controller, service, and view listed in `project-index.json` before updating it.

## Regeneration

```bash
./bapXphp update
./bapXphp ci
```
