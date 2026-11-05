# UPSTREAM: scroll-craft

Provenance record for the vendored `scroll-craft` agent skill and its engine.
`NEBO.md` is the project adapter. Every other file in this directory came from
upstream and is read-only (see **Rule**).

## Source

| Field | Value |
|---|---|
| Upstream repository | <https://github.com/nateherkai/scroll-craft> |
| Path in upstream | `plugins/nateherk-design/skills/scroll-craft/` |
| Upstream release | **0.3.1** (`plugins/nateherk-design/.claude-plugin/plugin.json` `version`; latest `CHANGELOG.md` release, 2026-09-30) |
| Installed | 2026-11-05 |
| Licence | MIT, Copyright (c) 2026 Nate Herk. The skill directory carries no licence file of its own, so the upstream repository-root `LICENSE` was vendored as `LICENSE`. |
| Research snapshot read | `/tmp/sc/scroll-craft-main` (external copy used for review; not referenced at runtime) |

The engine files carry no separate version string. The release version above plus
the sha256 hashes below are the version identity.

## Rule: vendored read-only

- Every file copied from upstream is **byte-identical and never edited** in this
  repository. Upstream `SKILL.md` (Bootstrap) is explicit: the engine is the
  mechanism, never edit it per project, theme it with tokens.
- Project adaptation belongs in `NEBO.md` only. `UPSTREAM.md` is provenance
  metadata and is replaced on upgrade.
- An upstream fix belongs upstream, then a re-vendor (see **Upgrade procedure**).
  A local patch to a vendored file makes the next upgrade unknowable.

## Files copied (byte-identical to upstream)

All paths below are relative to `.claude/skills/scroll-craft/`. `LICENSE` is the
upstream repository-root file; the other seventeen files are copied from the
upstream skill directory.

| Path | sha256 |
|---|---|
| `SKILL.md` | `6e798bb51c3dcbe4f35f098dcdd3cdd20d08e538a33993bcae909c9fd0a500a1` |
| `CHANGELOG.md` | `1be4ff92fc5076895b071f39c067cafe1c5063c40d6890cfd3d20b23b64fc68b` |
| `LICENSE` | `dd471a27eca72488a53e6ea6fbe9392d81aa3f01e950e63fe328affb2ddfa6e3` |
| `engine/scrollcraft.js` | `4a419b3ede51f64790af7b56c0195ea01c98a84df8e0e768ebf343956b922f39` |
| `engine/scrollcraft.css` | `e19eb3aa73b0944626ca56e6e427c76f44753481e04d60f4bac20e8cf1d7758a` |
| `references/approved-collection.md` | `8a963a46af4c6ed40656fb5b21589629ee3ce30aa06a8b31c10bc132b74f2780` |
| `references/assets.md` | `0cc3871ad903ccaf3bf769cf3a05a54a9326ed558ac3e45e57f19043ce70efa6` |
| `references/device-diag.html` | `8ec61db711e3f74ec38a81f418a051a88d9574240f5bbde4716ed9f053775e59` |
| `references/devices.md` | `e5ae5a4f2924fc1b13177bf0b0ac10f04e686edd916dc33755ed1420a6a478f5` |
| `references/feel.md` | `3951f1f0331f329dab14e8766edeecb6ce8e05437ea8986c48a074726bef41a0` |
| `references/hero-depth.md` | `2426fb9703d4398b1e3b666b2db7f4b68e7cd41112da77700a7d63f2965dd726` |
| `references/taste.md` | `f33c581024f466ab98612ae291aa46ccbc326875f8fa0918a3f45455a2c60e80` |
| `references/template.html` | `4be3e8d772cf063ea5d1e695d6c39443920b2ff0e8491656013bebb87d098096` |
| `references/uniqueness.md` | `0fd65a43624da868ca2027946bd4ce152605d6ea856eeae93b35721714c8b20e` |
| `references/verify.md` | `de645d648f738f3e19452083eadcc649cb8477058b6ad79ab4fa29198406150a` |
| `references/worldflight.md` | `451fead8f90604423b113cd0b0d3bfb6be1f7ca7a7b5ab9e2688ea3da762af4c` |
| `references/worlds.md` | `dea3e4cc20c7245f87638a6d88eb2e3fc22a69dabc9f75f001dd97a3121077bd` |
| `templates/FINGERPRINTS.md` | `8c1597e6b068bfa77086f847cf90b9e2ebeba3ec42f1e6cc522e222e2700da16` |

`references/` contains **12** files at release 0.3.1, including the non-Markdown
payloads `device-diag.html` and `template.html`. The task brief said 11; the
upstream directory has 12 and all 12 are vendored.

## Runtime engine copies (deployable, byte-identical)

These are the bytes the pages load. `engine/` above is kept for provenance and
upgrade diffing only; it is not loaded by PHP.

