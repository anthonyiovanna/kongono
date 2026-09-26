# Derived-Data Cache Platform

## Status

Phase 20A is Accepted documentation architecture through
[ADR 0197](../adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md)
and
[ADR 0198](../adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md).
Phase 20B accepts
[ADR 0199](../adr/0199-neutral-cache-contract-and-bounded-evidence-vocabulary.md)
and the optional Experimental `kongono/cache-contract` leaf. Phase 20C accepts
[ADR 0200](../adr/0200-deterministic-cache-profile-artifact-and-publication.md)
and the optional Experimental `kongono/cache-build` leaf. Phase 20D accepts
[ADR 0201](../adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md),
the optional Experimental `kongono/cache-adapter-contract`, and the separately
optional Experimental `kongono/cache-adapter-memory`. Phase 20E accepts [ADR 0202](../adr/0202-exact-current-cache-runtime-and-operation-lifecycle.md)
and `kongono/cache-runtime`. Phase 20F accepts
[ADR 0203](../adr/0203-supported-cache-application-workflow-and-explicit-codec-policy-mapping.md),
`kongono/cache-application`, three profile-evidence commands, and the copy-once
`cache-minimal` slice. Phase 20G accepts
[ADR 0204](../adr/0204-visibility-safe-cache-inspection-and-developer-intelligence.md),
the optional Experimental `kongono/cache-inspection` leaf, and the
[cache inspection guide](cache-inspection-and-intelligence.md). Phase 20H
accepts [ADR 0205](../adr/0205-phase-20-external-cache-acceptance-and-checkpoint.md)
and the root-owned [external acceptance](phase-20-external-cache-acceptance.md).
Phases 20A-H are Accepted and Phase 20 is closed.
Current capabilities include bounded
neutral values plus explicit profiles, canonical inert artifacts, strict
structural/exact-current validation, and contained content-addressed
publication, a neutral adapter protocol, and one bounded deterministic
process-local implementation, exact-current runtime operations, explicit
application profile workflow, and supplied-evidence inspection. No provider
integration, live backend observation, or production availability
exists.

## Accepted neutral contract

The Phase 20B leaf distinguishes application-owned namespace and logical key,
domain-separated key identity, inert codec name/version, opaque bytes, content
identity, caller-supplied microsecond instants, finite lifetime and freshness,
entry version, operation/outcome vocabulary, and detached entry evidence.
Empty bytes remain a value rather than a miss. Exact expiry is expired. Entry
evidence proves only internal structural consistency; it does not prove source
currentness, provider truth, authority, or an external effect.

## What this capability is

The Cache Platform is an optional upper application capability for
bounded, derived, disposable data that can be recomputed from authoritative
sources. It gives future applications an explicit provider-neutral contract
for identities, opaque bytes, freshness, typed outcomes, conditional mutation,
and lifecycle.

It is not persistence, a database, a source of truth, a credential store, a
session store, an authorization service, an audit record, a durable queue, a
lock manager, a rate limiter, or proof of an external effect. Applications
remain correct if the cache is absent, flushed, evicted, expired, rejected, or
unavailable. Only performance or availability may degrade.

## Explicit data and identity flow

The planned flow keeps every domain visible:

1. Application code selects an application value and owns its validation.
2. An explicitly selected application codec and schema/version encode it into
   bounded opaque bytes.
3. Content identity is derived from those exact bytes.
4. An application-owned cache namespace and logical key select an entry
   without becoming a provider address or content digest.
5. Explicit freshness policy and caller-supplied time accompany the operation.
6. A directly supplied matching adapter receives only the bounded neutral
   operation and produces complete untrusted evidence.
7. The runtime validates that evidence and returns a typed, immutable,
   detached outcome.
8. Application code validates and decodes hit bytes under its own codec and
   schema policy.

Logical key, key identity, backend address, codec identity, value bytes,
content identity, entry version, freshness evidence, provider identity, and
operation identity are never interchangeable. A hit is evidence about one
cache entry; it is not source currentness or authority.

## Accepted and planned package direction

Phase 20B accepts the optional Experimental `kongono/cache-contract` neutral
leaf. Phase 20C accepts `kongono/cache-build` for explicit profiles and inert
canonical currentness evidence. Phase 20D accepts a neutral adapter boundary
and a separately optional bounded in-memory adapter. Protocol `found` remains
distinct from a runtime fresh hit. Phase 20E accepts the exact-current
operation runtime. Phase 20F accepts application composition and explicit
codec/policy mapping. Phase 20G accepts offline supplied-evidence inspection
with optional detached aggregate runtime evidence. Phase 20H accepts only
root-owned fresh-process local acceptance and the exact checkpoint.

Dependencies point inward. Future cache packages may use neutral API metadata
and diagnostic foundations. Kernel, routing, HTTP, database, persistence,
security, Graph, agents, views, assets, and other lower packages do not depend
on caching. Applications supply adapters directly; no facade, singleton,
global registry, or service locator appears.

## Freshness and explicit time

Freshness is finite and closed. A future operation receives one immutable
caller-supplied `now` value and an explicit lifetime policy with a defined
boundary comparison. The runtime does not read an ambient clock.

