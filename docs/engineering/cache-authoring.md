# Cache Authoring

## Status

Phases 20A-H are Accepted and Phase 20 is closed. Phase 20B supplies only
the optional Experimental `kongono/cache-contract` immutable value and closed
evidence vocabulary. Phase 20C supplies the optional Experimental
`kongono/cache-build` explicit profile, canonical artifact, exact-current
validation, and contained publication boundary. Phase 20D supplies the
optional Experimental neutral adapter protocol and separately optional
Experimental bounded process-local memory implementation. Phase 20E supplies
the exact-current runtime. Phase 20F supplies explicit plan/build/check,
profile/codec identity mapping, direct runtime composition, three commands,
and the copy-once cache example. No framework serialization, provider
compatibility or production claim exists. The root-owned Phase 20H acceptance
proves only the bounded local stack.

## Present Phase 20C deterministic build

For current application composition and command examples, see the
[Cache Application workflow](../architecture/cache-application-workflow.md).

Applications may create a complete bounded `CacheProfileSet`, build canonical
inert JSON explicitly, validate supplied bytes structurally or against one
explicit current set, and publish the validated artifact under one explicit
existing root. This is build-time evidence only and never reads cache entries,
executes codecs, discovers configuration, or performs a cache operation.

## Present Phase 20B construction

Applications may currently construct only bounded `CacheNamespace` and
`LogicalCacheKey` values, domain-separated `CacheKeyIdentity`, inert
`CacheCodecIdentity`, `CacheValueBytes` and `CacheContentIdentity`, explicit
`CacheInstant`, finite `CacheLifetime` and `CacheFreshnessWindow`, opaque
`CacheEntryVersion`, closed operation/outcome enums, and detached
`CacheEntryEvidence`. Time is caller supplied; the package reads no clock.
Empty bytes are a valid value, exact expiry is expired, and entry evidence is
not proof of source currentness, provider truth, authority, or an external
effect.

## When caching is appropriate

Consider caching only for bounded data that is derived from an authoritative
source, safe to discard, and practical to recompute. The application must
remain correct after miss, expiry, eviction, rejection, backend failure, or a
complete flush. A cache may improve latency or reduce repeated computation; it
must not decide truth, authority, or security.

Do not cache a database or persistence record as its authoritative copy,
credentials, secrets, authentication or authorization decisions, capability
grants, policies, sessions, CSRF tokens, migration state, durable events,
audit records, receipts, queues, locks, leases, rate limits, counters, or proof
of an external effect. Do not use a hit to bypass an exact-current check owned
by another package.

## Namespace and logical-key design

Choose an application-owned namespace for one bounded semantic purpose. Keep
logical keys stable, deterministic, and independent of backend addresses. Do
not embed passwords, credentials, bearer tokens, session identifiers,
personally identifying data, raw request bodies, private paths, or other
secrets in namespaces or keys. Diagnostics should use closed categories,
bounded counts, or Protected digests rather than raw key material.

Treat namespace, logical key, key identity, content identity, entry version,
provider identity, and operation identity as different domains. A digest is
not automatically a key, and a key must not be concatenated into a provider
address without a separately accepted bounded adapter mapping.

## Codec and schema ownership

Application code selects the codec, media identity, and encoded schema version
explicitly. Validate application values before encoding and validate bounded
bytes before decoding. The neutral cache contract will carry opaque bytes; it
will not serialize PHP objects, call native `serialize()` or `unserialize()`,
load classes, infer types, execute callbacks, or discover codecs.

Include enough application-owned schema/version identity to distinguish
incompatible encodings. Changing a codec or schema produces a deliberate
rollout or namespace/key-version change rather than guessing whether old bytes
remain compatible.

## Finite freshness and invalidation

Select a finite lifetime based on the cost and correctness properties of the
derived data. Supply time explicitly in tests and future operations; do not
depend on an ambient system clock or sleep. Document the exact expiry-boundary
expectation.

Treat expiry and invalidation as different concerns. TTL limits how long
evidence may be reported fresh. Source-version or namespace/key-version
changes prevent incompatible evidence from being selected. Neither mechanism
grants authorization or proves the authoritative source is current.

There is no implicit forever entry, sliding expiry, stale-while-revalidate,
stale-if-error, background refresh, or development-only stale fallback.

## Correctness paths

Application behavior must handle each planned outcome explicitly:

- **fresh hit:** validate content identity and decode the bounded bytes under
  the selected codec/schema policy;
- **miss:** compute or read from the authoritative path;
- **expired:** treat the entry as unavailable for a fresh result and use the
  authoritative path;
