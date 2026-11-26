# Private JSON agent journal

Issue #192, following the #186 storage direction. The existing
source-pinned harness register references DeepSeek Harness
39163dd30f703a79079e2d2446545cff3c453738, packages/core/session/src/repair.ts
(c2835c920aba203c709e44686e55f43bb4d357ea59cfef3561c371b8ff115540)
and docs/subsystems/session-telemetry.md
(455dc79903517067ee2bc0a8cf5689fa8fa400fb7bd8cbd204224ff539ac5a16).
This change adapts the already registered private history/repair boundary,
not full event-log replay or checkpoint recovery.

## Runtime contract

New AgentConversation states explicitly select archive schema version 2.
The private archive root is derived from the canonical installation path,
outside both the application and the serving document root. Never accept
a directory from a request or fall back to public storage. Directories
and files must be owner-private, symlinks are rejected, and storage
failure is reported rather than redirecting new history into remoteDB.
Only CLI test fixtures can inject a temporary path through the test
environment.

The serving code retains the existing archive consent gate: owner chat
archives completed turns automatically; support archives only when
explicitly shared or handed off to the support team. The current prompt
window and replay remain in the actor-bound PHP session. No provider
requests, raw tool results, attachment data, or model reasoning are
persisted in the archive. The event projection retains visible messages,
best-effort credential redaction, and bounded trace metadata. Visible
messages may still contain personal data; protect the directory and
backups. The archive is not a clinical anonymization mechanism.

A cached accepted answer retains its server-confirmed archive status.
If the authorized archive failed, replay retries only persistence, not
inference or a business action. It preserves unavailable until storage
recovers and then reports saved. Changing the archive flag on a replay
cannot newly authorize an originally unshared Support message.

Per-session JSON is validated, locked, staged and atomically replaced.
A repeated event cannot change its recorded content or extend retention.
Independent writers append distinct correlated turn/trace pairs without
discarding the prior snapshot. Successful ticket retries are deduplicated.
Limits: 100 events, approximately 1 MiB, 16 trace steps, 12 source paths.
Expired snapshots cannot be updated while retained or recreated from an entirely expired event snapshot after cleanup. The inventory cap is enforced for new files. Owner-triggered
confirmed cleanup removes at most 100 expired private files; no automatic
cleanup or legacy remoteDB deletion. Interrupted staging files are
ignored and never replace the committed record.

Existing active v1 conversations keep their original archive authority
until session reset/idle expiry. Historical records remain in remoteDB
and are read only through the owner archive compatibility path. No bulk
copy or production transcript migration. Stable session IDs preserve CRM
links. Owner history combines up to 100 recent metadata rows, explicitly
indicates a source outage, and fetches a full transcript only on owner
selection/export. Tickets, customers and CMS remain authoritative
remoteDB collections.

## Verification

tests/private-agent-journal.test.cjs covers permissions, path containment,
event projection, identical replay, concurrent writers, interrupted
staging, stale archive expiry and bounded file cleanup, and owner-only export through the actual router.
tests/agent-session.test.cjs exercises the real Support and Enterprise
chat endpoints with a loopback provider and private temporary archive.
tests/remote-collection-contract.cjs proves new writes/reads require no
remoteDB call, legacy records remain accessible, and a legacy transport
failure preserves local history with an explicit partial state.
No SQL engine or actual production data is used.

Hosted directory permission and authenticated owner/live support acceptance
are separate. No migration is attempted on production and no automatic
deletion of historical rows. Full incremental inference start/step/end
journal and safe interrupted-task recovery are not implemented by this
snapshot format; harness-05/08/10 remain partial.
