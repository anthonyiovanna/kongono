# ADR 0206: Optional Object Storage Platform and Explicit Authority Boundary

- Status: Accepted
- Date: 2026-09-18
- Phase: 21A

## Context

Applications may need to retain authoritative binary content that is too large,
opaque, or lifecycle-sensitive for database rows. Kongono has no storage
contract, provider adapter, profile artifact, runtime, command, or application
workflow. Treating a path, bucket name, URL, environment variable, or installed
cloud SDK as storage authority would conflict with explicit composition,
install-none defaults, deterministic currentness, and denied ambient authority.

Storage is not cache. Cached values are derived and disposable; stored objects
may be authoritative and irreplaceable. A cache miss can be ordinary, while an
uncertain storage mutation is an integrity event. Cache expiry, eviction, and
best-effort replacement therefore cannot define storage semantics.

Phase 21A accepts documentation architecture only. It creates no package, PHP
API, schema, declaration, artifact, adapter, runtime operation, command,
configuration key, credential mechanism, provider integration, fixture, or
executable example.

## Decision

### Optional provider-neutral capability

Kongono Storage is an optional upper application capability for
application-owned objects and binary content. The framework remains fully
usable when every future Phase 21 package is absent. Installing Kongono implies
no writable directory, bucket, container, endpoint, credential, global facade,
background worker, or default object repository.

Applications opt in at the composition root. Future neutral storage packages
point inward to existing metadata and diagnostic foundations. Provider adapters
point inward to neutral storage contracts. Kernel, routing, HTTP, database,
persistence, security, Graph, agents, views, assets, cache, and other lower
packages do not depend outward on storage.

### Explicit authority and distinct identities

Storage authority is an explicitly supplied, capability-scoped application
decision. Data that names a possible object or provider location grants no
operation. The architecture keeps these domains distinct:

- logical object identity;
- logical namespace or collection identity;
- provider-specific locator, root, bucket, container, or endpoint;
- content digest and digest algorithm;
- media type and caller-owned metadata;
- version or concurrency token;
- operation identity and lifecycle; and
- application authorization to read, create, replace, delete, list, or inspect.

Possessing an identifier is not authorization. Provider credentials, tenant
mapping, authorization policy, and network-egress policy remain explicit
application or composition-root responsibilities.

### Separation from adjacent capabilities

- Cache retains bounded derived evidence and must remain safe to lose; storage
  may retain authoritative content and cannot inherit cache expiry or eviction.
- Database and persistence own structured records and transactions; storage is
  not an ORM, query engine, relational model, or transaction coordinator.
- Asset build owns deterministic application build output; storage does not
  discover, transform, bundle, or publish browser code.
- HTTP delivery, public-file serving, cache-control, CDN, CSP, and signed URL
  policy remain separate application boundaries.
- Upload parsing and validation remain ingress concerns; accepting request bytes
  does not itself authorize storage.
- Backup, replication, retention, legal hold, and disaster recovery remain
  separate operational capabilities.

### Provider separation

Future neutral contracts may describe only honest bounded storage semantics.
They do not expose AWS S3, Azure Blob Storage, Google Cloud Storage,
filesystem, Flysystem, or another provider's types or option vocabulary.
Provider-specific behavior belongs in independently optional adapters.
Unsupported capabilities fail explicitly rather than being guessed, silently
emulated, or weakened into a lowest-common-denominator promise.

The first planned concrete adapter is a contained local-filesystem adapter for
development, testing, and explicitly authorized deployments. It is not part of
Phase 21A and will not create a root-filesystem API.

### API status

Phase 21A exposes zero callable declarations. Every future public Phase 21
symbol begins Experimental unless a later accepted directive explicitly gives
another status; implementation details remain Internal. No Stable, 1.0, PHP
8.6, PHP 9, or semantic-version compatibility promise is made.

## Security effects

Logical identifiers, provider locators, credentials, and authorization evidence
remain separate. Future local adapters must prove containment under one exact
caller-supplied root and reject traversal, absolute escape, ambiguous
separators, device paths, reserved names, alternate data streams, links or
reparse-point escape, and unprovable case-folding behavior. Diagnostics omit
object contents, raw identifiers, paths, locators, URLs, credentials, signing
material, customer metadata, tenant identifiers, and provider response bodies.

## Consequences

- Applications can adopt storage without making it a root-framework service.
- Explicit authority and identity domains require more composition but prevent
  string values from becoming capabilities.
- Provider guarantees remain visible and replaceable rather than defining the
  neutral model.
- Storage failures and uncertain mutations cannot be collapsed into cache-like
  misses or invented success.
- Phase 21B and every later implementation increment require separate
  authenticated authority.

## Rejected alternatives

- A required root filesystem service was rejected because it violates
  install-none and grants ambient write authority.
- Raw paths or strings as authority were rejected because data is not a
  capability or authorization decision.
- A cloud-vendor SDK as the neutral contract was rejected because provider
  vocabulary cannot define Kongono's correctness model.
- A global storage facade or service locator was rejected because it hides
  selection, authority, credentials, lifecycle, and cleanup.
- Treating storage as cache was rejected because authoritative data cannot
  inherit eviction, expiry, or best-effort semantics.
- Current-working-directory and environment discovery were rejected as ambient
  authority.
- Runtime scanning and provider auto-selection were rejected because they make
  configuration, currentness, and effects nondeterministic.

## Deferred and excluded scope

Phase 21A implements no storage package, contract, schema, profile, artifact,
adapter, runtime, command, application workflow, inspection, acceptance
harness, provider, credential source, network call, directory, upload handler,
public URL, backup mechanism, media transformation, queue, watcher, or
background task.

## Related authorities

- [ADR 0207](0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md)
- [Storage Platform](../architecture/storage-platform.md)
- [Phase 21 roadmap](../architecture/phase-21-roadmap.md)
- [Storage authoring](../engineering/storage-authoring.md)
- [Cache Platform](../architecture/cache-platform.md)
- [Security policy](../../SECURITY.md)
- [Architectural doctrine](../doctrine.md)
