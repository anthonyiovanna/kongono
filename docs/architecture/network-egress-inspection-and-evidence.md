# Network-Egress Inspection and Bounded Evidence

## Purpose and context

Phase 15G implements the optional Experimental
`kongono/network-egress-inspection` upper evidence leaf. It makes one exact
supplied Phase 15F application profile and generated-wiring value inspectable.
It may also summarize one detached Phase 15D runtime result when the caller
supplies the exact corresponding operation.

Use it for deterministic aggregate inspection after application-owned
evidence already exists. Do not use it to discover policy, select a resolver
or transport, obtain credentials, load wiring, start an application, run or
replay an operation, probe an endpoint, observe live state, authorize work,
monitor a service, record telemetry, certify correctness, or repair anything.

## Input and calling contract

`NetworkEgressInspectionRequest` accepts exactly one typed
`NetworkEgressApplicationProfile`, one
`GeneratedNetworkEgressWiring`, and an optional pair of one
`EgressOperation` with one detached `EgressRuntimeResult`. The optional
values must be supplied together. Paths, directories, streams, callables,
loaded generated classes, resolver or transport ports, authority sources,
recorders, registries, containers, environment values, and currentness
services cannot enter the request.

`NetworkEgressInspector` first invokes Phase 15F structural and
caller-relative exact-current validation over the supplied wiring's canonical
bytes. It then independently compares every public profile, policy, source,
artifact, required-capability, generator implementation, namespace, class,
canonical-byte, and wiring-digest binding. It never loads or invokes generated
PHP.

## Optional runtime evidence and uncertainty

When present, the operation and runtime result must bind exactly:

- the operation policy reference equals the validated artifact reference;
- the origin is one of the source's exact allowed origins;
- the operation budget equals the source budget;
- the receipt operation identity equals the operation identity;
- the outcome, terminal lifecycle, attempt relation, transfer, cancellation,
  negotiated protocol, capability evidence, presence flags, and bounded
  counters are coherent; and
- the returned detached result is cleanup-complete.

All accepted terminal outcomes are preserved exactly. In particular,
`CancelledAfterPossibleEffect` and `UncertainEffect` remain uncertain
evidence. Inspection does not infer success or failure, authorize retry or
replay, or establish what happened outside the process. It does not inspect
the request body, headers, serialization, response, or failure object.

## Deterministic canonical output

`NetworkEgressInspection` uses schema
`kongono.network-egress-inspection`, version 1. Canonical JSON has fixed key
order, UTF-8 without BOM, LF-only framing, and one final LF. The lowercase
SHA-256 identity covers the exact canonical payload excluding only the
identity field.

The output contains only Protected lowercase SHA-256 bindings, Safe bounded
counts, closed policy-posture and optional terminal runtime tokens, boolean
presence and cleanup flags, and these fixed limitation meanings:

- scope: `network-egress-v1`;
- disclosure: `safe-aggregate-only`;
- supplied profile/wiring currentness:
  `caller-relative-exact-comparison-only`;
- live application state: `not-observed`;
- external authority currentness: `not-established`;
- DNS, endpoint, route, transport, certificate, provider, and account state:
  `not-observed`;
- receipt/result evidence: `bounded-claim-not-external-truth`;
- network effect: `not-established`;
- replay and repair: `not-performed`;
- network-egress-to-Graph relationship: `not-established`;
- trusted generated/in-process PHP: `not-sandboxed`;
- generated PHP loading: `not-performed`; and
- authority: `evidence-only`.

Raw policy, operation, receipt, and result identifiers; origins; hosts; ports;
URIs; IP addresses; methods; headers; bodies; credentials; tokens; cookies; CA
material; response or failure objects; status or reason text; caller labels;
paths; generated PHP; and foreign messages are omitted from serializations,
diagnostics, exceptions, views, and projections.

## Graph, Observability, and Security Audit projections

`NetworkEgressGraphEvidenceProjector` accepts an explicit optional already
validated Phase 11G `ApplicationGraphIntelligenceView` for the same role. It
copies only permitted Graph/artifact identities, contract version, fixed
vocabulary counts, limitations, and Safe aggregates. Absence is explicit. No
Graph is read, and no relationship, live currentness, authorization, or
executability is inferred.

`NetworkEgressObservationProjector` constructs a neutral
`kongono.network-egress.inspection` operational event.
`NetworkEgressSecurityAuditProjector` constructs a
`kongono.network-egress.inspection.security` Safe audit evidence value.
Neither projector records, exports, samples, buffers, flushes, stores, times,
retries, or delivers its result. Successful construction is not a delivery,
durability, tamper-evidence, compliance, security, endpoint-state, or
external-effect claim.

## Developer roles and independent verification

`NetworkEgressInspectionDeveloperRole` maps Chie to explain, Hantei to
verify, and Oshie to teach. The enum selects an evidence purpose only and does
not depend on, import, invoke, or impersonate those developer companions.

`NetworkEgressIntelligenceDeriver` maps one inspection and explicit role to
fixed ordered evidence groups and bounded role-appropriate statements. Views
state that role execution, network access, authority evaluation, repair,
replay, and certification were not performed; hidden-data access is none and
authority is evidence-only.

`NetworkEgressIntelligenceVerifier` independently reconstructs and compares
the permitted shape. It never calls the deriver, repairs or normalizes a
candidate, accesses hidden data, executes a role, or self-certifies. PASS is
bounded comparison evidence, not Hantei execution, authorization, approval,
external truth, or proof of effect.

## Side effects, lifecycle, concurrency, and performance

Inspection and projection perform no filesystem, environment, clock,
randomness, process, DNS, socket, TLS, HTTP, resolver, transport, application,
runtime, authority, credential, recording, or network operation. Production
objects are immutable and services retain no cross-call state. There is no
start/stop lifecycle or cleanup ownership.

Equal explicit inputs produce equal bytes under repeated, nested, concurrent,
and interleaved calls. Work is bounded by already finite lower-layer
collections and counters. There is no I/O, polling, background work, retained
session, or unbounded accumulation.

## Failure and security

Typed operational failures use diagnostics `KGN-NET-1549` through
`KGN-NET-1553` and one closed Safe component token. Foreign messages and
arbitrary caller data are not copied. A translated lower failure retains the
prior throwable identity without claiming native trace argument erasure.

Digests are comparison evidence, not secrets, authentication, authorization,
non-repudiation, current external authority, or external truth. Exact supplied
profile/wiring evidence does not establish live application, endpoint, route,
DNS, certificate, transport, provider, account, or deployment state. Trusted
generated and in-process PHP remains unsandboxed even though inspection never
loads generated PHP.

The package is optional and no existing production package depends on it.
Kongono remains operable without the package, a first-party resolver or
credential provider, provider adapter, live inspection probe, public
compatibility certification, Stable/1.0 API, or network. Phase 15H adds only
bounded local external acceptance and the exact local checkpoint; Phase 15 is
closed without a production-readiness claim.

## Related material

- [ADR 0161](../adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
- [Network-Egress Application Workflow](network-egress-application-workflow.md)
- [Network-Egress Runtime](network-egress-runtime.md)
- [Network-Egress Policy Artifact](network-egress-policy-artifact.md)
- [Application Graph Developer Intelligence](application-graph-developer-intelligence.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Phase 15 Roadmap](phase-15-roadmap.md)
