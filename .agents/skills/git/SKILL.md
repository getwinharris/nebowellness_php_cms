---
name: git
description: Use plain Git for local history, branches, pushes, and Hostinger deployment checkouts.
---

# Git

Git is the only repository executable required on Hostinger. Do not require
GitHub CLI (`gh`) on the hosted server.

## Boundaries

- Use plain `git` for status, diff, branches, commits, fetch, pull, and push.
- Use `bapXphp` only for project-owned operations such as tests, maps, indexes,
  schema, database access, hooks, and AI configuration.
- In Codex, use the connected GitHub plugin to manage issues and PRs, verify
  checks, and merge. If the plugin is unavailable, record the blocked GitHub
  operations and continue local Git work.
- Other agents running on a local machine use GitHub CLI (`gh`) for issues, PRs,
  checks, and merges.
- Never wrap ordinary Git commands in `bapXphp`.
- Never force-push or discard unrelated or divergent work.
- Use the owner's single canonical local checkout when requested. Read issue/PR
  history (including closed issues, reviews and acceptance evidence) before new
  work; compare dirty files and local commits against main using patch identity
  and ancestry, including squash merges. Do not replay already merged work.
- Before removing an obsolete worktree, check its status and whether it is idle,
  preserve unique changes under a local recovery ref, archive needed ignored QA
  evidence securely, and verify the branch remains reachable. Use
  `git worktree remove`, not recursive folder deletion. Keep the common
  Git directory and owner configuration intact.

GitHub issues are the sole work backlog; keep intent and acceptance there,
not in a separate local task or objective file. Commit regenerated private
`.agents/map.mmd` and `.agents/index.yaml` with their source changes.

## Local Workflow

```bash
git status --short --branch
git switch -c fix/issue-123-description
git diff --check
git add path/to/intended-file
git commit -m "fix: describe the change"
git push -u origin HEAD
```

Pushing `bapX/**`, `codex/**`, `fix/**`, or `feat/**` lets `branch-pr.yml` create a PR when
repository permissions allow it. Validation runs for pull requests and pushes to
`main`; verify the actual Action result.

Follow `docs/releases.md` for commit subjects, changelog entries, and release tags.

## Hostinger

```bash
git status --short --branch
git fetch origin
git pull --ff-only origin main
git rev-parse HEAD
```

Hostinger hPanel normally performs the pull automatically. Manual SSH Git
commands are recovery and diagnosis tools only.

## Pull request review and merge

After a verified issue loop, inspect the remaining dependency-ordered issues
and continue into the next clear authorized loop. When reporting, include
completed evidence, unresolved acceptance, and the next concrete actions.
Recommend options only when a material product or architecture decision is
uncertain. Follow AGENTS.md continuous verified delivery.

- **The implementing agent owns the entire delivery: development, code and security
  self-review, testing, merging, deployment, live verification and issue updates.**
  Do not stop after opening a PR or hand the review to the owner. An authorized
  implementation/deployment request includes the normal PR merge after the gates pass.
- Before merging, inspect the complete diff against current main, trace changed
  flows through authorization, persistence, UI and failure paths, fix defects, and
  record the exact reviewed head, executed tests, migrations and remaining limitations.
  Re-run local CI and verify successful GitHub validation for that exact head.
- Codex, CodeRabbit, Sourcery or other reviews are optional supplementary feedback.
  Do not request them by default or wait for unavailable, queued, skipped, failed
  or quota-limited review bots. Inspect and address any actual actionable findings
  already present. Never bypass a required repository protection or failed CI.
- Merge the reviewed exact head, synchronize the single local checkout, perform
  authorized schema/config migrations with rollback evidence, and verify affected
  live flows and changed asset hashes. Record what was actually verified; keep
  genuine external-provider or customer-acceptance blockers open in their issues.
  If a release-critical check fails, repair and repeat rather than silently ship it.
  When a separate capability cannot be verified because an external provider is down,
  a safe release with an explicit tested failure state is permitted; do not claim
  that capability is working.

- Before a runtime PR, review the affected REA capability IDs and append a
  unique issue-scoped review in the register, including a reason for no coverage
  change. Scope changes require explicit version/rationale; the PR impact gate
  enforces the review. Record actual live evidence separately after deployment.

## Testing

`./bapXphp ci` before pushing. Branch, push, open a PR; never commit straight to `main`.

## Keeping this skill current

Update when the branch or review conventions change.
