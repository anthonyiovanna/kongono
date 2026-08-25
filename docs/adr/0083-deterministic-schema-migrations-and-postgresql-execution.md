# ADR 0083: Deterministic Schema Migrations and PostgreSQL Execution

- **Status:** Accepted
- **Decision type:** Phase 6E implementation boundary
- **Stability:** Experimental APIs and artifacts

## Context

Kongono already has explicit database sessions and transactions plus immutable
SQL plans. Schema evolution needs its own authority and lifecycle. Runtime file
discovery, class scanning, live-schema inference, and arbitrary SQL migration
callbacks would obscure what is authorized, make identity environment-dependent,
and weaken persistent-runtime isolation.

## Decision

The application constructs one immutable `MigrationCatalog`; no database state,
filesystem order, reflection result, or generated output can become source
authority. Definitions use contiguous ordinals, bounded identifiers, explicit
Apply and optional Revert direction plans, and the closed eight-operation schema
vocabulary. Column types are the closed Boolean, Int64, Float64, Text, Binary,
Uuid, Json, and TimestampTz set.

Each direction declares Safe or Destructive risk, and construction rejects a
declaration that differs from risk derived from its operations. Execution then
applies a second gate: a destructive direction runs only under explicit Allow
policy. RevertLast affects only the final applied definition; an absent revert
plan is deliberately irreversible and fails visibly.

Canonical compact JSON with fixed keys and lowercase tokens binds each complete
definition, including revert semantics. SHA-256 identifies those bytes. A
catalog identity binds the ordered definition identities. These values are
derived evidence; they do not replace the caller-owned typed catalog.

Applied history must be a complete exact prefix of the current catalog. Missing,
changed, reordered, inserted-before-applied, unknown, duplicated, gapped, or
beyond-catalog records fail closed before application DDL.

`kongono/database-migration` owns the neutral catalog, operations, identity,
history, planning, result, executor contract, failures, and diagnostics.
`kongono/database-migration-pgsql` depends on that package plus the existing
neutral database and SQL identifier contracts. It owns PostgreSQL 17 DDL,
history storage, locking, and execution. The dependency is one-way.

The PostgreSQL runner accepts a caller-owned open session, requires transaction
and Serializable capabilities, begins exactly one Serializable ReadWrite
transaction, attempts one transaction-scoped advisory lock without waiting,
bootstraps and reads the configured history inside that transaction, validates
history and the reserved-history boundary, runs ordered DDL and one history
insert or delete per changed migration, then commits once. No-op calls retain
the same lock/read/commit proof. The runner closes the transaction boundary but
never the caller session.

Before commit, failure triggers one rollback attempt and deterministic
primary-plus-cleanup composition. A failed commit is uncertain: it is exposed,
never retried, and never followed by automatic down execution. PostgreSQL's
transactional DDL is proved for the accepted profile, but Kongono makes no
universal cross-provider rollback or distributed guarantee.

Diagnostics are exactly `KGN-MIGRATION-1001` through `1007`. Presentation is
deterministic and non-disclosing. IDs and checksums from application fixtures,
catalog bytes, history configuration, advisory keys, schema object names, SQL,
parameters, values, credentials, provider messages/codes, native classes,
traces, paths, and topology are excluded.

## Evidence

Focused tests prove bounds, canonical identity, exact-prefix planning, risk and
policy gates, DDL compilation, transaction ordering, failure composition,
diagnostic disclosure, and architecture boundaries. The root external harness
uses caller-supplied PostgreSQL 17.2 binaries, loopback-only ephemeral state,
independent sessions, real advisory-lock contention, transactional DDL rollback,
history tamper cases, and complete cleanup.

## Consequences

Migration source is explicit and reviewable; changes to already-applied
definitions are detected deterministically. Applications must deliberately
construct catalogs, choose policy, configure PostgreSQL infrastructure identity,
and handle irreversible or uncertain outcomes. Portability remains honest: the
neutral model is provider-independent, while DDL and transaction guarantees are
profile-owned.

## Rejected alternatives

- Runtime filesystem or class discovery.
- Filename, timestamp, or live-schema authority.
- Arbitrary SQL strings, callbacks, or migration object execution.
- Risk inferred only at execution time or destructive work allowed implicitly.
- Service location, ambient sessions, runtime reflection, or automatic retry.
- Automatic down migration after failure.
- History adoption, rewriting, checksum bypass, or provider-error disclosure.
- An ORM, mapper, repository, Active Record model, proxy, or lazy loading.

## Non-goals

This decision adds no CLI, migration-file format, loader, production service,
deployment integration, schema introspector, diff engine, another provider,
mapping metadata, generated repository, composition integration, Phase 6F work,
Stable API, production-readiness claim, or universal atomicity guarantee.
