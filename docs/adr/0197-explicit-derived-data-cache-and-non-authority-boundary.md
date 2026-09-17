# ADR 0197: Explicit Derived-Data Cache and Non-Authority Boundary

- Status: Accepted
- Date: 2026-09-15
- Phase: 20A

## Context

Kongono has explicit application composition, deterministic artifacts, and
bounded runtime capabilities, but it has no cache contract. Applications may
already keep disposable derived data under their own policy. A framework cache
boundary must not turn that optimization into application truth, ambient
infrastructure, or security authority.

Conventional cache APIs often combine application values, serialization,
logical keys, provider addresses, clocks, expiry defaults, read-through
callbacks, and stale fallback behind one global facade. Those combinations
conflict with Kongono's explicit identity, bounded evidence, operation-local
lifecycle, exact-current, and persistent-runtime doctrines.

Phase 20A accepts documentation architecture only. It creates no package,
callable declaration, schema, profile, artifact, adapter, backend, cache entry,
runtime operation, command, skeleton, test, dependency, or checkpoint.
Phase 20A is Accepted; Phases 20B-H remain Planned.

## Decision

### Optional upper application capability

The Derived-Data Cache Platform is optional. API-only, CLI, worker, agent,
server-rendered, frontend-backed, and mobile-backend applications may omit it.
Installing Kongono creates no cache singleton, facade, global namespace,
static registry, service-locator entry, connection, worker, or daemon.

Applications opt in through explicit composition. Future cache packages point
inward to neutral contracts and may depend on existing neutral metadata and
diagnostic foundations. Kernel, routing, HTTP, database, persistence,
security, Graph, agents, views, assets, and other lower packages do not depend
outward on caching.

### Derived evidence, never authority

A cache entry is bounded, derived, disposable evidence. It is not a database
or persistence record, source of truth, authentication or authorization
decision, capability grant, policy, credential, session, CSRF token, migration
state, queue, durable event, audit record, receipt, lock, lease, rate limiter,
counter, or proof that an external effect occurred. It cannot replace an
exact-current check required by another package.

Applications remain correct after a miss, expiry, rejection, backend loss,
eviction, or complete cache flush. Cache loss may reduce availability or
performance; it must not change correctness, authority, or security.

### Explicit identity and data domains

The planned neutral model keeps these concepts distinct:

- application-owned cache namespace;
- logical cache key;
- bounded key identity used for comparison or backend addressing;
- application-selected codec and media/schema identity;
- opaque bounded value bytes;
- content identity derived from those exact bytes;
- accepted entry version or mutation token;
- freshness policy and explicit time evidence;
- backend or provider identity; and
- operation identity, outcome, and diagnostic evidence.

No string silently crosses domains. A digest is not a logical key, a logical
key is not a backend address, codec identity is not deserialization authority,
expiry is not authorization, and a cache hit is not proof that its source is
current.

Future neutral contracts accept opaque bytes rather than PHP object graphs.
They do not use native `serialize()` or `unserialize()`, load classes, execute
callbacks, discover codecs, or infer application value types. Applications
own codec selection, schema/version policy, validation, and the mapping between
application values and bounded bytes.

### Replaceable adapters

Kongono owns a neutral cache contract, not a cache server. A future adapter
translates explicit bounded operations and returns complete evidence that is
untrusted until validated. It receives no ambient credential, environment,
filesystem, network, DNS, process, package-discovery, or configuration
authority.

The first planned implementation is a separately optional bounded in-memory
adapter. It supports process-local development and deterministic acceptance;
it makes no durability, distribution, shared-host, multi-process, production
availability, or performance claim. Redis, Memcached, database, filesystem,
cloud, vendor, PSR-6, and PSR-16 adapters remain deferred.

### Standards relationship

Phase 20A selects no PSR-6 or PSR-16 dependency. Optional interoperability may
be added later through adapters, but those interfaces do not replace Kongono's
explicit identity domains, supplied time, bounded opaque bytes, typed
miss/expiry outcomes, conditional mutation evidence, lifecycle, or
nondisclosure model. This preserves interoperability without making a
compatibility surface the internal correctness contract.

### Security, bounds, and diagnostics

Every future namespace, key, value, metadata item, list, total byte count,
lifetime, operation count, adapter result, diagnostic field, and operation
duration has a finite bound before an effect. Diagnostics identify the
operation, violated invariant, and actionable remediation. Safe or Protected
evidence may contain bounded counts, closed reasons, operation categories, and
digests, but never raw keys, cached values, serialized content, credentials,
tokens, backend addresses, connection data, application paths, arbitrary
provider messages, or unbounded payloads.

Cache evidence grants no identity, authentication, authorization, capability,
policy, credential, network, storage, process, Graph, or application-effect
authority.

### API status direction

Phase 20A exposes zero callable declarations. Phases 20B-H remain Planned;
future neutral, runtime, adapter, application, and inspection declarations
begin Experimental unless separately classified Internal or Public. No Stable
or 1.0 status is authorized. PHP 8.5 remains the accepted baseline; future
readiness comes from small explicit contracts, immutable values, replaceable
adapters, and supported-runtime tests, not unreleased behavior claims.

## Consequences

- Applications can opt into caching without making correctness depend on it.
- Explicit codecs and identity domains require more composition but remove
  hidden serialization and backend coupling.
- Replaceable adapters allow provider evolution without redefining cache
  semantics.
- Typed expected outcomes make miss, expiry, and conflict visible rather than
  collapsing them into an ambiguous nullable value.
- The platform cannot be used as a shortcut for authority, durability,
  coordination, or exact-current validation.

## Alternatives and tradeoffs

A global cache facade was rejected because it hides composition, backend,
namespace, time, and lifecycle. Native PHP serialization was rejected because
it combines class loading and object semantics with storage. Treating a cache
as persistence was rejected because eviction and backend loss are ordinary.
Making PSR-6 or PSR-16 the internal model was rejected because neither surface
defines all required identity, time, typed-outcome, concurrency, and disclosure
semantics. No-cache application behavior remains a first-class alternative.

## Deferred and excluded scope

Phase 20A implements no package, PHP API, schema, profile, canonical artifact,
generated code, adapter protocol, in-memory adapter, provider, backend,
serialization, runtime, mutation, command, application integration,
inspection, acceptance harness, cache server, dependency, skeleton, or
checkpoint. Redis, Memcached, filesystem, database, cloud, PSR, and vendor
compatibility are deferred.

Locks, leases, leader election, semaphores, queues, schedulers, durable jobs,
rate limits, counters, events, pub/sub, invalidation broadcasts, daemons,
workers, watchers, read-through behavior, background refresh, stale fallback,
performance superiority, production availability, Stable, and 1.0 claims are
excluded.

## Related authorities

- [ADR 0198](0198-deterministic-cache-freshness-atomicity-and-lifecycle.md)
- [Cache Platform](../architecture/cache-platform.md)
- [Phase 20 roadmap](../architecture/phase-20-roadmap.md)
- [Cache authoring](../engineering/cache-authoring.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Security policy](../../SECURITY.md)
- [Architectural doctrine](../doctrine.md)
