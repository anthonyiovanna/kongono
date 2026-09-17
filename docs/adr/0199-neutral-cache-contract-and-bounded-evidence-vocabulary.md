# ADR 0199: Neutral Cache Contract and Bounded Evidence Vocabulary

- Status: Accepted
- Date: 2026-09-15
- Phase: 20B

## Context

Phase 20A accepted an optional provider-neutral Derived-Data Cache Platform for
bounded, recomputable, disposable evidence. Cache availability or contents may
affect performance or availability, but never application correctness,
authority, or security. The first callable boundary must therefore define
precise inert values before any profile, adapter, backend, runtime, or
application workflow exists.

Application values, logical selection, encoded bytes, content evidence,
freshness, mutation evidence, operation identity, and outcomes belong to
different domains. Collapsing them would let a digest, hit, expiry, or provider
token silently acquire meaning or authority that it does not possess.

## Decision

Accept the optional Experimental `kongono/cache-contract` leaf. Its production
dependencies are only PHP 8.5, `kongono/api-metadata`, and
`kongono/diagnostics`. No existing production package depends on it.

### Identity domains

`CacheNamespace` is an application-owned 1-64 byte lowercase ASCII identity.
`LogicalCacheKey` is an application-owned 1-256 byte lowercase ASCII identity
with its separately bounded grammar. Neither is a path, URL, filename, class,
service, credential, provider address, environment name, or authority grant.

`CacheKeyIdentity` is a lowercase SHA-256 digest of the exact domain-separated
byte sequence `kongono.cache.key.v1\0{namespace}\0{logical-key}`. It is neither
a content identity nor backend authority. `CacheContentIdentity` is lowercase
SHA-256 evidence derived only from exact bounded opaque value bytes. It does
not prove source currentness, authentication, authorization, or an external
effect.

### Codec and opaque bytes

`CacheCodecIdentity` carries only a bounded lowercase name and integer version.
It does not discover, select, load, instantiate, execute, serialize,
deserialize, validate, or authorize a codec or class.

`CacheValueBytes` carries 0 through 1,048,576 exact bytes. Empty bytes are a
valid value rather than a miss. The contract assigns no encoding, media type,
schema, object, class, JSON, or serialization meaning to those bytes. Native
`serialize()` and `unserialize()` are outside the boundary.

### Explicit time and finite freshness

`CacheInstant` carries caller-supplied microseconds since the Unix epoch in the
closed range 0 through 253402300799999999. The package reads no clock and
accepts no ambient date/time object.

`CacheLifetime` is finite and positive, from 1 through 31536000000000
microseconds. `CacheFreshnessWindow` requires expiry strictly after storage and
within that maximum. Freshness is `fresh` only before expiry; the exact expiry
instant and every later instant are `expired`. A caller-supplied time earlier
than storage is rejected. There is no forever/default/sliding lifetime, stale
fallback, hidden refresh, or ambient time.

### Version, operation, and outcome vocabulary

`CacheEntryVersion` is one lowercase 64-character hexadecimal mutation-evidence
token. It is distinct from key/content identity and grants no lock, lease,
transaction, coordination, or authority claim.

The closed operation vocabulary is lookup, store, replace, and remove. Lookup
outcomes are fresh hit, miss, expired, rejected, and unavailable. Mutation
outcomes are stored, removed, not found, conflict, rejected, and unavailable.
These values perform no operation, map no provider output, retry nothing, and
decide no application policy.

### Detached entry evidence

`CacheEntryEvidence` carries exactly Contract Version 1, key identity, codec
identity, value bytes, matching content identity, freshness window, and entry
version. It retains no namespace, logical key, adapter, clock, callback,
stream, connection, credential, provider object, cleanup handle, or application
value. Construction proves only structural consistency. It performs no lookup,
mutation, freshness decision, backend validation, or currentness check.

### Bounds and diagnostics

Every identity, byte string, instant, duration, version, and diagnostic field
is bounded before later effects can exist. Six package diagnostics distinguish
invalid identity, invalid value bytes, invalid freshness evidence, invalid
entry version, invalid entry evidence, and exceeded contract bounds.

Package-owned context may contain only closed Safe violation, byte-count,
limit, operation, and outcome fields. Unsupported caller text becomes a fixed
fallback. Messages, context, and deterministic string presentation disclose no
raw namespace, logical key, codec name, cached bytes, digest/token, credential,
backend address, path, arbitrary provider text, or unbounded value. Native PHP
trace APIs are not claimed to be sanitized.

### Persistent-runtime safety and API status

All values are immutable and operation-independent. The leaf has no mutable
static state, singleton, registry, facade, service locator, ambient current
entry, destructor work, shutdown hook, Fiber-local state, or cross-operation
memory. Sequential, overlapping, and reentrant construction share no mutable
state.

The 21 production declarations are 17 Experimental values/enums and the
concrete exception, one Public package-family exception interface, and three
Internal diagnostic/abstract-exception/guard declarations. No declaration is
Stable.

## Consequences

Applications can name and validate neutral cache evidence without choosing a
provider or performing an effect. Lower packages remain independent of caching.
The strict separation makes later profiles, adapters, runtimes, and application
policy more explicit, at the cost of deliberate construction and validation.
Entry evidence cannot be used as proof that an authoritative source is current
or that any external operation occurred.

## Alternatives considered

- A PSR-6 or PSR-16 contract was rejected as the internal correctness model
  because provider interoperability cannot redefine Kongono's identity,
  freshness, atomicity, uncertainty, bounds, or authority rules.
- PHP object serialization, JSON coercion, and generic codec callbacks were
  rejected because they collapse application policy and executable behavior
  into the neutral contract.
- Ambient clocks, default TTLs, forever entries, sliding expiry, and stale
  fallback were rejected because freshness must remain explicit and closed.
- A facade, registry, read-through callback, or provider discovery was rejected
  because it would create ambient authority and persistent-runtime state.

## Excluded behavior

Phase 20B adds no schema/profile artifact, generated code, publication,
adapter, backend, engine, provider, in-memory implementation, runtime cache
operation, serialization, callback, retry, command, skeleton, application
workflow, inspection, external acceptance, provider compatibility, durability,
distribution, performance, production-readiness, Stable/1.0, Phase 20C, or
Phase 21 behavior. Phases 20C-H remain Planned and separately authorized.
