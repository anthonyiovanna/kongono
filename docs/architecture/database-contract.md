# Low-Level Database Contract

## Purpose and status

Phase 6B accepts the exact version-1 low-level database architecture. Phase 6C
implements it as two Experimental packages within the current twenty-five-
package graph. The provider adapter and external proof are current. Phase 6D
adds the separate immutable plan and compiler layer, and Phase 6E uses this
unchanged low-level boundary for explicit migration execution. No mapper,
repository, composition integration, or production database process is
implemented.

## Packages and dependency direction

The neutral package is `kongono/database`, namespace `Kongono\Database\`. Its
direct dependencies are only PHP
`>=8.5.0 <8.6.0`, `kongono/api-metadata @dev`, and
`kongono/diagnostics @dev`.

The first adapter is `kongono/database-pdo-pgsql`, namespace
`Kongono\DatabasePdoPgsql\`. Its direct dependencies are PHP
`>=8.5.0 <8.6.0`, `ext-pdo`, `ext-pdo_pgsql`, and
`kongono/database @dev`.

```text
future application composition (Phase 6G)
    -> kongono/database-pdo-pgsql
        -> kongono/database
            -> api-metadata and diagnostics
```

The neutral package does not depend on the adapter. Neither package
depends on Kernel, Composition, Dependency Injection, Application Graph, HTTP,
Chie, Hantei, Oshie, or another Data Platform layer. The neutral package owns no
driver, DSN, credential source, environment lookup, pool, SQL parser, query
builder, dialect compiler, migration, mapper, repository, CLI, logging, or
telemetry.

## Version-1 surface

| Type | Responsibility and exact operations |
| --- | --- |
| `DatabaseConnector` | Application-lifetime configured factory; `openSession(): DatabaseSession`. |
| `DatabaseExecutor` | `execute(DatabaseStatement): RowStream\|CommandResult`. |
| `DatabaseSession` | Executor plus `capabilities(): DatabaseCapabilities`, `begin(TransactionOptions): DatabaseTransaction`, `close(): void`, `isClosed(): bool`. |
| `DatabaseTransaction` | Executor plus `state(): TransactionState`, `commit(): void`, `rollback(): void`, `close(): void`, `isClosed(): bool`. |

Each opened session is a fresh operation-owned lease. Adapter configuration is
explicitly supplied to the adapter constructor; the neutral API accepts no DSN,
username, password, environment, provider option, config array, or service
locator. Native driver handles never cross the neutral boundary.

## Statements, parameters, and values

Final readonly `DatabaseStatement` carries immutable raw SQL, ordered
parameters, and an explicit `ResultExpectation` of `Rows` or `Completion`. SQL
is non-empty valid UTF-8, contains no NUL, and is at most 1,048,576 bytes. The
neutral layer does not parse, rewrite, normalize, format, classify, or
grammar-validate it. It never interpolates values.

Statements contain zero to 65,535 named `DatabaseParameter` values. SQL uses
provider placeholders; a parameter name omits the leading colon, has 1–128
ASCII bytes, matches `[A-Za-z_][A-Za-z0-9_]*`, remains in caller order, and is
unique by exact bytes. There is no case folding, Unicode normalization,
positional alias, duplicate overwrite, array expansion, inference,
serialization, or coercion.

Final readonly `DatabaseValue` has package-owned factories for exactly six
`DatabaseValueKind` cases: `Null`, `Boolean`, `Integer`, `Float`, `Text`, and
`Binary`. Integer is the supported 64-bit native range. Float must be finite. Text
and Binary are each at most 16,777,216 bytes. Other PHP or domain values require
explicit caller conversion or later accepted codecs; arbitrary input is never
stringified.

## Capabilities and unsupported behavior

Immutable, duplicate-free, canonical `DatabaseCapabilities` provides exact
membership for `Transactions`, `ReadOnlyTransactions`,
`IsolationReadCommitted`, `IsolationRepeatableRead`, `IsolationSerializable`,
and `AffectedRows`. The opened session reports these values. Callers do not infer
them from SQL or ambient server inspection. Unsupported isolation, access, or
other requested behavior fails before partial execution where the provider can
determine it.

Version 1 excludes savepoints, nesting, two-phase or distributed transactions,
multiple active streams, LOB streaming, generated keys, notifications, COPY,
server cursors, pipelines, asynchronous I/O, pooling, and native handles.

## Results and row identity

A statement expecting `Rows` returns a forward-only, single-pass `RowStream`;
one expecting `Completion` returns immutable `CommandResult`. Any mismatch is a
visible package-owned failure. A stream exposes immutable ordered columns,
`fetch(): ?DatabaseRow`, explicit close, and closed state; it never silently
buffers the complete result.

Only one stream may be active per session or transaction. Another execute,
begin, or commit fails until it closes. Each `DatabaseColumn` preserves its
zero-based ordinal and exact provider label. Labels are valid UTF-8 without NUL,
at most 1,024 bytes; a result has at most 4,096 columns. Duplicate and
byte-distinct case variants remain ordered.

`DatabaseRow` preserves column order, supports ordinal access, and supports an
exact-label lookup only when the label is unique. Missing or duplicate labels
fail; lookup never chooses a duplicate or case-folds. Returned Text and Binary
cells are each limited to 16,777,216 bytes. Unsupported native values and
oversized cells fail.

`CommandResult::affectedRows(): ?int` may return `null` when no meaningful count
exists. Version 1 defines neither generated keys nor universal affected-row
semantics.

## Transactions and lifecycle

`TransactionOptions` selects `TransactionIsolation` as `Default`,
`ReadCommitted`, `RepeatableRead`, or `Serializable`, and `TransactionAccess` as
`ReadWrite` or `ReadOnly`. `TransactionState` is `Active`, `Committed`,
`RolledBack`, or `Failed`.

One transaction may be active per session. Unsupported options fail before
begin. Begin while active, session execution while active, and nesting fail.
Transactional execution occurs only through `DatabaseTransaction`. Commit and
rollback require no active stream and are one-attempt terminal operations.
Closing an active transaction attempts rollback once.

Closing a session releases stream/result resources, rolls back an active
transaction if necessary, and releases the native connection/lease in reverse
ownership order. Cleanup is idempotent only after successful terminal release.
Failures remain observable; cleanup preserves rather than replaces a primary
failure. Explicit close is authoritative. Destructors may only be defensive.

No automatic connection, statement, transaction, commit, rollback,
serialization, or deadlock retry occurs. A session is not shared concurrently
between operations or fibers. Independent sessions may be created concurrently
only after adapter documentation and proof. No mutable state crosses sessions or
operations.

## First PostgreSQL PDO proof profile

The first proved profile is PostgreSQL 17.2 through PDO `pdo_pgsql` only. This
does not claim support for another PDO driver or universal portability. The
adapter internally creates PDO, uses exception mode, native prepares where the
profile supports them, explicit parameter types, and no emulated-placeholder
rewriting. It establishes required session behavior explicitly and releases
native references within the operation-owned graph.

Configuration and credentials are explicit adapter-owned inputs. Sensitive
constructor parameters are marked where PHP supports it. The Phase 6C proof uses
the caller-supplied PostgreSQL 17.2 executables in an isolated external temporary
tree with ephemeral credentials and loopback networking. It assumes no installed
service or remote network, embeds no credential, changes no permanent
configuration, performs no normal-runtime database startup, and cleans its owned
process and tree.

## Failure and security boundary

The neutral marker family categorizes validation, session-state,
unsupported-capability, connection, binding, execution, result-decoding,
transaction-begin/commit/rollback, and cleanup failures. Phase 6C assigns the
closed `KGN-DB-1001` through `KGN-DB-1011` diagnostic family and corresponding
classes. Native errors remain internal previous causes
only.

Safe candidates require later exact schema classification and are limited to
package identifiers, category, lifecycle state, capability, bounded parameter
or column count, expectation, and remediation. Safe output excludes DSNs,
credentials, hosts, ports, database/schema/table/column names, SQL, placeholders,
parameters, returned cells, provider messages/codes/details, native class names,
transaction contents, and topology. The future packages perform no logging,
tracing, telemetry, incident storage, secret detection, redaction, sanitization,
AI call, or network disclosure.

## Higher-layer relationship

Phase 6D leaves every low-level contract and adapter semantic unchanged. Its
two separate higher-layer packages build immutable plans and compile them into
the existing `DatabaseStatement`; sessions and transactions execute that result
through the contract on this page. Phase 6E's separate migration package uses
the same explicit session/transaction ports for PostgreSQL 17 DDL and history;
the low-level package does not depend upward on it. Direct raw statements remain
supported. Kernel and every Phase 5 lifecycle owner remain unchanged. No
mapping, repositories, composition/runtime integration, installed service,
production database startup, or Phase 6F behavior exists.

## Related material

- [ADR 0079](../adr/0079-neutral-database-session-and-provider-capability-contract.md)
- [ADR 0080](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md)
- [ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md)
- [ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md)
- [ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md)
- [Deterministic Data Platform](data-platform.md)
- [Immutable SQL plans](database-sql-plans.md)
- [Deterministic database migrations](database-migrations.md)
- [Phase 6 roadmap](phase-6-roadmap.md)
- [Database contracts engineering guide](../engineering/database-contracts.md)
