# Native A2UI catalog foundation

Issue #167; depends on native ability contracts (#148–#163) and retail
retirement (#165). Reference: A2UI v1.0 at
`36725eee042da90c526f469f94246f96e16fdc35`,
`specification/v1_0/docs/a2ui_protocol.md` (Catalog structure and schema
conventions), and `docs/a2ui_custom_functions.md` (function boundaries).
Native guidance: rea-wp-abilities-verify and rea-wp-abilities-audit.

## Implemented local contract

- `A2uiCatalogService::catalog()` describes a versioned read-only custom
  catalog: literal Text, Stack, Metric and Card. No renderer function exists
  yet. Server-side native ability metadata is separate from renderer-side
  functions; it is never a permission grant or a remotely callable endpoint.
- `validComponent()` validates an ASCII subset of component IDs, exact
  component/property schemas, bounded literal text and child references.
  Unknown properties, dynamic data bindings, self/duplicate child references
  and executable components are rejected. Text remains data: the future
  renderer MUST use textContent, not innerHTML.
- `serverAbilities()` accepts only an already trusted scoped
  NativeAbilityRegistry, intersects discovery with an explicit service
  read allowlist and checks readonly/non-destructive annotations. Disabled
  modules and owner-only tools remain inaccessible to support; private
  session context, legacy retail tools and arbitrary writes are excluded.

## Native read-only runtime snapshot

Both agent endpoints now use the same server-side projection of successful
native model-tool results. A fresh accepted tool-backed answer may carry one
A2UI v1.0 `createSurface` envelope with the explicit catalog ID and a
bounded, validated literal tree (root, max 48 nodes, depth <= 6). Initial
projections cover published consultation fees, programmes, article searches,
owner historical appointment summaries and owner therapy request summaries.
The projection independently checks scope and the native output schema;
it cannot invent a price or count, accept model-authored HTML or expose
arbitrary tool records. Partial lists are labelled; historical appointments
and enquiry requests are not represented as confirmed appointments.

The common PHP-to-JS integration uses `AgentToolRegistry::observedUi()`,
`A2uiCatalogService::observedSurface()`, and `assets/a2ui-readonly.js`.
The browser repeats schema and complete-tree validation before committing
one DOM subtree with literal textContent. Unknown, duplicate, missing,
orphaned, cyclic or shared-child nodes and function calls are rejected.
The ordinary answer and source links remain available. No UI result is
written to session history, journals or model prompt memory; restored
answers are deliberately text-only rather than stale records.

## Remaining acceptance

This is an implemented read-only snapshot subset, not a complete
A2UI-conformant SDK. Incremental updateComponents/updateDataModel/deleteSurface,
live bindings, catalog negotiation, model-authored surfaces, renderer functions,
typed authorized user actions, confirmation, and production acceptance remain
outstanding. No new route or mutation is exposed. Add these through separately
scoped PRs; keep server abilities and renderer functions distinct.

## Tests

`node --test tests/a2ui-catalog.test.cjs` uses synthetic literal data and
trusted support/admin registry fixtures. It verifies strict payload
validation and role/module discovery without querying live records or executing a UI function. The same test suite
exercises full tree validation and a DOM fixture which throws on innerHTML.
`tests/agent-session.test.cjs` uses an isolated loopback model provider and
synthetic PHP sessions to prove both real chat routes project actual observed
tools, deny failed/direct responses and omit UI from replay/history.


Chromium fixture acceptance: `NODE_PATH="$HOME/node_modules" node tests/browser/a2ui-readonly.cjs`
renders the actual PHP Support and Enterprise templates, sends mocked
read-only JSON responses through their actual chat scripts, and verifies
literal text, no overflow, malformed-surface fallback and zero page errors
at 1440, 375 and 390 pixels. The separate loopback provider/session test
proves the server projection. Neither fixture is live authenticated
production acceptance.
