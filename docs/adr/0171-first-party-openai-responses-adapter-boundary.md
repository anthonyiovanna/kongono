# ADR 0171: First-Party OpenAI Responses Adapter Boundary

- Status: Accepted
- Date: 2026-09-09
- Scope: Phase 17A only

## Context

Phase 14 supplies Kongono's provider-neutral model-invocation contracts,
deterministic plan evidence, bounded runtime, exact-current application,
Agent-proposal bridge, and visibility-safe inspection. Phase 15 supplies
governed outbound HTTP and exact selected-address transport. Phase 16 supplies
secret-free service bindings, bounded resolution evidence, operation-scoped
credential leases, exact-current external-service composition, and offline
inspection. Phase 16A-H are Accepted, and Phase 16 is closed at commit
`3690aab42e3759d3f42e711703d4f23e6588fa85`.

Those accepted layers intentionally contain no concrete model-provider
adapter. Applications that explicitly choose OpenAI's Responses API need one
outer integration that preserves the neutral core, uses the existing governed
transport and credential boundaries, and treats provider material as untrusted
evidence.

Phase 17A accepts only the architecture and fixed roadmap for that integration.
It creates no package, PHP declaration, schema, codec, request, parser,
adapter, credential source, network operation, command, generated artifact,
Chie integration, provider call, or runtime behavior.

## Decision

### Optional outer provider profile

Phase 17 is the optional First-Party OpenAI Responses Adapter milestone. Its
selected surface is the OpenAI Responses API. OpenAI is not Kongono's default
provider, and the adapter does not change Phase 14's provider-neutral core.
Applications that do not use OpenAI do not install it; applications that do
not use AI do not install Phase 14 or Phase 17 packages.

The permitted conceptual direction is:

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

Each arrow is permitted dependency direction after validation. It grants no
credential access, network access, provider permission, tool authority,
application effect, or trust in model output.

### Exact surface and provider drift

The profile names the OpenAI Responses API exactly. Kongono does not advertise
generic "OpenAI-compatible" behavior. A proxy, gateway, compatible server,
regional or cloud-hosted variant, another provider, or a changed protocol
requires its own explicit profile, authenticated fixtures, mapping evidence,
and support decision.

The application supplies one exact model identity through its Phase 14 plan.
No marketing alias becomes a framework default. Model availability, aliases,
feature support, context or rate limits, pricing, retention, account policy,
and provider behavior are volatile external evidence rather than Kongono
authority.

### Existing transport and service boundaries

The first-party adapter uses Kongono's accepted HTTP messages, Phase 15
network-egress application, explicit resolver evidence and cURL transport, and
Phase 16 external-service application and credential lease. It does not add
the OpenAI SDK, a generic third-party HTTP client, a second DNS or retry stack,
ambient proxy discovery, or direct socket/cURL behavior inside the adapter.

An optional community adapter may adopt another dependency under its own
package and support policy, but that dependency cannot become a transitive
requirement of Kongono's neutral core.

### Explicit mapping and finite protocol surface

Later implementation must name one Kongono-owned mapping version independent
of provider marketing names. Request encoding and response or SSE decoding are
deterministic, bounded, strict, fixture-driven, and testable offline. Unknown
required structure, duplicate or ambiguous fields, malformed UTF-8 or JSON,
invalid event order, unsupported content, oversized material, and truncated
input fail closed.

Provider-specific values remain inside the adapter leaf. Only intentionally
mapped, validated, bounded values cross into Phase 14 contracts. Raw provider
JSON, event payloads, headers, bodies, native exceptions, and provider error
text do not cross that boundary.

The initial typed surface must set finite limits for instructions, input
items, text, schemas, tools, metadata, headers, request and response bytes,
events and event bytes, output items and text, tool arguments, usage numbers,
and elapsed operation time. It exposes no arbitrary option bag, raw body
override, raw header injection, base-URL string, or opportunistic forwarding
of unsupported provider fields.

### Authority, storage, and credentials

Each invocation requires explicit current application authorization, one
exact-current Phase 14 model plan, one exact-current Phase 16 service binding,
one credential lease, and current Phase 15 network authority. Provider or
model identifiers, credentials, earlier responses, response or conversation
identifiers, cache keys, receipts, Graph facts, generated artifacts, and Agent
proposals grant none of these.

There is no ambient API-key environment lookup, `.env` loading, global client,
static current provider, default organization or project, hidden endpoint or
model, or service-locator lookup. Provider-side storage or continuation is an
explicit application choice. A request preference against provider storage is
not proof of immediate deletion, zero retention, compliance, or erasure.

Secret material is borrowed through the Phase 16 lease only while constructing
the terminal authorization field for one operation. It cannot enter plans,
profiles, deterministic artifacts, generated PHP, URLs, query strings, model
input, output, diagnostics, exceptions, logs, traces, metrics, operational or
audit evidence, receipts, Graph facts, inspection, tests, reports, commands,
retry state, caches, globals, static properties, or serialized values.
Credential and network permissions remain independent. Retirement and
reference release do not prove physical erasure of PHP or extension memory.

