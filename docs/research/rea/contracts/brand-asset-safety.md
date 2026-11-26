# Native appearance upload boundary

Issue #170, part of #125/#121. Owner-only, CSRF-protected appearance edits
require the revision of the canonical settings record. A legacy-only or
unavailable record disables editing rather than creating a partial canonical
row. All ordinary settings patches merge under a row lock; stale appearance
edits fail without replacing other settings. Administrator credential edits
patch only submitted credential fields.

- Logo: PNG or JPEG, maximum 512 pixels per axis and 100 KiB.
- Favicon: PNG or PNG-compressed ICO, maximum 64 pixels per axis and 50 KiB.
  Every ICO directory entry and embedded PNG must pass bounds and decoding
  checks. New SVG and opaque DIB icon uploads are unsupported; existing
  published artwork is preserved.
- Client filenames, MIME and sizes are not authoritative. Inspect actual
  size, format and dimensions, then decode every accepted raster payload.
  New uploads require PHP GD decoding; if unavailable, fail closed without
  changing the previous artwork. MIME detection falls back to server-side
  image metadata. Store only real HTTP uploads under immutable server-selected
  names. Public brand routes accept simple image filenames.
- Validate all inputs before moving either file. If a later file move fails,
  remove only files staged by that operation. Register each successfully
  staged asset in MySQL media_files before publishing the settings reference.
  After an uncertain database outcome, retain the uniquely named safe files
  and any inventory entries; do not risk deleting a published asset.
- Persist only changed appearance fields. Validate hex colors and text/link
  contrast against the revision-checked palette; explicit reset persists
  safe defaults. Revalidate stored colors before emitting CSS.
- Separate persistence success from audit logging. Never delete replaced
  artwork automatically; managed brand files share the media archive workflow
  and its live reference protection. Archive preserves the original URL.

Tests: node --test tests/brand-assets.test.cjs covers the palette and shared
multipart helper with real uploads, corrupt and oversized images and
immutable names. tests/brand-appearance-integration.cjs exercises the real
admin routes with a disposable local MariaDB database, including role/CSRF
gates, canonical and stale-revision rejection, unrelated settings preservation,
media provenance, archive protection and concurrent settings patches.
The integration suite runs in CI with a test-only local database. Neither
fixture is production owner acceptance; a complete configurable site-profile
editor is still pending.
