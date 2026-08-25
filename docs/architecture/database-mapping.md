# Compile-Time Database Mapping

## Purpose and context

Phase 6F implements the optional Experimental `kongono/database-mapping`
package above the neutral database and SQL-plan contracts. It compiles one
explicit application-owned mapping source into deterministic evidence and
readable typed mapper/repository PHP. Raw parameter-bound statements and
immutable SQL plans remain first-class; mapping is an ergonomic layer, not a
replacement or an ORM.

## Use when

Use this boundary when an application owns final readonly scalar record classes
and wants a closed four-operation generated repository. Select the application
root and exact relative `kongono.data-mapping.json` path explicitly, build and
validate the artifact before runtime, generate and validate PHP, publish it to
an application-owned output directory, then load only the exact current file
through an explicit application boundary.

Do not use it for relations, aggregates, custom queries, generated identities,
schema management, discovery, dynamic models, provider-specific behavior, or
an ambient persistence session. Use the low-level database contract or typed SQL
plans when the closed generated surface does not express the operation.

## Source and record contract

The version-1 source has exactly `schema`, `version`, `generated_namespace`, and
`mappings`. Each mapping names one existing record, mapper, repository, table,
assigned identity, and ordered fields. Each field binds one record field to one
column, scalar kind, nullability, constructor parameter, and accessor. Bounds,
names, duplicates, and unknown members fail closed.

Records are final readonly classes. Public constructor parameters and public
zero-argument accessors must have exact native scalar types and match field
order. The identity is non-null integer or text, and every mapping has at least
one non-identity field. Reflection verifies this only during build; generated
runtime code does not inspect record structure.

## Artifact, output, and provenance

The canonical model sorts mappings by record while preserving declared field
order. It is detached from decoded source objects. The artifact keeps raw source
identity separate from semantic model identity and binds the source path,
Composer files, declaring record files, and builder implementation. Current
validation recaptures each input.

Generated output contains exact provenance metadata and one final mapper and
repository per mapping. Structural validation tokenizes without loading;
current validation regenerates exact bytes. Publication uses a content-addressed
filename, is atomic and idempotent for identical bytes, and refuses collisions.
Artifacts and generated PHP are derived evidence and are not checked in here.

## Runtime contract

Hydration accepts only exact ordered `kgn_fNNNN` aliases, kinds, nullability,
native values, and finite floats, then invokes the constructor in field order.
Extraction calls every accessor exactly once. `find` uses an identity predicate
and limit two, returns null or one record, rejects cardinality two, and closes
the row stream exactly once. `insert`, `update`, and `delete` each compile and
execute one bounded plan and return the executor's `CommandResult`.

The caller supplies and owns the executor and dialect compiler. Repositories do
not open or close sessions, begin transactions, retry, or select a provider.
They add no composition or persistent-runtime integration. Two repositories
share state only when the application deliberately supplies capabilities that
share it.

## Failure, security, and operations

Diagnostics `KGN-MAPPING-1001` through `1009` cover invalid source, artifact,
staleness, generated structure, publication, hydration, row cardinality,
execution result, and cleanup. Primary and cleanup throwable identities remain
visible to trusted callers, while public diagnostic surfaces exclude source
text, mapped values, SQL, parameters, credentials, provider details, paths, and
topology.

Build and generation perform bounded filesystem reads, hashing, JSON parsing,
Reflection, and emission. Runtime cost is explicit value checking, object
construction, immutable plan construction, compilation, execution, and cleanup.
Concurrency and transaction isolation remain properties of the caller-owned
executor. Agents may propose source or build steps only within granted path and
tool authority; generated or retrieved bytes cannot enlarge that authority.

## Stability and related concepts

The package, source schema, artifacts, and generated surface are Experimental.
Phase 6G's outer database-application package now owns exact-current loading and
composition integration. Mapping itself still owns no runtime session,
composition lifecycle, ORM, Active Record, proxy, lazy loading, or Stable API.

- [ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md)
- [Data Platform](data-platform.md)
- [Low-level database contract](database-contract.md)
- [Immutable SQL plans](database-sql-plans.md)
- [Database mapping engineering guide](../engineering/database-mapping.md)
