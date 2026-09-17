# ADR 0198: Deterministic Cache Freshness, Atomicity, and Lifecycle

- Status: Accepted
- Date: 2026-09-15
- Phase: 20A

## Context

Cache behavior becomes ambiguous when a runtime reads an ambient clock, applies
a hidden default TTL, returns stale data after failure, exposes partially
written state, silently overwrites concurrent changes, or retains mutable
operation state. Those ambiguities are unsafe in persistent runtimes and make
tests depend on timing and provider accidents.

Phase 20A accepts the architecture for deterministic freshness, atomicity, and
lifecycle. It does not implement a clock, entry, adapter, runtime, mutation,
cleanup mechanism, or callable API.
Phase 20A is Accepted; Phases 20B-H remain Planned.

## Decision

### Explicit supplied time and finite freshness

Every future operation or explicitly bounded operation scope receives one
caller-supplied immutable `now` value. The runtime does not read the system
clock, environment, request singleton, static state, or adapter clock
implicitly. Freshness policy defines a bounded finite lifetime and the exact
comparison at the expiry boundary.

There is no default forever entry, hidden sliding expiry, implicit TTL,
newest-entry selection, background refresh, stale-while-revalidate,
stale-if-error, or development-only stale fallback. Any later stale policy
must be separately named and accepted at an application boundary and cannot be
reported as a fresh hit.

### Typed outcomes and failures

Fresh hit, miss, expired, conditional conflict, rejected, and unavailable are
distinct typed outcomes. Miss and expiry are expected application outcomes,
not exceptions, and neither is disguised as `null` when `null` could be valid
application data.

Invalid caller input, violated package invariants, malformed or incomplete
adapter output, exceeded bounds, and uncertain backend effects use explicit
failures. An unavailable backend or uncertain mutation is never reported as
success. A failure cannot silently return an older entry.

### Atomic visibility and conditional mutation

Future adapters never expose partial entry state. Reads return detached
immutable values. Writes become visible atomically or fail. Conditional
replace and remove operations bind to explicit accepted version or mutation
evidence, so overlapping operations cannot silently overwrite or remove a
newer value.

Atomic cache entry visibility does not make the cache a transaction manager or
distributed coordination service. Locks, leases, leader election, semaphores,
queues, schedulers, durable jobs, rate limiting, counters, and exactly-once
claims remain excluded. Cache stampede policy stays application-owned until a
separately accepted coordination capability exists.

### Operation isolation and deterministic retirement

Mutable state is local to one operation. Sequential, overlapping, reentrant,
and persistent-runtime calls do not share selected keys, time, policy,
provider state, partial bytes, outcomes, or diagnostics. Every success and
failure path attempts required retirement and cleanup deterministically.

Results are complete, detached, and immutable. They retain no adapter, clock,
callback, stream, connection, mutable buffer, credential, application object,
or operation-local cleanup handle. Cleanup does not depend on a destructor,
shutdown hook, background worker, or later request.

Primary and cleanup truth are preserved. Cleanup failure does not erase the
primary failure, and a primary success with incomplete required cleanup is not
silently presented as an unqualified success.

### Bounded evidence and nondisclosing diagnostics

Future operations define finite bounds for namespaces, logical keys, value
bytes, metadata, entry counts, total capacity, lifetimes, operation counts,
provider results, duration, and diagnostic fields before an effect. Adapter
output remains untrusted until complete validation succeeds.

Diagnostics state the operation category, closed reason, violated invariant,
and actionable remediation. They may include bounded Safe counts or Protected
digests. They omit raw keys, cached bytes, encoded values, credentials,
secrets, tokens, backend addresses, connection data, application paths,
arbitrary provider output, and unbounded content.

## Consequences

- Freshness tests use explicit values rather than sleeping or changing a
  process-global clock.
- A caller can distinguish expected cache absence from malformed behavior or
  backend failure.
- Conditional mutations surface conflicts instead of silently losing updates.
- Persistent runtimes can reuse immutable configuration without retaining
  operation state.
- Providers must supply stronger complete evidence and cleanup semantics than
  a minimal nullable get/set interface.

## Alternatives and tradeoffs

Reading the system clock was rejected because it introduces ambient mutable
input. Hidden default TTLs and forever entries were rejected because freshness
would depend on undocumented policy. Implicit stale fallback was rejected
because old evidence could masquerade as current. Unconditional overlapping
writes were rejected because they hide lost updates. Cache-based locks and
leases were rejected because entry atomicity does not establish coordination,
durability, or exactly-once semantics.

## Deferred and excluded scope

Phase 20A chooses no timestamp representation, numeric limits, eviction
algorithm implementation, adapter method signature, backend protocol, retry
policy, stale policy, coordination primitive, callback API, runtime command,
or production provider. Phases 20B-H remain Planned.

## Related authorities

- [ADR 0197](0197-explicit-derived-data-cache-and-non-authority-boundary.md)
- [Cache Platform](../architecture/cache-platform.md)
- [Phase 20 roadmap](../architecture/phase-20-roadmap.md)
- [Cache authoring](../engineering/cache-authoring.md)
- [Persistent-runtime doctrine](0006-persistent-runtime-first.md)
- [Actionable diagnostics](0026-actionable-diagnostics-and-error-boundaries.md)
- [Bounded diagnostic disclosure](0028-bounded-diagnostic-context-and-disclosure.md)
