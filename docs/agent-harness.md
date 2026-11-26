---
type: doc
title: Native agent harness and retrieval contract
description: PHP execution loop, scoped RAG, provider protocol, observability and evaluation acceptance.
category: docs
---
# Native agent harness and retrieval contract

Issues #128, #82 and #134. The PHP/MySQL/JavaScript runtime adapts documented OpenAI patterns, without a hosted orchestration service, embedding index, or Agents SDK dependency.

```mermaid
flowchart TD
 A[Support or owner request] --> B[Authentication, CSRF, session, input limits]
 B --> C[Instruction RAG: role and topic rules]
 C --> D[Knowledge RAG: scoped CMS and owner records]
 D --> E[Tool RAG: rank allowed schemas by question]
 E --> F[Native model inference]
 F --> G{Function call or final output}
 G -->|Function| H[Validate scope, arguments and budget]
 H --> I[Read-only PHP service]
 I --> J[Correlate result with call]
 J --> F
 G -->|Final output| K[Guard: actual native tool, completed inference, known facts]
 K -->|Accept| L[Answer and observed source links]
 K -->|Reject| M[Explicit failure and human handoff]
 H --> N[Trace metadata]
 F --> N
 N --> O[Private bounded session journal]
```

- Instruction RAG uses the versioned `config/agent-instructions.php`: shared, role and topic rules. Treat source records and the question as data, not instructions.
- Knowledge RAG uses visible practitioners, published programme bodies and published blog passages in `ClinicKnowledgeService`. Rank up to five title/paragraph matches; this is lexical search, not semantic embedding search. Retrieve exact programme details by validated slug. Owner aggregates and public/customer data have separate scopes.
- Tool RAG uses `AgentToolRegistry`: role/module filtering and question-dependent schema ranking, not provider-hosted deferred tool search. Expose the small allowed catalogue (fewer than 20) and validate each native call again. Unknown names, invalid types, extra keys and missing required values fail closed. Chat Completions uses strict function schemas and nullable optional arguments.
- Native execution uses Google functionDeclarations/functionResponse and Chat Completions assistant tool_calls plus tool messages with the same tool_call_id. No plain-generation downgrade for conversational answers, including secondary Gemma. Unsupported protocols fail explicitly; Anthropic native tool protocol is not implemented. Navigation, ticket submission and account permission notices are application actions, not model answers.
- Execution bounds: four provider rounds, eight tool calls, bounded request/result size and provider deadlines. Reject invalid/duplicate completion call identifiers; exhausted loops fail. These are application limits, not a guarantee about total network latency.
- Output acceptance requires a successful model-requested tool and a usable final model response. Known named practitioner qualification/registration and programme duration checks reject missing facts; the reported persona echo is rejected. These checks do not prove every possible claim. Actual source links are derived from tool results. Old conversation-state versions are invalidated.
- Trace correlation records provider attempts, model vs harness tool origin, hashed call reference, duration, status and provider-reported token counts. It excludes raw tool payloads, prompts, credentials and hidden reasoning. Private JSON session journals contain redacted visible conversation plus traces, subject to consent, permissions and size limits. Operational traces and private transcripts are distinct.
- Evaluation: deterministic loopback provider fixtures test actual call/result protocols, unauthorized tools, malformed arguments, duplicate identifiers, looping calls, provider outage, no-tool completion, persona echoes, session boundaries, source checks and replay. HTTP 200 alone is not an acceptable inference.

## Sources

[OpenAI Function calling](https://developers.openai.com/api/docs/guides/function-calling) · [Retrieval](https://developers.openai.com/api/docs/guides/retrieval) · [Tool search](https://developers.openai.com/api/docs/guides/tools-tool-search) · [Guardrails](https://openai.github.io/openai-agents-python/guardrails/) · [Tracing](https://openai.github.io/openai-agents-python/tracing/) · [Agent workflow evaluation](https://developers.openai.com/api/docs/guides/agent-evals) · [Harness engineering](https://openai.com/index/harness-engineering/) · [Google Gemma on Gemini API](https://ai.google.dev/gemma/docs/core/gemma_on_gemini_api).

26 Nov 2026 synthetic real-provider probe: 31B returned no complete HTTP response within 12 seconds; 26B returned HTTP 503. No native tool call observed. This does not establish unsupported capability. Keep #82 open until real successful inference plus retrieval is observed.

## Release

Initialize the declared agent_sessions collection using the existing authenticated schema migration; seed already published telephone and WhatsApp numbers only when the canonical settings fields are absent. Preserve explicit owner settings. Verify owner-only JSON downloads and failure paths before deployment. Never use production medical/customer data in evaluations.

### Gemma native REST protocol
Both configured Gemma 4 models have native function support. Google declarations use uppercase REST schema types and omit parameters for no-argument functions. Preserve the full returned model parts as JSON objects (including empty argument objects and opaque thought signatures); correlate functionResponse by the returned ID when present. Use the documented Gemma thinking level, no OpenAI-only fields or forced unsupported mode. Keep instructions separate and retrieve source data through tools rather than repeating a large precomputed data dump. The isolated protocol fixture verifies this round trip for both models and both surfaces. A second minimal live REST probe, without optional generation settings, returned HTTP 503 for both configured models; no call was returned. The live provider remains unverified, not classified as incapable.
