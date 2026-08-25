# ADR 0079: Neutral Database Session and Provider-Capability Contract

- **Status:** Accepted
- **Date:** 2026-08-24
- **Phase:** 6B

## Context

Phase 6A accepts a layered Deterministic Data Platform while deliberately
selecting no package, provider, API, or runtime. The first implementation slice
needs a small neutral execution contract that preserves explicit SQL, provider
truthfulness, operation ownership, and persistent-runtime isolation without
turning Kernel or the current dependency resolver into a database owner.

Phase 6B fixes the package, statement, value, and capability architecture before
Phase 6C may implement it. Neither package described here exists yet.

## Decision

### Future package boundaries

The future neutral Experimental package is `kongono/database` under
`Kongono\Database\`. Its only intended direct dependencies are PHP
`>=8.5.0 <8.6.0`, `kongono/api-metadata @dev`, and
`kongono/diagnostics @dev`. It owns neutral contracts, immutable values,
lifecycle state, package-owned exceptions, and diagnostic schemas. It owns no
driver, credentials, environment lookup, pool, parser, query builder, dialect,
migration, mapper, repository, Kernel, Graph, HTTP, CLI, logging, or telemetry.

The separate future Experimental first adapter is
`kongono/database-pdo-pgsql` under `Kongono\DatabasePdoPgsql\`. Its intended
direct dependencies are PHP `>=8.5.0 <8.6.0`, `ext-pdo`, `ext-pdo_pgsql`, and
`kongono/database @dev`. It adapts PostgreSQL through PDO `pdo_pgsql` only.
The neutral package never depends on the adapter. Neither future package depends
on Kernel, Composition, Dependency Injection, Application Graph, HTTP, Chie,
Hantei, Oshie, or another Data Platform layer. Composition integration belongs
to Phase 6G.

### Connector and execution surfaces

The Accepted version-1 neutral vocabulary is:

- `DatabaseConnector`, an application-lifetime explicitly configured factory
  with `openSession(): DatabaseSession`;
- `DatabaseExecutor`, with
  `execute(DatabaseStatement): RowStream|CommandResult`;
- `DatabaseSession`, extending that execution surface with
  `capabilities(): DatabaseCapabilities`,
  `begin(TransactionOptions): DatabaseTransaction`, `close(): void`, and
  `isClosed(): bool`; and
- `DatabaseTransaction`, extending that execution surface with
  `state(): TransactionState`, `commit(): void`, `rollback(): void`,
  `close(): void`, and `isClosed(): bool`.

The neutral API accepts no DSN, credential, environment name, configuration
array, provider option, or service locator. Adapter configuration is supplied
explicitly to the adapter constructor through composition. No contract returns
PDO or another native handle. Version 1 has no callback transaction helper,
generic `query()` magic, model operation, global connection, or ambient current
transaction.

### Immutable statements and values

Final readonly `DatabaseStatement` contains one non-empty valid UTF-8 SQL string
with no NUL and at most 1,048,576 bytes, an ordered list of zero to 65,535
`DatabaseParameter` values, and one `ResultExpectation`: `Rows` or
`Completion`. The neutral layer never parses, rewrites, normalizes, formats,
classifies, or grammar-validates SQL and never interpolates values. SQL uses
named provider placeholders; a parameter stores its name without the colon.

Parameter names contain 1–128 ASCII bytes matching
`[A-Za-z_][A-Za-z0-9_]*`. They are unique by exact byte comparison and retain
caller order. There is no case folding, Unicode normalization, numeric
positional alias, last-value-wins rule, array expansion, list inference, object
serialization, or implicit conversion.

Final readonly `DatabaseValue` provides package-owned factories and is
discriminated by exactly these `DatabaseValueKind` cases: `Null`, `Boolean`,
`Integer`, `Float`, `Text`, and `Binary`. Integer uses the supported 64-bit
native range. Float is finite; NaN
and infinities fail. Text is valid UTF-8. Text and Binary are each limited to
16,777,216 bytes. Arrays, resources, objects, enums, DateTime values, decimals,
UUIDs, JSON, streams, and large objects require explicit caller conversion or a
later accepted codec. Arbitrary values are never stringified for coercion.

### Provider capabilities

Immutable, duplicate-free, canonical `DatabaseCapabilities` supports exact
membership over the closed version-1 `DatabaseCapability` cases
`Transactions`, `ReadOnlyTransactions`, `IsolationReadCommitted`,
`IsolationRepeatableRead`, `IsolationSerializable`, and `AffectedRows`.
The opened session reports capabilities; callers do not infer them from SQL or
ambient inspection. Unsupported requested behavior fails before partial
execution wherever the provider can determine support at that boundary.

Savepoints, nested and distributed transactions, two-phase commit, multiple
active row streams, large-object streaming, generated keys, notifications,
COPY, server cursors, pipeline mode, asynchronous I/O, pooling, and native
handles are outside version 1.

### First future proof profile

PostgreSQL through `pdo_pgsql` is the first future acceptance profile, not a
portability claim. The adapter will create PDO internally, use exception mode,
native prepares where supported by the accepted profile, explicit parameter
types, and no emulated-placeholder rewriting. Explicit adapter-owned values
construct configuration. Credential-bearing constructor parameters are marked
sensitive where PHP supports it, and safe output excludes DSN, username,
password, host, database, SQL, parameters, rows, and provider payloads.

The adapter rejects unsupported drivers and capabilities, explicitly establishes
required session behavior, and releases PDO and statement references inside the
operation-owned object graph. A Phase 6C proof may use only an isolated,
caller-supplied local PostgreSQL executable/profile with ephemeral credentials
and storage outside the repository. It assumes no installed service, embeds no
credentials, reaches no remote network, changes no permanent server or PHP
configuration, and adds no normal-runtime database startup. Exact executable,
PostgreSQL version, profile, harness, and command remain Phase 6C decisions.

## Consequences

Applications receive a provider-neutral contract only where its semantics are
exact, while the first adapter and its capability evidence remain visible.
Explicit factories and operation-owned sessions prevent ambient authority and
native-handle leakage. The strict statement and value bounds make validation and
future acceptance deterministic.

The contract intentionally exposes fewer conveniences than a generic PDO or ORM
wrapper. Later providers, codecs, SQL plans, schema, mapping, and composition
integration require separate accepted work.

## Rejected alternatives

- Putting database ownership in Kernel, Composition, the resolver, or Graph.
- A neutral package depending on its first adapter.
- A universal PDO adapter or support claim for arbitrary PDO drivers.
- DSNs, credentials, environment lookup, or native handles in the neutral API.
- SQL parsing, rewriting, interpolation, positional aliases, implicit value
  conversion, or collection expansion.
- Ambient connections, service location, callback transactions, pooling, or
  request-time discovery.

## Explicit non-goals

Phase 6B creates no package, PHP declaration, adapter, dependency, configuration,
connection, database, SQL execution, schema, test, command, or artifact. MySQL,
SQLite, SQL Server, Oracle, ODBC, and other providers remain later candidates.
Phase 6C has not begun.

## Related material

- [ADR 0077: Layered Data Platform and Explicit SQL Boundary](0077-layered-data-platform-and-explicit-sql-boundary.md)
- [ADR 0078: Deterministic Data Plans and Operation-Scoped Persistence](0078-deterministic-data-plans-and-operation-scoped-persistence.md)
- [ADR 0080: Explicit Database Result, Transaction, and Failure Lifecycle](0080-explicit-database-result-transaction-and-failure-lifecycle.md)
- [Database contract architecture](../architecture/database-contract.md)
- [Database contracts engineering guide](../engineering/database-contracts.md)
