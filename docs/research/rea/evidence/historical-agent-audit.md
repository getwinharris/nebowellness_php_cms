<!-- Historical reference only: Sri Panchami, not Nebo current status. Local paths normalized. -->
---
type: doc
title: REA Agent, Support and Commerce Gap Audit
description: Source-pinned REA analysis comparing Sri Panchami PHP with DeepSeek Harness, Langfuse core, Chatwoot core and Bagisto.
category: docs
---

# REA audit: what Sri Panchami still lacks—and what already exists

**Audit date:** 15 November 2026. **Repository:** `bapxmediahub/bapXphpAiBackend`.

## Main result

This audit identifies **28 scoped gaps or incomplete capabilities**, with implementation targets and acceptance tests. They are not all urgent features to build. P0 means correctness/release safety first; P1 means meaningful operational improvement; P2 means optional product expansion that needs a real customer requirement.

The largest problem is not the absence of an “AI framework.” It is the difference between displaying a chat, recording a final response, and retaining enough evidence to reconstruct a reliable, authorized business operation. Likewise, two commerce counters do not establish an attributed conversion funnel, and a successful CI check is not a completed AI review.

**Architecture stays native PHP.** Existing MySQL remains the business-data authority. Each Support/Enterprise conversation remains **one private JSON file**. No new conversation SQL table, vendor telemetry export, paid evaluator, Laravel migration or automatic data collection was introduced by this audit.

## Exactly what was inspected

The comparison separates three local states:

- **Main source snapshot:** `08a988729c90c63d2bc9676829a82baf1850fbc4`. A Git commit is not by itself proof of which revision Hostinger currently serves.
- **Remote draft #241:** `651e7d354b64c0fb8b56087c8f85a26efa422c25` at initial audit. Its JSON chats, History and basic agent work are not merged production capabilities.
- **Uncommitted local continuation:** HEAD `a5c3418072ad3ab8b01f643b866c4132e51c43cd` plus dirty files. This includes the richer `AgentTraceService`, feedback, and ten-case golden evaluator. These were inspected but deliberately left untouched; they must not be presented as shipped code.

