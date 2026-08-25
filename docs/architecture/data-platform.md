# Deterministic Data Platform

## Purpose and current status

Phase 6 is the Deterministic Data Platform milestone. Phase 6A accepts its
layered doctrine in [ADR 0077](../adr/0077-layered-data-platform-and-explicit-sql-boundary.md)
and [ADR 0078](../adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md).
Phase 6B accepts the exact low-level contract in
[ADR 0079](../adr/0079-neutral-database-session-and-provider-capability-contract.md)
and [ADR 0080](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md).
Phase 6C implements that narrow boundary and records the provider proof in
[ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md).
Phase 6D implements the bounded immutable plan and PostgreSQL 17 compiler layer
accepted by [ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md).
Phase 6E implements deterministic typed migrations and PostgreSQL 17 execution
under [ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md).
Phase 6F implements optional compile-time mapping and generated typed data
access under [ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md).
Phase 6G implements generated data-application integration and operation-owned
sessions under [ADR 0085](../adr/0085-generated-data-application-and-operation-owned-sessions.md).
Phase 6H accepts the whole-platform external evidence and checkpoint boundary
under [ADR 0086](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md).

Current Kongono has twenty-seven production packages. The Data Platform slice is
limited to the neutral low-level database contract, one internal PostgreSQL PDO
adapter, immutable version-1 SQL plans, one stateless PostgreSQL 17 compiler,
an explicit typed migration catalog/planner, a PostgreSQL 17 migration
compiler/executor, the optional Experimental mapping build package, the outer
database-application integration package, focused tests, and an isolated
external acceptance harness. Mapping output is derived and not checked in.
There is no framework data command, ORM, pooling, migration startup, deployment
entrypoint, runtime telemetry, automatic retry, or further provider.

## Why the platform is layered

Connectivity, SQL, migrations, mapping, and runtime ownership solve different
problems and carry different portability, failure, security, and lifecycle
contracts. Treating them as one ORM monolith would hide which layer owns a
decision and make the lowest common denominator look more portable than it is.

The accepted conceptual dependency direction is:

```text
composition and operation-lifecycle integration
    -> optional generated mapping and repository ergonomics
        -> schema state and migration planning/execution
            -> SQL/query/command plans and dialect compilation
                -> connectivity, execution, results, provider failure adaptation
```

The diagram is conceptual, not a package graph. Higher layers may depend on
accepted lower-layer contracts, but a lower layer does not acquire ORM or
application-lifecycle responsibility. Later authenticated phases select actual
package boundaries and APIs.

## Explicit SQL and provider capabilities

Raw SQL remains a first-class, explicit, reviewable escape hatch. Executable
values use parameter binding and are never interpolated into SQL strings.
Identifiers cannot use value parameters; a later accepted phase must define a
validated identifier model before identifiers can be composed.

Higher-level plans must identify their required provider or dialect
capabilities. Driver-neutral behavior is claimed only when proved. Dialect-
specific compilation and provider-native behavior remain visible, and
unsupported capabilities fail closed. Phase 6C proves PostgreSQL 17.2 through
PDO `pdo_pgsql` as the first profile; it does not select a SQL
grammar, dialect compiler, universal PDO adapter, or broader support matrix.

## Implemented Phase 6C low-level boundary

