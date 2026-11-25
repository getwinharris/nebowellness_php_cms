# AGENTS.md

Repository instructions for coding agents.

## Repository

- `getwinharris/nebowellness_php_cms` is the only working repository and the Hostinger
  deployment source. Issues, branches, PRs and releases all live there.
- The repository is independent and unforked. Do not add an upstream remote or
  synchronise from any other copy.
- Project skills live in `.agents/skills/<name>/SKILL.md`. Git hooks live in
  `.agents/hooks/`. Activate them with `./bapXphp hooks install`. GitHub Actions
  workflows live in `.github/workflows/`.
- The product has exactly two PHP agent surfaces: customer support and owner/admin chat.

## Work order

### Verification depth

For repository questions and changes, use at least three evidence hops before answering
or committing. Continue beyond three whenever the affected workflow crosses more layers:

1. Read this file and only the `discovery`, `query_examples`, and `summary` block at
   the top of `index.yaml`.
2. Narrow-search `index.yaml` or `docs/project-index.json` for the exact route,
   concept, filename, collection, class, or skill.
3. Open the returned original source and answer with its path/symbol.

The minimum is entry instructions → exact index match → original source. For changes,
also follow the affected route through controller, service, schema/runtime collection,
view, and rendered browser state where those layers exist. Do not run broad directory
listings, recursive globs, Git history, or read the whole generated index when narrow
queries answer the question. Broaden only when a target is absent, and state that
absence as a finding.

1. `./bapXphp map` and `./bapXphp schema list` before proposing any change.
2. Read this file.
3. **Check `docs/project-index.json` before claiming any feature exists.** It is the
   generated, committed inventory of every route, controller, service, view and
   collection. If something is not in it, it does not exist — add it rather than
   assuming it is there. Read `docs/systematic-map.mmd` for the wiring diagram.
   Use root `index.yaml` to query original project concepts and relationships,
   including blogs, images and hosted `/remotedb` collections. Read its `discovery`
   section first and query narrowly; never load the entire generated file into context.
4. Search GitHub for an issue with the same scope. Update a match or open an
   evidence-backed issue with reproduction steps, affected paths, suspected cause,
   and acceptance checks. Skip issue changes for read-only diagnosis or when the
   user declines tracking. One agent owns the issue for each scope and rechecks
   GitHub immediately before creating it.
   In Codex, use the connected GitHub plugin for issues, PRs, checks, and merges.
   Other agents running locally use `gh` for those operations. Use plain Git for
   branch, commit, and push operations.
5. Read the matching `.agents/skills/<name>/SKILL.md`.
6. Read `storage/schema/collections.php` for schema and `Design.md` for UI.
7. Inspect existing implementations before creating any file, route, service, view,
   collection or navigation item.

### Improve the guidance with the change

When improving a feature or workflow, review the project skills and canonical
docs that describe the affected behavior. If the work changes a contract, reveals
stale guidance, or establishes a reusable implementation or verification step,
update the relevant `.agents/skills/<name>/SKILL.md` and canonical document in the
same PR. Put product behavior in its owning document, repeatable steps in the
skill, and turn-specific evidence in the issue or PR.

Use the scope of the work to decide what to revise. A homepage layout improvement
can update `Design.md` and the frontend skill with the new layout contract and
browser checks. A change with no reusable lesson or contract change can leave
the existing guidance as written. Regenerate affected indexes and maps with
`./bapXphp update` and record the guidance changes in the PR.

### UI workflow

1. Before editing UI, read [Design.md](Design.md) and the matching public or admin
   UI skill. Identify the route, template, stylesheet selectors, and JavaScript
   that own the behavior. Follow the controller and service when the flow reads
   or writes server state.
2. Record the issue, baseline behavior, and acceptance checks before the first UI
   edit. Use the built-in Browser at desktop and 375px. For responsive changes,
   also check public pages at 390×844 and include both sides of affected breakpoints. Record unavailable browser
   access or data and label source findings as unverified in the browser. If
   GitHub writes are unavailable, keep the issue draft and record the blocker.
3. Assign responsibilities before implementation. PHP renders semantic HTML and
   owns authorization, CSRF, validation, and persisted state. JavaScript owns
   browser state and interactions such as URL fragments, reactive controls,
   and asynchronous updates through existing routes. CSS owns responsive
   layout, tokens, and visual states.
4. Put new and migrated app styles in `assets/css/band.css`. Move affected
   legacy rules into that file, remove their duplicates in the same change, and
   verify the resulting cascade. Use existing JavaScript assets or a focused,
   deferred script under `assets/` for browser behavior. Move affected inline
   behavior into that asset when editing it.
5. Repeat the baseline scenarios in the Browser, including relevant keyboard,
   touch, module, and fallback states. Record the results and run `./bapXphp ci`
   before review. Use synthetic data and redact credentials and personal data
   before sharing evidence. Mark unrun checks explicitly.

## Architecture

- **Frontend:** PHP templates in `views/`, browser JavaScript in `assets/`, and
  `assets/css/band.css` as the canonical stylesheet, following `Design.md`. No SPA
  or build step.
