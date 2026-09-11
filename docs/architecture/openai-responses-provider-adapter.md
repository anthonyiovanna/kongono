# OpenAI Responses Provider Adapter

## Current status

Phase 17A accepts [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md)
and the fixed architecture. Phase 17B accepts
[ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md)
and the optional Experimental `kongono/model-provider-openai-responses`
offline mapping package. Phase 17C accepts
[ADR 0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md) as the
accepted one-shot non-streaming adapter boundary. Phase 17D accepts [ADR
0174](../adr/0174-pull-based-openai-responses-sse-streaming.md) as the accepted
pull-based text SSE boundary. Phase 17E accepts [ADR
0175](../adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md)
and is Accepted for strict non-streaming structured results and one inert
function-call suggestion. Phase 17F accepts [ADR
0176](../adr/0176-exact-current-openai-responses-application-workflow.md) for
the exact-profile application workflow. Phase 17G accepts [ADR
0177](../adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md)
and the optional Experimental offline inspection leaf. Phase 17H accepts [ADR
0178](../adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md)
and the root-owned local proof described in the [Phase 17 acceptance
guide](phase-17-openai-responses-local-acceptance.md). Phase 17A-H are Accepted
and Phase 17 is closed; Phase 18 remains separately unauthorized.

The package now contains one locally executable `InvocationAdapter` using
explicit Phase 14-16 collaborators for non-streaming and the exact pull-based
text SSE profile, plus the exact Version 2 structured and action profiles. It
has no provider SDK, ambient credential or endpoint,
automatic retry, reconnect, replay, command, Chie
connection, public-provider request, or live support claim. Phase 16A-H remain
Accepted and Phase 16 is closed at commit
`3690aab42e3759d3f42e711703d4f23e6588fa85`. The inspection package is
offline, read-only, stateless, and visibility-safe, and exposes no hidden
payload, credential, endpoint, network, provider observation, authority,
replay, repair, recording, exporting, certification, or role execution. Phase
18 is unauthorized.

## Current one-shot composition

`OpenAiResponsesInvocationAdapter` has identity `openai.responses.v1` and may
open once. `open()` validates the exact plan, profile, operation context,
mapping-version shape, and finite credential lifetime, then creates only the
deterministic versioned body. Version 2 streaming is rejected before external-
service application creation. The first session pull creates one fresh
exact-profile Phase 16 application, constructs one credential-backed outbound
request, and runs one Phase 15-governed attempt. An early close creates no
application. Successful sessions emit one result and then only `null`;
failures and cancellations never replay.

The outbound request is `POST` to the application-owned canonical HTTPS origin
with an empty base path and `/v1/responses`. Its only provider headers are
`Accept: application/json`, `Content-Type: application/json`, and a Bearer
authorization field created while credential material is borrowed. The
credential is not retained by the adapter, context, session, result, or
receipt. The origin is not hard-coded to a public provider hostname.

Responses require status 200, exactly one valid `application/json` content
type with only an optional UTF-8 charset, and a readable body within the
decoder bound. The adapter reads bounded chunks without stringification,
rewind, or unbounded `getContents()` and attempts exactly one body close after
ownership. It maps completed and the two supported incomplete outcomes, usage,
most-restrictive visibility, and a hashed provider-response identity into one
Phase 14 text, structured, or inert action-suggestion result.

There is one attempt and no retry, fallback, hedging, reconnect, polling, or
replay. Uncertain-effect, cancelled-before-effect, and
cancelled-after-possible-effect evidence remains terminal failure evidence.
Diagnostics `KGN-OPENAI-RESPONSES-1709` through `1714` expose only closed Safe
context values.

## Accepted offline mapping version 1

Mapping version `kongono-openai-responses/1` wraps one exact Phase 14 version-1
profile. The provider identity is exactly `openai`, the model identity is
caller-supplied, and the canonical capabilities are exactly `TextResult` then
`UsageEvidence`. `ResponseStoragePreference` is explicitly Disabled or Enabled
and maps to the request `store` boolean. It is a preference, not proof of
retention, deletion, privacy, compliance, or erasure.

