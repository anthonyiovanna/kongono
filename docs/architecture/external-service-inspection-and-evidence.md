# External-Service Inspection and Evidence

## Purpose and context

The optional Experimental `kongono/external-service-inspection` package is the
Phase 16G evidence leaf above the accepted Phase 16 binding and application
packages and the accepted Phase 15 egress platform. It converts one explicitly
supplied exact-current Phase 16F profile, plus an optional detached completed
result, into deterministic visibility-safe evidence. It does not observe or
control a live application.

## Use when

Use inspection after trusted application code already owns one Phase 16F profile
and needs reproducible evidence about its current supplied binding. Add the
detached completed result when the caller also needs a bounded terminal summary
of the selected binding, operation and receipt relation, credential lifecycle,
service outcome, and nested egress evidence.

## Do not use when

Do not use this package to obtain credentials, touch credential material, resolve
an endpoint, perform DNS, socket, TLS, HTTP, provider, or account work, construct
a request, load generated PHP, start an application, invoke a runtime, evaluate
authority, record or export signals, retry or replay an operation, repair state,
probe a live service, or certify compatibility, compliance, erasure, or effect.

## Inputs and calling contract

`ExternalServiceInspectionRequest` accepts exactly:

1. one `ExternalServiceApplicationProfile`; and
2. zero or one detached completed `ExternalServiceApplicationResult`.

The profile must be exact-current relative to its own complete supplied binding
source, canonical binding artifact bytes, selected binding identity, and nested
network-egress profile. The optional result must already be terminal. No source,
lease, consumer, resolver, transport, authority evaluator, cancellation port,
clock, request factory, operation request, generated class, material value, or
application instance is accepted.

## Exact-current profile comparison

`ExternalServiceInspector` calls the Phase 16F public profile factory with only
the four accepted inputs. It independently compares the reconstructed and
supplied application identity; complete ordered binding source; artifact source
identity, source digest, artifact digest and canonical bytes; selected binding;
service profile; endpoint; credential source/reference; and exact ordered
required capabilities. It separately reconstructs and compares the nested Phase
15 profile, policy identity, source and artifact digests, capability projection,
and address, transport-security, redirect, proxy, cookie, and credential
postures. Any mismatch fails closed.

Endpoint and credential-reference values are compared through their package-owned
equality methods and are never rendered. Their published Protected projections
are domain-separated digests bound to the selected binding and the exact
content-addressed artifact. Generated PHP is neither input nor loaded.

## Optional completed-result evidence

For a supplied result, inspection requires the selected binding identity and the
external operation identity to match the nested receipt operation identity. Each
of the six Phase 15 outcomes maps to the same named external-service outcome.
Succeeded and before-effect results terminate as Retired; possible-effect and
uncertain-effect results remain Failed at the service layer. Failure presence and
the closed external failure kind must match the nested egress failure kind.

Credential evidence must be Succeeded and Retired with no failure, exactly one
acquisition, one material-use attempt, one retirement attempt, and completed
cleanup. Inspection does not acquire a lease or material and does not call a
consumer. Cleanup and retirement are bounded claims, not physical memory erasure.

The nested egress result must preserve success-versus-response and
non-success-versus-failure shape, receipt lifecycle, attempt, transfer,
cancellation, negotiated protocol and capability relations, and all policy
ceilings for connection attempts, resolution candidates, transfer bytes,
buffering, retained evidence, and elapsed time. A possible network or provider
effect remains uncertain. No result authorizes retry or replay.

## Output and deterministic evidence

`ExternalServiceInspection` emits schema
`kongono.external-service-inspection`, version 1. Canonical JSON is UTF-8 without
BOM, LF-only, has exactly one final LF and fixed key order, and includes a
lowercase SHA-256 identity over the payload excluding only the identity field.
The value contains only:

- Protected lowercase SHA-256 bindings for profile, source, artifact, selected
  binding, service profile, endpoint, credential source/reference, capabilities,
  nested profile/policy/source/artifact, and optional operation/receipt evidence;
- Safe bounded binding, capability, origin, budget, credential, transfer,
  cancellation, elapsed-time, resolution, progress, buffering, and retention
  counts;
