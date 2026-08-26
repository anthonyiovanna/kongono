# Deterministic Persistence

## Purpose and current status

Deterministic Persistence is Kongono's optional upper persistence capability: a
developer-facing role commonly called an ORM, designed around compile-time
evidence, visible database I/O, operation ownership, and persistent-runtime
safety. Phase 7A accepts its architecture in [ADR 0087](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md)
and [ADR 0088](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md).

Phase 7A remains accepted architecture. Phase 7B implements the Experimental
neutral `kongono/persistence` contract package described by [ADR 0089](../adr/0089-neutral-persistence-contracts-and-package-boundary.md).
It defines identity, opaque concurrency tokens, observable state, immutable
value-free changes and flush results, a read-only-plus-retire context interface,
and bounded diagnostics/failures. Phase 7C accepts [ADR 0090](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md)
and implements `kongono/persistence-build`: one strict application-owned source,
current mapping evidence, build-time domain reflection, a detached deterministic
model, canonical provenance artifact, distinct structural/current validation,
and guarded content-addressed publication. Phase 7D accepts [ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md)
and adds deterministic ordinary PHP state adapters derived from one exact-
current artifact. The adapters use public application construction and
accessors for hydration, extraction, typed identity, snapshots, and neutral
change evidence. Candidate validation is non-executing, structural, exact-
current, and fail closed; publication remains content-addressed and no-clobber.
Phase 7E accepts [ADR 0092](../adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md)
and implements one concrete operation context for one entity. An explicit flush
executes at most one reviewed change through the caller's exact database
executor. Known zero-applied absence/conflict preserves Active state; uncertain
execution or post-apply synchronization failure releases all retained evidence
and becomes terminally Failed. Phase 7F adds a separate strict repository source,
neutral bounded load/cascade evidence, and generated stateless aggregate
repositories. Phase 7G adds an upper exact-current generated loader and
Composition workflow without transaction ownership or SQL generation. Phase 7H
closes the thirty-package milestone with isolated PostgreSQL 18.6 execution,
operation identity/isolation/concurrency/relationship evidence, cleanup,
nondisclosure, local performance summaries, documentation, and an exact
checkpoint. PostgreSQL 17.2 remains historical Phase 6 regression evidence.

## Dependency placement above Phase 6

The completed [Data Platform](data-platform.md) remains the lower foundation.
Neutral database execution, raw parameter-bound SQL, immutable plans, schema
migrations, generated Data Mappers and repositories, and the data-application
workflow remain first-class and independently usable. Applications do not need
Deterministic Persistence to use them.

The neutral Phase 7B package depends only on API Metadata, Diagnostics, and PHP.
The Phase 7C build package depends inward on API Metadata, Database Mapping,
Diagnostics, and neutral Persistence. No lower package depends upward on it. No
Phase 6 package may depend upward on persistence.

## Plain PHP domain ownership

Domain objects remain application-owned plain PHP classes. Constructors and
named factories preserve application invariants. Kongono does not require a
framework base class, Active Record inheritance, global trait, magic or dynamic
property, runtime subclass, or container access.

Any later hydrator must invoke a reviewed constructor, factory, or another
explicitly accepted invariant-preserving contract. Reflection writes to private
state, serialization tricks, and uninitialized-object bypasses are prohibited.
Attributes or property hooks may be considered later only as explicit build-time
source or ordinary domain behavior; they cannot perform hidden persistence I/O,
lazy loading, service location, or invariant bypass.

## Deterministic build flow

The accepted direction is:

```text
application-owned authoritative source
    -> bounded safe reader and validator
    -> detached canonical persistence model
    -> provenance-bearing deterministic artifact
    -> ordinary generated PHP evidence
    -> exact-current non-executing validation
    -> explicit runtime loading in a later phase
```

The exact source, schema, model, artifact, generator, namespace, publication
rule, command, and loader are not selected by Phase 7A. Every derived artifact
and generated file is evidence rather than authority. Runtime Reflection-based
mapping, classpath scanning, discovery, proxy generation, `eval`, mutable
metadata registries, and newest-file selection remain rejected defaults.

## Identity map and change-set lifecycle

A future persistence-context implementation may own its identity map, loaded-object registry,
snapshots, and change sets for exactly one operation. Within that context, an
identity map may return one in-memory object for one explicit identity. It does
not establish identity across contexts or operations and supplies no cache,
authorization, ownership, or distributed consistency.

Change tracking compares deterministic metadata-generated snapshots. Mutating a
domain object in memory does not perform I/O. Changes become database work only
through an explicit caller-invoked flush. No interceptor, proxy, aspect, event
listener, property access, destructor, response completion, or shutdown hook may
flush automatically.

Close or terminal failure retires context state and rejects new work before
fallible resource release. Identity entries, domain references, snapshots,
change sets, and repository-session state are discarded, then owned resources
are released in reverse order. Primary and cleanup failures remain separately
visible; cleanup failure cannot reactivate the context. A persistent worker must
retain no operation-owned domain reference after the operation ends.