The stateless request encoder accepts one through 64 System, User, or Assistant
messages in one non-streaming text request. It emits compact JSON with
binary-ASCII object-key order, preserves message order, fixes
`/v1/responses` and `application/json`, and is bounded to 524,288 bytes.
`max_output_tokens` is deliberately absent because the neutral limit is in
bytes, not provider tokens. Arbitrary options and every unsupported request
field are omitted.

The stateless decoder accepts a complete body of at most 2,097,152 bytes and
JSON depth at most 64. It requires valid UTF-8, valid JSON, a top-level object,
and no duplicate member names at any depth. It accepts exactly one terminal
completed or incomplete assistant message with one bounded `msg_...` identifier
and one `output_text` item carrying a required empty `annotations` list. Text
must satisfy the request byte limit and neutral `ResultText` rules. Completed
`incomplete_details` and terminal `usage` may be absent or null. Incomplete
reasons are limited to `max_tokens` and `content_filter`; request-side
`max_output_tokens` remains deliberately omitted because its token unit differs
from the neutral byte limit.

A non-null usage object contains the exact bounded aggregate token integers and
may contain the exact known `input_tokens_details.cached_tokens` and
`output_tokens_details.reasoning_tokens` objects. The known detail counts must
not exceed their matching aggregates. They are validated as untrusted protocol
structure and discarded rather than retained, exposed, forwarded, or trusted.

Unknown top-level members are ignored only after whole-body validation and are
not forwarded or trusted. Unknown nested members, multiple items, nonempty
annotations, refusals, structured content, tools, function calls, streaming,
nonterminal, failed, cancelled, and ambiguous shapes fail closed. Decoded values remain
bounded untrusted claims and are not yet a Phase 14 `ModelResult`.

Phase 17B.1 corrects this envelope from an architect-selected documented shape.
It remains deterministic offline mapping evidence, not live compatibility,
provider certification, or proof of current public-provider behavior.

Diagnostics `KGN-OPENAI-RESPONSES-1701` through `1708` use only closed Safe
context tokens. Input/output text, raw JSON, identifiers, model names,
endpoints, headers, native messages, and secret-like values remain excluded.
All production objects are immutable or stateless and safe for sequential use
in a persistent process without retained bodies or ambient state.

## Current offline mapping version 2

Mapping version `kongono-openai-responses/2` is non-streaming and accepts
exactly one of two profiles. Structured mode requires canonical capabilities
`StructuredResult`, `UsageEvidence` and one explicit named strict schema.
Action mode requires `ActionSuggestion`, `UsageEvidence` and one explicit
named function whose parameters are a strict schema. Cross-mode definitions,
both definitions, missing definitions, other capability orders, and Version 2
streaming fail closed.

The schema subset has an object root and only object, array, string, integer,
number, boolean, and null nodes. Objects declare a closed property map, require
the complete property-name set exactly once, and set `additionalProperties` to
false. Arrays declare one item schema. Scalar enums are optional, nonempty,
duplicate-free, and exact-type. Source bytes, JSON and logical depth, nodes,
properties, required names, enum entries, descriptions, and property names are
bounded. Duplicate members, unknown keywords, references, recursion, unions,
conditions, formats, defaults, inference, and coercion are rejected. Canonical
JSON recursively orders object members while preserving array order. Schema
definitions are explicit provider-profile input; Kongono never invents them.

Structured requests add one exact strict `text.format` JSON-schema object.
Completed responses must carry one assistant `output_text` whose text is a
duplicate-safe JSON object and validates locally against the exact schema. The
normalized structured `ResultText` is canonical JSON and remains untrusted
evidence, not a truth claim.

Action requests disable parallel tool calls, require tool choice, and send one
exact strict function definition. Completed responses must contain exactly one
matching bounded `function_call`; its argument object is duplicate-safe,
schema-validated, canonicalized, and mapped to one neutral `ActionSuggestion`.
The suggestion is inert: no callable or tool is resolved, dispatched, queued,
approved, authorized, or executed; no function output or second provider
operation is created.

