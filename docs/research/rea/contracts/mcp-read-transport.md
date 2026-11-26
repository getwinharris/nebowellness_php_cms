---
type: doc
title: Scoped MCP published read transport
description: Native request-scoped delegated access and typed read-tool transport.
category: docs
---

# Component contract: scoped MCP published reads (issue #162)

## Reference evidence and protocol pin

The pinned WordPress MCP adapter at f778ba25c166ddfbb17dba2a349f7e23e9008614
separates JSON-RPC decoding, authenticated request context, schema projection,
tool listing and execution. Its decoder requires a single object and preserves
typed request identifiers. HttpSessionValidator checks the authenticated actor
against its protocol session. ToolsHandler distinguishes protocol errors from
tool execution errors. Those are useful boundaries, not a requirement to import
the WordPress runtime or its older stateful transport.

Official MCP revision **2026-07-28**:
- https://modelcontextprotocol.io/specification/2026-07-28/basic/transports/streamable-http
- https://modelcontextprotocol.io/specification/2026-07-28/server/discover
- https://modelcontextprotocol.io/specification/2026-07-28/server/tools
- https://modelcontextprotocol.io/specification/2026-07-28/schema

This revision uses POST and request metadata instead of protocol sessions or
an initialize handshake. This implementation supports the JSON-response read-tool
subset. It does not claim OAuth onboarding, legacy initialize compatibility,
subscriptions, elicitation, external writes or universal MCP client compatibility.

## Scope and authorization

McpGrantService issues high-entropy bearer grants to an authorized database
operator, with 1–90 day expiration. MySQL stores a digest, not plaintext.
The CLI lists redacted grants and explicitly revokes them. Token issue requires
a private interactive terminal. No credential is provisioned by deployment.
A browser session, user ID argument, model provider key or MySQL password is
not an MCP credential.

McpController accepts the dedicated /mcp route over HTTPS (only the PHP
loopback development server may use HTTP), validates Origin when present,
and uses no browser session. Each request rechecks the presented grant,
expiry, revocation and scope. Rate limiting is applied before lookup and
per authorized grant. Missing storage fails closed.

The external registry explicitly permits six existing published clinic
read names, with native module checks. The session context, all owner-only
and commerce abilities, arbitrary files, SQL and mutation operations are
excluded. No second catalogue or domain implementation is created.

McpReadService validates a bounded single JSON-RPC object, correlates IDs,
requires protocol, method and applicable name headers to match request
metadata and body, and projects native input/output schemas and read-only
annotations. It uses the shared registry for execution and output
validation. Tool failures return isError without successful structured
data. Discovery is private and non-cacheable.

## Operations and acceptance

After deploying the schema, an authorized operator may run:

    ./bapXphp mcp:grants list
    ./bapXphp mcp:grants issue "Trusted client" 30
    ./bapXphp mcp:grants revoke GRANT_ID

Do not run issue merely to verify deployment or expose a real token in a
test report. Clients must support explicitly configured Authorization
Bearer credentials and the pinned protocol. Every request includes
Accept: application/json, text/event-stream; Content-Type: application/json;
MCP-Protocol-Version; Mcp-Method; and Mcp-Name for tools/call. Request params
carry the required _meta protocolVersion, clientInfo and clientCapabilities.

The disposable MariaDB HTTP integration creates only synthetic grants,
published/draft fees, and restores fixture settings. It exercises real
routing, credential isolation, expiry/revocation, origin, exact discovery,
typed execution, forbidden names, malformed inputs and no-store behavior.
Live authenticated acceptance requires an explicitly issued real grant
and a compatible client. OAuth/legacy support remains separate from this
limited private-client implementation.
