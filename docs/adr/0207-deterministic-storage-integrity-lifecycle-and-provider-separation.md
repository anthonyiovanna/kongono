# ADR 0207: Deterministic Storage Integrity, Lifecycle, and Provider Separation

- Status: Accepted
- Date: 2026-09-18
- Phase: 21A

## Context

Object storage becomes unsafe when writes overwrite silently, retries hide
uncertain effects, whole objects must be buffered, provider acknowledgements
are treated as universal durability proof, or handles and temporary state leak
between operations. Provider APIs also differ in atomicity, consistency,
conditional mutation, metadata, listing, and failure semantics. A neutral
contract must not invent guarantees that an adapter cannot prove.

Phase 21A accepts documentation architecture only. It implements none of the
contracts or behavior described here.

## Decision

### Integrity and mutation evidence

Future storage contracts distinguish create-only writes, conditional
replacement, version-matched mutation, deletion, absence, pre-existence, known
completion, known failure, and uncertain outcomes. Silent overwrite is
forbidden. An unqualified write is never documented as safe replacement.

Checksums, lengths, versions, conditional tokens, and acknowledgements are
bounded evidence with explicit provenance. They prove no more durability,
atomicity, consistency, or concurrency safety than the selected adapter and
provider actually guarantee. An uncertain outcome means a provider may have
accepted a mutation without a conclusive acknowledgement; it is not success,
failure, absence, or permission to retry.

### Bounded streaming and lifecycle

The planned neutral boundary supports streaming without requiring an entire
object in memory. Every operation declares finite chunk, total-byte, metadata,
duration, and result bounds before an effect. Backpressure, cancellation,
close, abort, ownership transfer, and cleanup are explicit.

Mutable state is operation-local. Success, expected outcomes, cancellation,
primary failure, and cleanup failure all retire resources deterministically.
Open files, sockets, temporary files, multipart state, buffers, locks,
callbacks, credentials, and metadata must not escape into a later persistent
worker operation. Destructors, shutdown hooks, and garbage collection are not
the primary lifecycle protocol. Primary and cleanup truth remain visible.

### Provider capability declarations

Every future adapter publishes its supported operations and actual guarantees
for atomicity, durability, consistency, conditional mutation, metadata,
listing, ordering, and cleanup. Unsupported behavior is rejected explicitly.
The neutral layer never upgrades, guesses, or invisibly emulates a provider
guarantee.

Listing is optional and capability-declared. When supported, it is bounded,
paginated, ordered under a documented contract, and continued only with opaque
continuation evidence. Empty, truncated, stale, and unsupported results remain
distinct. Listings are evidence, never authorization or discovery authority.

### Deterministic build and exact-current runtime

Future application-owned storage declarations are explicit build inputs. A
planned build step validates them and emits one canonical versioned storage
profile artifact. Publication is explicit, content-addressed, contained, and
copy-once. A planned runtime consumes only the exact current artifact and never
scans declarations, rebuilds, reconciles stale state, or chooses a convenient
artifact or provider.

Artifacts, currentness evidence, digests, profiles, and provider declarations
grant no filesystem, network, credential, storage, authorization, or effect
authority.

### Diagnostics and inspection

Diagnostics are bounded, stable, actionable, and nondisclosing. Safe evidence
may describe closed operation kinds, declared capabilities, bounds, algorithm
identifiers, generic outcome categories, and provenance state. Object bytes,
raw object identifiers, paths, provider locators, URLs, credentials, signing
material, customer metadata, tenant identifiers, arbitrary provider messages,
and response bodies remain Protected or Forbidden.

Planned inspection is offline, read-only, stateless, and authority-preserving.
It does not open or list objects, contact providers, validate credentials,
perform operations, mutate runtime state, rebuild artifacts, or disclose
Protected or Forbidden values.

## Consequences

- Callers must handle uncertain mutations explicitly and reconcile only under
  separately owned application policy.
- Streaming implementations have a larger lifecycle contract but avoid a
  universal whole-object memory requirement.
- Provider portability means honest capability negotiation and rejection, not
  equal behavior across providers.
- Deterministic profile evidence can support exact-current composition without
  becoming operation authority.
- Persistent runtimes can reuse immutable configuration without retaining
  operation state or resources.

## Rejected alternatives

- Silent overwrite was rejected because it hides lost updates.
- Universal whole-object buffering was rejected because object size must not
  silently become process memory usage.
- Hidden retries were rejected because mutation truth may be uncertain and
  retry policy is application-owned.
- Lowest-common-denominator guarantee inflation was rejected because it makes
  unsupported behavior appear safe.
- Destructor-only cleanup was rejected because retirement must be timely and
  observable.
- Directory scanning as discovery was rejected because provider state is not
  configuration or authority.
- Automatic public URLs were rejected because HTTP delivery and access policy
  are separate.
- Treating acknowledgement as stronger durability than a provider promises was
  rejected because evidence cannot manufacture external truth.

## Deferred and excluded scope

No concrete bounds, PHP signatures, schema, artifact bytes, provider protocol,
filesystem implementation, cloud adapter, retry policy, multipart transfer,
encryption/KMS, retention, replication, backup, public delivery, upload
controller, media processing, runtime, command, inspection, or acceptance
harness is selected in Phase 21A.

## Related authorities

- [ADR 0206](0206-optional-object-storage-platform-and-explicit-authority-boundary.md)
- [Storage Platform](../architecture/storage-platform.md)
- [Phase 21 roadmap](../architecture/phase-21-roadmap.md)
- [Storage authoring](../engineering/storage-authoring.md)
- [Persistent-runtime doctrine](0006-persistent-runtime-first.md)
- [Actionable diagnostics](0026-actionable-diagnostics-and-error-boundaries.md)
- [Bounded diagnostic disclosure](0028-bounded-diagnostic-context-and-disclosure.md)