## Purpose and position

The current integration is one optional outer adapter for applications that
explicitly choose OpenAI's Responses API. It does not alter Kongono's neutral
model-invocation core or make OpenAI the framework default. Applications not
using OpenAI will not install the adapter, and applications not using AI need
neither Phase 14 nor Phase 17.

The full planned flow is:

```text
application-owned invocation intent and current authorization
  -> Phase 14 exact-current model-invocation plan/application
  -> optional Phase 17 OpenAI Responses adapter profile
  -> Phase 16 exact-current service binding and credential lease
  -> Phase 15 current egress authority, resolver evidence, and cURL transport
  -> one explicitly authorized HTTPS request
  -> bounded untrusted provider response or SSE events
  -> strict provider-specific validation and normalization
  -> Phase 14 provider-neutral result/events/receipt
  -> deterministic reverse cleanup and preserved uncertainty
```

Validation must precede every arrow. A model plan does not grant network
authority; a binding does not grant credential access; a credential does not
grant network access; a provider response does not grant tool authority; and
an action suggestion does not authorize an application effect.

## Exact provider selection

The selected profile is the OpenAI Responses API, not a generic
"OpenAI-compatible" protocol. Similar field names, routes, or JSON shapes do
not establish equivalent semantics. Another provider, proxy, gateway,
compatible server, regional or cloud-hosted variant, or protocol revision
needs separate profile identity, fixtures, mapping evidence, and support
authority.

The application supplies an exact model identity through the Phase 14 plan.
Kongono supplies no default model, organization, project, account, endpoint,
or provider. Model availability, aliases, limits, features, pricing, retention,
and provider policies remain volatile external facts.

## Why the existing transport is reused

Kongono already has explicit boundaries for request messages, policy,
authority currentness, resolution, exact selected-address binding, transport,
finite budgets, cancellation, cleanup, and uncertain effects. The first-party
adapter therefore composes [governed network egress](outbound-http-and-network-egress.md)
and [external-service binding](external-service-binding-and-secret-leases.md)
instead of adding the OpenAI SDK, another HTTP client, direct cURL calls,
another DNS implementation, ambient proxy discovery, or a second retry engine.

This is a package-boundary decision, not a general claim against SDKs. A
community adapter may own another dependency and support policy outside the
neutral core.

## Application-owned choices

The application owns and supplies:

- the provider selection and exact model identity;
- one exact-current Phase 14 invocation plan;
- the exact service endpoint and secret-free Phase 16 binding;
- the credential reference and credential-source implementation;
- current credential and network authorization;
- storage and continuation posture;
- any separately admitted tool or application-effect mapping;
- cancellation, budgets, clocks, resolver policy, and transport collaborators;
  and
- retention, audit, deployment, and operational decisions.

No environment-variable API-key lookup, `.env` loading, service-locator
lookup, global client, static current provider, hidden endpoint/model, or
ambient organization/project is selected.

## Deterministic mapping and finite bounds

Phase 17B defines one Kongono-owned mapping version independent of
provider marketing names. Encoding and decoding must be deterministic, strict,
bounded, fixture-driven, and offline-testable. Unknown required structure,
duplicate or ambiguous fields, malformed UTF-8 or JSON, invalid SSE order,
unsupported content, oversize material, and truncation fail closed.

Finite limits cover instructions, input items, input text, schemas, tools,
metadata, headers, request bytes, response bytes, event count and bytes, output
items and text, tool arguments, usage numbers, and elapsed time. Provider
fields are never forwarded opportunistically. There is no raw request-body
override, raw header injection, base-URL string, or arbitrary option bag.

Provider-native values remain inside the adapter leaf. Only bounded validated
values intentionally mapped to Phase 14 cross the neutral boundary. Raw JSON,
SSE payloads, headers, bodies, native exceptions, and provider error text do
not cross it.