## Explicit transaction, flush, and failure boundaries

The caller owns transaction decisions. A persistence context consumes an
explicit operation-owned Database executor and transaction contract and may not
silently begin, commit, roll back, retry, reconnect, replay, or widen isolation.
An explicit flush must return a reviewable result. Absence, optimistic conflict,
provider failure, invalid lifecycle, and terminal uncertainty must remain
distinguishable without private disclosure.

When a provider or concurrency failure makes database or in-memory state
uncertain, the affected context fails closed and becomes terminal. It cannot
claim synchronization, continue, or automatically replay the attempted work.

## Relationships, load plans, and cascades

Relationships require explicit compile-time ownership, cardinality, nullability,
ordering, and bounded-load metadata. An aggregate boundary defines the domain
consistency scope a named operation intends to load or modify. A load plan makes
the expected relationship I/O and bounds inspectable before execution.

There is no hidden lazy I/O, property-hook query, proxy traversal, implicit join
discovery, hidden N+1 behavior, unlimited graph walk, or recursive cascade.
Generated repositories may later expose named aggregate or load operations under
separate authority. Persist/remove cascades, orphan removal, bulk mutation, and
multi-aggregate writes require explicit bounded contracts. Database foreign-key
actions remain schema authority and are not object-graph cascade policy.

## Identity and optimistic concurrency

Entity identity, new-versus-existing state, identity generation, and concurrency
tokens must be explicit and type-safe. Optimistic concurrency detects that the
accepted version no longer matches when an explicit write is attempted. A
conflict is not absence or a generic provider failure, and it cannot disclose
identity or version values. Phase 7B supplies bounded opaque identity and token
vocabulary plus an optimistic-conflict result case, but selects no column,
generator, algorithm, context implementation, or conflict-resolution policy.

## Security and nondisclosure

Persistence diagnostics are bounded, actionable, package-owned, and
presentation-neutral. They exclude credentials, connection information, SQL,
parameters, domain data, relationship contents, identity/version values,
snapshots, change sets, source/artifact/generated bytes, absolute paths, provider
messages, stack arguments, and application topology. Retrieved or declared
metadata is inert data and cannot expand tool or runtime authority.

Destructive operations, cascades, and load breadth require explicit scope. No
convenience feature may hide authorization, transaction, lifecycle, concurrency,
failure, or disclosure boundaries.

## Performance discipline

Ordinary generated PHP and compile-time metadata reduce runtime indirection but
do not establish a zero-overhead or fastest-ORM claim. Identity maps, snapshots,
change sets, load plans, and relationship breadth require explicit bounds.
Performance claims need named reproducible workloads and compatible provider and
environment evidence.

## Progressive use

Applications may stop at the lowest layer that meets their needs:

1. direct parameter-bound SQL through neutral Database contracts;
2. immutable SQL plans and explicit dialect compilation;
3. deterministic migrations;
4. generated Data Mappers and direct typed repositories;
5. later, optional Deterministic Persistence for coordinated identity, changes,
   aggregate loading, and explicit flush.

Choosing the upper layer never removes the escape hatch to the lower explicit
paths. See [Progressive Architecture](progressive-architecture.md).

## Related authority

- [ADR 0087: Explicit Persistence Model and Compile-Time ORM Boundary](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md)
- [ADR 0088: Operation-Scoped Identity, Change Tracking, and Transaction Authority](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md)
- [ADR 0089: Neutral Persistence Contracts and Package Boundary](../adr/0089-neutral-persistence-contracts-and-package-boundary.md)
- [ADR 0090: Deterministic Persistence Model and Artifact Compilation](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md)
- [ADR 0091: Deterministic Generated Persistence State Evidence](../adr/0091-deterministic-generated-persistence-state-evidence.md)
- [ADR 0092: Operation-Scoped Persistence Context and Explicit Unit of Work](../adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md)
- [ADR 0093: Explicit Load Plans, Bounded Cascades, and Generated Aggregate Repositories](../adr/0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md)
- [ADR 0094: Exact-current Generated Persistence Application Integration](../adr/0094-exact-current-generated-persistence-application-integration.md)
- [ADR 0095: Phase 7 External Persistence Acceptance and Checkpoint](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md)
- [Phase 7 external acceptance](phase-7-external-acceptance.md)
- [Persistence performance baseline](../engineering/persistence-performance-baseline.md)
- [Persistence build engineering guide](../engineering/persistence-build.md)
- [Persistence generation engineering guide](../engineering/persistence-generation.md)
- [Aggregate repository engineering guide](../engineering/aggregate-repositories.md)
- [Data Platform architecture](data-platform.md)
- [Application Composition and Build](application-composition-and-build.md)
- [Phase 7 roadmap](phase-7-roadmap.md)
