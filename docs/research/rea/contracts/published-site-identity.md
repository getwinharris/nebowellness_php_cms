# Shared published site identity capability

Issue #174 follows #172 and #125. Native guidance: rea-wp-abilities-audit
and rea-wp-abilities-verify. Read-only original WordPress 7.1.4 archive
members verified against the pinned REA graph:
class-wp-ability.php (0c0aa1db27f284cb9942865e6fbed0610178439430e2ac9a9f6a7dd209df049e),
class-wp-rest-abilities-v1-list-controller.php
(0b4dc26d32048268de00dbd27d816e2a319f11f4d243aee7d8b208c95aeef4d5),
and class-wp-rest-abilities-v1-run-controller.php
(e343c9b7f598d4280baea9731364170cb2c01b3437371fa9b6c9ec8a10a415c1).
Trace discovery permissions, validate_input, check_permissions, execute and
validate_output. Do not execute imported code.

## Domain and permissions

The stable native name published_site_identity v1 accepts an empty object.
It returns only name, short_name, tagline, description, public_email, address,
hours_weekday, hours_weekend, organization_type, phones, whatsapp and source.
SiteProfileService::identity reads the canonical settings row, then uses
publicIdentity and the existing published resolver/contact channel projection.
Absent, invalid, unconfigured or noncanonical identity fails closed. The
reference identity remains eligible only on its configured Nebo origin.
Draft, prior versions, admin label, credentials, tokens, customer records,
raw settings, and arbitrary selection arguments are excluded. No durable
business write, mail, payment or network action is performed by the read.

Both trusted agent scopes discover the same schema and invoke it through
NativeAbilityRegistry; authorized MCP exposes the same read via the existing
published-read allowlist and independently checked expiring grant. Seven
published reads, five owner reads and one support-context read are registered.
There is no new grant scope, endpoint or write permission.

AgentToolRegistry requires fresh observed identity retrieval for explicit
public contact/name/location/hours questions. Generic creative prompts and
private customer contact queries do not require that tool. The acceptance
guard checks concrete email and international phone/WhatsApp claims against
observed records; it is not a complete semantic proof of all free-form
claims or current open/closed status. The model still chooses tools and
writes the response. A2UI server projection builds a bounded literal-only
card and actual /contact source from the successful result, without exposing
private fields or model-authored executable content.

## Verification

- tests/site-identity-ability.test.cjs: schema, role and argument validation,
  legacy/malformed/unconfigured/missing state, private draft/history exclusion,
  A2UI projection and concrete contact-answer guard.
- tests/agent-session.test.cjs: direct guessed contact answer rejected at the
  real Support endpoint even when the model returns a successful completion.
- tests/site-identity-agent-integration.cjs: disposable MariaDB and loopback
  provider, both actual chat routes, correlated tool results, publication
  changes, invalid data and fabricated replies. Test fixture verifies its
  provider is local before invoking.
- tests/mcp-read-integration.php: real HTTP protocol and authorization checks,
  typed published identity, malformed source, rejected draft arguments,
  no unrelated state mutation, and grant revocation.
- Live acceptance: verify source fingerprint and non-mutating public Support
  query. Authenticated owner and MCP production acceptance remain separate;
  never issue a real grant or publish a new business profile just to test.

## Natural public contact phrasing (#182)

One acceptance classification covers explicit public business/clinic/site
identity, opening hours and common your/our email, telephone and WhatsApp
questions. The same categories drive the required successful read and
concrete-answer guard. Explicit private actor queries and ordinary model
identity greetings are not converted into public-business lookups.
When an explicit contact number is requested, check the full international
digits (formatted or unformatted) against the appropriate observed channel;
when a published email is requested, require the actual value. Do not
claim this is a semantic validator for every identity or hours statement.
Both agent routes use the same guard and are tested with a real disposable
MariaDB record and correlated loopback provider responses, including the
ordinary business-email wording that previously bypassed the gate.