## Text, structured output, refusal, usage, and suggestions

Text output, schema-constrained structured output, refusal evidence, and
function or tool-call arguments remain distinct; the adapter cannot flatten
all output into one convenient string or assume that the first item is text.
Schema validation checks shape, not truth or authority.

Function or tool calls can become only inert Phase 14 action suggestions.
They execute nothing and do not bypass Agent admission, authentication,
authorization, capability, or application-effect gates.

Provider identifiers, status values, refusal or safety labels, rate-limit
headers, token counts, cached or reasoning-token counts, usage, and
acknowledgements are bounded provider claims. They do not prove billing, cost,
delivery, uniqueness, legal status, safety, authority, or effect. The initial
profile makes no live pricing or cost calculation claim.

## Credential and disclosure boundary

Secret material is borrowed through the Phase 16 operation-scoped lease only
while constructing the terminal authorization field for one request. It does
not enter deterministic evidence, generated PHP, URLs, query strings, model
input or output, diagnostics, logs, traces, metrics, operational events,
Security Audit records, receipts, Graph facts, inspection, tests, reports,
commands, caches, retry state, globals, static properties, or serialized
values.

Credential and network permissions are independently current. Lease retirement
reduces exposure but does not prove reliable erasure of PHP or extension
memory. Developer diagnostics use closed boundary identifiers and bounded
remediation while omitting bodies, headers, private addresses, native text,
and stack arguments.

## Storage and provider retention

Provider-side storage or continuation is explicit, never ambient. The initial
safe profile does not rely on provider-held conversation state, a previous
response identifier, or background execution. A request preference against
provider storage is not proof of immediate deletion, zero retention, legal
compliance, or provider-side erasure; those are external account and provider
claims requiring separate verification.

## Streaming and backpressure

Accepted Phase 17D streaming uses Responses server-sent events only through Phase 15's
pull-based bounded transport. Consumer demand advances the stream. The adapter
validates event type/order, supplied sequence evidence, item and content
indexes, terminal state, completeness, counts, and aggregate bytes.

There is no background reader, unbounded buffer, detached task, callback
reentrancy, hidden queue, retained connection, or automatic reconnect.
Cancellation and failure trigger deterministic reverse cleanup. A missing
terminal event, malformed frame, unsupported event, truncation, disconnect, or
cleanup failure preserves uncertainty rather than inventing success or
replaying the request.

The reader accepts only one closed text event sequence and emits `Started`,
nonempty `ContentDelta` events, optional `Usage`, and one terminal event before
the result. It reads at most 8,192 bytes at a time, bounds total, retained,
line, data, JSON-depth, and event counts, and closes the body once. Phase 15 may
have buffered the response before it becomes a PSR-7 body, so this is honest
pull-based delivery rather than an immediate first-token, nonblocking, or
realtime latency guarantee.

## Failure, retry, and uncertainty

Current diagnostics distinguish offline mapping failures and the Phase 17C
invalid-context, lifecycle, external-service, HTTP response, response-body and
cleanup, and result-normalization boundaries. Phase 17D diagnostics distinguish
SSE framing, event mapping, ordering/completeness, and cleanup failures. Phase
17E adds one strict-schema diagnostic and version-aware request/response
diagnostic context without exposing schema, value, or argument content.
Provider-specific rate-limit, authentication-rejection, and refusal diagnostics
remain later-row work.

There is no automatic retry, fallback model or provider, endpoint failover,
credential refresh, re-resolution, reconnect, polling, or replay. Once a
request may have reached the provider, billing and effect state may be
uncertain. Any future retry policy needs separately accepted idempotency,
budgets, current authority, and provider-specific semantics.

## Lifecycle and persistent runtimes

Each invocation and stream has one owner, finite state transitions, reverse
cleanup, and isolated state. No credential, request, response, SSE parser,
output, previous response identifier, connection, lease, or current adapter
remains ambient after retirement. Sequential, overlapping, cancelled,
malformed, refused, rate-limited, transport-failed, provider-failed, and
cleanup-failed operations in one process cannot leak state into one another.
Destructors are not the primary cleanup protocol, and process termination is
not success evidence.

