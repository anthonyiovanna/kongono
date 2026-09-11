# Phase 17 Roadmap: First-Party OpenAI Responses Adapter

## Status and entry state

Phase 17A accepts [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md)
and the [provider-adapter guide](openai-responses-provider-adapter.md) as
documentation architecture only. Phase 16A-H remain Accepted, and Phase 16 is
closed at commit `3690aab42e3759d3f42e711703d4f23e6588fa85`.

Phase 17B accepts [ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md)
and the optional Experimental offline mapping package. It adds no SDK,
credential, endpoint, HTTP transport, network command, runtime adapter, Chie
integration, provider call, or live support. Phase 17C accepts
[ADR 0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md) as the
accepted one-shot non-streaming adapter boundary. Phase 17D accepts [ADR
0174](../adr/0174-pull-based-openai-responses-sse-streaming.md) and is Accepted.
Phase 17E accepts [ADR
0175](../adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md)
and is Accepted. Phase 17F accepts [ADR
0176](../adr/0176-exact-current-openai-responses-application-workflow.md) and is
Accepted. Phase 17G accepts [ADR
0177](../adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md)
and the optional Experimental offline inspection leaf. Phase 17H accepts [ADR
0178](../adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md)
and the root-owned local acceptance proof. Phase 17A-H are Accepted and Phase
17 is closed. Current topology is 89 packages / 375 local production edges /
acyclic; PHP inventories are 1,726 production / 894 package-test / 92 root-test.

## Fixed Phase 17 roadmap

| Row | Required boundary | Required exclusions |
| --- | --- | --- |
| **17A - Architecture and fixed roadmap** | Accepted documentation-only architecture for one optional first-party OpenAI Responses API adapter above the Phase 14-16 boundaries. | No PHP, package, API, schema, codec, adapter, SDK, credential, network, command, Chie integration, provider call, or runtime behavior. |
| **17B - Deterministic offline protocol mapping** | Accepted optional Experimental `kongono/model-provider-openai-responses` foundation with exact mapping version `kongono-openai-responses/1`, typed text-and-usage profile, bounded deterministic request encoding, strict duplicate-safe response decoding, synthetic fixtures, and nondisclosing diagnostics. | No network, credential access, SDK, runtime adapter session, streaming, tool execution, ambient endpoint/model, application workflow, or live support claim. |
| **17C - One-shot non-streaming invocation adapter** | Accepted `InvocationAdapter` implementation composing one exact Phase 14 source with one fresh exact-profile Phase 16 application operation and Phase 15 transport, then strictly closing and normalizing one terminal non-streaming response. | No streaming, automatic retry/fallback, retained session, provider SDK, ambient credential/model, background work, tool execution, Chie wiring, or live compatibility claim. |
| **17D - Pull-based Responses SSE streaming** | Accepted bounded incremental SSE/event mapping with consumer-driven pulls, closed order/completeness validation, uncertainty preservation, contiguous neutral emissions, and deterministic cleanup. | No background reader, reconnect, replay, unbounded buffer, realtime/WebSocket API, immediate first-token guarantee, audio, image, hosted tool, or retained conversation. |
| **17E - Structured output and inert action suggestions** | Accepted mapping Version 2 with explicit closed strict schemas, locally validated non-streaming structured results, and one inert function-call suggestion. | No function/tool execution, remote MCP, web/file search, computer use, code execution, second provider operation, application effect, schema invention, or model-output authority. |
| **17F - Exact-current application profile and supported workflow** | Accepted deterministic adapter-profile/currentness evidence and one explicit application-owned build/check/run composition over accepted Phase 14-16 inputs. | No registry, default provider/model, global client, environment discovery, dynamic compatibility mode, automatic migration, broad framework bundle, or Chie application. |
| **17G - Visibility-safe inspection and verification** | Accepted optional Experimental offline read-only inspection of one exact-current adapter profile and optional matching detached completed result with role-aware bounded evidence and independent verification. | No credential access, networking, provider observation, body loading, generated-code execution, authority evaluation, retry, replay, repair, recording, exporting, certification, or role execution. |
| **17H - Local external acceptance and checkpoint** | Accepted root-owned deterministic loopback provider-protocol emulator proving Phase 17 boundaries and regressions, followed by one exact local checkpoint. | No real credential/account, public OpenAI endpoint, provider certification, live compatibility claim, production readiness, push, tag, release, Stable/1.0, Chie integration, or Phase 18 behavior. |

The order is fixed. Completion of one row does not authorize the next. Only a
separately authenticated directive plus accepted completion evidence may
change a row's governed status.

## Dependency direction

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

Provider-specific semantics point inward to accepted neutral contracts; no
neutral lower package points outward to the adapter. Arrows express permitted
composition only after validation. They do not grant credential or network
access, provider permission, tool authority, application effect, or trust.

## Exit conditions

- **17A:** ADR 0171, the provider-adapter guide, and this fixed roadmap are
  Accepted with current documentation reconciled and no implementation change.
- **17B:** one separately authorized optional Experimental foundation proves an
  exact mapping version, typed profile, bounded non-streaming encoding and
  decoding, authenticated local fixtures, and nondisclosing diagnostics
  entirely offline.