- **Backend:** controllers and services in `app/`. Route → controller → service →
  MySQL via `DatabaseService`.
- **Schema:** `storage/schema/collections.php` is canonical. Update it before changing
  any collection shape.
- **Runtime store:** hosted MySQL only for admin-editable records. Do not create local
  product, category, consultant, order, user, setting, media-metadata or secret copies.
  Blog posts are Markdown with YAML frontmatter in `content/blog/posts/`; image
  binaries remain files. Media uploads and usage are stored in MySQL `media_files`.
  The media library also reads legacy YAML and bundled artwork as protected assets.
- **Agents:** customer support and owner/admin chat are the two agent surfaces.
  PHP handles server requests and JavaScript handles browser interaction. Admin
  agent context goes through `AgentContextService` and stays aggregate and scoped.

## Environment

- The remote endpoint is `/remotedb`, all lowercase. `DatabaseService::remoteCall()`
  must throw on transport, non-2xx and invalid-response failures; never convert a DB
  outage into an empty catalogue.
- `.env` holds only `APP_NAME`, `APP_URL`, the `BAPX_MYSQL_*` values and
  `BAPX_REMOTE_DB_URL`. Every other secret belongs in the MySQL `secrets` table via
  Admin → Integrations.

## Model facts you must not guess

Your training has a cutoff and this project uses models released after it. **Search the
web before asserting that a model, endpoint or API field does not exist.** Saying "that
model name looks wrong" without checking sends the owner chasing a configuration problem
that is not there.

- `gemma-4-31b-it` **is a real, current model** on the Gemini API, served from
  `https://generativelanguage.googleapis.com/v1beta/models`. It is the configured model
  for both agents. Proof:
  <https://ai.google.dev/gemma/docs/core/gemma_on_gemini_api> and
  <https://huggingface.co/google/gemma-4-31B-it>. It was wrongly called fake twice
  during development; the real defect was ours, in response parsing.
- It is a **reasoning model**. Its reply may contain the model thinking out loud, either
  as parts flagged `"thought": true` or as prose before the answer. Read the answer with
  `AiClient::answerFromParts()`; never take `parts[0]`, which is usually the reasoning.
  Reference: <https://github.com/google-gemini/cookbook/issues/1198>.
- Google documents `minimal` and `high` thinking levels for hosted Gemma 4. The
  REST payload sets `generationConfig.thinkingConfig.thinkingLevel`. Keep the
  response-part filter in both agent paths and treat a thought-only completion
  as unusable. See <https://ai.google.dev/gemma/docs/core/gemma_on_gemini_api>.

The general rule: when a fact about an external service decides what you change, verify
it against that service's own documentation and record the URL beside the code.

## Testing

This is a web app and a PWA. **Verify in a browser, not the terminal** — desktop and
375px mobile. Terminal checks cover generators, schema and tests only.

When working from Codex or ChatGPT Work, use the built-in **Browser** through its
computer-use controls for local and live UI verification. Do not use an external
Playwright CLI, standalone browser driver, or substitute terminal HTTP checks for
the browser workflow. Keep UI actions grounded in the current browser state.

After route, schema, controller, view or workflow changes, run `./bapXphp update`
to regenerate `map.mmd`, `docs/systematic-map.mmd`, and the indexes. Fix stale
expectations to match the agreed behavior; never weaken validation to hide a defect.
Before every commit, read the affected paths in `docs/systematic-map.mmd` after the
final regeneration and confirm the generated wiring matches the implemented workflow.
Repeat this regenerate-and-read check after each logical change set added to that commit.
Run `./bapXphp ci` before the PR and verify GitHub checks before merging. After
deployment, repeat the affected flows in the live Browser and attach only
redacted evidence to the original issues. A merge or an SMTP acceptance message alone does not prove
deployment or inbox delivery.

Local browser-test credentials and the fixed customer test ID are stored in
`.env.test-user`. The file is gitignored; read it locally when authentication is
required, and never copy its credential values into tracked files, command output,
test reports or logs.

```bash
php -S 127.0.0.1:8811 index.php    # local server
./bapXphp ci                        # lint, tests, maps, smoke
```

Check computed styles rather than trusting a screenshot: an author rule like
`display:flex` silently overrides `[hidden]` and breaks JS filters with no error.

## Pull request review and merge

- Include `[**@codex**](https://github.com/codex) review` in the PR description.
  Post the same request as a PR comment after pushing review fixes. Check for an
  existing request for the same head commit before posting.
- Check CI and reviewer findings on the current PR head, fix actionable findings,
  and repeat the checks. When authorized to deploy, merge the PR after those gates
  pass and verify the resulting commit on `main`.

## Rules

- Extend existing controllers, services and views. No parallel implementations.
- Customer-facing UI follows `Design.md`.
- Admin mutations are auditable via `AuditLogService`, which must never break the
  operation it records.
- Secrets are never committed and never placed in `.env`.
- Before pushing to `main`: run `./bapXphp ci` and confirm it is green. `main` deploys
  to production, so a red build can still ship.
- Branch, push, open a PR. Never commit directly to `main`.