### Streaming, outputs, and effects

Later streaming support uses the Responses server-sent event surface through
Phase 15's pull-based bounded transport. Consumer demand owns progression. The
adapter validates event type and order, sequence evidence when supplied,
item/content indexes, terminal state, completeness, total counts, and aggregate
bytes. There is no background reader, unbounded buffer, detached task, callback
reentrancy, hidden queue, retained connection, or automatic reconnect.

Text, schema-constrained structured output, refusal evidence, and function or
tool-call arguments remain distinct. Structured output remains untrusted even
after schema validation. Function or tool calls may become Phase 14 action
suggestions only; they never execute a function, command, extension, network
request, database or filesystem change, Agent capability, or application
effect. Existing admission, authorization, capability, and effect boundaries
remain controlling.

Hosted provider tools, web or file search, computer use, code execution,
remote MCP, image generation, audio, realtime sessions, batch or background
work, fine-tuning, vector stores, file upload, and provider-managed
conversation state are outside the initial boundary.

### Provider evidence, failures, and uncertainty

Provider response and request identifiers, rate-limit headers, token and cached
token counts, reasoning-token counts, finish/status values, refusal or safety
labels, usage, and acknowledgements are bounded provider claims. They do not
prove billing, cost, delivery, uniqueness, legal status, safety, authority, or
an external effect. Phase 17 makes no live pricing or cost-calculation claim.

Later adapter failures distinguish local validation, unsupported mapping,
credential denial or unavailability, stale or denied authority, resolution and
transport failure, oversize or malformed response, provider rejection, rate
limitation, authentication rejection, incomplete response, refusal,
cancellation, protocol violation, and cleanup failure. Diagnostics name only
the failed boundary and bounded remediation; they omit secret material,
request input, output, bodies, headers, URLs, private addresses, native
messages, and stack arguments.

There is no automatic retry, fallback model or provider, endpoint failover,
credential refresh, re-resolution, reconnect, polling, or replay. Once an
attempt may have reached the provider, effect and billing state may be
uncertain. A missing terminal event, malformed frame, unsupported event,
truncation, disconnect, or cleanup failure preserves uncertainty rather than
inventing success or replaying the request.

### Lifecycle and developer roles

Each invocation and stream has one owner, finite state transitions, reverse
cleanup, and operation isolation. No credential, request, provider response,
parser state, model output, response identifier, connection, lease, or current
adapter remains ambient after retirement. Sequential, overlapping, cancelled,
malformed, refused, rate-limited, transport-failed, provider-failed, and
cleanup-failed operations in one process cannot leak state into one another.
Destructors are not the primary cleanup protocol, and process termination is
not success evidence.

Chie may explain accepted architecture and later diagnose bounded accepted
failures from supplied evidence. Hantei may independently verify deterministic
offline artifacts, fixtures, and acceptance evidence. Oshie may teach mapping
and safe use. None discovers credentials, authorizes access, chooses a provider
or model, sends a request, executes a tool, elevates a provider claim, or
silently repairs state. Phase 17 does not connect Chie or an Agent directly to
a live provider.

### Package and API direction

If separately authorized, later implementation should use one optional outer
package named `kongono/model-provider-openai-responses` unless a later row
proves a narrower split is required. It may depend inward only on the minimum
accepted API Metadata, Diagnostics, HTTP message, Phase 14 invocation, Phase
15 egress, and Phase 16 external-service packages required by that row. No
neutral lower package may depend outward on it.

User-selectable provider profile or factory surfaces begin Experimental.
Provider codecs, parsers, header construction, event machines, mapping tables,
and lifecycle helpers remain Internal by default. Phase 17A changes no API
status and makes no Public, Stable, 1.0, provider-certified,
production-readiness, compliance, performance-superiority, or cost-accuracy
claim.

## Fixed Phase 17 roadmap

