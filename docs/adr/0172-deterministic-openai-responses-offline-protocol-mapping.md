# ADR 0172: Deterministic OpenAI Responses Offline Protocol Mapping

Status: Accepted

Date: 2026-09-10

Scope: Phase 17B only

## Context

ADR 0171 fixed the architecture for one optional first-party OpenAI Responses
adapter but intentionally added no provider implementation. Kongono needs a
deterministic, testable mapping vocabulary before any credential, egress, or
Phase 14 runtime-adapter composition can be considered. Provider documentation
is volatile external evidence and cannot define repository authority for this
phase.

## Decision

Create the optional Experimental
`kongono/model-provider-openai-responses` outer leaf. Its only production
dependencies are `kongono/api-metadata`, `kongono/diagnostics`, and
`kongono/model-invocation-contract`. Existing neutral packages do not depend
on it.

The package owns mapping version `kongono-openai-responses/1` and an exact
profile wrapper over one caller-supplied neutral profile. The neutral contract
must be version 1, the provider must be exactly `openai`, the model must remain
caller-selected, and capabilities must be exactly `TextResult` followed by
`UsageEvidence`. Storage is an explicit Disabled or Enabled preference mapped
to the request `store` boolean; it establishes no retention or deletion fact.

The stateless request encoder accepts one through 64 System, User, or Assistant
messages in a non-streaming text request. It produces compact JSON with
binary-ASCII object-key order, preserves message order, and fixes the relative
path `/v1/responses` and media type `application/json`. Encoded bytes are
limited to 524,288 and accompanied by their count and uppercase SHA-256. The
neutral output-byte limit is not emitted as provider `max_output_tokens`
because bytes and tokens are distinct units.

The stateless response decoder accepts a complete body of at most 2,097,152
bytes and JSON depth at most 64. It rejects invalid UTF-8, malformed JSON,
non-object roots, duplicate object members at any depth, inexact integer
representations, and unsupported or ambiguous nested shapes. It accepts only
one terminal completed or incomplete assistant message containing one
`output_text` item. Output text is bounded by the request and neutral
`ResultText`. The message carries one bounded `msg_...` identifier that is
validated but not returned, and `output_text` carries a required empty
`annotations` list. Completed `incomplete_details` and terminal `usage` may be
absent or null. Incomplete reasons are limited to `max_tokens` and
`content_filter`; request-side `max_output_tokens` remains deliberately omitted
because bytes and tokens are distinct units.

A non-null usage object requires the three exact aggregate token integers and
may additionally contain the exact known `input_tokens_details.cached_tokens`
and `output_tokens_details.reasoning_tokens` integers. Detail counts are
bounded by their aggregates, validated as untrusted structure, and then
discarded; only aggregate `ResponseUsage` evidence is retained.

Unknown top-level members may be ignored only after whole-body syntax,
duplicate, and required-member validation. They are neither forwarded nor
trusted. Unknown nested members, nonempty annotations, refusals, structured output,
tools, function calls, streaming events, nonterminal states, failed or
cancelled responses, and multiple message or text items fail closed.

Decoded response evidence remains Internal and untrusted. It is not a Phase 14
`ModelResult` and proves no provider identity, billing, cost, safety, truth,
authorization, delivery, retention, or effect.

The diagnostic family is `KGN-OPENAI-RESPONSES-1701` through `1708`. Context
uses a closed Safe schema and exceptions present no raw input, output, JSON,
identifier, model, endpoint, header, native message, secret-like value, or
previous-exception text. No claim is made that PHP traces are sanitized.

## Consequences

The mapping can be proven offline with authenticated synthetic fixtures and
repeatable mixed-operation tests. Production objects are immutable or
stateless and retain no ambient current profile, body, cache, registry, clock,
random value, callback, or background work.

This decision adds no SDK, HTTP message, credential acquisition, endpoint,
DNS, socket, cURL, network request, `InvocationAdapter`, adapter session,
application workflow, streaming, structured output, tool execution, Chie
wiring, live compatibility claim, or production-readiness claim.

The mapping version, profile, storage preference, exception marker, and three
leaf exceptions are Experimental. Every other production declaration is
Internal; none is Public or Stable.

Phase 17B.1 corrects the supported envelope from an architect-selected
documented shape. Its synthetic offline fixtures do not establish live
compatibility, provider certification, or current public-provider behavior.

## Current and next status

Phase 17A through Phase 17G are Accepted. Phase 17H remains Planned and requires
separate authenticated authority; it alone may checkpoint Phase 17. Phase 18
does not begin automatically.

See the [package README](../../packages/model-provider-openai-responses/README.md),
the [OpenAI Responses provider-adapter guide](../architecture/openai-responses-provider-adapter.md),
the [Phase 17 roadmap](../architecture/phase-17-roadmap.md), and
[ADR 0171](0171-first-party-openai-responses-adapter-boundary.md).