There is no implicit TTL, default forever entry, sliding expiry, newest-entry
selection, hidden refresh, stale-while-revalidate, stale-if-error, or
development fallback. A separately accepted application stale policy could
exist later, but stale evidence can never masquerade as a fresh hit.

## Expected outcomes and failures

Fresh hit, miss, expired, conditional conflict, rejected, and unavailable are
separate planned outcomes. Miss and expiry are ordinary control flow rather
than exceptions. An ambiguous nullable get is insufficient because `null` may
be valid application data.

Invalid input, violated invariants, malformed or incomplete adapter evidence,
exceeded bounds, and uncertain effects are failures. Unavailability or an
uncertain mutation never becomes success, retry, or stale fallback
implicitly.

## Atomic operations and lifecycle

A future adapter exposes no partial entry. Reads return detached immutable
bytes and evidence. Writes become visible atomically or fail. Conditional
replace and remove operations bind to explicit entry-version evidence so an
overlapping operation cannot silently overwrite or remove a newer value.

Atomic entry visibility does not authorize locks, leases, queues, counters,
leader election, semaphores, schedulers, durable jobs, rate limits, or
exactly-once claims. Stampede handling remains explicit application policy.

Mutable state belongs to one operation and is retired on every path.
Sequential, overlapping, reentrant, and persistent-runtime calls do not leak
keys, time, policy, partial bytes, adapter state, results, or diagnostics.
Detached results retain no adapter, clock, callback, connection, stream,
credential, mutable buffer, or application object.

## First adapter and future providers

The first planned adapter is a bounded in-memory implementation with atomic
visibility, explicit versions, finite capacity, deterministic eviction policy,
and operation isolation. It is intended for process-local caching and
deterministic tests. It is not durable, distributed, shared-host,
multi-process, or a production availability claim.

Redis, Memcached, filesystem, database, cloud, PSR-6, PSR-16, and vendor
providers are deferred. A future provider is replaceable only by preserving
the neutral identities, time, outcomes, uncertainty, bounds, validation, and
lifecycle rules. Provider output remains untrusted.

## Security, nondisclosure, and bounds

Cache evidence grants no identity, authentication, authorization, capability,
credential, policy, storage, network, process, Graph, or effect authority.
Credentials, secrets, grants, sessions, CSRF tokens, unredacted sensitive
data, durable records, and authoritative decisions do not belong in cache
entries.

Every namespace, key, value, metadata item, list, entry count, total byte
count, lifetime, operation count, provider result, duration, and diagnostic
field has a finite bound before an effect. Diagnostics can carry closed
reasons, bounded counts, and Protected digests, but never raw keys, cached
values, credentials, backend addresses, arbitrary provider output, or
unbounded data.

## Relationship to other capabilities

- Storage may retain authoritative and irreplaceable objects; cache retains
  derived disposable evidence. A storage miss, uncertain mutation, version,
  durability claim, or provider acknowledgement cannot be interpreted through
  cache expiry, eviction, stale fallback, or best-effort semantics. See the
  [Storage Platform](storage-platform.md).
- Database and persistence remain authoritative record boundaries; cached
  projections never replace their current reads or transactions.
- HTTP cache-control and browser cache metadata are delivery policy, not this
  server-side derived-data capability.
- Views and assets may consume explicit application values, but gain no
  ambient cache helper or dependency.
- Model calls, agents, and external effects are not silently read through or
  replayed from cache; cached evidence grants no action authority.
- Authentication, authorization, sessions, credentials, and security policy
  remain outside cached data.
- Observability may describe bounded operation evidence only after separately
  accepted integration and must not expose keys or values.
- Graph, Chie, Hantei, and Oshie receive only the offline bounded Safe or
  Protected projections accepted by Phase 20G.

## Developer-friendly principles

The future surface is small and explicit: choose a namespace, logical key,
codec, schema version, finite freshness policy, caller-supplied time, and
direct adapter. Handle fresh hit, miss, expiry, conflict, rejection, and
unavailability visibly. Validate and decode bytes outside the neutral contract.
Test cleanup and operation isolation without sleeping or relying on global
state.

These are architectural principles, not current callable examples. The
compact planned workflow is documented in
[Cache authoring](../engineering/cache-authoring.md).

## Phase 20A non-claims

Phase 20A does not implement cache behavior. It adds no PHP declaration,
package, schema, generated artifact, adapter, in-memory store, provider,
backend connection, serializer, runtime, command, skeleton, fixture, test,
dependency, configuration, acceptance harness, or checkpoint. It performs no
cache read, write, remove, flush, expiry, eviction, refresh, retry, lock,
network, filesystem, database, or background operation.

No distributed consistency, durability, provider compatibility, PSR
conformance, performance superiority, production availability, Stable, 1.0,
PHP 8.6, PHP 9, Phase 20B, or Phase 21 claim is made.

## Related material

- [ADR 0197](../adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md)
- [ADR 0198](../adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md)
- [Phase 20 roadmap](phase-20-roadmap.md)
- [Cache authoring](../engineering/cache-authoring.md)
- [Progressive architecture](progressive-architecture.md)
- [Application composition and build](application-composition-and-build.md)
- [Security policy](../../SECURITY.md)