| Path in this repository | Copied from | sha256 |
|---|---|---|
| `assets/vendor/scrollcraft/scrollcraft.js` | `engine/scrollcraft.js` | `4a419b3ede51f64790af7b56c0195ea01c98a84df8e0e768ebf343956b922f39` |
| `assets/vendor/scrollcraft/scrollcraft.css` | `engine/scrollcraft.css` | `e19eb3aa73b0944626ca56e6e427c76f44753481e04d60f4bac20e8cf1d7758a` |
| `assets/vendor/scrollcraft/LICENSE` | upstream repository-root `LICENSE` | `dd471a27eca72488a53e6ea6fbe9392d81aa3f01e950e63fe328affb2ddfa6e3` |

The runtime copies are never edited either. They are loaded with the existing
`?v=<?= filemtime(...) ?>` cache-busting pattern (see `NEBO.md` §2), and the only
project-edited scroll stylesheet is `assets/css/scrollcraft-nebo.css`.

**Path note.** The first draft of the plan used `assets/js/scrollcraft.js` and
`assets/css/vendor/scrollcraft.css`. `tests/run.php` asserts that `assets/js`
must not exist (guard against the removed legacy SPA directory), so the Lead
approved the corrected location `assets/vendor/scrollcraft/`. Do not recreate
`assets/js/` or `assets/css/vendor/` for this engine.

## Files deliberately omitted

`scripts/` is not vendored. None of it is read by `SKILL.md` when authoring a
page: the engine and the references are the complete payload.

| Omitted file | Upstream purpose | Why omitted here |
|---|---|---|
| `scripts/doctor.mjs` | preflight for node, ffmpeg, playwright, Chrome, API key, workspace | requires Node/ffmpeg/Playwright/paid key |
| `scripts/encode.sh` | dense-GOP video encode for scrubbing | requires ffmpeg; no video is used |
| `scripts/kie.mjs` | paid kie.ai image/video generation | paid asset generation is out of scope |
| `scripts/serve.mjs` | Node static server for the verification harness | Node runtime; not used |
| `scripts/shoot.mjs` | Playwright screenshot/scroll harness | Playwright is a browser runtime dependency |
| `scripts/workspace.mjs` | Node workspace/fingerprint resolver | Node runtime; registry decision is in NEBO.md |
| `scripts/worldflight-assert.mjs` | Playwright worldflight assertions | Playwright; worldflight is not used |

Reasons in the repository contract: this is a no-build-step PHP app (`CLAUDE.md`,
Architecture), `.claude/skills/frontend-php/SKILL.md` forbids adding a browser
runtime or browser dependency, and `CLAUDE.md` Testing requires the built-in
Browser for UI verification. No npm, Node, Playwright, ffmpeg or paid API key is
introduced by this install.

Also not vendored because they are outside the skill payload: the upstream
repository's `EXAMPLES.md`, `media/`, other plugins, and a generated build
workspace (`builds/`, `.scrollcraft.json`). Only the registry seed template is
vendored (`templates/FINGERPRINTS.md`).

## Verification at install (2026-11-05)

Engine copies, including the runtime copy, hash identically:

```
4a419b3ede51f64790af7b56c0195ea01c98a84df8e0e768ebf343956b922f39  upstream engine/scrollcraft.js
4a419b3ede51f64790af7b56c0195ea01c98a84df8e0e768ebf343956b922f39  skill engine/scrollcraft.js
4a419b3ede51f64790af7b56c0195ea01c98a84df8e0e768ebf343956b922f39  assets/vendor/scrollcraft/scrollcraft.js

e19eb3aa73b0944626ca56e6e427c76f44753481e04d60f4bac20e8cf1d7758a  upstream engine/scrollcraft.css
e19eb3aa73b0944626ca56e6e427c76f44753481e04d60f4bac20e8cf1d7758a  skill engine/scrollcraft.css
e19eb3aa73b0944626ca56e6e427c76f44753481e04d60f4bac20e8cf1d7758a  assets/vendor/scrollcraft/scrollcraft.css

dd471a27eca72488a53e6ea6fbe9392d81aa3f01e950e63fe328affb2ddfa6e3  upstream LICENSE
dd471a27eca72488a53e6ea6fbe9392d81aa3f01e950e63fe328affb2ddfa6e3  assets/vendor/scrollcraft/LICENSE
```

`cmp` reports both engine files identical. `SKILL.md` frontmatter is unchanged:
`name: scroll-craft`, `description: ...`, `allowed-tools: ...`, and it satisfies the
repository's portable-frontmatter test regex in `tests/run.php`.

## Upgrade procedure

1. Download the new upstream release of
   `plugins/nateherk-design/skills/scroll-craft/`.
2. Re-copy the files listed under **Files copied** and the three runtime copies
   byte-identically: no manual edits, no reformatting, no line-ending changes.
3. Update the release version, install date and every sha256 in this file.
4. Re-run the verification commands above; confirm `cmp` equality and
   `name: scroll-craft`.
5. Re-read `NEBO.md` against the upstream `CHANGELOG.md`. Adapter changes go in
   `NEBO.md` only.
