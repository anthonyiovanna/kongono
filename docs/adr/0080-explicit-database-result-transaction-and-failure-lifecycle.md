# ADR 0080: Explicit Database Result, Transaction, and Failure Lifecycle

- **Status:** Accepted
- **Date:** 2026-08-24
- **Phase:** 6B

## Context

ADR 0079 fixes the future neutral database package, execution surface, values,
capabilities, and first PostgreSQL PDO proof profile. Persistent runtimes also
need exact result, transaction, cleanup, concurrency, and failure behavior before
an adapter can safely exist.

Phase 6B therefore accepts those semantics as architecture only. No database
runtime or PHP declaration exists yet.

## Decision

### Results, streams, columns, and rows

The Accepted result vocabulary is `RowStream`, `DatabaseColumn`, `DatabaseRow`,
and `CommandResult`. A statement expecting `Rows` returns `RowStream`; one
expecting `Completion` returns `CommandResult`. A provider/result mismatch is a
package-owned failure, never an implicit conversion.

`RowStream` is forward-only and single-pass. It exposes immutable ordered
columns, `fetch(): ?DatabaseRow`, `close(): void`, and `isClosed(): bool`, and
never silently buffers the entire result. Version 1 permits at most one active
row stream per session or transaction. Another execute, begin, or commit attempt
fails visibly until the stream is closed.

Each `DatabaseColumn` preserves its zero-based ordinal and exact
provider-returned label. Labels are valid UTF-8, contain no NUL, and contain at
most 1,024 bytes. One result contains at most 4,096 columns. Duplicate labels
and byte-distinct case variants remain ordered and are never overwritten in an
associative map.

`DatabaseRow` retains values in column order and provides ordinal access plus
unique exact-label access. Exact-label access fails if the label is absent or
duplicated; it never selects the first or last duplicate and never case-folds.
Every returned Text or Binary cell is limited to 16,777,216 bytes. Unsupported
native types and oversized cells fail visibly. Large-object streaming is outside
version 1.

Immutable `CommandResult` may expose `affectedRows(): ?int`. `null` means the
provider has no meaningful count for that operation. Version 1 defines no
generated key or last-insert-ID contract and makes no universal affected-row
semantics claim.

### Transaction values and restrictions

Immutable `TransactionOptions` contains one `TransactionIsolation` case:
`Default`, `ReadCommitted`, `RepeatableRead`, or `Serializable`; and one
`TransactionAccess` case: `ReadWrite` or `ReadOnly`. `TransactionState` is
exactly `Active`, `Committed`, `RolledBack`, or `Failed`.

Unsupported isolation or access fails before begin. Version 1 permits one active
transaction per session. Begin while active, direct session execution while a
transaction is active, and nested transactions fail visibly. All transactional
execution uses the returned `DatabaseTransaction`.

### Ownership and deterministic cleanup

The connector may be immutable and application lifetime. Every
`openSession()` returns a fresh operation-owned session or lease. Sessions,
transactions, native statements, row streams, and provider results never cross
operation ownership.

Closing a row stream releases its statement and result resources once. Commit
and rollback require no active row stream and are each one-attempt terminal
operations. Closing an active transaction attempts rollback exactly once.
Closing a session closes active row/result resources, rolls back an active
transaction when necessary, and releases the native connection or lease in
deterministic reverse ownership order.

Cleanup becomes idempotent only after successful terminal release. Failed
cleanup remains observable and is never silently reclassified as success.
Primary and cleanup failures retain both causes under the combined-failure
doctrine; cleanup cannot erase the primary failure.

Destructors may be defensive only. No destructor, shutdown function, static
registry, fiber-local, thread-local, superglobal, or process-global state is an
authoritative lifecycle mechanism or replacement for explicit close.

### Retry and concurrency

The package performs no automatic connection, statement, transaction, commit,
rollback, serialization-failure, or deadlock retry. A later retry policy must
explicitly own idempotency and external-side-effect rules.

One session is not used concurrently by multiple operations or fibers. A
connector may create independent sessions concurrently only when the adapter
documents and proves that behavior. Sessions share no mutable statement,
parameter, row, cursor, transaction, or error state.

### Failure and diagnostic boundary

The future neutral package owns one marker family and categorized package-owned
failures for validation, session state, unsupported capability, connection,
binding, execution, result decoding, transaction begin, commit, rollback, and
cleanup. Phase 6C must allocate exact identifiers through the diagnostics
process. Phase 6B assigns no identifiers and creates no exception class.

Package-owned presentation strings are deterministic and non-disclosing.
Native/provider exceptions may survive only as internal previous-cause evidence;
they and their traces never enter Safe context. After explicit schema
classification, Safe candidates are the package-owned diagnostic identifier,
failure category, lifecycle state, requested capability, bounded parameter or
column count, result expectation, and documented remediation.

Protected or prohibited presentation data includes DSNs, credentials, hosts,
ports, database/schema/table/column names, SQL, placeholder names, parameter
values, returned cells, provider messages/codes/details, native class names,
transaction contents, and internal topology. The future packages perform no
logging, tracing, telemetry export, incident storage, secret detection,
redaction, sanitization, AI call, or network disclosure.

## Consequences

The result model preserves column order and duplicate labels without hidden
buffering. Explicit transaction state and one-attempt cleanup keep uncertain
provider state visible and suitable for persistent workers. Failures remain
actionable without promoting native or sensitive detail into diagnostics.

Callers must manage streams, transactions, and sessions explicitly and cannot
assume retries, nested work, concurrent session use, or destructor cleanup.

## Rejected alternatives

- Associative rows that overwrite duplicate labels or case-fold names.
- Whole-result buffering hidden behind a stream interface.
- Multiple active results, nested transactions, ambient transaction context, or
  callback-based retry.
- Commit, rollback, or cleanup retried until they appear successful.
- Destructor or process teardown as authoritative resource cleanup.
- Safe diagnostic copying of SQL, data, credentials, native errors, or provider
  details.

## Explicit non-goals

Phase 6B implements no result, row, transaction, exception, diagnostic schema,
retry policy, logger, database process, PHP source, test, package, dependency, or
runtime integration. Savepoints, distributed transactions, large-object
streaming, generated keys, and concurrent session use remain outside version 1.
Phase 6C has not begun.

## Related material

- [ADR 0079: Neutral Database Session and Provider-Capability Contract](0079-neutral-database-session-and-provider-capability-contract.md)
- [ADR 0078: Deterministic Data Plans and Operation-Scoped Persistence](0078-deterministic-data-plans-and-operation-scoped-persistence.md)
- [Database contract architecture](../architecture/database-contract.md)
- [Database contracts engineering guide](../engineering/database-contracts.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
