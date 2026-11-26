---
name: backend-php-mysql
description: Use when editing PHP controllers, services, hosted MySQL persistence, authentication, agent context, orders, reviews, media metadata, payments, or audit behavior.
---
# PHP and MySQL Backend

- Follow the root `AGENTS.md` repository contract.
- Keep route -> controller -> service -> MySQL boundaries through `DatabaseService`; extend existing services instead of creating parallel stores.
- Hosted MySQL is the only runtime store for admin-editable records. Local development uses direct MySQL or the configured `<APP_URL>/remotedb` fallback. Never create local product, category, consultant, order, user, setting, media-metadata, or secret copies.
- `storage/schema/collections.php` declares collection shapes. `bapXphp db init` creates tables. `db sync` is only a compatibility alias for initialization; it does not import or export JSON/YAML.
- Blog bodies remain Markdown with YAML frontmatter in `content/blog/posts/`. Image binaries remain in `assets/images/` or writable upload storage.
- Write new upload and usage metadata to MySQL `media_files`. Keep historical YAML and bundled assets readable and protected. Only provenance-checked managed uploads qualify for owner archiving. Preserve original URLs and allow restoration.
- Filter customer/support context through `AgentContextService`. Expose the bounded owner aggregate through a model-selected read-only tool, not an eager dump for every request. Keep personal customer data out. Do not force irrelevant tool calls for greetings, clarifications or creative work; record direct inference separately from inference using retrieved records. Enforce relevant evidence for current business claims, and never credit a previous failed provider exchange.
- Keep both agent surfaces on the shared AiClient native tool harness. Consult provider documentation and use loopback protocol fixtures before changing retry or routing behavior. Count logical failed turns for model switching; record individual HTTP attempts separately. A transient Google 503 may retry the same provider request once within its deadline; do not replay executed tools or report provider success from a canned fallback.
- Provider diagnostics must use the pinned saved model and shared native transport. Verify the exact fresh response and, for tools, a model-origin synthetic challenge round trip; never count a generic HTTP 2xx or nonempty answer. No business tools, routing state mutations, raw provider bodies or keys in diagnostic evidence. Follow docs/research/rea/contracts/provider-diagnostics.md.
- Editorial generation must require an explicit successful provider finish reason, reject truncated/refused output, validate reviewable Markdown and ignore model-proposed owner-only fields. Return bounded truthful errors without raw upstream diagnostics. Never persist an AI suggestion before an authorized ordinary save.
- Public consultation booking is retired. `ConsultationService`, `consultation_messages`, and `consultation_signals` are retained legacy code; historical appointments remain owner-accessible. Do not restore public booking, messaging, calls, or WebRTC without an explicit product change.
- When direct MySQL is unavailable, use the collection-allowlisted `DatabaseService` protocol at lowercase `/remotedb`. Configure its credential through Admin -> Integrations/MySQL secrets. Never expose arbitrary SQL, credentials, or secret records. An empty remote password fails closed; preserve the 503 rejection.
- Keep payment clients as small `integrations/` wrappers, source secrets through `SecretService` or system environment variables, and verify signatures before mutating orders or wallet balances.
- Use `bapXphp read blog <slug>` and `bapXphp write blog [slug]` for blog operations. Product and other admin-editable content must be read and written through hosted MySQL-backed application flows.

## Testing

Run `php -l` on every changed PHP file and then `./bapXphp test`. For storage changes, exercise the affected admin page against the remote database. Confirm transport, non-2xx, and invalid-response failures surface visibly; repeated reads are memoized within a request; and mutations invalidate cached reads.

## Keeping this skill current

Update when a service boundary, remote mutation protocol, consultation product policy, or runtime store changes. Read `storage/schema/collections.php` first because it is canonical.
