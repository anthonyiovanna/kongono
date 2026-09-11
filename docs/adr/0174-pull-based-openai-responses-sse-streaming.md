# ADR 0174: Pull-Based OpenAI Responses SSE Streaming

Status: Accepted

Date: 2026-09-10

Scope: Phase 17D only

## Context

ADRs 0171 through 0173 established the optional OpenAI Responses mapping and
one-shot non-streaming adapter over the accepted Phase 14 invocation, Phase 16
service-binding, and Phase 15 governed-egress boundaries. Kongono also needs a
bounded streaming profile without creating a second transport, an ambient
client, or a background reader.

The neutral Phase 14 runtime already owns consumer-driven stream pulls,
cancellation checks, event continuity, budgets, terminal result validation,
and session cleanup. The provider adapter therefore maps only the closed
Responses SSE subset needed by the exact version-1 text profile.

## Decision

Extend `kongono/model-provider-openai-responses` with one Internal pull-based
SSE session selected only when the exact neutral capability list is
`TextResult`, `Streaming`, `UsageEvidence` and the invocation requests
streaming. The existing `TextResult`, `UsageEvidence` profile continues to use
the Phase 17C non-streaming session. Request encoding sets `stream` from that
validated choice, and streaming requests use `Accept: text/event-stream`.
The provider and adapter identities remain unchanged because streaming is an
explicit capability of the same versioned Responses mapping, not a different
provider or discovery target.

The first consumer pull performs the same single Phase 16 application
operation and single Phase 15 governed-egress attempt as Phase 17C. There is no
retry, fallback, reconnect, resume, replay, polling, provider SDK, or parallel
network stack. Each later consumer pull advances the owned PSR-7 response body
only as needed to produce one neutral emission.

The reader accepts only status 200, one `text/event-stream` content type with
an optional UTF-8 charset, and a readable body. Reads are at most 8,192 bytes;
the total body limit is 2,097,152 bytes; retained unread, physical-line, and
single-data-field limits are each 262,144 bytes. A non-EOF empty read fails
immediately. The reader never stringifies, rewinds, seeks, detaches, or calls
`getContents()` on the body.

Each SSE frame has an optional single `event:` field before exactly one
single-line nonempty `data:` field and ends at an empty LF or CRLF line. BOM,
bare CR, comments, keepalives, `id`, `retry`, multiline data, `[DONE]`, unknown
fields, unterminated frames, and trailing terminal bytes fail closed. JSON is
decoded to depth 64 with duplicate-member rejection. At most 4,096 provider
events are accepted.

Mapping version `kongono-openai-responses/1` accepts only the closed ordered
text sequence from `response.created` through exactly one terminal
`response.completed`, `response.incomplete`, or `response.failed`. Provider
sequence numbers are contiguous from zero. Response, message, output, content,
text, completion, and usage snapshots must agree exactly. Unknown events,
refusals, tools, structured output, annotations, nonzero indexes, sequence
gaps, duplicate terminals, missing terminals, and inconsistent snapshots fail
closed.

Successful streams emit neutral `Started`, one nonempty `ContentDelta` per
provider delta, optional terminal `Usage`, `Completed`, and then the normalized
Phase 14 result. Provider failure emits neutral `Failed` and no result.
Neutral sequence numbers are contiguous from one. Completed and incomplete
results reuse the Phase 17C normalization and retain the accumulated text,
status, finish reason, visibility, usage, and derived non-provider result
identity.

Only the terminal emissions may be queued: optional `Usage`, one terminal
neutral event, and one result. Frames, decoded provider events, and accumulated
state are released as soon as their relation has been validated. No unbounded
event or delta list is retained.

The response body receives exactly one close attempt on success, parsing or
mapping failure, cancellation, or explicit session close. Close is idempotent.
If the primary operation and cleanup both fail, the bounded exception retains
both identities programmatically without exposing raw messages or stream
content. Diagnostics `KGN-OPENAI-RESPONSES-1715` through `1718` identify
framing, mapping, order/completeness, and cleanup failures using only closed
Safe tokens and bounded integers.

## Consequences

Applications may exercise the exact text-and-usage Responses SSE subset through
the existing neutral runtime and governed external-service stack. Backpressure
is honest at the adapter boundary because only a consumer pull advances PHP
body reads. It does not promise immediate first-token delivery from an open
socket: Phase 15 may already have buffered the response before the adapter owns
the PSR-7 stream. There is no asynchronous, nonblocking, realtime, WebSocket,
or zero-latency claim.

All new parser, frame, event, state-machine, normalizer, session, and state
declarations are Internal. No declaration becomes Public or Stable. The graph
remains 88 acyclic production packages and 366 local production edges. PHP
inventories become 1,685 production, 876 package-test, and 88 root-test files.

Local synthetic fixtures prove framing, order, completeness, bounds,
nondisclosure, pull behavior, cancellation-compatible cleanup, and terminal
normalization. They do not prove current public OpenAI behavior, DNS, TLS,
credentials, model entitlement, latency, billing, retention, compatibility,
deployment, or production readiness.

## Rejected alternatives

A second adapter/provider identity was rejected because it would misrepresent
one capability choice as a separate provider contract. A provider SDK, direct
cURL use, or another HTTP client was rejected because Phase 15 and Phase 16
already own governed transport and credential access. A background reader,
callback pump, async task, or unbounded queue was rejected because it would
bypass neutral pull cancellation and budget checkpoints. Generic SSE tolerance,
`[DONE]`, unknown-event skipping, reconnect, and replay were rejected because
they weaken the closed mapping and cannot resolve uncertain external effects.

## Current and next status

Phase 17A through Phase 17G are Accepted. Phase 17H remains Planned and requires
separate authenticated authority; it alone may create the milestone checkpoint.
Phase 18 does not begin automatically.

See [ADR 0171](0171-first-party-openai-responses-adapter-boundary.md),
[ADR 0172](0172-deterministic-openai-responses-offline-protocol-mapping.md),
[ADR 0173](0173-one-shot-openai-responses-invocation-adapter.md), the
[OpenAI Responses provider-adapter guide](../architecture/openai-responses-provider-adapter.md),
and the [Phase 17 roadmap](../architecture/phase-17-roadmap.md).
