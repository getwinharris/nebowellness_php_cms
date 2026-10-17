---
type: doc
title: Remote Database Operations
description: The application connects directly to hosted MySQL with the BAPX_MYSQL_* values in .env.
category: module
---
# Remote Database Operations

The application connects directly to hosted MySQL with the `BAPX_MYSQL_*` values in `.env`. If direct MySQL is unavailable from a developer machine, `DatabaseService` and `bapXphp db` use `APP_URL/remotedb`; production resolves that fallback to `https://www.nebowellness.com/remotedb`.

Remote reads are memoized for the lifetime of one PHP request, including across service instances, and the cache is cleared after every mutation. Transport errors, non-2xx responses, and malformed payloads throw exceptions instead of being treated as valid empty collections. The `/remotedb` controller is forced to terminate at directly configured hosted MySQL so it cannot recurse into itself when MySQL is unavailable.

`/remotedb` accepts allowlisted read queries for diagnostics. Requests authenticate with the existing MySQL password, sent in the request body or as a Bearer token. Record mutations use explicit `upsert`, `delete`, or `replace` actions against declared schema collections. The bridge fails closed when the password is absent.

Set `BAPX_MYSQL_PASS` in the ignored operator environment. `DatabaseService` and `bapXphp db` include it automatically in every remote call. Never place it in a URL, tracked file, issue, or log.

```bash
bapXphp db status
bapXphp db upsert products '{"id":"prod-example","slug":"example"}'
bapXphp db delete products prod-example
```

Use `bapXphp db hosted` for owner-authorized SQL, including protected `secrets` maintenance. Do not use `db raw` for mutations. Product image imports use authenticated record operations when direct MySQL is unavailable.
