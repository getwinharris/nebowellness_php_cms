---
name: bapxcms-cli
description: Use when running or changing the repository-owned bapXcms CLI, including orientation, validation, maps, knowledge indexes, schema, database, logs, blogs, images, and development-user commands.
---

# bapXcms CLI

Treat `./bapXcms help` and the dispatch in `cli/bapXcms` as executable evidence.
Do not infer a command from old documentation. Normal coding tools and plain Git
remain valid; the CLI is for project-owned workflows, not a wrapper requirement for
every filesystem or Git operation.

## Orientation and query workflow

```bash
./bapXcms help
./bapXcms map
./bapXcms schema list
./bapXcms route:list
./bapXcms skills
./bapXcms status
./bapXcms understand
```

Read `.agents/index.yaml` discovery and query it narrowly. Use `docs/project-index.json` for
existence, `.agents/map.mmd` for wiring, original files for truth, and `/remotedb` for live
admin-editable values.

## Validation and generated artifacts

```bash
./bapXcms lint path/to/changed.php
./bapXcms test
./bapXcms rea status
./bapXcms rea skills
./bapXcms rea skill rea-wp-abilities-audit
./bapXcms rea skills-check
./bapXcms release:identity
./bapXcms ai:probe text
./bapXcms ai:probe tools
./bapXcms release:verify https://nebowellness.com
./bapXcms rea check
./bapXcms update
./bapXcms ci
./bapXcms index
./bapXcms docsmap
./bapXcms codemap
```

`update` regenerates the REA progress report, committed maps and indexes; `ci` checks drift. Never hand-edit
generated maps or `.agents/index.yaml`.

The two developer artifacts are written only to `.agents/map.mmd` and
`.agents/index.yaml` by default. The direct PHP generators accept an explicit
alternate output for verification. The CLI's map/index/update/CI commands use
the private canonical paths; the docs map and project index remain in docs/.
GitHub issues, not the retired local task/objective commands, track all work.

## Content, database and operations

```bash
./bapXcms read blog [slug]
./bapXcms write blog [slug]
./bapXcms blog:image <slug> <image> [options]
./bapXcms db query <collection> [filters]
./bapXcms db find <collection> <id>
./bapXcms db init
./bapXcms db status
./bapXcms logs [--limit N]
./bapXcms dev:user [--dry-run]
```

Blog commands operate on Markdown files. Product and other admin-editable values
must come from hosted MySQL. `db sync` currently performs initialization only; do
not describe it as JSON/YAML import or export.

## Scope

There is no `bapXcms tui` or application-agent CLI. The two application agents are
the customer support and owner/admin web surfaces. User attachments and web QA use
the active coding client's explicit tools and paths, outside this application CLI.

## Testing

For a CLI change, compare `./bapXcms help` with the dispatch and target file, execute
the smallest safe command, then run `./bapXcms ci`. Never print environment files,
credentials, secret values or customer records.

## Keeping this skill current

Update this skill when help, dispatch, an implementation file, or the binding
contract changes. A help entry without an implementation is a gap, not a feature.
