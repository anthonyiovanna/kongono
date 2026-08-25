# ADR 0081: PostgreSQL PDO Adapter and Real Provider Proof

- **Status:** Accepted
- **Date:** 2026-08-24
- **Phase:** 6C

## Context

ADRs 0079 and 0080 accept a minimal provider-neutral execution, result,
transaction, lifecycle, and failure contract. Phase 6C must implement that
contract without starting query planning, migrations, mapping, composition
integration, or a general PDO abstraction.

## Decision

### Neutral and adapter packages

Phase 6C implements the Experimental `kongono/database` package under
`Kongono\Database\` and the Experimental `kongono/database-pdo-pgsql` adapter
under `Kongono\DatabasePdoPgsql\`. The adapter depends one way on the neutral
package. The neutral package depends only on PHP, API Metadata, and Diagnostics;
neither package depends on Kernel, Composition, Dependency Injection,
Application Graph, HTTP, or a later Data Platform layer.

The neutral package owns explicit connectors, executors, operation-owned
sessions, immutable statements and values, capabilities, forward-only results,
ordered duplicate-safe rows, explicit transaction options and states, and a
package-local failure family. The PostgreSQL adapter owns the concrete session,
statement, result, transaction, and native cleanup implementation.

### Explicit PostgreSQL PDO profile

The first implemented profile is PostgreSQL 17 through PHP 8.5 PDO
`pdo_pgsql`. `PdoPgsqlConfiguration` receives host, port, database, username,
and password explicitly. Credential-bearing parameters use
`#[\SensitiveParameter]`; configuration has no environment, file, global,
service-locator, or ambient credential lookup and exposes no credential getter
or string representation.

PDO and `PDOStatement` remain inside Internal adapter implementation. The
connector returns only the neutral `DatabaseSession`. Each open creates an
independent connection with exception mode, native prepares, and disabled
stringified fetches and emulated prepares.

### Binding, results, and lifecycle

Execution sends caller SQL unchanged to native prepare and binds each exact
named neutral value with an explicit PDO parameter type. The adapter does not
parse, classify, normalize, rewrite, interpolate, or retry SQL. `Rows` admits
only a forward-only stream and `Completion` only a command result; mismatches
fail visibly.

The first decoding profile accepts PostgreSQL null, boolean, int2/int4/int8,
finite float4/float8, text/varchar/bpchar/name, and bytea values within neutral
bounds. It preserves numeric column order, exact labels, duplicates, and case
variants. Unsupported metadata or native types fail rather than coercing.

One session owns at most one active stream and one active transaction. Session
execution is unavailable while a transaction is active, and transaction work
uses only the returned transaction. Commit and rollback are one-attempt
terminal operations. Active transaction close attempts rollback once. Session
close releases results, rolls back active work, and releases the connection in
reverse ownership order. Failure state is not retried or silently reused.

### Diagnostics and nondisclosure

The neutral package reserves `KGN-DATABASE-1001` through
`KGN-DATABASE-1011` for validation, lifecycle state, unsupported capability,
connection, binding, execution, result, transaction begin/commit/rollback, and
cleanup. Public presentation is deterministic and schema-owned. Native causes
may remain only as `getPrevious()` evidence.

Messages, stringification, diagnostic context, tests, the runner, and the
completion report exclude DSNs, credentials, hosts, ports, object names, SQL,
placeholders, values, cells, provider messages and codes, native class names,
traces, and topology.

### External evidence boundary

The root-owned `acceptance:phase6` runner validates caller-supplied PostgreSQL
17 binaries, creates one cryptographically named external temporary cluster,
uses trust authentication and a fixed non-sensitive role, listens only on an
unoccupied `127.0.0.1` high port, and never attaches to an existing service.
It proves capabilities, values, result shapes, row identity, stream lifecycle,
transactions and all isolation/access combinations, read-only enforcement,
session isolation, unsupported types, no automatic retry, and adversarial
nondisclosure.

The runner stops its owned server, proves listener retirement, and deletes only
its validated temporary tree before emitting success. Its canonical success
object contains only status, profile, server major, case count, and cleanup.
This is one local PostgreSQL 17.2/libpq 16.14 proof, not universal PostgreSQL,
PDO, provider, deployment, or production-readiness evidence.

## Consequences

Kongono now has a small real low-level database boundary that remains explicit,
operation-owned, provider-truthful, and suitable for persistent runtimes. The
root contains twenty-one production packages. Kernel and every Phase 5
lifecycle owner remain unchanged.

Callers manage sessions, streams, transactions, and cleanup explicitly. The
implementation deliberately omits convenience APIs that would hide provider or
lifecycle behavior.

## Explicit non-goals

Phase 6C implements no SQL/query/command plan, dialect compiler, query builder,
identifier model, schema inspection, migration, mapper, repository, ORM,
Active Record, proxy, lazy loading, generated database wiring, composition
integration, Kernel/HTTP/CLI integration, pooling, telemetry, deployment,
multiple active results, savepoints, generated keys, COPY, notifications, or
Phase 6D work.

## Evidence

The accepted implementation passes package and architecture tests, maximum
PHPStan, configured PER-CS, canonical quality, repeated byte-identical real
PostgreSQL acceptance, nondisclosure probes, and ownership-safe cleanup. Exact
command counts and environment versions are recorded in the Phase 6C task
report.

## Related material

- [ADR 0079](0079-neutral-database-session-and-provider-capability-contract.md)
- [ADR 0080](0080-explicit-database-result-transaction-and-failure-lifecycle.md)
- [Low-level database contract](../architecture/database-contract.md)
- [Database contracts engineering guide](../engineering/database-contracts.md)
- [Phase 6 roadmap](../architecture/phase-6-roadmap.md)
