# ADR 0092: Operation-Scoped Persistence Context and Explicit Unit of Work

- Status: Accepted
- Date: 2026-08-25
- Phase: 7E

## Context

Phase 7B established neutral persistence values, Phase 7C compiled deterministic
persistence metadata, and Phase 7D generated non-executing state evidence. A
first runtime boundary is now needed to prove explicit change execution,
operation-owned references, caller transaction authority, and terminal failure
semantics without loading generated PHP or widening into an entity manager.

## Decision

The runtime lives in the existing Experimental `kongono/persistence` package.
Creating another container-like package would split the neutral contracts from
their first bounded implementation and invite service location or ambient
session ownership. The package therefore adds one direct dependency on the
neutral `kongono/database` package; lower database packages do not depend on
persistence, and `persistence-build` remains strictly above the runtime package.

`OperationPersistenceContext` owns exactly one application entity, one explicit
`PersistenceStateAdapter`, one application-assigned identity, one initial
snapshot when managed, and one entity state. It produces at most one validated
`PersistenceChange` per flush. New entities insert, managed entities may update,
and explicitly removed managed entities delete. Multi-entity coordination,
relationships, aggregates, cascades, repositories, and bulk work are rejected.

`PersistenceStateAdapter` supplies application-value-aware but database-neutral
identity, snapshot, insertion, update, and deletion evidence. A separate
`PersistenceChangeExecutor` receives one reviewed change and the caller's exact
`DatabaseExecutor`. These capabilities are explicit and are not discovered,
registered, reflected, loaded from generated output, or retained after flush.

Flush is the only write boundary. Empty changes return `NoChanges` without
calling the executor. A non-empty flush enters `Flushing`, calls the change
executor exactly once, and interprets `Applied`, `EntityAbsent`, or
`OptimisticConflict`. Absence and conflict mean zero writes were applied and
restore `Active` while preserving pre-flush state. Applied insert/update must
recompute and verify identity and snapshot before returning Active and Managed;
applied deletion releases references and becomes Retired.

The caller owns the session and transaction. The context never begins, commits,
rolls back, closes, retries, reconnects, selects a provider, or widens isolation.
It accepts either a session or transaction solely because each implements
`DatabaseExecutor`.

An executor/provider throw after `Flushing`, or failed synchronization after an
Applied outcome, is terminally uncertain. The context becomes Failed, releases
entity, adapter, identity, snapshot, and pending evidence, then throws
`PersistenceUncertainStateException` with the original cause retained only as
protected evidence. It performs no retry, rollback, cleanup, replay, or later
synchronization. Only `state()` and idempotent `retire()` remain accepted.

Retirement is explicit and releases operation-owned references before Retired.
No destructor, shutdown handler, response completion, property access, entity
method, event, proxy, or hook performs I/O or retirement. Reentrant retirement
while Flushing fails visibly. These rules make sequential persistent-worker use
safe without relying on process teardown.

Diagnostics use fixed category and remediation tokens. Messages and Safe
context exclude entity and identity values, concurrency tokens, snapshots,
changes, SQL, parameters, provider messages, paths, topology, and application
data. The retained previous cause is not Safe presentation.

## Consequences

- One entity and one change make execution and uncertainty inspectable.
- Applications retain explicit database and transaction authority.
- Absence, optimistic conflict, and terminal provider uncertainty remain
  distinct outcomes.
- Operation-owned domain references are deterministically releasable in
  persistent workers.
- Generated-state runtime integration, repositories, relationships, cascades,
  and Composition remain deferred to later authenticated phases.

## Rejected alternatives

- A second persistence runtime container, entity manager, ambient context, or
  service locator.
- Multi-entity collections hidden behind a generic unit of work.
- Automatic transaction control, retry, refresh, merge, rollback, or replay.
- Runtime Reflection, scanning, generated-file loading, newest-file selection,
  proxying, lazy loading, or automatic flush.
- SQL generation or provider selection inside the persistence context.

## Related material

- [ADR 0088](0088-operation-scoped-identity-change-tracking-and-transaction-authority.md)
- [ADR 0089](0089-neutral-persistence-contracts-and-package-boundary.md)
- [ADR 0091](0091-deterministic-generated-persistence-state-evidence.md)
- [Persistence context engineering guide](../engineering/persistence-context.md)
- [Deterministic Persistence architecture](../architecture/deterministic-persistence.md)