| Row | Required boundary | Required exclusions |
| --- | --- | --- |
| **17A - Architecture and fixed roadmap** | Accepted documentation-only architecture for one optional first-party OpenAI Responses API adapter above the Phase 14-16 boundaries. | No PHP, package, API, schema, codec, adapter, SDK, credential, network, command, Chie integration, provider call, or runtime behavior. |
| **17B - Deterministic offline protocol mapping** | Planned optional Experimental `kongono/model-provider-openai-responses` foundation with an exact Kongono mapping version, typed provider profile, bounded request encoding, bounded non-streaming response decoding, local fixtures, and nondisclosing diagnostics. | No network, credential access, SDK, runtime adapter session, streaming, tool execution, ambient endpoint/model, application workflow, or live support claim. |
| **17C - One-shot non-streaming invocation adapter** | Planned `InvocationAdapter` implementation composing one exact Phase 14 source with one exact-current Phase 16 application operation and Phase 15 transport, then strictly normalizing one terminal non-streaming response. | No streaming, automatic retry/fallback, retained session, provider SDK, ambient credential/model, background work, tool execution, or Chie wiring. |
| **17D - Pull-based Responses SSE streaming** | Planned bounded incremental SSE/event mapping with consumer-driven pulls, order/completeness validation, cancellation, uncertainty preservation, and deterministic reverse cleanup. | No background reader, reconnect, replay, unbounded buffer, realtime/WebSocket API, audio, image, hosted tool, or retained conversation. |
| **17E - Structured output and inert action suggestions** | Planned strict JSON-schema output and provider function-call mapping into validated Phase 14 structured results and action suggestions only. | No function/tool execution, remote MCP, web/file search, computer use, code execution, application effect, schema invention, or model-output authority. |
| **17F - Exact-current application profile and supported workflow** | Planned deterministic adapter-profile/currentness evidence and one explicit application-owned build/check/run composition over accepted Phase 14-16 inputs. | No registry, default provider/model, global client, environment discovery, dynamic compatibility mode, automatic migration, broad framework bundle, or Chie application. |
| **17G - Visibility-safe inspection and verification** | Planned offline read-only inspection of one exact-current adapter profile and optional detached completed result with role-aware bounded evidence and independent verification. | No credential access, networking, provider observation, body loading, generated-code execution, authority evaluation, retry, replay, repair, recording, or certification. |
| **17H - Local external acceptance and checkpoint** | Planned root-owned deterministic loopback provider-protocol emulator proving Phase 17 boundaries and regressions, followed by one exact local checkpoint. | No real credential/account, public OpenAI endpoint, provider certification, live compatibility claim, production readiness, push, tag, release, Stable/1.0, Chie integration, or Phase 18 behavior. |

The order is fixed. Completion of one row does not authorize the next. Only a
separately authenticated directive plus accepted completion evidence may
change a row from Planned to Accepted. Phase 17H alone may authorize the Phase
17 checkpoint.

## Current and planned status

Phase 17A-G are Accepted. Phase 17H remains Planned and requires separately
authenticated architect authority; it alone may checkpoint Phase 17. Phase 18
is unauthorized and does not begin automatically.

## Consequences

Kongono has an explicit developer-facing boundary for a future first-party
Responses adapter without placing provider semantics in neutral packages or
duplicating accepted authority, transport, resolution, credential, and
lifecycle mechanisms. The cost is deliberate typed mapping work, fixture
maintenance, strict bounds, and explicit drift review for every supported
provider feature.

Local fixtures and later loopback acceptance can prove bounded mapping and
composition, but cannot certify a live provider, account entitlement, pricing,
retention, public DNS or TLS, compliance, deployment, performance, or
production readiness.

## Alternatives rejected

- Put Responses semantics into Phase 14 neutral contracts.
- Install the OpenAI SDK or a second HTTP stack as the default path.
- Advertise generic "OpenAI-compatible" behavior without separate evidence.
- Discover API keys, models, endpoints, organizations, or projects ambiently,
  or introduce a global provider client.
- Expose arbitrary raw option, header, or body bags.
- Add automatic retry, reconnect, provider/model fallback, or background
  polling.
- Treat structured output, tool calls, identifiers, usage, or provider
  acknowledgements as authority or truth.
- Connect Chie or an Agent directly to a live provider in Phase 17A.
- Implement a package or runtime in this documentation-only row.

## Prohibited scope

Phase 17A adds no implementation, fixture, dependency, schema, command,
credential, provider request, public endpoint access, tool execution,
application effect, background work, provider registry, default model,
production claim, checkpoint, release, Phase 17B, or Phase 18 behavior.

## Related material

- [OpenAI Responses provider adapter](../architecture/openai-responses-provider-adapter.md)
- [Phase 17 roadmap](../architecture/phase-17-roadmap.md)
- [Provider-neutral model invocation](../architecture/provider-neutral-model-invocation.md)
- [Model-invocation runtime](../architecture/model-invocation-runtime.md)
- [Model-invocation application workflow](../architecture/model-invocation-application-workflow.md)
- [Outbound HTTP and governed network egress](../architecture/outbound-http-and-network-egress.md)
- [External-service bindings and secret leases](../architecture/external-service-binding-and-secret-leases.md)
- [Phase 16 roadmap](../architecture/phase-16-roadmap.md)
- [Official Responses API reference](https://platform.openai.com/docs/api-reference/responses)
- [Official Responses streaming reference](https://platform.openai.com/docs/api-reference/responses-streaming)
- [Official function-calling guide](https://platform.openai.com/docs/guides/function-calling)
- [Official structured-outputs guide](https://platform.openai.com/docs/guides/structured-outputs)

The external links are volatile protocol evidence, not repository authority or
proof of current support. Phase 17B requires separate authenticated authority.
