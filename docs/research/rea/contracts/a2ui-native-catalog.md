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

## Remaining acceptance

This is a native contract foundation, not an A2UI runtime or a complete
conformant SDK. Surface lifecycle, tree-wide validation (cycles, orphan and
duplicate IDs), dynamic data model, safe client rendering, negotiation,
action correlation, invocation reauthorization, user consent, browser QA
and text fallback remain unimplemented. No model/renderer advertisement
or new routes are exposed. Add these through separately scoped PRs.

## Tests

`node --test tests/a2ui-catalog.test.cjs` uses synthetic literal data and
trusted support/admin registry fixtures. It verifies strict payload
validation and role/module discovery without calling a provider, querying
live records or executing a UI function.
