# ADR 0177: Visibility-Safe OpenAI Responses Inspection and Bounded Evidence

- Status: Accepted
- Date: 2026-09-11
- Phase: 17G only

## Context

Phase 17F accepted an exact-current application profile over the Phase 14
provider-neutral invocation, Phase 16 external-service binding, and Phase 15
governed-egress boundaries. Developer intelligence needs a way to inspect that
composition without obtaining provider, credential, network, application, or
effect authority and without exposing model or transport content.

## Decision

Add the optional Experimental
`kongono/model-provider-openai-responses-inspection` upper evidence leaf. One
`OpenAiResponsesInspectionRequest` carries exactly one explicit
`OpenAiResponsesApplicationProfile` and one optional detached terminal
`InvocationRuntimeResult`.

The inspector recreates the application profile through the accepted Phase 17F
factory, compares the returned identity, and independently checks the public
provider, neutral invocation, plan artifact, adapter, external-service profile,
binding, mapping-version, result-mode, storage, structured-output, and function
definition relationships. Currentness is caller-relative only.

Optional runtime evidence must be retired and cleanup-complete. Receipt, result,
request, profile, mode, version, usage, cost, event identity, and event sequence
relationships are checked without reading text, structured values, action
arguments, event payloads, response identifiers, provider claims, bodies,
headers, endpoints, credentials, or foreign failure text. All accepted terminal
outcomes remain honest evidence; no absence or failure becomes success.

The canonical version-1 schema is `kongono.openai-responses-inspection`. Its
identity is lowercase SHA-256 over the exact canonical payload excluding only
the identity field. UTF-8 JSON uses fixed ordering, LF-only framing, and one
final LF. Output is limited to Protected digests, closed tokens, booleans, and
bounded Safe aggregates.

Chie, Hantei, and Oshie identifiers select explain, verify, and teach evidence
purposes only. The deriver executes no role. The verifier reconstructs the
permitted shape independently, does not call the deriver, and reports closed
matching or mismatch evidence without repair or candidate disclosure.

Graph projection is optional and same-role. It copies only contract-permitted
Graph identities, closed vocabulary counts, limitations, and Safe aggregates;
the OpenAI-to-Graph relationship remains not-established. Observability and
Security Audit projection only construct one neutral operational event and one
Safe audit value. Nothing records, exports, samples, flushes, persists, retries,
or calls a clock.

Diagnostics `KGN-OPENAI-RESPONSES-1724` through `1728` cover invalid inspection,
profile/binding mismatch, optional runtime mismatch, invalid intelligence or
verifier evidence, and projection failure. Context contains one closed Safe
component token, translations preserve prior throwable identity, and messages
omit foreign presentation.

## Security and lifecycle

The package is deterministic, offline, read-only, stateless, and retains no
cross-call state. It performs no credential lookup, DNS, socket, TLS, HTTP,
provider observation, body read, application or runtime execution,
generated-code loading, authority evaluation, replay, retry, repair, recording,
exporting, certification, filesystem, environment, process, time, or randomness
effect. Digests are comparison evidence, not secrets, authentication,
non-repudiation, compatibility, or external truth.

## Consequences

Applications gain bounded visibility without a parallel execution or monitoring
surface. The evidence cannot prove live provider, account, model, credential,
network, application, Graph, authorization, delivery, durability, compliance,
or security state. Phase 17H remains Planned and separately unauthorized.

## Related material

- [OpenAI Responses provider adapter](../architecture/openai-responses-provider-adapter.md)
- [OpenAI Responses inspection guide](../architecture/openai-responses-inspection-and-evidence.md)
- [Phase 17 roadmap](../architecture/phase-17-roadmap.md)
- [ADR 0176](0176-exact-current-openai-responses-application-workflow.md)
