# Deterministic Database Migrations

## Current boundary

Phase 6E implements two Experimental packages. `kongono/database-migration`
contains the provider-neutral authoritative model and pure planner.
`kongono/database-migration-pgsql` contains the PostgreSQL 17 compiler and
executor. The PostgreSQL package depends downward on the neutral package; the
existing database and SQL packages do not depend upward on migrations.

The application explicitly constructs and retains a `MigrationCatalog`.
Catalog order is semantic: ordinals start at one and remain contiguous, IDs are
bounded stable tokens, and definitions hold Apply plus optional Revert plans.
There is no runtime discovery, migration-file execution, live-schema inference,
or database adoption of source authority.

## Model and identity

The closed operations are CreateTable, DropTable, AddColumn, DropColumn,
CreateIndex, DropIndex, RenameTable, and RenameColumn. Column types are Boolean,
Int64, Float64, Text, Binary, Uuid, Json, and TimestampTz. CreateTable,
nullable AddColumn, and CreateIndex are Safe; destructive operations are
Destructive. A direction's declared risk must equal its derived risk.

Compact canonical JSON binds every definition, including reversibility, and a
SHA-256 identity binds those bytes. The catalog's canonical identity binds the
ordered definition identities. Identity is inspectable derived evidence, not
authority and not a publication or runtime-loading format.

Applied history is valid only when it is an exact prefix of the catalog. The
planner exposes ApplyPending and RevertLast. Destructive directions require an
explicit Allow policy after construction-time risk validation. RevertLast never
searches or automatically rewinds multiple migrations.

## PostgreSQL execution

Configuration supplies one qualified history-table identity and one signed
advisory-lock key without environment lookup or string representation. Each
call uses one caller session and exactly one Serializable ReadWrite transaction:

```text
capability check -> begin -> try advisory lock -> bootstrap/read history
    -> validate history and reserved table -> compile/execute ordered DDL
    -> insert/delete history -> commit -> close transaction boundary
```

No-op work still locks, reads, validates, and commits. Lock denial is immediate;
there is no wait or retry. Pre-commit failure attempts rollback once. Commit
failure is uncertain and is not retried or followed by an automatic revert.
The executor does not close the caller session.

The configured history table is reserved infrastructure. An operation may not
use it as an application table source or target. History changes and
application DDL share the same accepted PostgreSQL transaction.

## Failure and security boundary

Seven package diagnostics distinguish invalid definitions, history mismatch,
destructive review, irreversibility, lock denial, execution, and uncertain or
cleanup failure. Safe surfaces contain only bounded package-owned structural
tokens. Application migration identities, catalog bytes, schema names, SQL,
parameters, values, history/lock configuration, credentials, native/provider
details, paths, and topology are prohibited.

## Stability and exclusions

Both packages are Experimental. PostgreSQL 17 is the sole DDL/execution profile.
There is no migration CLI, file format, discovery, schema diff, mapper,
repository, ORM, Active Record, lazy loading, generated data layer, runtime
composition integration, production startup service, or Phase 6F behavior.

See [ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md)
and the [engineering guide](../engineering/database-migrations.md).