- **conditional conflict:** do not overwrite or remove a newer value; decide
  explicitly whether to recompute or end the operation;
- **rejected:** correct the caller input, policy, bounds, or adapter mismatch;
  and
- **unavailable:** preserve application correctness without inventing success,
  retry, or stale fallback.

Invalid evidence and uncertain mutation are failures. Never convert them into
a hit or assume a write occurred.

## Conditional mutation and isolation

Use explicit accepted entry-version evidence for future conditional replace
and remove operations. Unconditional overlapping mutation risks lost updates.
Atomic entry visibility does not make the cache a lock manager, lease service,
queue, counter, scheduler, or transaction coordinator.

Keep mutable state inside one operation. Verify that sequential, overlapping,
reentrant, and persistent-runtime calls do not share selected keys, supplied
time, policy, bytes, results, adapter state, or diagnostics. Results should be
detached and retain no adapter, clock, callback, stream, connection,
credential, mutable buffer, or application object.

## Encoding migrations and rollout

For a changed encoded value:

1. assign a new explicit schema or codec identity;
2. decide whether to use a new namespace or key-version component;
3. deploy readers and writers under one documented compatibility plan;
4. treat old or undecodable entries as misses rather than guessing;
5. preserve finite lifetimes so obsolete entries retire naturally; and
6. remove compatibility only after the application no longer selects the old
   identity.

Do not run an implicit scan, destructive global flush, or background migration
through the cache contract. Authoritative data migration belongs to its owning
system.

## Testing guidance

Future tests should cover:

- fresh hit with exact bounded bytes and content identity;
- ordinary miss;
- equality and just-after behavior at the expiry boundary;
- conditional replace/remove conflict against a newer version;
- rejected caller input and malformed adapter evidence;
- unavailable backend and uncertain mutation without false success;
- deterministic capacity/eviction behavior for the bounded memory adapter;
- cleanup after success, expected outcome, primary failure, and cleanup
  failure; and
- sequential, overlapping, reentrant, and persistent-runtime isolation.

Supply time and adapters directly. Do not sleep, mutate global clocks, rely on
test order, retain state across cases, contact a real provider, or assert raw
keys and values in diagnostics.

## Local memory development and provider portability

The Phase 20D in-memory adapter is Accepted as a bounded process-local profile
for development and deterministic tests. It does not establish durability,
distribution, shared-host behavior, multi-process
visibility, production availability, or provider compatibility.

Keep application code at the neutral contract and explicit composition
boundary. Provider-specific addressing, credentials, networking, retry, and
diagnostics belong in a separately accepted adapter. Redis, Memcached,
filesystem, database, cloud, vendor, PSR-6, and PSR-16 integrations are not
current capabilities.

## Planned future workflow

This outline is Planned and is not executable code:

1. declare one explicit bounded cache profile;
2. select one application codec/schema and finite freshness policy;
3. plan, build, and check the profile through future human/machine-equivalent
   commands;
4. compose one directly supplied identity-matching adapter and supplied time;
5. perform one bounded operation and handle its typed outcome;
6. validate and decode detached hit bytes in application code; and
7. inspect only one supplied exact-current build and optional detached aggregate runtime evidence through the accepted Phase 20G upper leaf.

No step discovers configuration, installs a provider, selects a default
backend, reads an ambient clock, serializes objects, retries implicitly,
returns stale data, runs a callback, or flushes a cache destructively.

## Review checklist

- Is the value derived, disposable, bounded, and safe to recompute?
- Does application correctness survive every absence and failure path?
- Are namespace, logical key, codec, content identity, version, time, and
  provider identity explicit and separate?
- Are keys and values free of credentials, secrets, sessions, grants, and
  unredacted sensitive data?
- Are lifetime and expiry-boundary semantics finite and documented?
- Are miss, expiry, conflict, rejection, and unavailability handled visibly?
- Are conditional mutations bound to explicit version evidence?
- Is mutable state operation-local with deterministic cleanup?
- Can the adapter be replaced without changing application truth?
- Does the design avoid facade, registry, service location, magic
  serialization, hidden clock, stale fallback, and coordination misuse?

## Related material

- [Cache Platform](../architecture/cache-platform.md)
- [Phase 20 roadmap](../architecture/phase-20-roadmap.md)
- [Phase 20 external acceptance](../architecture/phase-20-external-cache-acceptance.md)
- [ADR 0197](../adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md)
- [ADR 0198](../adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md)
- [Security policy](../../SECURITY.md)
- [Error handling](error-handling.md)
- [API status](api-status.md)
