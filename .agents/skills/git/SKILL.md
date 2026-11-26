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

- Include `[**@codex**](https://github.com/codex) review` in the PR description.
  Post the same request as a PR comment after pushing review fixes. Check for an
  existing request for the same head commit before posting.
- Check CI and reviewer findings on the current PR head, fix actionable findings,
  and repeat the checks. When authorized to deploy, merge the PR after those gates
  pass and verify the resulting commit on `main`.

## Testing

`./bapXphp ci` before pushing. Branch, push, open a PR; never commit straight to `main`.

## Keeping this skill current

Update when the branch or review conventions change.
