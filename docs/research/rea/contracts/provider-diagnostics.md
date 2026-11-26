---
type: doc
title: Pinned provider diagnostic contract
description: Native text and function round-trip checks derived from reference implementation and provider wire contracts.
category: docs
---

# Component contract: provider diagnostics (completion-08, issue #82)

## Reference evidence

Read-only bapXphpAiBackend revision
17aa4c632b39e80b52c82b30170bc2e84d52a71b.
The reference files were read from that exact Git tree, not the moving local branch.

- app/Services/AiClient.php (SHA-256 de33c42526207ef46ad6870ed9c33b9b2c458543def4d873b73266aeb11f986f):
  probeTargets/probePlan select one configured connection/model; probeSucceeded
  requires an exact answer and a real tool call in tools mode. probe creates a
  random value available only from the synthetic function result, then inspects
  the final answer and observed timings.
- app/Services/AiProviderHealthService.php (SHA-256
  74cbeda3afa176020abc92b2227c32397aea748c76fe9dc3dc4106eb7450b540):
  distinguishes provider observations and credential/model-scoped cooldowns
  from business data or proof of health. Its provider-hold policy is a separate
  remaining capability, not implemented by this change.

Compare OpenAI's Chat Completions function calling and Google Gemma native
function declarations/response envelopes (manifest sources openai-chat,
openai-tools and gemma). These are reference source and protocol evidence,
not proof that either remote provider is currently reachable.

## Ownership and execution

Nebo keeps its existing production routing, key storage and actor-scoped
business tool registry. The diagnostic uses the same private provider
transport and continuation methods through ModelToolSet. ProviderProbeTools
has no database, account, or business context; it exposes only the
parameterless connection_probe function. No diagnostic changes the production
three-failed-turn routing state. The saved Google primary's existing fallback
is selectable explicitly; arbitrary model IDs or endpoint overrides are not.

1. AdminController::testAi validates admin session and CSRF, then accepts
   the saved model choice and text/tools mode. CLI uses the same AiClient::probe.
2. Resolve the saved connection and pin one authorized model. Reject invalid
   selection and unsupported protocol before any network request.
3. Generate a fresh challenge. Text mode sends it in the prompt; tools mode
   only reveals it through the successful synthetic function. Native protocols
   preserve tool call IDs and required message envelopes.
4. Text requires an explicitly completed natural finish and exact visible
   challenge. Tools requires one valid observed model-origin call, completed
   final inference, and exact hidden challenge. Unknown/malformed/repeated calls,
   no tool, refusal, length limit, missing finish, and incorrect answer fail.
5. Return bounded observed trace metadata and safe outcome categories. Never
   persist or display raw provider error bodies, keys, prompts, challenge values,
   tool payloads or reasoning. Admin audit events are distinct from agent runs.

A text challenge proves the configured endpoint returned the expected fresh
visible response; it is not proof of intelligence or of every business answer.
A synthetic tool challenge proves that isolated transport/continuation contract,
not actual permissions/retrieval/business facts on either live agent surface.
Provider outage does not establish unsupported model capability.

## Acceptance

- tests/ai-probe.test.cjs reproduces the old false success on HTTP 200 wrong
  output, then verifies native Chat Completions and Google text and tool round
  trips, actual challenge correlation, invalid/missing finish, wrong/unknown
  tools, malformed arguments, provider errors, target allowlisting and no raw
  secrets. It calls the real PHP CLI and POST controller, with a loopback
  provider and test-mode DB.
- tests/fixtures/ai-probe-route.php verifies guest/customer/CSRF denial before
  provider dispatch. Invalid form shapes never reach a provider.
- tests/browser/ai-probe.cjs renders the actual integration view with synthetic
  results in isolated Chrome at desktop and mobile widths; it verifies the
  selected model/mode POST and failure state. It is not live owner authentication.
- Live provider acceptance uses the saved configuration, with exact text and
  tools diagnostics for each available model. Record actual trace outcomes;
  follow with release source verification and live agent questions separately.

No provider settings are changed, no external dependency or database migration,
no new shop functionality. Profile discovery, general cooldown/Retry-After
routing, real provider recovery and complete agent evaluation remain separate.
