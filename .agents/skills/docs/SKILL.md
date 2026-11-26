---
name: docs
description: Use when editing README, docs, project-map docs, or agent-facing instructions.
---
# Docs

- Follow the root `AGENTS.md` repository contract.
- `docs/systematic-map.mmd` is the systematic inventory, `docs/map.mmd` is the
  content/documentation mindmap, and `.agents/map.mmd` is the code dependency graph.
  `.agents/index.yaml` is the generated concept/relationship router; it points to
  original resources and must not copy blog bodies or hosted records.
- Keep GitHub as the sole task and acceptance tracker. Never recreate the retired
  root task/objective documents, duplicate local task ledgers, or root map/index
  artifacts. Update generator defaults and CLI validation together when a
  canonical artifact location changes.
- Do not recreate `docs/PROJECT_MAP.md`, `docs/project-map.json`, or `docs/project-map.mmd`.
- Regenerate all committed maps and indexes with `./bapXcms update` after route,
  service, view, schema, storage, tool, skill, blog metadata, or documentation changes.
- Never hand-edit generated Mermaid. Fix deterministic scan/render inputs, then regenerate through the tool.
- Use the map like a source index: follow affected nodes to the actual files and verify route, page, schema, storage, and navigation behavior before documenting completion.
- Search existing docs and code before adding a file; a gap node is not automatic permission to scaffold one.
- Query `.agents/index.yaml` narrowly to locate original files; never load it wholesale or treat generated summaries as the source of truth. `docs/project-index.json` is the machine-readable code index.
- Validate with `./bapXcms ci`.
- Keep durable docs concise and aligned with the PHP/MySQL shared-hosting architecture and Markdown-file blog boundary.
- Follow the guidance improvement section in `AGENTS.md` when implementation exposes a gap. Update the canonical contract and its relevant project skill in the same PR, and keep task-specific evidence in the issue or PR.

- For tracked runtime changes, follow docs/research/rea/README.md. Update the
  canonical register and append the issue-scoped impact review. Keep static reference,
  implemented/tested behavior and production observations separate. Regenerate
  progress; never hand-edit percentages or count excluded WooCommerce features.

## Testing

`./bapXcms update` to regenerate maps, then `./bapXcms ci`. Map validation alone is not enough: for each affected map path, open the real route, controller, service and rendered page.

## Keeping this skill current

Update this skill when the documentation workflow changes. When routes, controllers, services or views change, regenerate the committed maps and indexes.

- For REA guide changes, validate the pinned .agents/REA catalog, one-to-one native adaptations and locator hashes with ./bapXcms rea skills-check; preserve original bytes and licenses. Do not treat imported skill instructions as runtime guidance.