## Developer experience

The current supported path is deliberately small and explicit:

1. define one Phase 14 model plan;
2. define one Phase 16 service binding and credential reference;
3. select one Phase 17 adapter profile;
4. call the Phase 17F workflow `build()` or its write-free `check()` with the
   exact Phase 14 paths/source, provider profile, and Phase 16 profile;
5. call the returned single-use application's `run()` with the explicit Phase
   17C factory, identities, authority-currentness, cancellation, credential
   purpose/lifetime, receipts, and clocks;
6. receive one typed result or a nondisclosing terminal failure; and
7. prove behavior with local fixtures and later loopback acceptance.

No facade, magic property, reflection scan, annotation injection, global
singleton, hidden autowiring, mandatory framework bundle, provider registry,
or default model is introduced.

## Offline and external testing

Protocol mapping and the one-shot adapter are proved with locally authenticated
bounded fixtures. Tests cover encoding, strict decoding, exact request
construction, one-attempt lifecycle, bounds, disclosure, cancellation,
uncertainty, body and session cleanup, sequential isolation, and exact mapping-
version behavior. Phase 17D adds synthetic completed, incomplete, and failed
SSE fixtures with framing, ordering, lifecycle, and neutral-runtime tests.
Phase 17E adds synthetic structured and function-call fixtures proving exact
canonical request hashes, schema bounds and adversarial rejection, local value
validation, inert action normalization, one attempt, zero effects, and no
second operation.
Fixture content is synthetic evidence and does not grant live support.

Only Phase 17H may add a root-owned deterministic loopback provider-protocol
emulator and checkpoint under separate authority. It uses no real credential,
account, public OpenAI endpoint, provider certification, or production claim.

## Chie, Hantei, Oshie, and Agents

Chie may explain accepted architecture and later diagnose supplied bounded
failure evidence. Hantei may independently verify offline artifacts, fixtures,
and acceptance evidence. Oshie may teach the mapping and safe use. None may
discover credentials, authorize access, select provider/model state, send a
request, execute a tool, accept provider claims as truth, or repair state.

Phase 17 does not connect Chie or an Agent directly to a live provider. Agent
output remains a proposal, and any capability or effect remains governed by
the accepted Agent Principal and security boundaries.

## Residual risks and non-claims

Strict local mapping cannot prove provider availability, live schema behavior,
model entitlement, pricing, rate limits, retention, billing, safety,
compliance, public DNS/TLS, deployment, latency, performance, or production
readiness. Provider drift can invalidate fixture-based support evidence. A
successful response is untrusted evidence and cannot establish truth,
authorization, or an external effect. Cleanup cannot guarantee physical memory
zeroization, and uncertain transport outcomes cannot be made certain locally.

Phase 17C is a locally executable one-shot composition, not a live-provider
compatibility claim. No Public, Stable, 1.0, provider-certified, cost-accuracy,
or performance-superiority claim is made.

## Fixed Phase 17 roadmap

