---
type: doc
title: Release and commit workflow
description: How to record changes, select a version, and verify deployment.
category: docs
---

# Release and commit workflow

- Use a scoped branch and one commit per coherent change. Prefer Conventional Commits
  subjects such as `fix(forms): preserve verified email on retry` or
  `docs(repo): explain route discovery`. Explain the reason in the body when it
  is not obvious and link the issue from the PR. Preserve published history.
- Record user- or operator-visible changes under **Unreleased** in [the changelog](changelog.md).
  Leave routine internal edits in Git history.
- Use annotated `vMAJOR.MINOR.PATCH` tags for releases. Start at `v0.1.0` when
  the first baseline is selected. While on `0.x`, use minor bumps for features
  and explicitly documented breaking changes, and patches for compatible fixes.
  Starting at `1.0.0`, use major for breaking changes, minor for compatible
  features, and patch for compatible fixes.

## Cut a release

1. Select the version and identify its entries under Unreleased. Document any
   migrations, configuration changes, or rollback considerations in the PR.
2. Run `./bapXphp update` and `./bapXphp ci`, address review feedback, and merge
   through the normal PR workflow.
3. Verify that Hostinger deployed the intended main commit and check affected
   production flows in the built-in Browser. Record the tested commit and results.
   If deployment or verification fails, keep the entries under Unreleased and
   resolve the failure before proceeding.
4. Move the verified entries into a dated release section in a follow-up changelog
   PR. Create an annotated tag on the verified deployment commit and publish GitHub
   release notes from that section. Never move a published tag. Publish a
   corrective release instead.

Main can deploy without a tag. A version labels a verified deployment and does
not itself deploy anything.
