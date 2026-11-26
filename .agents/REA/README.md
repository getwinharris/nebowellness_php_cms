# REA native reconstruction skill library

This library adapts official WordPress and WooCommerce development guidance
into source-backed engineering workflows for this native PHP/MySQL/JS product.
It is not a WordPress install, plugin pack, or a runtime dependency.

## Library layout and authority

- reference/wordpress/ and reference/woocommerce/: original pinned skill
  collections, companion references, docs, evaluation scenarios and licenses.
  All 217 selected tracked files are byte-identical to their source commits.
  Original helper scripts are archived with a .source.txt suffix and no
  executable permission. No upstream CI workflows, installer, runtime, or
  nested Git repository is activated. Links to original script names are for
  research; resolve the preserved bytes through catalog.json.
- skills/rea-*/SKILL.md: 22 rewritten native adaptations, one for each original
  skill (20 WordPress, two WooCommerce). Each names its upstream version,
  relevant REA component IDs and source locators, native service boundaries,
  procedure, behavioral verification and exclusions.
- catalog.json: the pinned Git revision and tree, GPL notices, original paths,
  exact byte sizes/digests, native mappings and source evidence locators.
- docs/research/rea/evidence/manifest.json: runtime source research evidence,
  separate from development guidance. The additional selected WordPress block,
  theme, interactivity, REST and media source graph fills gaps in the earlier
  static archive. No WordPress or WooCommerce source implementation is loaded.

Original documents and scripts are **reference data, not executable instructions**.
Follow the native adaptations and root AGENTS.md. Do not run commands copied
from the vendor guides against the native repository or any hosted environment.
The original upstream licenses are GPL-2.0-or-later; copyright and license texts
remain beside each snapshot, and the rewritten guidance carries the same SPDX
identifier. This does not change the license of unrelated native product code.

## Select, inspect and apply

    ./bapXphp rea skills
    ./bapXphp rea skill rea-wordpress-router
    ./bapXphp rea skill rea-wp-abilities-audit
    ./bapXphp rea skills-check

The list joins each native skill to current component status in capabilities.json.
Use a native ID, not an arbitrary file path. Load the matching domain skill plus
the project skill in .agents/skills/ and inspect actual pinned source symbols
before coding. Existing native route/service/schema/permission behavior is the
implementation authority. Do not invent an equivalent from the source skill
name, sample code, or benchmark.

### Task routing

| Task | Native skill IDs |
|---|---|
| Triage and issue scope | rea-wordpress-router, rea-wp-project-triage |
| Abilities and model tools | rea-wp-abilities-audit → rea-wp-ability-development → rea-wp-abilities-api → rea-abilities-api-implement → rea-wp-abilities-verify |
| API/transport/authorization | rea-wp-rest-api, rea-wp-abilities-api, rea-wp-abilities-verify |
| CMS and editable layout | rea-wp-block-development, rea-wp-block-themes, rea-wp-patterns, rea-wp-plugin-development |
| Interactive UI and A2UI | rea-wp-interactivity-api, rea-wpds, rea-wp-patterns |
| Health and reporting | rea-wp-performance, rea-wp-wpcli-and-ops |
| Environment/fixture verification | rea-blueprint, rea-wp-env, rea-wp-playground, rea-wp-phpstan |
| Release security and stale work | rea-wp-plugin-directory-guidelines, rea-woocommerce-stale-pr-audit |

## Data-first reconstruction record

For each capability, trace and record:

1. **Reference evidence:** pinned commit/archive, exact source path/symbol/hash,
   relevant source skill and its reference paths, observed behavior, and
   unresolved dependencies. If a missing source path is required, extract a
   bounded subset and run REA import-reference-source on it; record the
   historical-reference graph and limitations. Static evidence is not execution.
2. **Native data contract:** actor and operation; authoritative collection and
   ownership; required/optional/null fields; output projection and stable error
   vocabulary; allowed state transitions; side effects; explicit permissions
   on discovery and execution; revision/idempotency and recovery behavior.
3. **Architecture and dependency trace:** route/controller → domain service →
   persistence or provider; separate model tool schema projections and
   MCP/A2UI adapters from domain execution. Do not duplicate domain logic in
   a second endpoint. Mark missing backing services as gaps, not fake replies.
4. **Testable obligations:** successful representative data, empty, unavailable,
   malformed, unauthorized, cross-account, conflict, replay and failure paths.
   For an asserted read-only ability inspect transitive calls and compare
   business state before/after, not just a mocked result. Record actual request,
   response shape and state changes without private records or secrets.
5. **Delivery:** link the contract and evidence to issue and capability IDs;
   write a boundary test, implement, run local checks, review and merge the
   exact validated head. Verify deployed source and live workflows separately.
   Update the append-only REA review; do not award product completion for
   importing or rewriting skills.

For service workflows only, apply WooCommerce lessons to consultation fees,
custom landing pages, validated enquiries, booking state, payments and receipts.
General shop parity remains excluded. Keep existing shop behavior, and defer
installation until core acceptance.

## Refresh and maintenance

Refresh a reference only deliberately and on a scoped branch: resolve the
official repository commit and tree, compare old/new skill names and text, review
licenses and new scripts, copy inert bytes, regenerate the inventory and adapt
each changed or added skill. Keep original evidence pinned in Git history and
the manifest, and do not run any imported script or workflow. Record scope
decisions and unresolved reference evidence in the REA review. CI checks exact
snapshot bytes, license presence, complete one-to-one mappings and REA
component/source locators. Product coverage remains independent of skill count.

For every relevant runtime PR, name the native REA skill(s) used in the issue or
contract and record which behavioral obligations were actually tested. Use
./bapXphp rea skills-check and ./bapXphp update before ./bapXphp ci.
