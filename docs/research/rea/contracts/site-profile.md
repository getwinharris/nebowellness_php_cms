# Versioned native site identity

Issue #172, part of #125/#121. Native REA skills: rea-wp-block-themes,
rea-wp-plugin-development. Read-only pinned source: WordPress 7.1.4 archive
SHA-256 a147146c159362778ecbbbfb07bb20ddf4e85b130f971c824e656a8f938c5f87;
wp-admin/options-general.php SHA-256
0ac15c8b3bc66d1497b611324a5ed613cd3202dbb69001acb1ba79fe5f854377
(manage_options, settings_fields, blogname, blogdescription, site icon);
wp-includes/class-wp-theme-json-resolver.php SHA-256
8c1eae3025d000dfe828e76f2630caa54084c619b1bf7d1e627108531b433bbe
(merged core/block/theme/user data). Both exact original archive members
were inspected without executing the vendor runtime.

## Runtime contract

One canonical settings record stores site_profile version 1: published,
draft, previous and can_restore. Each non-null identity has the same strict
allowlisted field schema. The owner-only /admin/site-profile route previews
the saved draft; POST /admin/site-profile/save applies one authorized
revision-checked transition. Publish commits only the saved draft, not
unsaved form values. Restore swaps with the preceding published identity
and discards the draft. All settings patches preserve unrelated fields.
The existing AuthService and CSRF gate run before the controller; the
canonical settings row lock rejects concurrent stale edits. Audit records
action metadata, not draft values or unrelated settings.

The public resolver projects only a validated published identity. Existing
Nebo reference copy is isolated in config/reference-site-profile.php and
only enabled for the configured Nebo host (not the incoming Host header).
A separate installation without a published profile displays a neutral
identity; invalid/unavailable published data never selects a different
installation's identity. Optional empty values remain empty. The previous
logo/favicon/palette and phone/WhatsApp contracts remain authoritative.
Publishing a profile does not reset owner-selected assets or rebrand authored
page bodies. Known stale inherited artwork remains excluded; a configured
profile without a logo renders text. Public manifests use only actual
available configured PNG icon dimensions; full PWA icon generation deferred.

Public header/footer/contact, default search metadata, organization structured
data, public Markdown/OpenAPI and public/admin manifests use the profile.
Legacy reference defaults remain on the existing deployment until the owner
publishes a profile. Configured profiles do not inherit reference SEO or
social contacts. The owner editor contains a saved-draft preview and field
comparison; it is not an arbitrary HTML editor or whole-site preview.
No new table or paid dependency is introduced.

## Acceptance and limitations

- tests/site-profile.test.cjs: domain transitions, strict validation,
  two isolated in-memory settings fixtures rendered through actual PHP routes,
  public metadata and manifests, draft isolation and unavailable/unconfigured
  projections.
- tests/browser/site-profile.cjs: actual PHP contact and editor templates at
  1440/375/390 for two identities. 12 Chromium fixture scenarios, no overflow
  or page errors.
- tests/site-profile-integration.cjs: real owner routes in disposable MariaDB,
  auth/CSRF, draft/confirm/publish/restore, stale revisions, unrelated settings,
  public output and separate synthetic identities. Runs in GitHub CI; do not
  report production acceptance from this fixture.
- General page bodies, navigation ordering, policies, typed service/form
  builders, interactive A2UI actions, and a second independent deployed client
  are not complete. No automatic production profile publication or customer
  record mutation is authorized by this implementation.
