# ADR 0201: Neutral Cache Adapter and Bounded Memory Implementation

- Status: Accepted
- Date: 2026-09-16

## Context

Phases 20B-C define neutral cache evidence and deterministic cache-profile
artifacts, but they intentionally provide no cache adapter, backend, or runtime
operation. A replaceable protocol is needed before any runtime can use a
directly supplied implementation, and deterministic local development needs a
small bounded implementation without creating a production-provider promise.

## Decision

Phase 20D adds the separately optional Experimental
`kongono/cache-adapter-contract` package. Its Version 1 protocol accepts only
explicit validated requests and exposes lookup, absent-only store,
version-bound replace, and version-bound remove. A protocol `found` result
means only that detached entry evidence was returned; it is not the Phase 20E
runtime concept of a fresh hit. The adapter does not read a clock or evaluate
freshness.

Phase 20D also adds the separately optional Experimental
`kongono/cache-adapter-memory` package. One directly supplied adapter instance
owns a private finite map with explicit entry-count and aggregate-byte bounds.
It uses the fixed `oldest-insertion-first` policy. Lookup never changes order;
successful replacement becomes newest. Store and replacement create
deterministic instance-local versions only on success. Versions are conditional
mutation evidence for that adapter instance, not durable identities or global
ordering.

Each mutation constructs and validates a complete candidate state before one
state swap. Rejection, conflict, absence, and failure preserve the previously
visible state. Returned entry evidence is detached, so later replacement,
removal, or eviction cannot alter earlier results.

## Consequences

The memory adapter is process-local, volatile, isolated per instance, and
intended for deterministic tests and bounded local use. It has no durability,
distribution, shared-host, multi-process, crash-recovery, performance,
production-availability, or provider-compatibility claim. Cache loss, eviction,
conflict, rejection, and unavailability never grant authority or invent
success.

Applications or a future runtime must supply an adapter directly. The neutral
contract permits later separately optional Redis, Memcached, cloud, filesystem,
database, PSR-6, or PSR-16 implementations without changing cache-domain
meaning. This phase adds no serializer, codec execution, exact-current runtime,
application workflow, command, inspection, provider integration, retry, stale
fallback, lock, lease, queue, session, counter, or checkpoint. Phase 20E
remains separately Planned.