- closed policy, service, credential, egress, lifecycle, transfer, cancellation,
  protocol, and failure-kind tokens; and
- booleans for optional-result, attempt, response, failure, credential-cleanup,
  and possible-effect uncertainty.

Raw identities, schemes, hosts, ports, origins, paths, endpoints, credential
references or material, provider/account labels, requests, responses, headers,
bodies, status/reason data, artifact/generated bytes, filesystem paths, foreign
messages, exception text, environment, and process data are absent.

## Graph, Observability, and Security Audit projections

The Graph projector accepts only an explicit `ApplicationGraphIntelligenceView`
for the same Chie, Hantei, or Oshie role. Absence is explicit. A matching view
copies only permitted artifact/Graph identities, fixed count vocabularies, Safe
aggregates, and fixed limitations. A role mismatch fails closed. Every projection
states `external-service-to-graph-relationship-not-established`.

The Observability projector constructs one
`kongono.external-service.inspection` operational event. The Security Audit
projector constructs one `kongono.external-service.inspection.security` Safe
evidence value. Neither projector receives or invokes a recorder, exporter,
clock, sampler, buffer, backend, retry, process, filesystem, credential, resolver,
DNS, or network mechanism. Construction proves no delivery, durability, tamper
evidence, compliance, authorization, authentication, security posture, external
state, or effect.

## Role-addressed evidence and verification

The closed role mapping is `chie -> explain`, `hantei -> verify`, and
`oshie -> teach`. These tokens select evidence purpose only; the package neither
depends on nor executes those roles. Every derived view says that role execution,
hidden-data access, credential access, network access, authority evaluation,
replay, repair, and certification were not performed and that authority remains
evidence-only.

The verifier independently reconstructs the permitted ordered shape. It accepts
exact evidence and returns a closed mismatch verdict for altered, reordered, or
expanded evidence without calling the deriver, repairing the candidate,
accessing hidden data, or certifying itself.

## Side effects, lifecycle, concurrency, and performance

Inspection is offline, read-only, stateless, and synchronous. Objects retain no
request or result across calls and have no start/stop lifecycle, cache, registry,
singleton, ambient state, background worker, filesystem, environment, time,
randomness, process, credential, resolver, DNS, or network side effect.
Independent immutable inputs may be inspected concurrently. Cost is linear in
the bounded binding, origin, capability, and Graph aggregate counts plus hashing
of already supplied canonical artifact bytes; no response body is consumed.

## Failure and security limitations

Diagnostics `KGN-EXTERNAL-SERVICE-INSPECTION-1628` through `1632` expose only one
closed Safe component token. Translated failures preserve the prior throwable
identity but never include its presentation. Native traces may retain ordinary
PHP arguments; no zeroization claim is made.

Currentness is caller-relative exact comparison only. Live application,
credential availability/validity/material/source state/physical erasure,
external authority, resolver/DNS/endpoint/route/transport/certificate/provider/
account state, and network/provider effects are not established. Detached
results and receipts are bounded claims rather than external truth. Digests are
comparison evidence, not secrets, authentication, authority, non-repudiation,
external truth, or proof of erasure/effect. Trusted generated and in-process PHP
is not a sandbox; generated PHP is not loaded.

## Stability and related concepts

The package and its consumer-facing declarations are Experimental. Diagnostic,
canonicalization, and base-exception implementation details are Internal. There
is no Public, Stable, 1.0, checkpoint, provider-compatibility, or production-
readiness claim. See [ADR 0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md),
the [external-service binding guide](external-service-binding-and-secret-leases.md),
the [Phase 16 roadmap](phase-16-roadmap.md), and the
[network-egress inspection guide](network-egress-inspection-and-evidence.md).

Phase 16H exercises this unchanged evidence leaf through root-owned local
external acceptance and closes Phase 16 at one exact local checkpoint. The
inspection remains offline and does not load generated PHP, retrieve
credentials, resolve or network, replay, repair, record, export, or certify.
See [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md) and
the [Phase 16 external-acceptance guide](phase-16-external-acceptance.md).
