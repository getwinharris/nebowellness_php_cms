---
title: bapXcms — service-first CMS and CRM
description: Independent PHP/MySQL service-first white-label CMS and CRM, with Nebo Wellness as the reference deployment.
category: root
---

# bapXcms — Nebo Wellness reference deployment

PHP 8.2 web application for small PHP hosting with a `public_html` deployment. It uses
server-rendered PHP templates, hosted MySQL as the primary runtime store, Markdown
blog posts, and file-based images. There is no SPA or frontend build step.

bapXcms is independently maintained for service-led businesses. Its target is
an owner-configurable CMS, CRM and extensible operational/ERP system with
AI-assisted editing through typed, approved operations. The separate
[bapXphpAiBackend](https://github.com/bapxmediahub/bapXphpAiBackend) is a
commerce-led CMS/CRM; neither repository is an upstream for the other.
Retail shop code inherited by this project is being retired, not expanded.
Service bookings, verified service payments and receipts remain in scope.
See the [product boundary and comparison](docs/product-boundary.md).


## Start here

| Question | First source | Then verify |
|---|---|---|
| What are the agent rules? | `AGENTS.md` | matching `.agents/skills/*/SKILL.md` |
| Does a route/service/view/collection exist? | `docs/project-index.json` | original PHP/schema file |
| How is a request wired? | `.agents/map.mmd` | `app/routes.php` → controller → service → view |
| Where is a blog or image? | `.agents/index.yaml` narrow concept/filename search | returned Markdown/image path |
| What is the database shape? | `storage/schema/collections.php` | owning service/admin form |
| What is a live editable value? | hosted MySQL through `DatabaseService` | lowercase `/remotedb` fallback |
| Which REA skill should I use? | .agents/REA/README.md | ./bapXcms rea skills and native adaptation routing |
| What remains from REA research? | docs/research/rea/progress.md | canonical register, pinned evidence and acceptance tests |
| What controls UI? | `Design.md` | `assets/css/band.css` and PHP view |

Do not read all of `.agents/index.yaml`. Read its discovery header, copy the applicable narrow
query, open the returned original source, and stop when the source answers the question.

```bash
sed -n '1,/^concepts:$/p' .agents/index.yaml
rg -n -B4 -A14 'path: "/campaigns"' .agents/index.yaml
rg -n -B4 -A14 'id: "schema:campaign_pages"' .agents/index.yaml
rg -n -B4 -A12 'filename: "<image-name>"' .agents/index.yaml
```

Use the [feature lookup guide](docs/README.md#feature-lookup) for common workflows
and the [release guide](docs/releases.md) and [changelog](docs/changelog.md) for history.

## Source-of-truth boundaries

- Admin-editable services, consultants, campaign pages, enquiries, users, settings,
  secrets, and related records live only in hosted MySQL.
- Local development uses direct hosted MySQL or configured `<APP_URL>/remotedb`; it
  must not create a local customer-data or service catalogue copy.
- Blog bodies live in `content/blog/posts/*.md` with YAML frontmatter.
- Image binaries live under `assets/images/` or the writable upload location.
- `storage/schema/collections.php` is the canonical database schema.
- `MediaService` stores new upload metadata in MySQL `media_files`; bundled media and legacy YAML are protected compatibility inputs.
- Generated maps and indexes point to originals; they are navigation artifacts, not
  alternate sources of truth.
- GitHub issues are the sole backlog; AGENTS.md and Design.md hold durable
  engineering and product rules. Do not maintain separate local task or
  objective ledgers. The private generated developer map and knowledge index
  are in .agents/; the docs inventories stay under docs/.

## Repository layout

```text
app/routes.php                  HTTP route registry
app/Controllers/               request/auth/response handling
app/Services/                  domain and persistence boundaries
views/                         server-rendered PHP templates
assets/                        CSS, JavaScript, and image binaries
content/blog/posts/            Markdown blog/help articles
storage/schema/collections.php canonical MySQL schema
docs/project-index.json        generated existence inventory
.agents/map.mmd                generated dependency graph
.agents/index.yaml             generated concept/relationship router
tests/run.php                  application regression suite
```

## Local setup

Copy safe connection placeholders from `.env.example` into an ignored `.env`.
Required configuration is the application URL plus hosted-MySQL/direct-remote access.
Application credentials are managed through Admin → Integrations and stored in the
MySQL `secrets` collection; never put real values in tracked files.
Use `APP_NAME` and `APP_URL` for installation identity and Admin → Settings for site
and store behaviour.

```bash
./bapXcms help
./bapXcms map
./bapXcms schema list
./bapXcms serve
```

The development server runs at `http://127.0.0.1:6020`. Browser-test credentials and
the fixed customer test ID are in ignored `.env.test-user`; never print or commit them.

## Verified project commands

```bash
./bapXcms route:list             # registered routes and owners
./bapXcms skills                 # tool-compatible project skills
./bapXcms db status              # direct/remote database readiness
./bapXcms db query products --limit 5
./bapXcms read blog [slug]
./bapXcms write blog [slug]
./bapXcms blog:image <slug> <image> [options]
./bapXcms dev:user --dry-run
./bapXcms logs --limit 20
./bapXcms rea status             # computed component progress
./bapXcms update                 # regenerate REA progress, maps and indexes
./bapXcms ci                     # lint, tests, drift validation, smoke
```

There is no `bapXcms tui` or agent-orchestration CLI. Web QA uses the active coding
client's browser capability and is not part of the application runtime.

## Request architecture

```text
app/routes.php
  → app/Controllers/*Controller.php
    → app/Services/*Service.php
      → DatabaseService
        → hosted MySQL directly
        → <APP_URL>/remotedb fallback
  → views/**/*.php
```

Database transport, HTTP, and invalid-response failures must surface as errors; an
outage must never be rendered as a legitimate empty catalogue.

## Validation

Run the smallest relevant check while working, then the full suite before publishing:

```bash
php -l path/to/changed.php
./bapXcms update
./bapXcms ci
```

For UI changes, test the actual route at desktop and 375px mobile. Terminal smoke tests
do not replace browser validation.

## Deployment

`getwinharris/nebowellness_php_cms` is the deployment repository. The one-time Hostinger
Git integration tracks `main`; changes merged to `main` are pulled to the live site at
https://nebowellness.com/. After merge, verify the deployed revision and live route health—merge
status alone is not deployment proof. See `docs/README.md`, `docs/deployment-hostinger.md`,
`AGENTS.md`, and `docs/systematic-map.mmd` for the verified navigation and operating contracts.

Use plain Git locally. Pushing `codex/**`, `fix/**`, or `feat/**` can create a PR via
`.github/workflows/branch-pr.yml`; `.github/workflows/ci.yml` validates PRs and pushes
to `main`.
