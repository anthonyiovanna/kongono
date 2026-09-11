# OpenAI Responses Inspection and Evidence

## Purpose and context

Phase 17G provides optional visibility over one explicit Phase 17F application
profile. It sits above provider-specific mapping and the neutral invocation,
external-service, Graph, Observability, and Security Audit contracts. It is an
evidence transformer, not a provider client, runtime, monitor, or authority
boundary.

## Use when

Use the package to derive deterministic Safe aggregate evidence for an
application-owned developer surface after the caller already possesses one
accepted OpenAI Responses application profile. An optional detached completed
runtime result can be checked for attribution to that exact neutral request and
profile.

Do not use it for endpoint discovery, account or entitlement validation,
credential access, live compatibility testing, billing truth, authorization,
application execution, result presentation, replay, repair, monitoring,
certification, or compliance claims.

## Input and calling contract

The request contains exactly one `OpenAiResponsesApplicationProfile` and an
optional `InvocationRuntimeResult`. The profile must be reconstructible through
the Phase 17F factory from its own explicit provider, model-invocation, and
external-service components. Optional runtime evidence must already be terminal,
retired, and cleanup-complete and must bind to the exact request and neutral
profile.

## Output and canonical evidence

`OpenAiResponsesInspection` emits schema
`kongono.openai-responses-inspection`, version 1. Fixed-order UTF-8 JSON is
LF-only with one final LF. Its identity is lowercase SHA-256 of the exact
payload excluding only the identity field.

Permitted evidence comprises Protected digests for application, neutral
application, plan, request, external-service binding, receipt, and result
relationships; closed mapping, mode, storage, streaming, outcome, lifecycle,
finish, and posture tokens; presence booleans; and bounded message, capability,
usage, event, attempt, elapsed-time, and retained-byte counts. Raw identities,
names, schemas, prompts, messages, results, action arguments, usage labels,
provider claims, endpoints, network material, credentials, paths, generated
PHP, bodies, headers, and foreign messages are omitted.

Currentness is caller-relative. Live application, provider, network,
credential, account, and model state is not observed. Detached runtime evidence
is a bounded claim, no Graph relationship is established, no authority is
evaluated, no role is executed, and no replay, repair, recording, exporting, or
certification occurs.

## Optional runtime evidence

The inspector compares receipt invocation and profile evidence, result identity,
mode and version, usage and cost presence, finish reason, event identities and
sequences, lifecycle state, attempt count, elapsed time, and retained evidence.
It never calls result text, structured-value, action-argument, or event-payload
accessors. Succeeded, failed, cancelled, and rejected outcomes remain distinct.

## Graph, observation, and audit projections

Graph input is optional. Absence is explicit; a role mismatch fails closed.
Only Graph/artifact identities, fixed type counts, Safe totals, and the accepted
limitations cross the boundary. `openai-responses-to-graph-relationship` remains
not-established.

The observation projector constructs
`kongono.openai-responses.inspection`. The Security Audit projector constructs
`kongono.openai-responses.inspection.security` Safe evidence. Neither records,
exports, samples, flushes, persists, retries, or proves delivery, durability,
tamper evidence, authorization, security, compliance, or external truth.

## Developer roles and verification

`chie`, `hantei`, and `oshie` map exactly to `explain`, `verify`, and `teach`.
They address evidence purposes only and do not import, invoke, impersonate, or
depend on any role implementation. Every view records that hidden-data access,
role execution, networking, authority evaluation, replay, repair, recording,
exporting, certification, and provider observation were not performed.

The independent verifier reconstructs the exact expected role, inspection,
Graph, limitation, and serialization shape without calling the deriver. It does
not normalize or repair candidates and returns only matching or mismatch tokens.

## Side effects, lifecycle, concurrency, and performance

There are no filesystem, environment, process, DNS, socket, TLS, HTTP,
credential, provider, application, runtime, recorder, exporter, time, or random
side effects. The package has no start/stop lifecycle and retains nothing across
calls. Immutable values and stateless services permit nested and concurrent use.
Work is bounded by accepted request, event, usage, and Graph collection limits;
canonicalization and digest comparison are linear in bounded evidence size.

## Failure and stability

Invalid profiles, mixed bindings, mismatched runtime evidence, role or Graph
mismatch, altered intelligence, and projection construction fail closed through
diagnostics `KGN-OPENAI-RESPONSES-1724` through `1728`. Diagnostics expose only
one closed Safe component token and preserve a translated prior cause.

Consumer-facing types are Experimental; canonical and diagnostic helpers are
Internal. The separate Phase 17H checkpoint accepts only root-owned local
composition evidence. There is no live OpenAI proof, provider certification,
compatibility guarantee, Stable or 1.0 claim, or production-readiness claim.

- [ADR 0177](../adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md)
- [Provider adapter guide](openai-responses-provider-adapter.md)
- [Phase 17 roadmap](phase-17-roadmap.md)
- [Phase 17 local acceptance](phase-17-openai-responses-local-acceptance.md)
