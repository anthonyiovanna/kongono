# Database Migrations Engineering Guide

## Use the explicit catalog

Construct a `MigrationCatalog` in application-owned code from ordered
`MigrationDefinition` values. Every Apply plan and optional Revert plan must use
the typed operation vocabulary and must declare the risk derived from its
operations. Do not scan directories, reflect classes, execute migration files,
query a live schema for authority, or substitute associative arrays and SQL
callbacks.

Treat definition and catalog canonical bytes and SHA-256 values as derived
identity evidence. Never edit an already-applied definition. Exact history must
remain a prefix of the current catalog; insertion, reorder, mutation, deletion,
or unknown records are deliberate failures.

## Execute deliberately

Create `PostgreSqlMigrationConfiguration` from an explicitly qualified history
table and signed advisory key, then use `PostgreSql17MigrationExecutor` with a
caller-owned open `DatabaseSession`. Choose `DestructiveMigrationPolicy::Allow`
only after review of the selected direction. `Deny` is the safe default.

ApplyPending applies the unapplied suffix in catalog order. RevertLast affects
at most the final applied definition. It fails if the definition has no Revert
plan or if destructive work was not allowed. Never build an automatic fallback
that runs Revert after an Apply failure.

The executor owns its one transaction, not the session. After success or a
handled failure, the caller remains responsible for session close. A commit
failure is uncertain; stop and investigate rather than retrying. Cleanup
failures retain the primary failure and must remain visible.

## Operation constraints

- AddColumn is version-1 Safe only when the column is nullable.
- Primary-key columns must exist in CreateTable and be non-null.
- PostgreSQL identifiers must remain within the accepted 63-byte profile.
- The configured history table is reserved and cannot be an application
  operation source or destination.
- Use explicit qualified names. The compiler emits canonical PostgreSQL 17 DDL
  with no semicolon or newline.

## Validation

Run focused syntax, cache-disabled style, maximum PHPStan, PHPUnit, architecture,
and Hantei checks before canonical quality. Real provider behavior is proved by
the root-owned command with caller-supplied PostgreSQL binaries:

```powershell
composer acceptance:phase6 -- "C:\laragon\bin\postgresql\postgresql-17.2-3-windows-x64-binaries\bin"
```

The harness owns ephemeral loopback-only state and cleanup. It is test evidence,
not a product command or production provider manager.

## Nondisclosure and non-goals

Do not render migration IDs/checksums from application fixtures, canonical
catalog bytes, database object names, SQL, parameters, values, history or lock
configuration, credentials, provider messages/codes, traces, paths, or topology.
Do not add discovery, introspection, retry, another dialect, CLI, ORM, mapping,
repository, Active Record, lazy loading, composition integration, or Phase 6F
behavior within this boundary.