The Experimental neutral package is `kongono/database` under
`Kongono\Database\` and depends only on PHP `>=8.5.0 <8.6.0`,
`kongono/api-metadata @dev`, and `kongono/diagnostics @dev`. The separate
Experimental adapter is `kongono/database-pdo-pgsql` under
`Kongono\DatabasePdoPgsql\` and depends on PHP, `ext-pdo`,
`ext-pdo_pgsql`, and `kongono/database @dev`. The neutral package never depends
on the adapter. Neither package depends on Kernel, Composition, Dependency
Injection, Application Graph, HTTP, developer-intelligence roles, or another
Data Platform layer. Phase 6G's outer package owns composition integration while
the neutral and provider packages remain unchanged.

Version 1 separates an application-lifetime `DatabaseConnector` from fresh
operation-owned `DatabaseSession` values. `DatabaseExecutor` accepts immutable
`DatabaseStatement` and returns `RowStream|CommandResult` according to an
explicit `Rows` or `Completion` expectation. Sessions report exact canonical
capabilities and create explicit `DatabaseTransaction` values; no native handle,
DSN, credential, config array, global default, callback transaction helper, or
ambient current transaction crosses the neutral API.

Raw SQL is final-readonly input: non-empty UTF-8, no NUL, and at most 1,048,576
bytes. The neutral layer does not parse, rewrite, normalize, format, classify,
grammar-check, or interpolate it. Zero to 65,535 named parameters retain exact
caller order. Names contain 1–128 ASCII bytes matching
`[A-Za-z_][A-Za-z0-9_]*`, omit the colon, and are exact-byte unique.
`DatabaseValue` accepts only `Null`, `Boolean`, 64-bit `Integer`, finite `Float`,
valid-UTF-8 `Text`, and `Binary`; Text and Binary are limited to 16,777,216
bytes. Other values require explicit caller conversion or later codecs.

Session-reported capabilities are exactly `Transactions`,
`ReadOnlyTransactions`, `IsolationReadCommitted`,
`IsolationRepeatableRead`, `IsolationSerializable`, and `AffectedRows`.
Unsupported requested behavior fails before partial work where provider
semantics allow that determination.

Rows are forward-only and single-pass. One stream may be active per session or
transaction. Columns preserve zero-based ordinals, exact provider labels,
duplicates, and byte-distinct case variants. Labels are valid UTF-8 without NUL,
at most 1,024 bytes, with at most 4,096 columns. Row lookup by exact label
requires one match; absent or duplicate labels fail. Text and Binary cells are
limited to 16,777,216 bytes. Completion results may return a nullable affected
row count without generated-key or universal affected-row semantics.

Transactions select isolation `Default`, `ReadCommitted`, `RepeatableRead`, or
`Serializable` and access `ReadWrite` or `ReadOnly`. State is `Active`,
`Committed`, `RolledBack`, or `Failed`. One transaction may be active per
session; nesting and direct session execution while active fail. Commit and
rollback require no active stream and are one-attempt terminal operations.
Close releases resources in reverse order, attempts rollback once when needed,
keeps failed cleanup visible, and preserves both primary and cleanup causes.

There is no automatic connection, statement, transaction, commit, rollback,
serialization, or deadlock retry. One session is not shared concurrently across
operations or fibers. Destructors are defensive only; no global, static,
thread-local, fiber-local, or process-lifetime state is authoritative.

The neutral marker family categorizes validation, session state,
unsupported capability, connection, binding, execution, result decoding,
transaction begin/commit/rollback, and cleanup. Phase 6C assigns the closed
`KGN-DB-1001` through `KGN-DB-1011` diagnostic family and corresponding
exceptions. Native errors remain internal previous causes only;
safe output excludes connection details, SQL, identifiers, values, cells,
provider/native details, transaction contents, and topology.

The first adapter keeps PDO internal, uses exception mode, native prepares
where the accepted profile supports them, explicit parameter types, and no
emulated-placeholder rewriting. It establishes required session behavior and
releases native references within the operation-owned graph. Its external proof
uses the caller-supplied PostgreSQL 17.2 binaries, loopback only, ephemeral
external storage and credentials, and complete cleanup. It assumes no installed
service, remote network, embedded credential, permanent configuration change, or
normal-runtime database startup.

## Resource and transaction ownership

Application-owned configuration and factories are separate from
operation-owned connection leases, statements, cursors, transactions, and
result streams. These names describe Accepted direction, not current PHP APIs.

Later contracts must define acquisition, release, collection versus streaming,
bounds, concurrency, and failure behavior. Cleanup runs deterministically in
reverse ownership order and preserves primary and cleanup failures. Uncertain
cleanup remains visible; a persistent worker must not silently reuse uncertain
state.

Transactions are explicit control-flow and resource boundaries. Begin, commit,
rollback, nesting or savepoints, unsupported behavior, retry ownership, and
failure composition require exact later contracts. Kongono does not
automatically retry arbitrary application callbacks, hide partial external
side effects, or imply distributed transaction guarantees.

## Persistent-runtime isolation

Sequential, overlapping, nested, fiber-based, and coroutine-based operations
must remain isolated without an ambient current connection or transaction.
No transaction, cursor, bound parameter, entity, identity map, mutable query
builder, or result stream may leak across operations.

Static facades, global connection registries, service location, thread-local or
fiber-local authority, model-side infrastructure discovery, hidden property-
read I/O, and property-hook dependency resolution are rejected defaults. The
Kernel stays lifecycle-neutral. Existing generated composition, Web
Application Kernel-operation ownership, Web Runtime delivery ownership, and
PHP-CGI front-controller ownership remain unchanged.

## Deterministic plans and generated mapping

Discovery, metadata validation, mapping analysis, and generation should happen
before production request execution where practical. Derived SQL plans,
migration plans, and mapping artifacts must be versioned, bounded, content-
identified, inspectable, reproducible, and rejected when missing, stale,
partial, mixed, ambiguous, tampered, or unsupported.

Artifacts are derived evidence, never authority. Their authoritative sources,
formats, publication, invalidation, and currentness rules belong to later
accepted slices. Production requests do not scan the filesystem, discover
entities, reflect arbitrary application classes, generate proxies, use `eval`,
or mutate generated output.

The optional ergonomic mapping direction is Data Mapper, using explicit
metadata compiled to readable typed ordinary PHP hydrators and extractors plus
explicit repository/query interfaces. Active Record is not the core model.
Domain objects do not require a framework base class or magic persistence
methods.

Hidden lazy loading and runtime-generated proxy subclasses are rejected as the
default. Relationships use explicit fetch plans. Any later lazy mechanism must
make its type, bounds, lifecycle, failure, concurrency, and serialization
behavior visible and separately accepted.

## Schema and migrations

Schema and migration work is operational, deterministic, and separate from
model side effects. Phase 6E defines the caller-owned typed catalog as source
authority, canonical order and identities, exact-prefix history, explicit
destructive review, one-attempt advisory locking, and PostgreSQL 17 transaction
and failure behavior.

Live database introspection cannot silently become authority. Automatic
rollback is not universally promised; reversibility exists only where explicit
operations and provider semantics prove it. Phase 6E selects no migration file
format, command, live-schema diff, or provider beyond PostgreSQL 17.

## Diagnostics, security, and disclosure

Actionable diagnostics must not disclose connection strings, credentials,
tokens, raw bound values, private SQL, provider payloads, database or host
names, internal topology, or customer data. Any Safe field requires explicit
package-owned classification under the existing diagnostics doctrine.

Potential later Safe facts include bounded parameter counts, provider-
capability names, operation categories, plan or source identities, transaction-
state categories, and fixed remediation. Phase 6A adds no secret detector,
sanitizer, logger, tracer, incident store, telemetry exporter, or new
diagnostic surface.

## Developer experience and performance

The convenient path must remain explainable through precise types, actionable
diagnostics, readable SQL and plans, generated ordinary PHP, an explicit raw-
SQL escape hatch, deterministic workflows, visible transactions and cleanup,
and testable boundaries. Provider-independent fakes are appropriate only where
their semantics are truthful.

No zero-overhead, fastest-ORM, universal-portability, or superiority claim is
accepted. Performance evidence must name the workload, PHP/runtime/provider
versions, statements, rows, memory and allocations where observable, latency
distribution, and warm or cold conditions. The architecture permits later
prepared-statement reuse, batching, streaming, projection, explicit fetch
plans, and generated mapping without promising them now.

Chie may later explain accepted material, Hantei may later prove a fixed
profile, and Oshie may later teach accepted topics. They do not define,
implement, approve, migrate, mutate, or certify Phase 6A.

## Accepted Phase 6D SQL-plan boundary

Phase 6D adds Experimental `kongono/database-sql` and
`kongono/database-sql-pgsql` above the unchanged low-level packages. The first
owns immutable bounded plan structure, capabilities, and diagnostics. The
second compiles only that accepted structure for PostgreSQL 17 and performs no
I/O. Every value occurrence becomes an ordered named parameter, including
limit and offset. Explicit all-row intent is source-visible. Raw
`DatabaseStatement` remains supported for grammar outside version 1.

The layer adds no schema or migration behavior, mapping artifact, repository,
proxy, Application Graph/composition integration, provider service, production
runtime behavior; Phase 6E remains a separate higher layer. Kongono remains pre-1.0 and is not
production-ready.

## Accepted Phase 6E migration boundary

Phase 6E adds `kongono/database-migration` and
`kongono/database-migration-pgsql`. The first owns the explicit immutable source,
closed schema-operation/type vocabulary, canonical identities, exact-prefix
history, planner, policy, results, and diagnostics. The second owns PostgreSQL
17 DDL, configured reserved history storage, transaction-scoped advisory locks,
and one-attempt transactional execution through the existing neutral session.

This layer performs no discovery, file execution, live-schema inference,
automatic retry/down, mapping, repository, ORM, runtime composition, or
production provider startup.

## Accepted Phase 6F mapping boundary

Phase 6F adds optional Experimental `kongono/database-mapping`. One explicit
application-owned inert source is validated against exact final readonly record
classes at build time. A detached canonical model and provenance-bearing
artifact generate ordinary typed mapper/repository PHP. Current validation is
non-executing and publication is atomic, content-addressed, idempotent, and
no-clobber. Generated output is derived and is not checked in.

Repositories expose only find, insert, update, and delete through caller-owned
executor and dialect-compiler capabilities. Raw parameter-bound statements and
typed SQL plans remain first-class. The package adds no ORM, runtime record
Reflection, proxy, identity map, unit of work, automatic transaction/retry, or
session lifecycle. Phase 6G's outer package owns exact-current loading and
composition integration.

## Accepted Phase 6G application integration

Phase 6G adds Experimental `kongono/database-application` as the provider-neutral
outer layer. Its workflow loads exact-current generated data access before the
accepted Composition workflow so symbol provenance binds the generated
repository file. Each generated operation constructs one
`OperationDatabaseSession` from the explicit connector input and closes it once
through reverse cleanup. The profile has no application root and exactly one
typed operation root. The optional data-minimal skeleton selects PostgreSQL 17
explicitly without moving provider choice into the package.

## Accepted Phase 6H external closure

Phase 6H adds no package or production behavior. The root-owned
`composer acceptance:phase6` harness is the whole-platform acceptance owner for
all eight packages and the optional copied data-minimal workflow. It proves
PostgreSQL 17 session isolation, transaction visibility and terminals,
deterministic migrations, generated repository and operation lifetimes,
exact-current fail-closed state handling, nondisclosure, and complete owned
cleanup.

The same run emits the version-1 four-scenario local observational measurement
baseline after 10 warmups and 50 `hrtime(true)` nanosecond samples per scenario.
Nearest-rank summaries are evidence, not thresholds or reproducibility
identities. Phase 6H adds no runtime telemetry, SLA, portability,
production-readiness, zero-overhead, or competitor claim.

## Related material

- [ADR 0077](../adr/0077-layered-data-platform-and-explicit-sql-boundary.md)
- [ADR 0078](../adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md)
- [ADR 0079](../adr/0079-neutral-database-session-and-provider-capability-contract.md)
- [ADR 0080](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md)
- [ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md)
- [ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md)
- [ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md)
- [ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md)
- [ADR 0085](../adr/0085-generated-data-application-and-operation-owned-sessions.md)
- [ADR 0086](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md)
- [Phase 6 external acceptance](phase-6-external-acceptance.md)
- [Database performance baseline](../engineering/database-performance-baseline.md)
- [Database application integration](database-application-integration.md)
- [Low-level database contract](database-contract.md)
- [Immutable SQL plans](database-sql-plans.md)
- [Deterministic database migrations](database-migrations.md)
- [Compile-time database mapping](database-mapping.md)
- [Database contracts engineering guide](../engineering/database-contracts.md)
- [Phase 6 roadmap](phase-6-roadmap.md)
- [Application composition and build](application-composition-and-build.md)
- [Runtime model](runtime-model.md)
- [Architectural doctrine](../doctrine.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
