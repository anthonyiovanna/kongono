# ADR 0173: One-Shot OpenAI Responses Invocation Adapter

Status: Accepted

Date: 2026-09-10

Scope: Phase 17C only

## Context

ADRs 0171 and 0172 established the optional first-party OpenAI Responses
boundary and its deterministic offline version-1 protocol mapping. The mapping
deliberately had no `InvocationAdapter`, credential access, HTTP request, or
network effect. Kongono now needs one narrow executable composition that reuses
the accepted Phase 14 invocation runtime, Phase 16 exact-current binding and
credential lease, and Phase 15 governed egress without introducing a provider
SDK or a parallel transport stack.

## Decision

Extend `kongono/model-provider-openai-responses` with one Experimental final
`OpenAiResponsesInvocationAdapter` identified exactly as
`openai.responses.v1`. One immutable Experimental invocation context supplies
the expected plan identity, an explicit external-service application factory,
operation and receipt identities, credential purpose and finite lease lifetime,
current network authority, cancellation, and clocks. No value is discovered
from environment variables, registries, globals, or hidden defaults.

The adapter is one-open only. `open()` validates the exact adapter identity,
plan/context identity, neutral profile, supported non-streaming text shape, and
the relation between credential lifetime and the invocation duration. It
encodes the Phase 17B.1 body but creates no application, acquires no credential,
performs no resolution, and opens no transport. The first session pull creates
at most one fresh Ready external-service application whose profile must be the
exact profile advertised by its factory.

Complete `kongono/http-message` with an Experimental outbound `Request` and
PSR-17 `RequestFactory`. The provider request factory borrows credential
material only while constructing one `POST` request to the application-owned
canonical HTTPS origin, with an empty endpoint base path and exact path
`/v1/responses`. It emits only `Accept: application/json`,
`Content-Type: application/json`, and `Authorization: Bearer ...`, plus the
exact bounded encoded body. Empty, oversized, or header-unsafe material fails
closed. The adapter, context, session, result, and receipts do not retain the
borrowed credential.

The Internal session has the closed lifecycle `Ready -> Active -> Emitted ->
Exhausted -> Closed`, with `Ready -> Closed` and `Active -> Failed -> Closed`
failure paths. Its first pull runs exactly one Phase 16 application operation;
the second and later successful pulls return `null`. Close is idempotent, and
an early close releases the encoded request without creating the application.
There is no retry, fallback, hedging, redirect policy, resume, polling,
background work, or replay. Failed-before-effect, uncertain-effect,
cancelled-before-effect, and cancelled-after-possible-effect outcomes remain
terminal failures.

The response boundary requires status 200, exactly one syntactically valid
`application/json` media type with only an optional UTF-8 charset, a readable
body, and no more than the Phase 17B.1 decoder bound. The body is read in bounded
chunks without stringification, implicit rewind, or unbounded `getContents()`,
and is closed exactly once after ownership is obtained. Stalls, dishonest size
claims, read errors, cleanup errors, and combined primary-plus-cleanup failures
remain bounded and nondisclosing. Non-200 bodies are not parsed.

One accepted decoded response becomes one Phase 14 `ModelResult`. The request
version, invocation identity, and exact neutral profile are preserved. The
mode is Text; action suggestion and cost are null. Completed maps to Completed,
`max_tokens` to Length, and `content_filter` to ContentFiltered. Optional usage
maps to the canonical `input_tokens`, `output_tokens`, and `total_tokens`
quantities. Visibility is the most restrictive input-message visibility.
Result identity is `openai.responses.` plus the lowercase SHA-256 of the
mapping version, a NUL byte, and the bounded provider response identifier; the
raw provider identifier is not exposed as the neutral identity.

Diagnostics `KGN-OPENAI-RESPONSES-1709` through `1714` cover invalid context,
lifecycle, external-service, HTTP response, response-body/cleanup, and
normalization failures. Diagnostic context contains only closed Safe tokens and
bounded integers. Raw credentials, endpoints, headers, request or response
bodies, provider identifiers, model names, native messages, and stack arguments
do not enter safe exception presentation.

## Consequences

Applications can execute the exact Phase 17B.1 non-streaming text mapping
through accepted neutral runtimes using only explicit application-owned
collaborators. Deterministic local fakes prove success, cancellation,
uncertainty, cleanup, nondisclosure, one-attempt behavior, and sequential
isolation without DNS, sockets, cURL, a real credential, or a provider call.

The outbound request, PSR-17 request factory, application factory interface,
invocation context, adapter, and invocation exception are Experimental. The
session, state enum, request factory, body reader, result normalizer, and
mapping helpers are Internal. No Phase 17 declaration is Public or Stable.

This decision does not establish live OpenAI compatibility, provider
certification, production readiness, or current external protocol behavior.
Streaming, structured output, action suggestions, an application workflow,
inspection, Chie integration, and local external acceptance remain Phase
17D-H work and are not begun by this decision.

## Current and next status

Phase 17A through Phase 17G are Accepted. Phase 17H remains Planned and requires
separate authenticated authority; it alone may checkpoint the milestone. Phase
18 does not begin automatically.

See the [package README](../../packages/model-provider-openai-responses/README.md),
the [OpenAI Responses provider-adapter guide](../architecture/openai-responses-provider-adapter.md),
the [Phase 17 roadmap](../architecture/phase-17-roadmap.md),
[ADR 0171](0171-first-party-openai-responses-adapter-boundary.md), and
[ADR 0172](0172-deterministic-openai-responses-offline-protocol-mapping.md).
