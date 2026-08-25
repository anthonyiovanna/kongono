# Database Contracts Engineering Guide

## Status and use

This guide describes the Accepted Phase 6B contract as implemented by Phase 6C.
Both `kongono/database` and `kongono/database-pdo-pgsql` are current
Experimental packages. The slice remains low-level and provider-bounded. Phase
6D adds separate plan/compiler packages without changing these contracts; see
the [SQL plan engineering guide](database-sql-plans.md).

## Package inventory

| Package | Namespace | Maturity | Direct dependencies |
| --- | --- | --- | --- |
| `kongono/database` | `Kongono\Database\` | Experimental | PHP `>=8.5.0 <8.6.0`; `kongono/api-metadata @dev`; `kongono/diagnostics @dev` |
| `kongono/database-pdo-pgsql` | `Kongono\DatabasePdoPgsql\` | Experimental | PHP `>=8.5.0 <8.6.0`; `ext-pdo`; `ext-pdo_pgsql`; `kongono/database @dev` |

The adapter depends on the neutral package; the neutral package never depends
on the adapter. Neither depends on Kernel, Composition, Dependency Injection,
Application Graph, HTTP, developer-intelligence roles, or another Data Platform
layer. Phase 6G alone owns future composition integration.

## Exact interfaces and methods

| Type | Exact version-1 surface |
| --- | --- |
| `DatabaseConnector` | `openSession(): DatabaseSession` |
| `DatabaseExecutor` | `execute(DatabaseStatement): RowStream\|CommandResult` |
| `DatabaseSession` | Executor; `capabilities(): DatabaseCapabilities`; `begin(TransactionOptions): DatabaseTransaction`; `close(): void`; `isClosed(): bool` |
| `DatabaseTransaction` | Executor; `state(): TransactionState`; `commit(): void`; `rollback(): void`; `close(): void`; `isClosed(): bool` |
| `RowStream` | Immutable ordered columns; `fetch(): ?DatabaseRow`; `close(): void`; `isClosed(): bool` |
| `CommandResult` | Immutable; `affectedRows(): ?int` |

No neutral method accepts configuration or returns PDO/native objects. Do not
add generic query magic, callback transaction helpers, global defaults, ambient
transaction access, model operations, native escape hatches, or service lookup.

## Exact enums and values

| Type | Exact ordered vocabulary |
| --- | --- |
| `ResultExpectation` | `Rows`, `Completion` |
| `DatabaseValueKind` | `Null`, `Boolean`, `Integer`, `Float`, `Text`, `Binary` |
| `DatabaseCapability` | `Transactions`, `ReadOnlyTransactions`, `IsolationReadCommitted`, `IsolationRepeatableRead`, `IsolationSerializable`, `AffectedRows` |
| `TransactionIsolation` | `Default`, `ReadCommitted`, `RepeatableRead`, `Serializable` |
| `TransactionAccess` | `ReadWrite`, `ReadOnly` |
| `TransactionState` | `Active`, `Committed`, `RolledBack`, `Failed` |

`DatabaseValue` is final readonly and exposes package-owned factories for its
six cases. `DatabaseCapabilities` is immutable, canonical, duplicate-free, and
uses exact membership. `TransactionOptions` is immutable and contains one
isolation and one access value.

## Validation bounds

| Input or result | Exact bound and invariant |
| --- | --- |
| SQL | Non-empty valid UTF-8; no NUL; maximum 1,048,576 bytes |
| Parameters | Ordered zero to 65,535 values |
| Parameter name | 1–128 ASCII bytes; `[A-Za-z_][A-Za-z0-9_]*`; unique by exact bytes; no leading colon |
| Integer | Supported signed 64-bit native range |
| Float | Finite only; reject NaN and infinities |
| Text input/value | Valid UTF-8; maximum 16,777,216 bytes |
| Binary input/value | Maximum 16,777,216 bytes |
| Columns | Maximum 4,096 per result |
| Column label | Exact provider bytes; valid UTF-8; no NUL; maximum 1,024 bytes |
| Returned Text/Binary cell | Maximum 16,777,216 bytes |

The neutral layer does not parse, rewrite, normalize, format, classify, or
grammar-check raw SQL. It does not interpolate values. Parameter order and
exact names survive unchanged. Do not add case folding, Unicode normalization,
positional aliases, duplicate overwrite, array/list expansion, inference,
serialization, arbitrary stringification, or implicit conversion.

Arrays, resources, objects, enums, DateTime values, decimals, UUIDs, JSON,
streams, and large objects require caller conversion or later accepted codecs.

## Result and row invariants

| Concern | Required behavior |
| --- | --- |
| Result shape | `Rows` returns `RowStream`; `Completion` returns `CommandResult`; mismatch fails visibly |
| Stream | Forward-only, single-pass, explicitly closed, never whole-result buffered |
| Active stream | At most one per session or transaction; execute/begin/commit fail until it closes |
| Column identity | Preserve zero-based ordinal, exact labels, duplicates, order, and case variants |
| Row access | Ordinal access always follows column order; exact-label access requires exactly one match |
| Duplicate/absent label | Fail; never choose first/last and never case-fold |
| Unsupported/oversized cell | Fail visibly; no large-object streaming |
| Affected rows | Nullable integer; `null` means no meaningful provider count; no universal semantics |

Do not add generated keys, last-insert-ID, associative overwrite, implicit
buffering, multiple streams, native cursors, or provider-native row objects.

## Transaction and lifecycle state table

| State or operation | Exact invariant |
| --- | --- |
| Open session | Fresh operation-owned session/lease from an application-lifetime connector |
| Begin | Options must be supported; one active transaction per session |
| Active transaction | Session execution and another begin fail; execute only through transaction |
| Commit/rollback | No active stream; each is a one-attempt terminal operation |
| Close active transaction | Attempt rollback exactly once |
| Close stream | Release its statement/result resources once |
| Close session | Close row/result resources, rollback if needed, release native lease in reverse order |
| Cleanup idempotence | Only after successful terminal release; failed cleanup stays observable |
| Combined failure | Preserve primary and cleanup causes; cleanup never erases primary |
| Concurrency | A session is not shared across operations/fibers; mutable state never crosses sessions |
| Retry | None for connection, statement, transaction, commit, rollback, serialization, or deadlock |

Destructors may be defensive but cannot replace explicit close. Shutdown hooks,
registries, globals, thread locals, and fiber locals are never authoritative.
Connector concurrency requires adapter documentation and real proof.

## Failure categories and disclosure

| Category | Contract concern |
| --- | --- |
| Validation | Invalid neutral value, statement, parameter, option, column, or bound |
| Session state | Use after close or operation forbidden by active state |
| Unsupported capability | Requested feature absent from exact session capabilities |
| Connection | Opening or maintaining the operation-owned provider session |
| Binding | Exact named value binding and explicit provider type |
| Execution | Provider execution after valid binding |
| Result decoding | Result shape, label, column, native type, or cell conversion |
| Transaction begin/commit/rollback | Exact one-attempt transaction boundary |
| Cleanup | Explicit close/release/rollback and combined failure |

The neutral package owns one marker family with closed identifiers
`KGN-DATABASE-1001` through `KGN-DATABASE-1011`. Its schema exposes only fixed
package, failure-category, and remediation tokens. Native errors may be retained
only as internal previous causes.

Never expose DSNs, credentials, hosts, ports,
database/schema/table/column names, SQL, placeholders, parameter values, returned
cells, provider messages/codes/details, native classes or traces, transaction
contents, or topology.

## PostgreSQL PDO implementation profile

The first proof is PostgreSQL 17.2 via PDO `pdo_pgsql` only. The implementation
keep PDO internal; use exception mode, explicit parameter types, native prepares
where the accepted profile supports them, and no emulated-placeholder rewrite;
build configuration only from explicit adapter-owned values; mark sensitive
credential parameters where supported; establish required session behavior;
reject unsupported drivers/capabilities; and release PDO/statement references
inside the operation-owned object graph.

The Phase 6C harness uses caller-supplied PostgreSQL 17.2 executables, a fresh
external temporary cluster, ephemeral trust-authenticated credentials, an
unoccupied high loopback port, and a fixed non-sensitive role. It does not
assume a service, use a remote network, embed credentials, modify permanent
configuration, or start a database during normal framework runtime. It stops
its owned server and deletes only its validated owned tree before success.

## Implemented Phase 6C proof themes

- Exact statement, name, value, column, cell, and count bounds at below/at/above
  boundaries, including invalid UTF-8, NUL, duplicate exact names, finite floats,
  and 64-bit integer edges.
- Immutable caller order, exact byte labels, duplicates, case variants, ordinal
  rows, absent/duplicate label failures, and result-expectation mismatch.
- Forward-only one-stream behavior, explicit release, use-after-close, and no
  whole-result buffering.
- Capability canonicalization, exact membership, and fail-before-partial-work
  behavior for unsupported options where the provider can decide early.
- Transaction state transitions, one active transaction, direct-session
  rejection, no nesting, no stream at commit/rollback, one-attempt terminals,
  rollback-on-close, and primary-plus-cleanup failures.
- Sequential and overlapping operation isolation, connector concurrency only
  when proved, and no cross-session mutable/native state.
- Real local PostgreSQL `pdo_pgsql` behavior, explicit session settings,
  nondisclosure, cleanup, provider mismatch, and unsupported profile behavior.
- Proof that no retry, ambient authority, native exposure, request-time
  reflection, service location, hidden I/O, proxy, Active Record, or later-phase
  feature entered the slice.

## Phase 6 whole-platform evidence

Phase 6H's root harness composes the accepted eight-package direction without
changing it: `database` <- `database-pdo-pgsql`; `database` <- `database-sql`
<- `database-sql-pgsql`; `database`/`database-sql` <-
`database-migration` <- `database-migration-pgsql`; `database`/`database-sql`
<- `database-mapping`; and Composition plus Database Mapping and the neutral
Database contract <- `database-application`. Provider selection remains at the
host edge.

The external proof covers explicit SQL, explicit sessions, operation-owned
resources, generated typed Data Mappers/repositories, deterministic migrations,
mapping-before-composition exact-current build/check, and generated reverse
cleanup. Run and measurement interpretation are documented in the
[Phase 6 external guide](../architecture/phase-6-external-acceptance.md) and
[performance baseline](database-performance-baseline.md).

## Static-analysis and style expectations

Phase 6C and 6D first-party PHP uses `declare(strict_types=1)`, native types, final
readonly values where fixed here, exact unions and nullable returns, and
intentional Experimental API metadata. It must pass maximum PHPStan with strict
rules and configured PER-CS without a baseline, suppression, ignore, or
excluded path. Provider input begins as an explicit adapter boundary and is
validated into neutral values; `mixed` must not spread through the neutral API.

## Prohibited shortcuts

Do not expose PDO, use emulated placeholder rewriting, interpolate values,
guess types, buffer all rows, overwrite duplicate labels, auto-retry, hide
cleanup failure, use destructor cleanup as authority, share a session between
operations, store current state globally or per fiber, inject a container or
resolver, discover services/entities at request time, generate proxies, add
hidden lazy loading, use property hooks for infrastructure lookup, adopt Active
Record as the default, or extend the accepted bounded plans into arbitrary
expressions, migration discovery or schema inference, mapping, repositories,
composition integration, or Phase 6F behavior. Phase 6E uses these ports from a
separate higher layer without changing their ownership.

## Related material

- [Database contract architecture](../architecture/database-contract.md)
- [ADR 0079](../adr/0079-neutral-database-session-and-provider-capability-contract.md)
- [ADR 0080](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md)
- [ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md)
- [ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md)
- [ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md)
- [ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md)
- [ADR 0085](../adr/0085-generated-data-application-and-operation-owned-sessions.md)
- [ADR 0086](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md)
- [Data Platform architecture](../architecture/data-platform.md)
- [Immutable SQL plans](../architecture/database-sql-plans.md)
- [SQL plan engineering guide](database-sql-plans.md)
- [Migration engineering guide](database-migrations.md)
- [Phase 6 roadmap](../architecture/phase-6-roadmap.md)
