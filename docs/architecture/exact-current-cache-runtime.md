# Exact-Current Cache Runtime

Phase 20E accepts the optional Experimental `kongono/cache-runtime` leaf. It
composes the accepted cache profile and neutral adapter boundaries without
adding configuration discovery, serialization, provider integration, or an
application workflow.

## Explicit call sequence

1. The caller constructs one complete `CacheProfileSet` and supplies its
   canonical artifact bytes.
2. `CacheRuntimeBinding` calls
   `CacheProfileArtifactValidator::validateCurrent()` and selects exactly one
   caller-named profile.
3. The binding checks the directly supplied adapter's Version 1 protocol,
   expected identity, maximum value size, and aggregate capacity. Raw artifact
   bytes are not retained.
4. The caller supplies a lookup, store, replace, or remove request with one
   explicit `CacheInstant`.
5. The runtime derives `CacheKeyIdentity` from the selected namespace and
   logical key. Store and replace construct an inert entry candidate from the
   profile contract, codec, lifetime, value limit, bytes, and supplied instant.
6. Immediately before the one corresponding adapter call, the runtime acquires
   its instance-local guard. The guard is retired in `finally` on every path.
7. The adapter result is treated as untrusted. The runtime validates its key,
   allowed outcome, evidence shape, contract, codec, bytes, content identity,
   freshness relations, and candidate equality before producing detached
   runtime evidence.

## Outcomes and expiry

Lookup returns `FreshHit`, `Miss`, `Expired`, `Rejected`, or `Unavailable`.
Provider `Found` is not a runtime outcome: its complete entry is validated and
then evaluated at the caller's `now`. The entry is fresh only while
`storedAt <= now < expiresAt`. It is expired when `now >= expiresAt`, including
the exact boundary, and the expired result contains no entry or stale bytes.
Evidence whose `storedAt` is after `now` is invalid rather than a miss.

Store may return `Stored`, `Conflict`, `Rejected`, or `Unavailable`. Replace may
return `Stored`, `NotFound`, `Conflict`, `Rejected`, or `Unavailable`. Remove may
return `Removed`, `NotFound`, `Conflict`, `Rejected`, or `Unavailable`. Only
`Stored` carries validated detached entry evidence. Replace and remove bind the
operation to the caller-supplied expected entry version unchanged.

## Lifecycle and persistent runtimes

The runtime holds immutable binding state and one private instance-local active
operation guard. Same-instance reentry fails before a second adapter call.
Sequential calls after success, validation failure, adapter failure, or PHP
`Error` remain healthy. Separate runtimes, including runtimes sharing one
adapter and overlapping in Fibers, have independent guards. No operation-local
request, result, key, value, time, version, throwable, callback, Fiber, or
diagnostic state is retained after completion.

## Disclosure boundary

Runtime messages, stringified exceptions, and diagnostic projections never
include logical keys, namespaces, profile names, adapter identities, value
bytes, versions, digests, artifact bytes, paths, timestamps, or provider
messages. Context is limited to bounded Safe counts, limits, booleans, and
closed operation, outcome, and violation labels. The prior throwable remains
available programmatically for an adapter execution failure.

## Minimal explicit use

```php
$profiles = new CacheProfileSet([$profile]);
$binding = new CacheRuntimeBinding(
    $profiles,
    $artifactBytes,
    new CacheProfileName('application'),
    new CacheAdapterIdentity('bounded-memory', 1),
    $adapter,
);
$runtime = new ExactCurrentCacheRuntime($binding);
$result = $runtime->lookup(new CacheLookupRequest(
    new LogicalCacheKey('user:42'),
    new CacheInstant(1_700_000_000_000_000),
));
```

The application owns every supplied value and decides what to do with the typed
result. It remains correct after miss, expiry, rejection, unavailability,
eviction, backend loss, or complete flush.

## Current and planned scope

Phase 20E is Accepted. Phase 20F accepts the explicit application workflow,
codec mapping, commands, and copy-once slice. Phase 20G visibility-safe inspection
and Phase 20H root-owned external acceptance and checkpoint are Accepted. Redis, Memcached, database,
filesystem, network, cloud, distributed, PSR-6, and PSR-16 adapters remain out
of scope, as do production, performance, and compatibility claims.
