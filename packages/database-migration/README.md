# Kongono Database Migration

This Experimental package owns the provider-neutral, caller-constructed schema
migration model. A `MigrationCatalog` is the sole version-1 authority. It holds
bounded, contiguous definitions composed from eight typed operations, records
canonical SHA-256 identities, validates exact applied-history prefixes, and
produces ApplyPending or RevertLast plans under an explicit destructive policy.

The package performs no I/O. It does not discover files or classes, inspect a
live schema, execute SQL, open a database session, retry work, or provide ORM,
mapping, repository, CLI, logging, telemetry, or runtime-composition behavior.
Application code owns catalog construction and passes it explicitly to an
executor. Migration and catalog canonical bytes are derived evidence, not a
replacement for the caller-owned source definitions.

Failures use the closed `KGN-MIGRATION-1001` through `1007` diagnostics. Safe
presentation exposes only package-owned bounded structural values; migration
identities, object names, SQL, values, provider details, and topology remain
outside the diagnostic surface.

**Stability:** Experimental.
