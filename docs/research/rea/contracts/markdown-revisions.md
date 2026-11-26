# Native Markdown article revisions

Issue #195, cms-03/cms-04 under #125/#140. Original WordPress 7.1.4
archive members checked against the pinned REA graph: wp-includes/revision.php
SHA-256 1dc942d5eb3d9cee81b6c705a6fd52917c4ce5e5f509ece5ad6f748e345f8647
(wp_save_post_revision, wp_get_post_revisions, wp_restore_post_revision);
wp-admin/includes/post.php
86e825dbf107fc03f8ddb30141f25dbaac17b0526f6681d8b0d186a2bfaab1de
(wp_check_post_lock, wp_set_post_lock, wp_create_post_autosave).
No vendor runtime installed or executed.

## Native editorial contract

The existing Markdown file is the canonical article. Its exact-byte SHA-256
is the revision token. All supported article writers must use BlogService
with an explicit observed token, or the literal new for create-only. The
slug is permanent; collisions and stale saves/toggles/deletes are rejected.
The same private cooperative file lock covers read/check, prior snapshot
and atomic source replacement. A second byte comparison precedes commit.
The previous source is preserved verbatim in an immutable content-addressed
Markdown snapshot, in a per-installation sibling directory outside the
application and web document root. Directories are 0700, snapshots 0600;
unsafe permissions, symlinks and invalid checksums fail closed. Files are
bounded to 512 KiB, with a maximum of 100 distinct prior versions per slug.
At the limit refuse further distinct revisions rather than silently
delete history. No automatic historical cleanup or destructive migration.

Admin history lists metadata only. Explicit selection shows current and
prior title/body as escaped text (bounded preview); confirmed POST restore
requires the current revision and exact selected snapshot. It restores
editorial fields and retains current publication flag, date, category
and type. No newsletter is sent on restore. Deletion of an article through
the existing owner action archives its prior bytes; a deleted slug with
retained history is reserved. Recovering a deleted article needs a
separate explicit workflow, not silent slug reuse.

Native CLI blog/doc writers use BlogService, reject stale edits, preserve
hidden state, and default new content to hidden. The crop command uses
content-derived new image names and a checked article metadata update.
A failed crop reference save can leave an unreferenced image; never
overwrite historic source image bytes intentionally. Agent-generated
article forms create hidden drafts only; the owner publishes separately.
Arbitrary privileged manual file writes and Git deployment do not
participate in the cooperative lock. This is not a distributed lock or
a complete media history system. Keep private history in protected
backups and avoid deploying over owner-modified article source files.

## Acceptance

Temporary Markdown fixtures validate exact prior bytes, private permissions,
new-slug collision, stale edits and concurrent writers, invalid history,
publication-preserving restore and deleted slug reservation. Actual admin
routes test owner/CSRF gates, comparison, explicit confirmation and stale
restore. Browser fixtures use actual rendered HTML at 1440/375/390px,
checking comparison, current revision form binding, hidden state, no
overflow or page errors. No database server, customer data or production
article modification. Hosted private directory permissions and production
acceptance remain separate; full page revision history, deletion recovery,
autosave and edit leases remain partial or missing.
