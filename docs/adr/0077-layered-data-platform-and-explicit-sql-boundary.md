# ADR 0077: Layered Data Platform and Explicit SQL Boundary

- **Status:** Accepted
- **Date:** 2026-08-24
- **Phase:** 6A

## Context

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e`. Kongono now has a supported
pre-1.0 application-composition slice, but it has no database package, data
runtime, provider adapter, SQL API, migration system, or mapping layer.

The next milestone needs a coherent data direction without turning one ORM into
the framework's entire persistence model, hiding provider differences, or
granting database authority through ambient state. Phase 6A therefore fixes the
architectural layers and defaults before any package or PHP type is selected.

## Decision

### Layered capability family

Phase 6 is the Deterministic Data Platform milestone. Its five conceptual
layers remain distinct:

1. low-level connectivity, statement execution, results, and native provider
   failure adaptation;
2. explicit SQL/query/command planning and dialect compilation;
3. schema state and migration planning/execution;
4. optional generated data mapping and repository ergonomics; and
5. composition and operation-lifecycle integration.

Dependencies may move from a higher layer toward accepted lower-layer
contracts. A later phase may select physical package boundaries and APIs only
after its prerequisite architecture is accepted. The Kernel does not acquire
database or ORM responsibility.

### SQL remains explicit and first-class

Raw SQL remains an explicit, reviewable, parameter-bound escape hatch. Higher
layers must not conceal the database behind a lowest-common-denominator model.
They must declare required provider or dialect capabilities and fail visibly
when a capability is unsupported.

Executable values are never interpolated into SQL strings. Identifiers cannot
use value-parameter binding, so a later phase must select an explicit validated
identifier model before identifiers can be composed. This decision selects no
SQL grammar, query-builder API, identifier type, or dialect implementation.

### No ambient database authority

Connections, transaction scopes, sessions, repositories, and mappers must
arrive through explicit composition or method boundaries. The default
architecture rejects:

- static database facades and global connection registries;
- ambient current-connection or transaction lookup;
- runtime service location;
- model methods that silently discover infrastructure;
- hidden network I/O from property reads or serialization; and
- property hooks used as invisible dependency resolution.

Property hooks may later enforce local invariants or explicit value behavior.
They do not replace constructor dependencies and do not authorize hidden I/O.

### Optional Data Mapper direction

The future ergonomic mapping layer is optional and Data Mapper-oriented. Active
Record is not the core architecture, domain objects do not inherit a framework
base model merely to persist, and magic persistence methods are not injected
into every domain object.

The preferred direction is explicit mapping metadata compiled before runtime
to readable typed ordinary PHP hydrators and extractors, with explicit
repository and query interfaces. Identity, nullability, conversion, partial
selection, relations, inheritance policy, optimistic concurrency, and change
tracking require later accepted contracts.

Hidden lazy loading and runtime-generated proxy subclasses are rejected as the
default. Relationships use explicit load or fetch plans. Any later lazy
mechanism requires visible types, bounded behavior, lifecycle ownership,
serialization rules, and separate acceptance.

### Provider and portability truthfulness

Later implementation must distinguish:

- driver-neutral contracts whose behavior is actually proved;
- dialect-specific compilation and explicit capability profiles;
- provider-native behavior exposed through intentional extension points; and
- unsupported behavior that fails visibly.

The first adapter may later be PDO-backed, but Phase 6A selects neither PDO nor
SQLite, PostgreSQL, MySQL, another provider, a package name, or a support
matrix. Provider and version support claims require real external acceptance.

### Evidence-based performance

Kongono makes no zero-overhead, fastest-ORM, or superiority claim. Later
measurements must name the workload, PHP/runtime/provider versions, statement
and row counts, memory and allocations where observable, latency distribution,
and warm or cold conditions.

The architecture keeps prepared-statement reuse, batching, streaming,
projection, explicit fetch plans, and generated mapping possible. It promises
no optimization before evidence exists.

## Consequences

Applications can use explicit SQL without abandoning the higher-layer roadmap,
and higher layers cannot erase provider truth or acquire ambient authority.
The optional mapping direction can improve ergonomics while keeping domain
objects independent of framework persistence magic.

The separation imposes more explicit contracts, capability checks, and
lifecycle documentation. Those costs are accepted because they make behavior
inspectable and portable only where evidence supports it.

## Rejected alternatives

- A monolithic ORM that owns connectivity, SQL, migrations, mapping, and
  lifecycle as one inseparable boundary.
- Static facades, global registries, ambient connections, and service location.
- String interpolation of executable values into SQL.
- Hiding provider-specific behavior behind unsupported portability claims.
- Active Record as the core model, framework base entities, hidden lazy
  loading, and runtime-generated proxy subclasses.
- Property-hook dependency lookup or invisible network I/O.
- Performance or portability claims derived from design alone.

## Explicit non-goals

Phase 6A creates no package, PHP API, provider, connection, statement, result,
SQL grammar, query builder, capability-profile type, mapper, repository,
hydrator, proxy, schema, migration, command, configuration key, artifact, or
Application Graph integration. Phase 6B has not begun.

## Related material

- [ADR 0078: Deterministic Data Plans and Operation-Scoped Persistence](0078-deterministic-data-plans-and-operation-scoped-persistence.md)
- [Data Platform architecture](../architecture/data-platform.md)
- [Phase 6 roadmap](../architecture/phase-6-roadmap.md)
- [ADR 0004: Microkernel and Capability Architecture](0004-microkernel-capability-architecture.md)
- [ADR 0006: Persistent-Runtime-First Architecture](0006-persistent-runtime-first.md)
- [ADR 0068: Supported Application Composition and Build Boundary](0068-supported-application-composition-and-build-boundary.md)
