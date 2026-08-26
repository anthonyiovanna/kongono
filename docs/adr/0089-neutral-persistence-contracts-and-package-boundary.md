# ADR 0089: Neutral Persistence Contracts and Package Boundary

- **Status:** Accepted
- **Date:** 2026-08-25

## Context

Phase 7A accepted Deterministic Persistence as an optional upper capability over
the completed Phase 6 Data Platform. Direct parameter-bound SQL, immutable SQL
plans, migrations, generated Data Mappers, and direct repositories must remain
independently usable. Before any persistence runtime can be designed, later
packages need a small vocabulary that states identity, change, lifecycle,
concurrency, outcome, and failure semantics without importing database or
runtime authority.

## Decision

Create the Experimental `kongono/persistence` package with dependencies only on
`kongono/api-metadata`, `kongono/diagnostics`, and PHP. No existing package
depends on it, so the twenty-eight-package graph remains acyclic.

The package defines exact operation-local `EntityIdentity` values and opaque
`ConcurrencyToken` values; closed entity, context, change, and flush states;
immutable value-free `PersistenceChange`, `ChangeSet`, and `FlushResult`
records; and a six-method read-only-plus-retire `PersistenceContext` interface.
The interface is a contract only. Later implementations must be operation-
scoped, discard operation-owned domain references on retirement, and leave only
state observation safe after failure or retirement.

`FlushResult` represents only no-change, atomic applied, or optimistic-conflict
outcomes. Operational, lifecycle, cleanup, combined, or uncertain failures use
the package exception boundary instead of masquerading as a result. Identity
and concurrency contents remain opaque, exact, non-stringifying, and excluded
from diagnostic presentation. The closed package diagnostic context contains
only the Safe package, failure-category, and remediation tokens.

Phase 7B implements no concrete persistence context, identity map, snapshot
engine, unit of work, mapping source/model/artifact, generator, hydrator,
extractor, database executor, transaction helper, relationship, aggregate,
repository, query API, command, loader, Composition integration, application
skeleton, or provider behavior. Phase 7C is not begun.

## Consequences

- Later upper packages can depend on one neutral, inspectable vocabulary without
  creating a lower-layer cycle.
- Values and outcomes are bounded, immutable, deterministic, and suitable for
  maximum static analysis, but they perform no persistence work.
- Applications may continue using every direct Phase 6 path without installing
  or implementing a persistence runtime.
- All Phase 7B types remain Experimental except the Public package exception
  marker and Internal diagnostic/base-exception infrastructure.

## Rejected alternatives

- Depending directly on Database, SQL, Mapping, Database Application,
  Composition, Kernel, DI, or HTTP would mix neutral vocabulary with later
  execution or integration authority.
- Implementing an identity map, unit of work, flush engine, or transaction
  helper now would decide Phase 7 runtime behavior before its authorized phase.
- Runtime Reflection, scanning, proxies, Active Record, service location, magic
  properties, serialization hooks, and hidden lazy I/O would violate Kongono's
  deterministic and explicit boundaries.
- Exposing raw identities, tokens, fields, SQL, parameters, provider messages,
  or paths through diagnostics would violate the nondisclosure contract.

## Related material

- [ADR 0087: Explicit Persistence Model and Compile-Time ORM Boundary](0087-explicit-persistence-model-and-compile-time-orm-boundary.md)
- [ADR 0088: Operation-Scoped Identity, Change Tracking, and Transaction Authority](0088-operation-scoped-identity-change-tracking-and-transaction-authority.md)
- [Deterministic Persistence](../architecture/deterministic-persistence.md)
- [Phase 7 roadmap](../architecture/phase-7-roadmap.md)
- [Package layout](../engineering/package-layout.md)
