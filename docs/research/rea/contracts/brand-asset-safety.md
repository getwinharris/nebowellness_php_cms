# Native appearance upload boundary

Issue #170, part of #125/#121. The owner-only CSRF-protected appearance
route validates the entire submitted operation before storing new files or
patching the canonical settings record.

- Logo: PNG or JPEG, maximum 512 pixels per axis and 100 KiB.
- Favicon: PNG or ICO, maximum 64 pixels per axis and 50 KiB. ICO directory
  entries must all be bounded. New SVG uploads are not supported; existing
  published artwork is not deleted or replaced.
- The original filename, reported MIME and reported file size are not
  authoritative. The server checks actual file size, image metadata and
  file MIME; existing optional GD additionally decodes PNG/JPEG when available.
  Storage accepts only real HTTP uploads and generates an immutable image name.
  The brand directory's public route boundary allows simple image filenames.
- All submitted color values must be hex. Persist only changed appearance
  fields, validate text/link contrast when relevant fields change, and make
  an explicit reset persist safe defaults rather than unsetting merge keys.
  Existing valid colors are not silently recolored. Revalidate stored colors
  before emitting CSS.
- Report validation or uncertain persistence failures truthfully. Existing
  records and artwork are not deleted; a failed save may leave an
  unreferenced, safe, uniquely named asset for later owner-managed cleanup.

Synthetic test: node --test tests/brand-assets.test.cjs exercises actual
multipart uploads in a disposable loopback PHP server, invalid data and
dimensions, immutable naming, no storage after invalid combined input,
PNG/ICO and palette validation. Local route smoke tests enforce the brand
path boundary. This is not an authenticated production upload test, nor the
complete revision-safe site profile editor.
