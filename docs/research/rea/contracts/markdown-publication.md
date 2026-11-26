# Markdown publication eligibility

Issue #190, cms-05 under #125/#141. Inspected the original pinned
WordPress 7.1.4 archive members wp-includes/post.php
(SHA-256 34678856a4326b7e76ef120ba80a9e46c6903193e058b988555ec5c1f5cb8f26)
and wp-includes/cron.php
(SHA-256 dc27910a1ad004eb2aaabc19352246cc0b9e601bf2da870e141646fd9dd95591).
The original check_and_publish_future_post verifies status and due time;
wp_schedule_single_event records a timestamped event. Reconstruct the
visibility guarantee without installing a WordPress runtime or job engine.

## Native contract

The original Markdown document and its scalar frontmatter remain
authoritative. The existing blog module and category rules still apply.
A boolean publication flag and valid YYYY-MM-DD date determine eligibility:
hidden, scheduled, visible, or invalid. Eligibility starts at midnight in
the application's existing Asia/Kolkata timezone. Legacy undated posts
remain eligible; malformed dates/flags do not. No polling, write at due
time, or daemon. Index, detail, sitemap and article retrieval share the
same gate. Raw content directory HTTP access is denied even for an
existing file; legal and article routes still render through PHP.
Admin publication status and `./bapXcms blog:publication` report current
editorial eligibility of readable posts (not a background execution log
or a check of all module/category availability). Existing hidden state
survives an edit without an explicit flag. Date validation, scalar
frontmatter, slug validation and atomic replacement guard blog saves;
full concurrent edit leases/revision history remain separate. Scheduled
or hidden posts never cause an automatic newsletter at the due date.

## Acceptance

Isolated temporary Markdown files verify the precise local-midnight
boundary, hidden save preservation, invalid-date rejection without
altering source, direct public route denial, public index, shared agent
article search, and raw source denial. No fixture enters production.
General durable job dispatch, job history, exact-time scheduling and
scheduled CMS page publication are still incomplete; cms-05 remains
partial. No new database engine, schema collection or production content
mutation.
