# ADR 0158: Bounded Provider-Neutral Network-Egress Runtime

- Status: Accepted
- Date: 2026-09-07
- Phase: 15D

## Context

ADR 0155 separates application-owned outbound authority, policy, resolution,
transport, transfer evidence, and external effects. ADR 0156 supplies inert
provider-neutral operation and receipt values. ADR 0157 supplies exact-current,
deterministic policy artifacts. None of those boundaries coordinates an actual
attempt.

Applications need an optional coordinator that consumes those accepted values
without turning Kongono into a DNS implementation, HTTP client provider,
credential source, ambient transport registry, or external authority. The
coordinator must fail closed against rebinding, mixed resolution results,
capability drift, unbounded transfer evidence, cancellation, lifecycle
uncertainty, and incomplete cleanup.

## Decision

Accept the optional Experimental `kongono/network-egress-runtime` package as a
single-operation, synchronous, pull-based coordinator.

One immutable request binds an exact Phase 15B operation, exact Phase 15C policy
source and canonical artifact bytes, caller-supplied receipt and attempt
identities, and five explicit caller-owned collaborators: authority
currentness, resolver, transport, cancellation, and monotonic clock. There are
no global or default implementations.

Before resolution or transport activity, the runtime validates the exact-current
artifact and the operation's policy reference, budget, allowed origin, headers,
cookie and credential posture, and proxy posture. It requires current authority
before resolution and again immediately before transport admission.

The resolver is called once. Its result contains at most 64 unique canonical IP
literals and one exact selected member. Address classification is offline and
conservative. Public-only policy admits only globally routable candidates;
explicit-private policy additionally admits private and loopback candidates.
Any link-local, multicast, unspecified, documentation, reserved, or mixed
forbidden result denies the whole resolution. A literal operation origin must
equal the selected address.

The directly supplied transport reports a stable capability set, including an
HTTP protocol capability and TLS for HTTPS. It receives the exact selected
address and must preserve the canonical origin for HTTP authority and TLS
identity. The runtime opens at most one session and does not resolve again,
redirect, retry, race, hedge, fall back, or select another transport.

Each pull returns cumulative transfer evidence or `null`. The runtime enforces
state and byte monotonicity, negotiated-protocol stability, request and response
bytes, header counts and bytes, buffered bytes, retained evidence, candidate
count, 4,096 emissions, connect timeout, first-byte timeout, total timeout, and
monotonic time. A terminal emission must be followed by exactly one trailing
`null`. Limit-plus-one is a visible typed failure.

Cancellation is observed throughout admission and the pull lifecycle. Before
request transfer it produces `CancelledBeforeEffect`; after any request byte it
produces `CancelledAfterPossibleEffect`. Observed transfer evidence is retained
and never authorizes replay.

Every opened session receives exactly one close attempt. Expected denial,
resolution, transport, budget, timeout, protocol, and cancellation outcomes
return typed results. Unexpected collaborator failures preserve the original
throwable behind bounded exceptions. Cleanup-only and combined failures remain
separately inspectable. PHP engine `Error` remains unwrapped while cleanup is
still attempted. Reentrancy is rejected, active state clears in `finally`, and
completed operation state is not retained.

The coordinator never reads, seeks, rewinds, stringifies, copies, hashes, or
buffers request or response body streams. Diagnostics use the exact internal
range `KGN-EGRESS-RUNTIME-1520` through `KGN-EGRESS-RUNTIME-1529` and admit only
closed safe context. Origins, addresses, identities, digests, headers,
credentials, bodies, paths, and foreign exception presentation are omitted.

The Phase 15B receipt relation is narrowly evolved so `FailedBeforeEffect` and
`CancelledBeforeEffect` may retain either no attempt or one to sixteen attempt
identities while transfer remains `None`. `RejectedBeforeAttempt` remains
strictly attempt-free. All possible-effect and success relations remain
unchanged.

## Consequences

Applications can coordinate one governed outbound attempt while retaining
ownership of authority and every effect-capable implementation. Exact selected
address binding and the second authority probe make resolver-to-transport
rebinding visible and fail closed. Finite budgets and pull-based evidence bound
runtime observations without consuming bodies.

The package is not a PSR-18 client or control plane. It provides no DNS, socket,
TLS, credential, proxy discovery, cookies, concrete transport, provider SDK,
registry, command, application workflow, telemetry, persistence, background
worker, or proof of remote effect. Receipts and results remain claims rather
than authority or external truth.

The production graph becomes exactly 78 acyclic packages and 298 local
production edges. PHP inventories become 1,439 production, 730 package-test,
and 80 root-test files.

## Rejected alternatives

- A built-in HTTP client or DNS resolver would select a provider and enlarge
  dependency, network, credential, and compatibility boundaries.
- Validation resolution followed by transport-owned resolution would reopen
  rebinding and time-of-check/time-of-use ambiguity.
- Automatic redirect, retry, fallback, racing, or hedging would create new
  authority and effect semantics not admitted by Phase 15D.
- Reading bodies to meter them would violate PSR-7 stream ownership and make
  lifecycle, memory, and disclosure costs ambient.
- A global transport registry, proxy discovery, or credential lookup would hide
  application-owned authority and state.

## Status and follow-up

The marker exception interface is Public. Runtime collaborators, values,
results, coordinator, and concrete exceptions are Experimental. Diagnostics,
classifiers, guards, meters, receipt construction, and lifecycle composition
are Internal. Nothing is Stable.

Phase 15E through Phase 15G are implemented through separate accepted
decisions. Phase 15G adds only offline bounded inspection under ADR 0161;
Phase 15H remains Planned and requires separately authenticated architect
authority. This decision itself authorizes no concrete transport, provider
adapter, application workflow, inspection behavior, external acceptance
checkpoint, push, tag, or release.

## Related material

- [ADR 0155](0155-explicit-outbound-http-and-governed-network-egress-boundary.md)
- [ADR 0156](0156-neutral-versioned-network-egress-contract-foundation.md)
- [ADR 0157](0157-deterministic-network-egress-policy-artifact.md)
- [ADR 0161](0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
- [Network-egress runtime](../architecture/network-egress-runtime.md)
- [Phase 15 roadmap](../architecture/phase-15-roadmap.md)
