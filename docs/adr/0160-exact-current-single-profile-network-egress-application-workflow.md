# ADR 0160: Exact-Current Single-Profile Network-Egress Application Workflow

- Status: Accepted
- Date: 2026-09-08
- Deciders: Kongono architecture
- Scope: Phase 15F only

## Context

Phase 15C supplies deterministic policy evidence and Phase 15D coordinates one
bounded operation through explicit caller-owned ports. Phase 15E supplies one
optional cURL transport implementation. None of those boundaries owns an
application profile, deterministic application wiring, or a persistent-runtime-safe
one-operation application lifecycle.

Mixing that outer workflow into the runtime would make policy validation,
composition, and loaded-code ownership indistinct from address resolution and
transport cleanup. Depending on the cURL adapter would also turn one optional
implementation into an ambient default.

## Decision

Accept the optional Experimental `kongono/network-egress-application` package as
an outer leaf over API Metadata, Diagnostics, Network Egress Contract, Network
Egress Policy Build, and Network Egress Runtime. It does not depend on
`kongono/network-egress-curl` or any third-party client, resolver, or provider SDK.

One profile factory accepts exactly one complete `EgressPolicySource` and its
exact artifact bytes. It delegates caller-relative currentness validation to the
Phase 15C validator and derives a lowercase SHA-256 profile identity from a
versioned LF-terminated canonical projection of policy identity, source and
artifact digests, the required transport-capability set, and the fixed Phase 15F
profile contract. The projection omits origins, request data, credentials,
addresses, paths, and foreign messages.

One generator emits strict, canonical, content-addressed PHP containing bounded
identity and digest evidence only. Structural validation is separate from
caller-relative exact-current validation. Publication uses one explicit existing
application root and one relative contained existing output directory; it is
atomic, initial-only, no-clobber, and idempotent only for identical bytes. Loading
snapshots and validates one explicit expected file, loads it once, and reverifies
its path, bytes, identity, and metadata. Generated code has no load-time effect
other than declaring one fixed class and grants no authority.

The one-use loaded handle starts an application only from the exact in-memory
profile and one directly supplied resolver and transport. Required transport
capabilities are checked before resolution or transfer. There is no resolver
identity, discovery mechanism, transport registry, or automatic cURL selection.

The application has exactly Ready, Active, and Retired states. One run input
carries one operation, receipt identity, attempt identity, authority-currentness
port, cancellation port, and monotonic clock. One run constructs one exact
`EgressRuntimeRequest`, delegates to a fresh `NetworkEgressRuntime`, returns its
exact terminal result, retires in `finally`, and clears retained resolver and
transport references. Reentrancy, overlap, replay, and use after retirement fail
before another resolver or transport effect. Phase 15D remains the sole owner of
opened-session cleanup and its uncertainty evidence.

Non-`Error` package lifecycle and operation failures use closed nondisclosing
diagnostics `KGN-NET-1540` through `KGN-NET-1548`, retaining exact previous
throwables where applicable. Engine `Error` identity remains unchanged.

## Consequences

Applications gain deterministic, inspectable composition without an ambient
client or registry. They remain responsible for current authority evidence,
request content, resolver and transport implementations, and any credentials.
The package performs no DNS, network, credential, provider, retry, redirect,
fallback, pooling, background, command, or deployment work.

Trusted generated and in-process PHP is not a sandbox. Exact-current profile and
wiring evidence does not prove live authority, DNS truth, transport compatibility,
delivery, remote effect, or external state.

Phase 15G inspection is implemented separately under ADR 0161 as an offline
upper evidence leaf. Phase 15H external acceptance/checkpoint remains Planned.
This decision makes no Stable, production-readiness, release, provider
compatibility, or public interoperability claim.

## Related material

- [Network-egress application workflow](../architecture/network-egress-application-workflow.md)
- [Outbound HTTP and network egress](../architecture/outbound-http-and-network-egress.md)
- [Network-egress policy artifact](../architecture/network-egress-policy-artifact.md)
- [Network-egress runtime](../architecture/network-egress-runtime.md)
- [Network-egress inspection and bounded evidence](../architecture/network-egress-inspection-and-evidence.md)
- [Phase 15 roadmap](../architecture/phase-15-roadmap.md)