- **17C:** one separately authorized adapter composes one exact Phase 14 source
  with one exact-current Phase 16 operation and Phase 15 transport for one
  terminal non-streaming result without retry, fallback, or retained state.
- **17D:** separately authorized pull-driven SSE mapping proves bounded order,
  completeness, cancellation, uncertainty, and deterministic cleanup without
  background reading, reconnect, or replay.
- **17E:** separately authorized structured-output and function-call mappings
  produce validated results and inert action suggestions without executing an
  effect or treating model output as authority.
- **17F:** separately authorized exact-current adapter-profile evidence and
  explicit application composition provide one supported build/check/run path
  without discovery, defaults, registries, or Chie application wiring.
- **17G:** separately authorized offline inspection and independent
  verification expose only bounded role-aware evidence without credentials,
  bodies, networking, replay, repair, recording, or certification.
- **17H:** a separately authorized root-owned loopback emulator proves the
  accepted Phase 17 boundaries and regressions; only that row may create one
  exact Phase 17 checkpoint.

Each exit requires its own authenticated directive and accepted completion
evidence.

## Evidence, authority, and provider drift

Plans, profiles, mappings, fixtures, generated artifacts, credentials,
provider identifiers, response status, usage, structured output, action
suggestions, receipts, Graph facts, inspection, and verifier results are
evidence, not authority. Credential access and network access are independent
current permissions. Tool and application effects remain separately admitted
and authorized.

The selected provider surface is exactly OpenAI's Responses API. No generic
"OpenAI-compatible" claim is made. Provider documentation, protocol behavior,
model availability, feature support, aliases, limits, pricing, retention,
account policy, and acknowledgements can drift. Later support claims therefore
bind to one explicit Kongono mapping version and authenticated local fixtures.
Unknown or unsupported drift fails closed rather than being forwarded or
silently normalized.

## Package and API-status direction

If separately authorized, the optional outer package is expected to be
`kongono/model-provider-openai-responses` unless a later row proves a narrower
split necessary. It may depend inward only on the minimum accepted API
Metadata, Diagnostics, HTTP message, Phase 14 invocation, Phase 15 egress, and
Phase 16 external-service packages needed for that row.

User-selected provider profile or factory surfaces begin Experimental. Codecs,
parsers, headers, event machines, mapping tables, and lifecycle helpers remain
Internal by default. No Public, Stable, 1.0, provider-certified,
production-ready, compatibility, performance, compliance, or cost-accuracy
claim is selected by this roadmap.

## Developer experience and optionality

The intended path uses one explicit Phase 14 plan, one Phase 16 service
binding and credential reference, one selected Phase 17 adapter profile,
accepted deterministic build/check/run composition, one operation-scoped call,
typed results, and actionable nondisclosing failures. It introduces no facade,
magic property, hidden autowiring, environment discovery, reflection scan,
provider registry, global client, default model, or mandatory bundle.

Applications not choosing OpenAI do not install this adapter. Applications
without AI do not install Phase 14 or Phase 17. Direct use of the neutral Phase
14 port remains supported.

## Deterministic testing expectations

Rows 17B-G use locally authenticated synthetic fixtures and deterministic fake
collaborators. Tests cover exact encoding, decoding, SSE order and completeness,
bounds, malformed and unsupported inputs, nondisclosure, cancellation,
uncertainty, reverse cleanup, operation isolation, and mapping-version drift.
They use no real credential, account, public endpoint, or live support claim.

Only Phase 17H may add a deterministic root-owned loopback provider-protocol
emulator. Local acceptance cannot certify public DNS or TLS, credentials,
provider behavior, model entitlement, retention, pricing, billing, compliance,
deployment, performance, or production readiness.

## Current and planned status

Phase 17A-H are Accepted. The implemented package has
one locally executable adapter using explicit Phase 14-16 inputs for exact text,
pull-based text SSE, non-streaming structured-result, or inert function-call
suggestion profiles. It has no SDK, ambient credential or endpoint, retry,
reconnect, replay, background reader, tool execution, second provider operation,
Chie connection, live provider proof, or live support claim. The separate
inspection leaf is offline, read-only, stateless, and visibility-safe; it reads
no hidden payload and performs no networking, provider observation, authority
evaluation, replay, repair, recording, exporting, certification, or role
execution. Phase 17H adds only root-owned local acceptance and the exact
checkpoint. Phase 17 is closed. Phase 18 does not begin automatically and
remains separately unauthorized.

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
- [OpenAI Responses provider adapter](openai-responses-provider-adapter.md)
- [OpenAI Responses inspection and evidence](openai-responses-inspection-and-evidence.md)
- [Phase 17 local acceptance](phase-17-openai-responses-local-acceptance.md)
- [Provider-neutral model invocation](provider-neutral-model-invocation.md)
- [Model-invocation runtime](model-invocation-runtime.md)
- [Model-invocation application workflow](model-invocation-application-workflow.md)
- [Outbound HTTP and governed network egress](outbound-http-and-network-egress.md)
- [External-service binding and secret leases](external-service-binding-and-secret-leases.md)
- [Phase 14 roadmap](phase-14-roadmap.md)
- [Phase 15 roadmap](phase-15-roadmap.md)
- [Phase 16 roadmap](phase-16-roadmap.md)

External provider documentation remains volatile evidence rather than
repository authority. Phase 18 remains unauthorized.
