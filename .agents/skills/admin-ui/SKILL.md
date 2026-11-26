---
name: admin-ui
description: Use when editing owner/admin pages, CRUD forms, media library, environment editor, permissions, audit log, integrations, or admin navigation.
---
# Admin UI

- Follow the UI workflow in `AGENTS.md` and the responsive contract in `Design.md`. Record the issue, baseline, and acceptance checks before editing.
- Render records, initial form values, and native submissions in PHP templates. Use JavaScript for interactive controls, media selection, previews, and asynchronous feedback through existing routes. Load browser behavior from focused deferred scripts under `assets/`.
- Keep mutations, authorization, CSRF, validation, and audit logging in controllers and services. Local interaction state stays in the browser and persisted state comes from the server response.
- Put new and migrated styles in `assets/css/band.css`. Preserve the cascade when migrating existing template CSS and remove the corresponding duplicate rules.
- Use schema-driven resource fields and the media library for product, campaign, and consultant media.
- Keep the sidebar grouped as Clinic, Content, Enquiries, Commerce, Appearance, and Settings. `SettingsService::modules()` hides complete feature groups and related settings without hiding Enquiries.
- Campaign landing forms are schema-driven: restrict custom definitions to the supported field allowlist, validate select values against configured options, retain safe values after errors, and keep existing campaigns compatible when no custom fields are defined.
- In Campaign Pages, use visual field rows and the enquiry preview. Keep saved field keys stable through label and order changes, and report invalid definitions by row while keeping the form open for correction.
- Treat consultants as admin-managed public profiles. Their optional professional registration number is plain text and must be editable and clearable. Historical appointments remain owner-only. Do not create or display provider login credentials unless authentication is explicitly added as a separate product change.
- Validate with `php tests/run.php`; use a browser workflow for changed admin pages.

## Admin Panel Agent

The interface at `/admin/agent` sends a prompt plus a bounded site summary to the
configured AI endpoint. Treat it as a read-only assistant unless controller code
explicitly implements and authorizes a mutation. It does not currently edit blogs,
run the CLI, or trigger deployments.

### Implementation Pattern

The admin-agent flow:
1. `GET /admin/agent` calls `AdminController::agent()` and renders the page.
2. `POST /admin/agent/ask` calls `AdminController::agentAsk()`, which obtains an aggregate summary from `AgentContextService`.
3. `SecretService` supplies `agent_api_key`, `agent_model`, and `api_endpoint`.
4. The controller returns JSON; JavaScript in `views/admin/agent.php` renders the response.

### Agent Permissions

Keep context aggregate, allowlisted and purpose-specific. Do not expose passwords, secrets,
full user lists, payment data, or unrelated customer records. A future write
operation needs an explicit controller/service contract, authorization, confirmation,
CSRF protection and audit event; a prompt alone is never authorization.

## AI Integration Secrets

Configured in Admin → Integrations with these fields stored in MySQL `secrets` table:

| Key | Purpose |
|-----|---------|
| `agent_api_key` | Authentication key |
| `agent_model` | Model ID; defaults to `gemma-4-31b-it` |
| `api_endpoint` | Full provider API URL |

## Testing

Use authorized admin credentials for a development environment to log into `/admin`.
`bapXcms dev:user` creates a customer account. Record the blocked checks if no
development admin account is available. Exercise the affected flow in
the built-in Browser at desktop and 375px. Check applicable breakpoint edges, sidebar
and dialogs, computed overflow with long field values, keyboard focus, permission
errors, and save or delete feedback. Confirm that submitting one form preserves
values in the other forms. Record any unrun browser checks and run `./bapXcms ci`
before review.

## Keeping this skill current

Update when an admin route, sidebar group, or CRUD form contract changes. The sidebar is gated by `SettingsService::modules()`; re-check both the on and off states.

- Keep admin motion brief and limited to workspace section arrival, mobile sidebar and user-expanded groups. Do not animate status values, rows or inputs; change hidden/expanded semantics immediately and preserve focus and reduced motion.

- CRM uses owner-only routes, no-store responses, revision guards, private notes separate from customer-visible replies, and bounded customer context. Test ownership and JSON download authorization. Follow [the native harness contract](../../../docs/agent-harness.md); no fake inference.

- Consultation fee cards use revision-guarded canonical settings, integer minor units and explicit draft/published visibility. Keep published display fees separate from therapy payment offers. Test draft visibility, save/reload, stale edit and the shared tool.

- Blog AI proposals must come from actual completed provider inference. Show a separate review state, apply only to the unchanged editor snapshot, and never save automatically. Preserve links, images and alt text during enhancement; retain original text on errors, cancellation, stale edits or invalid suggestions. Use the route fixture and local browser acceptance in tests/browser/blog-editorial.cjs. Do not report isolated fixtures as production owner verification.

- The Site profile editor saves a bounded complete identity as a private
  draft, previews the saved fields, publishes only with explicit confirmation
  and current canonical settings revision, and restores the preceding
  published identity. Public templates and discovery must use the published
  projection, not draft/history. Reuse the existing appearance and contact
  channels. See docs/research/rea/contracts/site-profile.md; verify two distinct
  synthetic identities and stale/unauthorized requests.

- Native pages at /admin/pages use a bounded versioned section registry,
  server-generated section IDs, revision-checked native form operations,
  and an explicit saved-preview publication workflow. Section operations
  save valid draft fields only. Public /pages/{slug} and sitemap must
  resolve the published snapshot, never drafts. Keep existing clinic
  routes unchanged. See docs/research/rea/contracts/native-pages.md.

- When native page storage is confirmed absent, Pages offers only the
  owner/CSRF/confirmation-gated initialization of cms_pages. Never run
  migration from a public read. Distinguish real missing-table errors
  from permission/schema failures. The configured database path (direct
  or authenticated bridge) must enforce the same domain operation.
  Native page draft/publication/restore and media archive share the
  serving DB lock; new managed references require an active inventory
  entry, including images promoted from a draft. Preserve archive bytes.

- Live programmes, team and consultation fees are reusable page sections
  backed by the existing typed published abilities. Owners edit heading,
  intro and 1–12 display count; they cannot overwrite source records in
  the page editor. Disabled, empty and unavailable data are distinct,
  and saved preview uses the same bounded public renderer.

- PageSpeed Insights is an explicit owner POST at /admin/pagespeed/run,
  not an automatic report on GET. Use the configured public origin and
  approved general public pages, preserve separate lab/page/origin metric
  scope, show stale/access/quota/unavailable honestly and never expose keys.