| Row | Required boundary | Required exclusions |
| --- | --- | --- |
| **17A - Architecture and fixed roadmap** | Accepted documentation-only architecture for one optional first-party OpenAI Responses API adapter above the Phase 14-16 boundaries. | No PHP, package, API, schema, codec, adapter, SDK, credential, network, command, Chie integration, provider call, or runtime behavior. |
| **17B - Deterministic offline protocol mapping** | Accepted optional Experimental `kongono/model-provider-openai-responses` foundation with exact mapping version `kongono-openai-responses/1`, typed provider profile, bounded deterministic request encoding, strict bounded non-streaming response decoding, synthetic fixtures, and nondisclosing diagnostics. | No network, credential access, SDK, runtime adapter session, streaming, tool execution, ambient endpoint/model, application workflow, or live support claim. |
| **17C - One-shot non-streaming invocation adapter** | Accepted `InvocationAdapter` implementation composing one exact Phase 14 source with one fresh exact-profile Phase 16 application operation and Phase 15 transport, then strictly closing and normalizing one terminal non-streaming response. | No streaming, automatic retry/fallback, retained session, provider SDK, ambient credential/model, background work, tool execution, Chie wiring, or live compatibility claim. |
| **17D - Pull-based Responses SSE streaming** | Accepted bounded incremental SSE/event mapping with consumer-driven pulls, order/completeness validation, contiguous neutral emissions, uncertainty preservation, and deterministic cleanup. | No background reader, reconnect, replay, unbounded buffer, realtime/WebSocket API, immediate first-token guarantee, audio, image, hosted tool, or retained conversation. |
| **17E - Structured output and inert action suggestions** | Accepted strict JSON-schema mapping Version 2 for locally validated non-streaming structured results and one inert function-call suggestion. | No function/tool execution, remote MCP, web/file search, computer use, code execution, second provider operation, application effect, schema invention, or model-output authority. |
| **17F - Exact-current application profile and supported workflow** | Accepted deterministic adapter-profile/currentness evidence and one explicit application-owned build/check/run composition over accepted Phase 14-16 inputs. | No registry, default provider/model, global client, environment discovery, dynamic compatibility mode, automatic migration, broad framework bundle, or Chie application. |
| **17G - Visibility-safe inspection and verification** | Accepted optional Experimental offline read-only inspection of one exact-current adapter profile and optional matching detached completed result with role-aware bounded evidence and independent verification. | No credential access, networking, provider observation, body loading, generated-code execution, authority evaluation, retry, replay, repair, recording, exporting, certification, or role execution. |
| **17H - Local external acceptance and checkpoint** | Accepted root-owned deterministic loopback provider-protocol emulator proving Phase 17 boundaries and regressions, followed by one exact local checkpoint. | No real credential/account, public OpenAI endpoint, provider certification, live compatibility claim, production readiness, push, tag, release, Stable/1.0, Chie integration, or Phase 18 behavior. |

The order is fixed. Phase 17A-H are Accepted and Phase 17 is closed at the
exact local checkpoint. Phase 18 does not begin automatically and remains
separately unauthorized.

## Related material

- [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md)
- [ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md)
- [ADR 0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md)
- [ADR 0174](../adr/0174-pull-based-openai-responses-sse-streaming.md)
- [ADR 0175](../adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md)
- [ADR 0176](../adr/0176-exact-current-openai-responses-application-workflow.md)
- [ADR 0177](../adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md)
- [ADR 0178](../adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md)
- [Offline mapping package](../../packages/model-provider-openai-responses/README.md)
- [OpenAI Responses inspection and evidence](openai-responses-inspection-and-evidence.md)
- [Phase 17 roadmap](phase-17-roadmap.md)
- [Phase 17 local acceptance](phase-17-openai-responses-local-acceptance.md)
- [Provider-neutral model invocation](provider-neutral-model-invocation.md)
- [Model-invocation runtime](model-invocation-runtime.md)
- [Model-invocation application workflow](model-invocation-application-workflow.md)
- [Outbound HTTP and governed network egress](outbound-http-and-network-egress.md)
- [External-service binding and secret leases](external-service-binding-and-secret-leases.md)
- [Phase 14 roadmap](phase-14-roadmap.md)
- [Phase 15 roadmap](phase-15-roadmap.md)
- [Phase 16 roadmap](phase-16-roadmap.md)
- [Official Responses API reference](https://platform.openai.com/docs/api-reference/responses)
- [Official Responses streaming reference](https://platform.openai.com/docs/api-reference/responses-streaming)
- [Official function-calling guide](https://platform.openai.com/docs/guides/function-calling)
- [Official structured-outputs guide](https://platform.openai.com/docs/guides/structured-outputs)

Official links are dated only by the reader's retrieval and remain volatile
external protocol evidence. They are not repository authority or proof of
current adapter support.
