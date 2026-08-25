# Kongono PostgreSQL Database Migration

This Experimental package compiles the neutral migration operations to the
accepted PostgreSQL 17 DDL profile and executes plans through an explicit
caller-owned `DatabaseSession`. The runner requires serializable transactions,
uses one transaction-scoped advisory lock attempt, owns a configured history
table, validates exact history before application DDL, and commits or rolls back
once. It never closes the caller's session.

The compiler and runner contain no PDO handle, credential, connector,
environment lookup, filesystem discovery, live-schema inference, automatic
retry, automatic down migration, ORM, mapper, repository, CLI, logging,
telemetry, or production startup integration. A failed commit is uncertain and
is never retried. Pre-commit cleanup preserves both primary and cleanup failure
evidence without disclosing SQL, identifiers, parameters, configuration, or
provider details.

**Stability:** Experimental. The only claimed dialect profile is PostgreSQL 17.