Other open customer corrections (#245 dashboard, #247 blog, #249 collection relevance, #250 deletion) remain distinct. #240 has its own human-review path. This audit does not merge those PRs or delete production data.

## REA commands, evidence and honest limits

`rea -help` is not a supported command. `rea --help` identifies installed **REA 6.8.0** and its relevant operations. The system Node was 22.19; the installed package requires a newer supported runtime. The audit used an isolated npm-cached **Node 24.22.1** wrapper around the existing REA executable. It did not replace global Node, edit editor/MCP registration, install native decompilers, or execute upstream application code.

REA was used for:

1. **Nine hashed source-reference inventories**, including Bagisto PHP modules, Chatwoot support code, Langfuse tracing and both main/draft local services. These are `historical-reference` records with **partial inventory** semantics—not PHP runtime proofs or complete call graphs.
2. **DeepSeek session telemetry JavaScript/TypeScript analysis:** four parsed files, 7,735 visited AST nodes, zero syntax parse failures. Semantic unresolved facts remain. A bounded coordinator feature trace matched one source-module seed and traversed **40 nodes / 58 edges**. Those are static relationships, not observed production executions.
3. **A controlled public-site browser scenario:** load the live shop, find the Support button, open the widget, verify it is visible. All four corrected scenario steps completed, and REA terminated its own browser. No login, payment, message submission, cookies, request bodies, response bodies or header values were captured.

The first browser run used a nonunique product-card selector and failed strict matching; it is retained as a failed attempt. The corrected run passed its actions, but **network capture completeness remains incomplete** because requests were still pending at the capture boundary. That does not certify site-wide health. Similarly, an initial literal `session/event` graph seed produced no match; a documented source-module seed was then used. No-match was not treated as absence of the feature.

Key REA evidence IDs:

| Artifact | Evidence ID | Meaning |
|---|---|---|
| DeepSeek selected source analysis | `ev_91612ace5674e60eaf0484ac6a8969c2b66801a9557ea848e34344f826ea9a0d` | Bounded static analysis |
| Coordinator feature trace | `ev_2799a2fd5bc9574e215a123e03b53cdbbadd8ea62ebe3aa2110c5025f9f55852` | Static graph traversal; source-qualified |
| Corrected public shop/widget scenario | `ev_0d9e18fd35d441efb5d4ef6e21c31e307c2c023f4c1b96d3b98b854b13c50d16` | Controlled replay; actions completed, events incomplete |

Raw evidence stays outside production source in `Documents/rea-research/sps-20261115/evidence/`. The accompanying manifest records hashes, versions, authority and limitations; the matrix records source paths and tests. No private credentials or customer conversations are included.

## Compare the right layers


| Reference | Pinned commit | What is compared | License boundary |
|---|---|---|---|
| [deepseek-ai/deepseek-harness](https://github.com/deepseek-ai/deepseek-harness/tree/39163dd30f703a79079e2d2446545cff3c453738) | `39163dd30f70` | Session execution, capture, replay and telemetry contracts | MIT |
| [langfuse/langfuse](https://github.com/langfuse/langfuse/tree/168ededf52afd3642d3594cd0c27c7d394ae77dd) | `168ededf52af` | Observations, nested timing, usage and dataset/run provenance | MIT core; enterprise folders excluded |
| [chatwoot/chatwoot](https://github.com/chatwoot/chatwoot/tree/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf) | `1ef1d2b45ae0` | Support lifecycle, assignment, human response and satisfaction reporting | MIT core; enterprise/ excluded |
| [bagisto/bagisto](https://github.com/bagisto/bagisto/tree/c9209260c0f078e1b358c98050fe2e35a0ad75a4) | `c9209260c0f0` | PHP cart, order-line, inventory, shipment, refund and promotion patterns | MIT |

DeepSeek is an execution harness, not a ready-made customer-support CRM. Langfuse is an observability/evaluation data model, not an e-commerce backend. Chatwoot supplies support workflow patterns; Bagisto supplies PHP business-domain examples. The recommended adaptation is of these contracts, not of their entire stacks.

### Capabilities already present—do not rebuild them

Source inspection confirmed gateway-verified orders, retry-safe order insertion, post-payment stock-review safeguards, GST/invoice records, provider-verified full/partial refund amounts, product collections, coupons with meaningful limits, mail queues, reviews, PHP authentication and scoped read-only enterprise tools. `AuditLogService` already contains best-effort failure containment. Those are strengths to preserve.

Draft/local additions already include one-file chat history, actor checks, History/New Chat, collection-scoped answers, some trace metadata, explicit support feedback and a deterministic evaluation baseline. The gap is their completeness, integration and verification—not that every one of those concepts is absent.

## Source-backed gap list

For a full machine-readable list, see `REA_SriPanchami_Gap_Matrix_2026-11-15.json`. The status column below deliberately marks missing versus partial/local work.


### Agent execution

**A01 · P0 · Record execution when it happens, not after the answer**

**Observed:** Partial in uncommitted #241 work: AgentTraceService explicitly groups stages after execution; all spans are root-linked, without real start/end timing.

**Native-PHP/workflow change:** Instrument actual PHP boundaries with begin/end span IDs, parent IDs, monotonic durations and observed wall times. Keep trace/event records inside the same conversation JSON; do not invent causal order.

**Acceptance:** A two-tool, two-provider-attempt fixture reconstructs actual ordering and nesting; a timed-out tool has an ended error span.

**Evidence:** `app/Services/AgentTraceService.php::build`, `app/Services/AiClient.php`, `app/Services/AgentToolRegistry.php::run`; [D1](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/packages/session/session-telemetry/src/coordinator.ts#L58-L212); [L1](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/src/server/otel/OtelIngestionProcessor.ts#L437-L689)

**A02 · P0 · Recover incomplete turns and distinguish committed from delivered work**

**Observed:** Current file writes are atomic, but a full turn is appended only after answer construction. There is no canonical per-step sequence or crash-resume contract.

**Native-PHP/workflow change:** Store request ID, turn state, monotonic event sequence and terminal outcome in the same JSON. Persist started/failed/completed milestones so an interrupted turn is not silently absent.

**Acceptance:** Kill a disposable worker between retrieval and response; recovery shows an interrupted turn, never a falsely completed one.

**Evidence:** `app/Services/AgentConversationService.php::record/append`, `app/Controllers/SupportController.php::ask`; [D1](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/packages/session/session-telemetry/src/coordinator.ts#L58-L212); [D2](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/docs/subsystems/session-telemetry.md)

**A03 · P0 · Prevent duplicate tickets and actions when a chat request is retried**

**Observed:** SupportTicketService::create uses a new random ID for every creation. Transcript persistence happens after potential escalation; a subsequent save error returns a retry message.

**Native-PHP/workflow change:** Introduce a client request/idempotency key tied to actor and conversation, record the action receipt, and return the existing ticket on replay. Do not confuse failure to save an audit with failure to create a ticket.

**Acceptance:** Repeat one HTTP request after a simulated post-ticket disk failure: exactly one ticket exists and the returned receipt matches it.

**Evidence:** `app/Services/SupportTicketService.php::create`, `app/Services/SupportBotService.php::answer`, `app/Controllers/SupportController.php::ask`; [D1](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/packages/session/session-telemetry/src/coordinator.ts#L58-L212); [C1](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/listeners/reporting_event_listener.rb#L1-L163)

**A04 · P0 · Validate conversation ownership before model/tool execution**

**Observed:** Support derives user data safely, but the supplied conversation ID is checked at append time, after answer/tool work. This can waste paid calls or cause side effects before rejecting the chat ID.

**Native-PHP/workflow change:** Resolve and authorize the actor-owned chat at request ingress before provider calls or ticket actions; retain the append-time check for races.

**Acceptance:** A different guest/customer/admin conversation ID is rejected with zero model calls and zero new tickets.

**Evidence:** `app/Controllers/SupportController.php::ask`, `app/Services/AgentConversationService.php::append`; [D1](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/packages/session/session-telemetry/src/coordinator.ts#L58-L212); [C3](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/models/conversation.rb#L71-L139)

**A05 · P1 · Make multi-turn understanding match the History interface**

**Observed:** Draft History reopens messages, but Support modelReply builds its prompt from the current question/context; Enterprise mainly retains one verified report period.

**Native-PHP/workflow change:** Load a bounded, actor-owned sequence of prior turns and verified tool facts into the model request. Use explicit token/byte budgets and preserve source scope across ambiguous pronouns.

**Acceptance:** After “show Ayyappa products”, “what is the second one made of?” refers to the verified second item; a new chat has no inherited context.

**Evidence:** `app/Services/SupportBotService.php::modelReply`, `app/Controllers/AdminController.php::agentAsk`, `app/Services/AgentConversationService.php::getForActor`; [D1](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/packages/session/session-telemetry/src/coordinator.ts#L58-L212); [L1](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/src/server/otel/OtelIngestionProcessor.ts#L437-L689)


### Agent evidence

**A06 · P1 · Capture replayable source and instruction provenance**

**Observed:** Uncommitted traces include selected filters, source name and result count; they do not fully bind response claims to source revisions, result digests, prompt hash and tool-code revision.

**Native-PHP/workflow change:** Record sanitized source identifiers/version or fetch time, approved aggregate facts, prompt/tool version hashes and evidence references used by the answer. Exclude private raw rows and hidden reasoning.

**Acceptance:** Change catalogue/report data after a reply: the recorded evidence still explains the original answer and marks stale context.

**Evidence:** `app/Services/AgentTraceService.php::toolEvidence`, `app/Services/AgentInstructionService.php`, `app/Services/AgentToolRegistry.php`; [L1](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/src/server/otel/OtelIngestionProcessor.ts#L437-L689); [L2](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/prisma/schema.prisma#L745-L831)

**A07 · P1 · Measure provider usage and enforce a turn budget**

**Observed:** AiClient records HTTP/transport timing and bounds calls, but no response token-usage extraction was found; a max-token request parameter is not observed usage.

**Native-PHP/workflow change:** Extract supported input/output/cache usage with explicit unavailable values when absent; aggregate once per model call, cap retries/latency/tokens, and avoid counting root and child usage twice.

**Acceptance:** Fixtures for provider usage present, absent, retry and fallback return correct totals without converting unknown usage to zero.

**Evidence:** `app/Services/AiClient.php`, `app/Services/AgentTraceService.php::build`; [L1](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/src/server/otel/OtelIngestionProcessor.ts#L437-L689)


### Agent evaluation

**A08 · P1 · Expand deterministic fixtures into comparable answer-quality experiments**

**Observed:** The local unfinished suite has 10 deterministic helper cases and a baseline; it does not measure arbitrary answer faithfulness, full orchestration, multilingual cases or repeated model variability.

**Native-PHP/workflow change:** Keep free deterministic gates; add sanitized expected source IDs/amounts/actions, end-to-end transcript fixtures, adversarial and Tamil cases, code/prompt/model/fixture versions and per-case before/after comparisons.

**Acceptance:** An unrelated-product answer and a plausible but wrong GST total fail their exact evidence checks even when the answer is nonempty and secret-free.

**Evidence:** `cli/agent-eval.php (uncommitted)`, `tests/fixtures/agent-golden.json (uncommitted)`, `app/Services/AgentEvaluationService.php`; [L2](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/prisma/schema.prisma#L745-L831)


### Agent operations

**A09 · P1 · Separate audit failure, customer action success and telemetry delivery**

**Observed:** AuditLogService already catches DB write failures, which is good; it still returns an event labeled recorded, and the chat controller can report a generic failure after a ticket was created.

**Native-PHP/workflow change:** Represent delivery/commit states explicitly and preserve action receipts. For optional analytics, use safe local buffering/retry only if needed; never let derived metrics retry business actions.

**Acceptance:** Reject audit DB writes: successful business operations remain successful, the audit loss is visible, and reprocessing cannot duplicate events/actions.

**Evidence:** `app/Services/AuditLogService.php::record`, `app/Controllers/SupportController.php::ask`, `app/Services/SiteTelemetryService.php`; [D2](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/docs/subsystems/session-telemetry.md); [D3](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/docs/subsystems/product-telemetry.md); [C1](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/listeners/reporting_event_listener.rb#L1-L163)

**A10 · P1 · Scale and govern private JSON history without changing its storage contract**

**Observed:** One JSON file per chat, private paths and locking exist in draft; listings scan files, all writes share one lock, and transcript retention/export-access lifecycle is not implemented.

**Native-PHP/workflow change:** Preserve one canonical JSON per chat. Add bounded paging, safe retention/deletion and export audit, backup/restore checks and measured contention handling. Do not introduce a conversation SQL table.

**Acceptance:** Large history paging stays bounded; unauthorized export is denied; a deployment/restore preserves ownership and all committed turns.

**Evidence:** `app/Services/AgentConversationService.php::recent/recentForActor/append`, `app/Controllers/AdminController.php::agentConversation`; [D1](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/packages/session/session-telemetry/src/coordinator.ts#L58-L212); [L1](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/src/server/otel/OtelIngestionProcessor.ts#L437-L689)


### Support service

**S01 · P1 · Link handoff tickets back to the actual conversation**

**Observed:** A chat turn can hold ticket_id, but the support ticket stores generic context rather than an authoritative conversation/turn link and handoff summary.

**Native-PHP/workflow change:** Add actor-safe conversation/turn references and a verified handoff summary to the business ticket, with a restricted link for staff to read the matching JSON.

**Acceptance:** Human support opens one ticket and sees the relevant prior question, verified facts and handoff reason without another customer’s data.

**Evidence:** `app/Services/SupportTicketService.php::create`, `app/Services/SupportBotService.php::answer`; [C1](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/listeners/reporting_event_listener.rb#L1-L163); [C3](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/models/conversation.rb#L71-L139)

**S02 · P1 · Add assignment, priority, pending and snoozed work queues**

**Observed:** Support tickets currently use open/answered/closed and reply text; no assignee/team, priority, pending reason or snooze workflow was found.

**Native-PHP/workflow change:** Introduce a small PHP ticket state machine and explicit staff ownership, priority and due/snooze fields. Add only the queue features needed by the customer; no Rails migration.

**Acceptance:** Two staff members cannot unintentionally both own a ticket; a waiting-on-customer case is distinguishable from an unanswered urgent case.

**Evidence:** `storage/schema/collections.php::support_tickets`, `app/Services/SupportTicketService.php`, `views/admin/list.php`; [C3](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/models/conversation.rb#L71-L139); [C4](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/models/automation_rule.rb#L31-L83)

**S03 · P1 · Measure first response, handoff, resolution and reopen separately**

**Observed:** Operational model latency is present; created_at/updated_at and a current status do not preserve first human response, time waiting, time to handoff or reopen episodes.

**Native-PHP/workflow change:** Record explicit ticket transitions and timestamps, distinguish bot from human replies, and compute date-window metrics from those events. Do not use updated_at as every lifecycle timestamp.

**Acceptance:** A closed then reopened ticket retains both episodes and correct first-human-response time, without double-counting a repeated handoff.

**Evidence:** `app/Services/SupportTicketService.php::reply/close`, `app/Services/AuditLogService.php::agentSummary`; [C1](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/listeners/reporting_event_listener.rb#L1-L163)

**S04 · P1 · Separate resolution confirmation from satisfaction and silence**

**Observed:** Yes-resolved/No-need-help feedback exists only in unfinished local #241 changes, with metrics from a bounded recent sample. It is not deployed CSAT or all-traffic resolution reporting.

**Native-PHP/workflow change:** Keep explicit resolution confirmation; separately define satisfaction feedback and eligibility, display response rate/denominator and date range, and never assume silence means success.

**Acceptance:** Unrated, escalated, negative, reopened and confirmed cases remain distinct; a small recent sample is never labeled a site-wide success rate.

**Evidence:** `app/Services/AgentConversationService.php::rateSupportTurn (uncommitted)`, `app/Services/AgentEvaluationService.php::supportOutcomeSummary (uncommitted)`; [C1](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/listeners/reporting_event_listener.rb#L1-L163); [C2](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/models/csat_survey_response.rb#L1-L47)


### Support knowledge

**S05 · P1 · Broaden retrieval tests and close the feedback-to-content loop**

**Observed:** Specific Ayyappa collection routing is fixed in #249/draft merge; generic and Tamil retrieval, no-match handling, source-versioned policies and failure-cluster review remain incomplete.

**Native-PHP/workflow change:** Use deterministic intent/source routing, multilingual aliases, actual collection membership, confidence/clarification rules and a human-reviewed queue of repeated failed topics. Promote resolved failures into golden fixtures.

**Acceptance:** Ambiguous “festival products” requests clarification; Tamil/English variants match the same products; stale or missing policies are not fabricated.

**Evidence:** `app/Services/AgentContextService.php::requestedCollection/supportProducts`, `app/Services/SupportBotService.php::modelReply`, `app/Services/AgentEvaluationService.php`; [C4](https://github.com/chatwoot/chatwoot/blob/1ef1d2b45ae0a17a35c5f1bd055e4ddf04dadabf/app/models/automation_rule.rb#L31-L83); [L2](https://github.com/langfuse/langfuse/blob/168ededf52afd3642d3594cd0c27c7d394ae77dd/packages/shared/prisma/schema.prisma#L745-L831)


### Commerce

**C01 · P1 · Persist customer carts and safely merge guest carts**

**Observed:** CartService reads $_SESSION[cart]; no persisted cart lifecycle or cross-device customer-cart repository was found.

**Native-PHP/workflow change:** Use an authorized business cart record in existing MySQL infrastructure, with expiry/versioning and guest-to-account merge rules. Reprice and validate availability at checkout.

**Acceptance:** Customer signs in on another device and recovers only their cart; guest merge handles duplicate items without stale prices or cross-account access.

**Evidence:** `app/Services/CartService.php`, `app/Controllers/CommerceController.php`, `storage/schema/collections.php`; [B1](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Checkout/src/Cart.php#L353-L493)


### Commerce telemetry

**C02 · P0 · Build a deduplicated end-to-end checkout funnel**

**Observed:** Uncommitted SiteTelemetryService only records cart_add and checkout_initiated. Paid orders are authoritative, but no complete attributed, deduplicated stage/error funnel exists.

**Native-PHP/workflow change:** Emit allowlisted events at real business boundaries: cart validated, checkout started, payment attempt/failure, verified order and fulfilment transitions. Use a privacy-safe checkout correlation ID and event idempotency; payment verification is server-authoritative.

**Acceptance:** Gateway callback retry counts one purchase; failed checkout is not a successful stage; counts reconcile with paid-order records and clear denominators.

**Evidence:** `app/Services/SiteTelemetryService.php (uncommitted)`, `app/Controllers/CommerceController.php`, `app/Services/DatabaseService.php::createVerifiedOrder`; [B1](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Checkout/src/Cart.php#L353-L493); [D3](https://github.com/deepseek-ai/deepseek-harness/blob/39163dd30f703a79079e2d2446545cff3c453738/docs/subsystems/product-telemetry.md)

**C03 · P1 · Measure abandoned carts from persistent state, not guessed event ratios**

**Observed:** Session-only cart data and two action counters cannot establish who abandoned a checkout or its recoverable value.

**Native-PHP/workflow change:** Define active/converted/expired carts and a documented abandonment window. Report cart age/value and recovery outcomes; do not assume Bagisto’s two-day cutoff fits this business. Recovery messages require a separately approved customer-contact policy.

**Acceptance:** A completed or still-active cart is not labeled abandoned; rerunning the report yields stable cohort counts and totals.

**Evidence:** `app/Services/CartService.php`, `app/Services/SiteTelemetryService.php (uncommitted)`; [B5](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Admin/src/Helpers/Reporting/Cart.php#L110-L180)


### Commerce catalogue

**C04 · P2 · Add true SKU variants only where the catalogue needs them**

**Observed:** The product schema exposes one price and stock_status per product; no variant SKU/option-specific inventory/pricing contract was found.

**Native-PHP/workflow change:** Represent size/material/colour variants with their own SKU, price, stock and images while retaining a parent product and backward-compatible existing slugs.

**Acceptance:** Choosing a size changes the actual order-line SKU/price/availability; variant selection survives cart, checkout and invoice.

**Evidence:** `storage/schema/collections.php::products`, `app/Services/ProductService.php`; [B3](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Product/src/Type/Configurable.php)


### Commerce inventory

**C05 · P1 · Track numeric saleable inventory and reservations**

**Observed:** Current stock_status and post-payment on_hold protection exist. There is no numeric stock movement/reservation/source ledger in the inspected product contract.

**Native-PHP/workflow change:** Add quantity accounting and atomic reservations/release before promising stock. Retain the existing on_hold safeguard. Multi-location inventory is optional unless needed; do not overbuild it first.

**Acceptance:** Two simultaneous buyers cannot consume the same last item; cancellation/refund/restock changes quantities exactly once.

**Evidence:** `app/Services/ProductService.php`, `app/Services/DatabaseService.php::createVerifiedOrder`, `storage/schema/collections.php::products`; [B4](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Inventory/src/Models/InventorySource.php); [B3](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Product/src/Type/Configurable.php)


### Commerce fulfilment

**C06 · P1 · Support line-level partial shipment and remaining quantities**

**Observed:** Current fulfilment is primarily an order-level state and tracking fields. No shipment-line ledger with shipped/remaining quantities was found.

**Native-PHP/workflow change:** Introduce explicit shipment records with order-line quantities and tracking, validated against available quantities. Derive order-level status from the line ledger.

**Acceptance:** Shipping one of two items leaves the other outstanding; a repeated shipment submission cannot overship or emit duplicate notifications.

**Evidence:** `storage/schema/collections.php::orders`, `app/Services/OrderService.php`, `app/Controllers/AdminController.php::saveOrderStatus`; [B2](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Sales/src/Models/OrderItem.php#L76-L171); [B7](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Sales/src/Repositories/ShipmentRepository.php#L42-L139)


### Commerce after-sales

**C07 · P1 · Add returns and quantity-level refund allocation without replacing verified refunds**

**Observed:** Gateway-verified refunds, partial/full amount summaries and totals protection already exist. Missing: customer return requests, reasons/disposition and allocation to item quantities.

**Native-PHP/workflow change:** Keep provider verification as canonical. Add reviewed return/claim states and line/quantity allocations, inspect/replacement/refund decisions and stock disposition. Never infer payment success from customer text.

**Acceptance:** A partial return cannot exceed purchased quantity or verified refund amount; duplicate imports remain idempotent and invoice history is preserved.

**Evidence:** `app/Services/OrderService.php::importRefund/refundSummary`, `app/Services/DatabaseService.php::recordOrderRefund`, `storage/schema/collections.php::orders`; [B2](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Sales/src/Models/OrderItem.php#L76-L171); [B6](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Sales/src/Repositories/RefundRepository.php#L40-L180)


### Commerce promotions

**C08 · P2 · Extend promotion eligibility rather than rebuilding coupons**

**Observed:** Coupons already support fixed/percentage discounts, dates, spend limits, free shipping and usage rules. Product/category/customer-group rules and explicit stacking precedence are not modeled.

**Native-PHP/workflow change:** Add a small versioned eligibility rule contract for real marketing requirements, define conflict/stacking precedence, and derive applied promotions from saved order evidence.

**Acceptance:** One coupon cannot discount excluded products; totals are reproducible at checkout and historical invoices keep their original price evidence.

**Evidence:** `app/Services/CouponService.php`, `storage/schema/collections.php::coupons`; [B9](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/CartRule/src/Repositories/CartRuleRepository.php)


### Commerce retention

**C09 · P2 · Wishlist/save-for-later is optional backlog**

**Observed:** No customer-owned wishlist/save-for-later model/route was found. This is an optional feature opportunity, not a critical checkout defect.

**Native-PHP/workflow change:** Add customer-owned saved items only if requested, with price and availability revalidation before cart insertion. Do not assume paid extensions are part of the free reference.

**Acceptance:** Saved items are account-isolated; unavailable products cannot silently become purchasable when moved to cart.

**Evidence:** `app/Services/ProjectMapService.php`, `storage/schema/collections.php`, `app/Services/CartService.php`; [B8](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Customer/src/Repositories/WishlistRepository.php); [B1](https://github.com/bagisto/bagisto/blob/c9209260c0f078e1b358c98050fe2e35a0ad75a4/packages/Webkul/Checkout/src/Cart.php#L353-L493)


### Review automation

**R01 · P0 · Connect successful current-head CI to review requests**

**Observed:** Main only contains branch PR creation and Validate application. There is no post-CI review coordinator; bapX/** branches were omitted from branch automation.

**Native-PHP/workflow change:** The accompanying repair adds a trusted-default-branch metadata-only workflow, exact-head validation, once-per-reviewer/commit request markers and bapX/** coverage.

**Acceptance:** Failed/stale CI, foreign repository or changed head produces no request; no PR code executes with write tokens.

**Evidence:** `.github/workflows/ci.yml`, `.github/workflows/branch-pr.yml`; Direct repository/workflow and current GitHub metadata evidence.

**R02 · P0 · Respect reviewer-specific draft and author rules**

**Observed:** Five current PRs are drafts. CodeRabbit defaults exclude drafts unless configured; Sourcery documents that bot-authored comments are ignored.

**Native-PHP/workflow change:** Enable CodeRabbit draft/incremental review via validated configuration. Use native vendor settings or an explicitly configured dedicated real-user request token for post-CI comments; never substitute a GitHub Actions/App bot for Sourcery.

**Acceptance:** A missing or bot-type token is reported blocked, not successful. Existing drafts are never marked ready just to force a review.

**Evidence:** `.github/workflows/branch-pr.yml`, `new .coderabbit.yaml / review coordinator`; Direct repository/workflow and current GitHub metadata evidence.

**R03 · P0 · Detect quota failures and actual reviewer receipts**

**Observed:** Live manual request on #245 received Codex usage exhaustion. #240 has a completed current-head Codex receipt. Sourcery/CodeRabbit commands on #245 have no current-head receipt observed.

**Native-PHP/workflow change:** Classify requested, in-progress, completed-with-findings, quota-blocked and unknown separately. Bind receipts to current SHA and trusted bot identity; suppress blind quota retries.

**Acceptance:** A quota comment or a posted request never counts as reviewed; old-head and human-forged Codex summaries are ignored.

**Evidence:** `GitHub PR #240/#245 review summaries`, `new .github/scripts/review-automation.cjs`; Direct repository/workflow and current GitHub metadata evidence.

**R04 · P1 · Document external prerequisites without pretending code can provision them**

**Observed:** Current GitHub connection has push but not admin permissions; no dedicated reviewer request secret is configured. App entitlement/installation settings cannot be established from silence.

**Native-PHP/workflow change:** Document least-privilege REVIEW_REQUEST_TOKEN or native app configuration, default-branch activation requirement and quota recovery. Do not reuse unrelated secrets, buy credits or automatically merge.

**Acceptance:** Tokenless audit explicitly lists blocked prerequisites; a workflow job success is described as an audit run, not provider approval.

**Evidence:** `GitHub repository permissions/variable and secret-name inventory`, `docs/engineering/ai-review-workflow.md`; Direct repository/workflow and current GitHub metadata evidence.


## Important patterns not to copy blindly

**DeepSeek does not guarantee durable telemetry delivery.** Its cursor records handoff, not remote acknowledgement, and a process crash can lose buffered data. Its exported event copies are not redacted by default unless rules are installed. Adopt the explicit boundaries and sequence/deduplication identity, not an unqualified “crash-proof observability” claim or automatic session export.

**Chatwoot itself documents a best-effort handoff dedupe guard.** The inspected listener notes that its one-handoff assumption is not database-enforced. A PHP implementation should make idempotency an actual persisted invariant. Also, a bot-resolved event and a positive CSAT response are different concepts; preserve that distinction.

**Langfuse’s core and enterprise folders have different licenses.** This audit uses core source schemas and instrumentation patterns, not an assumption that every hosted/enterprise feature is free. An optional future collector must not become a required paid dependency of the customer app.

**Bagisto is not a drop-in source file for this project.** Its repositories depend on Laravel/Eloquent and a richer schema. Reimplement the smallest useful accounting/event invariant in existing PHP services. Do not replace the working checkout, GST or provider-refund safeguards. A reference’s abandonment cutoff, warehouse model and return policy are not automatically right for Sri Panchami.

## Review failure investigation and repository-side repair

Actual observed evidence:

- #240 has a completed Codex receipt for its current head. It is inaccurate to say every PR lacks review.
- #245 received a manual `@codex review` request on the exact green-CI head. The app replied **“You have reached your Codex usage limits for code reviews.”** This is a quota failure, not a code finding and not a successful review. #243 also had a prior quota reply.
- Human-authored Sourcery and CodeRabbit request comments were posted on #245. No current-head completion receipt was observed at audit time. Silence does not establish installation, entitlement, success or a specific failure cause.
- The existing branch workflow omitted `bapX/**`; no explicit trusted post-CI review coordinator or CodeRabbit repository draft configuration existed.
- Sourcery’s official commands documentation says bot comments are ignored. Posting a Sourcery mention using `GITHUB_TOKEN` would be a false repair.
- The current connection has push but not repository-admin permissions. No dedicated review-request token is configured. No existing secret was repurposed, printed or copied, and no credits or subscriptions were bought.

The accompanying isolated patch adds:

1. `.github/workflows/ai-review-evidence.yml`: after successful `Validate application`, inspect only metadata and check out **trusted default-branch code**, never PR code with secrets. It also provides an audit/manual request entrypoint.
2. `.github/scripts/review-automation.cjs`: exact-head/CI/same-repo checks, real-user token validation, per-provider idempotency, current-head receipts, in-progress detection, quota suppression and clearly labeled status notes. Request comments are never marked completed reviews.
3. `.coderabbit.yaml`: draft and incremental reviews enabled, schema-validated against current official configuration; no automatic approval/merge. Native app access/entitlement is still required.
4. `bapX/**` branch coverage and independent mocked Node tests. This is CI tooling only; production PHP hosting does not gain a Node dependency.

**Activation constraints:** the coordinator must be merged into the default branch before `workflow_run` can activate it. An authorized maintainer must configure a dedicated least-privilege **real-user** `REVIEW_REQUEST_TOKEN` if explicit post-CI commands are desired, or configure the native reviewer apps appropriately. Missing/bot credentials are a visible block, not a fallback. Codex’s exhausted provider quota must recover separately. The workflow cannot bypass that limit and must not retry it blindly. These prerequisites are not silently completed by a code PR.

The code must pass tests and human/security review before merging. The receipt audit does not weaken branch protections or authorize any product release.

## Implementation order for the product backlog

First complete the small customer corrections and their authenticated release checks; do not use this research to obscure unfinished #241 work. Then implement A02–A04 together as a small request-identity/lifecycle change, followed by A01/A06 for truthful evidence. Add S01–S04 for useful human support operations, and C02/C01/C03 for measurable commerce conversion. Variant, inventory and fulfilment expansion need their own data-contract and migration review. Keep P2 wishlist and promotion expansions behind a concrete customer need.

Each delivery should add failing fixtures first, update the actual services, regenerate documentation/maps, run current-head CI, obtain actual reviews and repeat the affected browser journeys. No claim here implies those missing product features have already been built or deployed.

## Reproduction recipe

Use the installed REA command’s `--help` for its exact version. Selected examples:

```sh
rea --help
rea import-reference-source /path/to/pinned/bagisto/packages/Webkul/Sales --format json
rea analyze-javascript-application /path/to/pinned/deepseek-harness/packages/session/session-telemetry \
  --detail summary --evidence_output /private/evidence/session.evidence.json \
  --max-semantic-nodes 12000 --max-semantic-nodes-per-file 5000 \
  --analysis-timeout-ms 30000 --format json
rea trace-application-feature /private/evidence/coordinator-trace-input.json --format json
rea capture-browser-scenario /private/evidence/public-shop-scenario.json --format json
node --test tests/review-automation.test.cjs
./bapXphp update
./bapXphp ci
```

A source import is not runtime verification. Preserve failed/no-match and incomplete results alongside successful ones. Do not install native decompilers or run upstream dependency scripts when a bounded source study answers the question. Do not capture credentials, customer requests, payment details, storage values or network bodies in public-site research.

## Official workflow references

- [Sourcery comment commands and bot-author limitation](https://docs.sourcery.ai/reviews/commands/).
- [Sourcery review settings](https://docs.sourcery.ai/reviews/configure/).
- [CodeRabbit configuration reference](https://docs.coderabbit.ai/reference/configuration).
- [Codex repository-specific review rules](https://developers.openai.com/blog/custom-code-review-rules-for-codex).
- [GitHub workflow-run security and untrusted code boundaries](https://docs.github.com/en/actions/reference/security/secure-use).
- [REA source and documented command surfaces](https://github.com/morluto/rea).

The open-source findings above are linked to immutable commit paths; the workflow documentation links are time-sensitive and were checked during this audit.


## Verified repository delivery

The base reviewer/research work is in **draft PR #253**. The supplemental four-source audit and race/receipt hardening are in **draft PR #254**, stacked on #253 rather than creating a competing workflow. The registers use different grouping and scope: the 27 entries in #253 and the 28 here are **not 55 unique product gaps**.

Latest verified supplemental head: `ec917eb168e2f6646ffc62ff05c52fbdfdedebce`.

- Reviewer tests: **33/33 passed**.
- PHP regressions: **162/162 passed**, 2,241 assertions.
- Full local project CI and current-head GitHub validation: **passed**.
- Workflow YAML parsed; CodeRabbit settings validated against the official schema.
- Only one reviewer workflow remains. Provider commands are separate literal comments, with distinct dispatch-claim and delivery evidence.
- Added shared manual/CI coordination, trusted-human marker recognition during audit, and explicit unknown-delivery state for interrupted requests.

Both PRs remain drafts. No production changes, vendor purchases, secret reuse or app-setting changes were performed. Provider quota/entitlement and default-branch activation remain explicit gates. The earlier uncommitted agent continuation was preserved. The 28 product/review capabilities are an evidence-backed backlog—not a claim of completed commerce or agent-runtime implementation.
