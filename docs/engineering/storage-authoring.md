# Storage Authoring

## Status

Phases 21A-F are **Current** and Accepted. The optional Experimental
`kongono/storage-contract` package now provides inert bounded values,
requests, results, and stream lifecycle interfaces. The optional Experimental
`kongono/storage-build` package provides explicit bounded profiles, canonical
artifact evidence, strict structural and exact-current validation, and
contained content-addressed publication. The optional Experimental
`kongono/storage-adapter-contract` package provides the neutral Version 1
adapter protocol, and the separately optional Experimental
`kongono/storage-adapter-local` package provides one explicitly rooted
contained implementation. The separately optional Experimental
`kongono/storage-runtime` package provides exact-current operations through one
directly supplied compatible neutral adapter and does not require the local
adapter. The separately optional Experimental `kongono/storage-application`
package supplies strict source loading, exact-current plan/build/check,
explicit guarded runtime composition, and three supported profile commands.
The copy-once `storage-minimal` example uses explicit roots. No cloud provider,
credential mechanism, generated business authorization, or public delivery is
implied. Phase 21G implements the separately optional Experimental
`kongono/storage-inspection` offline evidence leaf. It checks supplied in-memory profiles and artifacts, optionally
binds detached outcome aggregates, and grants no object or provider access.
See [ADR 0213](../adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [storage inspection guide](../architecture/storage-inspection-and-intelligence.md).
Phase 21G is Current and Accepted. Phase 21H adds
[root-owned local acceptance](../architecture/phase-21-external-storage-acceptance.md)
whose separate time-bound audit, architect acceptance, and exact checkpoint
gates completed with their recorded limits.

Use the [Storage Platform guide](../architecture/storage-platform.md),
[ADR 0206](../adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md),
[ADR 0207](../adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md),
[ADR 0208](../adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md),
[ADR 0209](../adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md),
[ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md),
[ADR 0211](../adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md),
[ADR 0212](../adr/0212-supported-storage-application-workflow-and-explicit-authorization.md),
the [deterministic storage-profile build guide](../architecture/deterministic-storage-profile-build.md),
the [contained local adapter guide](../architecture/contained-local-storage-adapter.md),
the [exact-current storage runtime guide](../architecture/exact-current-storage-runtime.md),
the [storage application workflow guide](../architecture/storage-application-workflow.md),
and the fixed [Phase 21 roadmap](../architecture/phase-21-roadmap.md) together.

## Use the Phase 21B contract

Use `StorageNamespace` and `LogicalObjectIdentity` for canonical application
identity and combine them only as `StorageObjectIdentity`. Use a separate
caller-supplied `StorageOperationIdentity` for correlation. These values never
become a path, URL, provider locator, credential, or authorization result.

Construct `StorageReadRequest` with an explicit maximum `StorageByteCount`.
Construct `StorageWriteRequest` only for `StorageOperation::Create` with
`StorageMutationCondition::ifAbsent()` or `StorageOperation::Replace` with
`StorageMutationCondition::ifVersion()`. `StorageDeleteRequest` also requires
`ifVersion()`. Bound `StorageListRequest` to 1-1,000 entries and treat its
`StorageContinuationToken` as opaque.

Handle `StorageReadOutcome`, `StorageMutationOutcome`, and
`StorageListOutcome` without collapsing absence, conflict, rejection,
unsupported capability, unavailability, or uncertain mutation. Only completed
results carry the success evidence permitted by their exact relation.
`StorageChunk` is the only contract value that carries object bytes.

## Author and validate a storage profile

Create a `StorageProfileDeclaration` from an exact name, Contract Version 1,
one unique logical namespace, a non-empty capability set, an optional digest
algorithm with the exact content-digest relation, and positive bounded chunk,
object, and list-page limits. Combine 1 through 256 declarations in a
`StorageProfileSet`; duplicate names or namespaces fail closed. Profile order
is ascending name-byte order and capability order follows the closed enum, so
caller order cannot affect source identity or artifact bytes.

`StorageProfileBuilder` produces the fixed
`kongono/storage-profile-artifact/1` canonical JSON artifact. Keep source,
build, and exact-byte artifact identities distinct. Use structural validation
for supplied bytes and exact-current validation only with one explicitly
supplied complete current set. Never scan, choose a newest artifact, repair
stale bytes, or infer declarations from files or environment.

Publish only an already validated artifact beneath one exact explicit absolute
root. The content-addressed destination is immutable and no-clobber; exact
ordinary existing bytes are idempotent evidence, while different bytes or an
unexpected path type fail closed. Publication creates no mutable current
pointer and grants no provider, credential, authorization, runtime, or object
operation authority. The Phase 21C package exposes no supported command.

## Supply an adapter explicitly

Construct an adapter descriptor only from one validated artifact, one exact
profile name present in that artifact, an adapter identity, and complete closed
guarantee and limitation sets. The descriptor does not establish exact
currentness or authorize an operation. Supply the adapter directly; do not use
a registry, facade, service locator, environment lookup, provider discovery,
or newest-artifact selection.

For the contained local implementation, supply one exact existing absolute
ordinary root through `LocalStorageRoot`. Create uses `if_absent`; replace and
delete require the exact accepted version. Keep the caller-owned write source
open on every return path and explicitly close each completed read source.
Treat list continuation evidence as opaque and listing as bounded ordinal
observation rather than a snapshot. Handle `uncertain` as an integrity event,
not success, failure, or retry permission.

## Choose the right capability

Use Storage when an application owns bounded binary or object content
whose persisted bytes may be authoritative and whose provider must be
replaceable behind explicit capabilities.

Use another capability when:

- the value is derived, disposable, and safe to recompute: use Cache;
- the data needs relational queries, transactions, or structured record
  semantics: use Database/Persistence;
- the bytes are deterministic application build inputs or browser assets: use
  the Asset Platform;
- request bodies must be authenticated and validated: use an upload/HTTP
  ingress boundary before any storage decision;
- content must be publicly delivered: use a separately governed HTTP/CDN
  delivery policy; or
- the requirement is backup, replication, retention, or disaster recovery:
  use a separately owned operational system.

## Model logical identities, not paths or URLs

Choose an opaque logical object identity for the application concept. Do not
embed a filesystem path, URL, bucket, provider key, endpoint, credential,
tenant secret, or environment-variable name. Keep the logical namespace
bounded and aligned to one application-owned purpose.

Mapping a logical identity to a provider locator is adapter work. The mapping
must be deterministic, validated, bounded, and unable to escape the adapter's
explicit authority. A content digest identifies exact bytes under one named
algorithm; it does not become a logical identity, provider key, authorization
decision, or durability proof.

## Select namespaces and bounds

For each profile, decide before an effect:

- the logical namespace purpose and identity grammar;
- maximum object and metadata sizes;
- streaming chunk and total-byte bounds;
- permitted operation kinds;
- duration, cancellation, and resource limits;
- whether listing is supported and its page/result bounds; and
- which content-digest algorithms and media-type evidence are accepted.

Do not create an unbounded catch-all namespace or use provider limits as the
only application bound. Keep customer metadata and tenant identifiers out of
Safe diagnostics.

## Create, replace, delete, and uncertainty

Choose the mutation intent explicitly:

- **create-only** rejects an existing object;
- **conditional replacement** binds to accepted version or concurrency
  evidence;
- **version-matched deletion** avoids removing a newer object; and
- any broader mutation requires its own explicit policy and evidence.

Never document an unqualified write as safe replacement. Handle known
completion, known failure, conflict, absence, rejection, unavailability, and
uncertain outcome separately. An uncertain result means the effect may have
occurred; do not call it success or automatically retry it. Application policy
owns reconciliation and user-visible consequences.

## Streaming, digest, size, and cleanup

Plan for bounded streaming instead of universal whole-object buffering. Define
who owns the source and destination stream, when ownership transfers, how
backpressure is expressed, when content length is known, and how the digest is
computed and verified without double-consuming data.

Validate declared and observed size before accepting success. Bind checksums to
their exact bytes and named algorithm. Treat provider checksums and ETags only
according to documented provenance; do not assume they are universal content
digests.

Every operation must close or abort exactly as its lifecycle requires. Cleanup
must cover success, expected outcomes, cancellation, primary failure, partial
provider progress, and cleanup failure. Preserve both primary and cleanup
truth. Do not depend on destructors, shutdown hooks, or a later request.

## Review provider capabilities

Before selecting an adapter, review its declared support for:

- create-only and conditional operations;
- atomic visibility and replacement;
- durability and acknowledgement meaning;
- read-after-write and listing consistency;
- version/concurrency token scope;
- metadata preservation;
- listing order, pagination, and continuation behavior;
- cancellation and abort behavior; and
- cleanup after partial or uncertain operations.

Reject an unsupported requirement. Do not emulate it invisibly or convert a
provider-specific accident into a neutral guarantee.

## Diagnostics and logging

Log only accepted bounded evidence. Suitable future fields may include a closed
operation kind, declared capability, byte/count limit, digest algorithm,
generic outcome category, and provenance state.

Do not log object bytes, raw logical identifiers, provider keys, filesystem
paths, URLs, credentials, signing material, customer metadata, tenant
identifiers, arbitrary provider errors, or response bodies. Hashing a value
does not automatically make it Safe. Keep operational evidence distinct from
authorization, audit durability, and business truth.

## Persistent-worker checklist

- Is every capability and collaborator supplied explicitly?
- Is mutable state scoped to one operation?
- Are handles, sockets, temporary files, buffers, callbacks, credentials, and
  metadata detached before return?
- Do success, cancellation, failure, and cleanup-failure paths retire state?
- Can sequential, overlapping, and reentrant calls remain isolated?
- Does a result retain only immutable detached evidence?
- Is there no static registry, current object, ambient root, or request
  singleton?
- Can cleanup complete without destructor or shutdown-hook reliance?

## Local-development containment

The first adapter is Accepted as a separately optional bounded local-
filesystem implementation. Supply one exact local root explicitly. Use neither
the current working directory, home directory, nor operating-system temporary
directory as an implicit production root.

Tests must cover traversal, absolute paths, separator ambiguity, reserved
names, device paths, alternate data streams, link/reparse escape, case-folding
collisions, mutation-time replacement of path components, and cleanup-time
containment. Never scan a directory to discover configured objects or select a
newest file. Document platform limitations rather than promising identical
behavior everywhere.

## Testing and adversarial checklist

Phase 21B contract tests cover the inert values and relations below. Phase 21C
build tests cover deterministic profile/artifact/currentness/publication and
publication cleanup. Phase 21D adapter tests cover the object effects and
operation cleanup below; accepted runtime tests preserve them:

- create-only success and already-existing outcomes;
- conditional replace/delete success and conflict;
- known failure versus uncertain mutation;
- empty, minimum, maximum, and over-limit objects and metadata;
- short reads, truncated writes, incorrect lengths, and digest mismatch;
- backpressure, cancellation, close, abort, and cleanup failure;
- provider evidence that is malformed, incomplete, or capability-inconsistent;
- bounded pagination, opaque continuation evidence, and ordering;
- traversal, link/reparse, alternate-stream, case, and race-style containment
  attacks for the local adapter;
- sequential, overlapping, reentrant, and persistent-worker isolation;
- nondisclosure in messages, structured diagnostics, and inspection; and
- unchanged behavior when optional Storage packages are absent.

Use deterministic fixtures and directly supplied collaborators. Do not contact
a real provider, depend on test order, use ambient credentials, sleep for
correctness, or retain state across cases unless the later acceptance profile
explicitly authorizes it.

## Conceptual adapter/runtime example - not executable

The following is a semantic sequence, not PHP, configuration syntax, a command,
or a promised API:

```text
application selects logical identity and create-only intent
application authorization permits that exact bounded operation
exact-current profile and directly supplied adapter are composed
bounded source chunks are transferred while size and digest evidence are checked
adapter reports known completion, known failure, conflict, or uncertainty
operation closes or aborts and retires every owned resource
application handles the typed outcome without hidden retry or public delivery
```

Phase 21B implements only the named inert request, result, evidence, and stream
contracts. Phase 21C implements only deterministic build-time evidence and
contained artifact publication; neither performs these object-operation steps.

## Review checklist

- Is Storage the correct capability rather than cache, database, or assets?
- Are logical identity, namespace, provider locator, content evidence,
  concurrency evidence, authority, and authorization distinct?
- Are mutation intent and uncertain outcomes explicit?
- Are all bytes, metadata, list results, durations, and resources bounded?
- Are streaming ownership, cancellation, close, abort, and cleanup documented?
- Are provider guarantees declared honestly?
- Does local mapping prove containment without ambient roots or scanning?
- Are diagnostics and inspection nondisclosing?
- Are Phase 21G and 21H labeled Accepted, with the time-bound audit and
  exact checkpoint distinguished from cloud or production certification?
