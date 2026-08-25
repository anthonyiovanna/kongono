# ADR 0078: Deterministic Data Plans and Operation-Scoped Persistence

- **Status:** Accepted
- **Date:** 2026-08-24
- **Phase:** 6A

## Context

ADR 0077 accepts a layered Data Platform with explicit SQL and no ambient
database authority. Persistent runtimes add another constraint: a process may
handle sequential, overlapping, nested, fiber-based, or coroutine-based work,
so database state cannot rely on process teardown or an implicit current
connection.

Phase 6A must define resource, transaction, deterministic-plan, migration, and
disclosure direction before any provider or runtime implementation is selected.

## Decision

### Operation-scoped resource ownership

Application-owned configuration and factories remain separate from
operation-owned leases, statements, cursors, transactions, and result streams.
Later contracts must define bounded resource use and whether results are
streamed or collected.

Operation resources are closed, released, or rolled back deterministically.
Cleanup proceeds in reverse ownership order, attempts every required release,
and preserves a primary failure together with cleanup failures. Uncertain
cleanup must remain visible rather than allowing silent reuse.

No transaction, cursor, parameter, entity, identity map, mutable query builder,
or operation result may leak across requests or other operations. Concurrency
semantics may not depend on thread-local, fiber-local, process-global, or other
ambient current state. The Kernel remains lifecycle-neutral, and existing
generated composition, Web Application, and Web Runtime ownership does not move.

### Explicit transaction semantics

Transactions are explicit control-flow and resource boundaries. A future API
must define begin, commit, rollback, nesting or savepoint support, unsupported
capability behavior, retry ownership, and primary-plus-cleanup failure
composition.

Kongono does not automatically retry arbitrary application callbacks, conceal
partial external side effects, or imply distributed transaction guarantees.
Transaction context is not ambient global state.

### Deterministic plans and generated evidence

Expensive discovery, validation, metadata analysis, mapping analysis, and code
generation should happen before production request execution where practical.
Derived SQL plans, migration plans, and mapping artifacts must be versioned,
bounded, content-identified, inspectable, reproducible, and rejected when
missing, stale, partial, mixed, ambiguous, tampered, or unsupported.

Generated artifacts are derived evidence, never authority. Each later slice
must separately select its authoritative source, artifact format, publication,
invalidation, and currentness rules.

Production request handling does not scan the filesystem, discover entities,
reflect arbitrary application classes, generate proxies, use `eval`, or mutate
generated output.

### Schema and migration direction

Schema state and migrations are deterministic operational concerns, not model
side effects. Later migration architecture must define:

- authoritative source state and canonical order;
- checksums and applied-history evidence;
- provider capability requirements;
- locking, concurrency, and preconditions;
- failure and partial-application evidence; and
- explicit review of destructive operations.

Live-database introspection cannot silently become source authority. Kongono
does not promise universal automatic rollback. A migration is reversible only
when its explicit operations and the selected provider semantics prove it.

### Diagnostics and non-disclosure

Data diagnostics must remain actionable without disclosing connection strings,
credentials, tokens, raw bound values, private SQL fragments, provider
payloads, database names, hostnames, internal topology, or customer data.

Individually classified Safe facts may include package-owned identifiers,
operation categories, provider-capability names, bounded parameter counts,
plan or source identities, transaction-state categories, and fixed
remediation. Existing diagnostic disclosure rules remain authoritative.

No automatic secret detector, sanitizer, logger, tracer, incident store, or
telemetry exporter is selected by this decision.

## Consequences

Future database work has explicit ownership and cleanup rules suitable for
persistent processes. Plans and generated mapping can remain deterministic and
reviewable, while migrations preserve provenance and partial-failure evidence.

Later phases must do more work to model provider capabilities, streaming,
cleanup, and combined failure precisely. They cannot rely on ambient state,
automatic retry, process teardown, or generated artifacts as authority.

## Rejected alternatives

- Application-shared mutable connections or operation state without an
  explicit accepted lifecycle contract.
- Ambient current transactions, implicit commit or rollback, and arbitrary
  callback retry.
- Request-time discovery, reflection, filesystem scanning, proxy generation,
  `eval`, or generated-output mutation.
- Live schema introspection as silent source authority.
- Universal rollback or distributed-transaction guarantees.
- Diagnostics containing SQL values, credentials, provider payloads, topology,
  or customer data.

## Explicit non-goals

Phase 6A implements no lease, pool, statement, cursor, result, transaction,
retry, plan, artifact, schema language, migration file, migration table,
locking mechanism, mapper, repository, provider, package, command, or runtime
integration. Phase 6B has not begun.

## Related material

- [ADR 0077: Layered Data Platform and Explicit SQL Boundary](0077-layered-data-platform-and-explicit-sql-boundary.md)
- [Data Platform architecture](../architecture/data-platform.md)
- [Phase 6 roadmap](../architecture/phase-6-roadmap.md)
- [ADR 0006: Persistent-Runtime-First Architecture](0006-persistent-runtime-first.md)
- [ADR 0013: Documentation Doctrine](0013-documentation-doctrine.md)
- [ADR 0068: Supported Application Composition and Build Boundary](0068-supported-application-composition-and-build-boundary.md)
