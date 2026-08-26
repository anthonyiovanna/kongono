# ADR 0087: Explicit Persistence Model and Compile-Time ORM Boundary

- **Status:** Accepted
- **Decision type:** Phase 7A persistence model and compile-time ORM boundary
- **Stability:** Architectural direction for an Experimental, pre-1.0 capability

## Context

Phase 6 completed Kongono's Deterministic Data Platform at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05`. Its neutral database contracts,
explicit parameter-bound SQL, immutable SQL plans, migrations, generated Data
Mappers and repositories, and operation-owned integration remain independently
useful. Applications need a path to more coordinated domain persistence without
turning those lower layers into a classic runtime-magic ORM or making framework
objects the domain model.

Kongono is persistent-runtime-first. Runtime mapping discovery, proxy creation,
hidden relation I/O, ambient state, and automatic flush would make authority,
cost, cleanup, and failure difficult to inspect. A persistence layer must instead
preserve plain PHP domain ownership, deterministic build evidence, explicit I/O,
and truthful performance and stability claims.

## Decision

Deterministic Persistence is an optional upper capability over selected Phase 6
and Composition contracts. It does not replace or deprecate neutral Database,
raw parameter-bound SQL, immutable plans, migrations, generated Data Mappers, or
direct repositories. Applications may use every lower path without installing
or invoking the future persistence capability. Lower Phase 6 packages must not
depend upward on it; a later authenticated phase must approve any package graph.

Application-owned domain objects remain plain PHP classes. Kongono will not
require framework base classes, Active Record inheritance, global traits, magic
or dynamic properties, runtime-generated subclasses, or container access.
Constructors and named factories remain the primary invariant boundary.
Hydration must use reviewed constructors, factories, or another later explicitly
accepted invariant-preserving contract. It must not mutate private state through
Reflection, serialization tricks, or uninitialized-object bypasses.
Attributes or property hooks may be evaluated later only as explicit build-time
source or ordinary domain behavior; they cannot become hidden I/O, lazy loading,
service location, persistence injection, or invariant bypass.

Persistence metadata must be deterministic, versioned, bounded, reviewable, and
compiled before runtime. A later accepted source may produce a canonical model,
provenance-bearing artifact, and ordinary generated PHP, but every derived form
is evidence rather than authority. Runtime Reflection mapping, classpath
discovery, scanning, proxy generation, `eval`, mutable metadata registries,
newest-file selection, and remote generated code are rejected defaults. Phase
7A selects no mapping source, schema, syntax, attribute, property-hook protocol,
generated namespace, package, command, or API.

Relationships must eventually be explicit compile-time metadata declaring
ownership, cardinality, nullability, ordering, and bounded load behavior.
Aggregate boundaries and load plans must make database I/O visible. A later
generated repository may expose named aggregate or load operations, but Phase 7A
selects no generic query language, magic dynamic finder, implicit join discovery,
runtime relation planner, lazy proxy, property-hook query, hidden N+1 behavior,
unlimited graph walk, or recursive cascade.

Raw SQL and immutable plans remain first-class query paths. Persistence may later
add generated named operations or typed bounded criteria only under separate
authority. It must not add SQL interpolation, ambient query context, magic method
parsing, or automatic full-table mutation.

Compile-time metadata and generated ordinary PHP are selected to reduce runtime
indirection and improve reviewability, not to claim zero overhead or superiority.
Any performance statement requires a named reproducible workload. Runtime
identity and change state must be explicitly bounded, and persistent workers
must release every operation-owned domain reference after each operation.

The intended developer experience is explicit but ergonomic: plain PHP domain
classes, reviewable metadata, generated typed repositories, visible load plans,
explicit flush and transactions, deterministic build/check, and actionable
bounded errors. Convenience must not conceal authority, lifecycle, cost,
concurrency, failure, or data disclosure.

The capability remains Experimental and pre-1.0 until later evidence says
otherwise. This decision makes no Stable API, production-readiness, portability,
performance, PHP 8.6/PHP 9, migration-compatibility, or full-framework claim.

## Consequences

- Direct Phase 6 SQL, plan, migration, mapping, and repository workflows remain
  supported without Deterministic Persistence.
- Plain application classes and their invariants remain outside framework
  ownership.
- Later phases must define and prove every source, model, artifact, generator,
  runtime contract, dependency edge, and bounded load behavior explicitly.
- Runtime convenience cannot be implemented through discovery, proxies, hidden
  I/O, private-state bypass, or automatic query behavior.
- Performance and portability claims remain evidence-bound and provider-specific.
- Phase 7A changes documentation only and begins no Phase 7B implementation.

## Rejected alternatives

- Replacing the Phase 6 Data Platform with one mandatory ORM.
- Active Record base classes or framework-owned domain objects.
- Runtime Reflection mapping, scanning, mutable registries, proxy generation,
  `eval`, or newest-file loading.
- Magic properties or methods, property-hook I/O, lazy relationship queries,
  generic dynamic finders, or implicit relation planning.
- Reflection or serialization techniques that bypass constructors and private
  invariants.
- Implicit joins, unbounded graph traversal, recursive cascades, or hidden N+1
  behavior.
- Claiming zero overhead, fastest-ORM status, portability, production readiness,
  or Stable compatibility without the required evidence.

## Related material

- [ADR 0088: Operation-Scoped Identity, Change Tracking, and Transaction Authority](0088-operation-scoped-identity-change-tracking-and-transaction-authority.md)
- [ADR 0084: Compile-Time Data Mapping and Generated Repositories](0084-compile-time-data-mapping-and-generated-repositories.md)
- [Data Platform architecture](../architecture/data-platform.md)
- [Deterministic Persistence architecture](../architecture/deterministic-persistence.md)
- [Phase 7 roadmap](../architecture/phase-7-roadmap.md)
