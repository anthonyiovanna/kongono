# Phase 6 Roadmap

## Purpose and milestone boundary

Phase 6 is the Deterministic Data Platform milestone. It establishes layered,
explicit, persistent-runtime-safe data capabilities without making the Kernel a
database owner or collapsing connectivity, SQL, migrations, mapping, and
lifecycle into a monolithic ORM.

Phases 6A and 6B accept architecture and documentation only. Phase 6B fixes the
neutral database and first PostgreSQL PDO adapter boundaries plus exact
version-1 execution, result, capability, transaction, lifecycle, and error
contracts. Phase 6C implements that boundary with two Experimental packages and
real isolated PostgreSQL 17.2 acceptance. Phase 6D is Accepted and implements
the separate immutable plan and explicit PostgreSQL 17 compiler layer. Phase 6E
is Accepted and implements typed schema migrations and the PostgreSQL 17
execution profile under ADR 0083.
Phase 6F is Accepted and implements optional compile-time mapping and generated
typed Data Mapper/repository support under ADR 0084. Phase 6G is Accepted and
implements generated data-application composition, operation-owned sessions,
and the optional data-minimal workflow under ADR 0085. Phase 6H is Accepted as
the external evidence, measurement, documentation, and checkpoint boundary
under ADR 0086. The milestone is checkpointed only after its single authorized
commit and every post-commit gate passes.

## Entry state

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e`. That checkpoint provides the
supported pre-1.0 application-composition, deterministic build, generated
wiring, existing web lifecycle integration, minimal skeleton, and external
application acceptance boundary. Kongono remains pre-1.0 and not production-
ready.

The Phase 6A entry boundary had nineteen production packages and no Data
Platform package or database runtime. The current repository has twenty-seven
production packages after the accepted Phase 6G implementation.

## Fixed dependency sequence

| Phase | Boundary |
|---|---|
| `6A` | Accepted: Deterministic Data Platform milestone, layered SQL/data doctrine, persistent-runtime constraints, and fixed A–H roadmap. Documentation only. |
| `6B` | Accepted: exact future neutral/first-adapter boundaries and low-level capability, execution, result, error, transaction, lifecycle, PostgreSQL PDO proof-profile contract. Architecture only; no driver implementation. |
| `6C` | Accepted: minimal driver-neutral database contracts, one PostgreSQL PDO adapter, focused unit/static/architecture proof, and isolated real PostgreSQL 17.2 acceptance. No query builder, migrations, or ORM. |
| `6D` | Accepted: immutable bounded Select/Insert/Update/Delete plans, explicit mutation scope, declared capabilities, deterministic named parameters, and PostgreSQL 17 compilation. Raw SQL remains first-class; no mapping or migrations. |
| `6E` | Accepted: deterministic typed migration catalogs, canonical identities, exact-prefix history, explicit destructive policy, and transactional PostgreSQL 17 execution. No ORM. |
| `6F` | Accepted: explicit compile-time mapping metadata, provenance artifacts, and generated typed Data Mapper/repository support. No runtime reflection, proxies, hidden lazy loading, or Active Record base model. |
| `6G` | Accepted: generated data-application composition, exact-current mapping/data loading, operation-owned sessions, and one optional minimal supported application data workflow. |
| `6H` | Accepted: whole-platform PostgreSQL 17 acceptance, isolation, transaction, migration, exact-current refusal, nondisclosure, local observational performance baseline, documentation, and exact checkpoint proof. |

The table fixes execution order and scope only. Every later subphase requires a
separately authenticated directive and architect acceptance. A roadmap row does
not select its implementation details early.

## Dependency graph

```text
6A architecture and doctrine
  -> 6B low-level contracts
      -> 6C first accepted adapter
          -> 6D SQL plans and dialect compilation
              -> 6E schema and migrations
                  -> 6F generated Data Mapper support
                      -> 6G composition and operation integration
                          -> 6H external acceptance and checkpoint
```

## Fixed milestone invariants

- The five conceptual layers remain distinct even if later package boundaries
  group implementation selectively.
- Raw SQL remains an explicit parameter-bound escape hatch.
- Provider capabilities and unsupported behavior remain visible.
- Connections, transactions, results, repositories, and mappers use explicit
  composition or method boundaries, never ambient authority.
- Operation resources have explicit lifecycle, cleanup, concurrency, and
  combined-failure rules suitable for persistent runtimes.
- Transactions are explicit; arbitrary callback retry and distributed
  guarantees are not inferred.
- Derived plans and generated PHP are deterministic evidence, not authority.
- Production requests perform no filesystem scanning, arbitrary mapping
  reflection, proxy generation, `eval`, or generated-output mutation.
- Data Mapper is optional; Active Record, hidden lazy loading, and runtime
  proxy subclasses are not default architecture.
- Migration source, order, checksums, locking, preconditions, history, partial
  failure, destructive review, and reversibility remain explicit.
- Diagnostics preserve the existing non-disclosure doctrine.
- Provider, portability, and performance claims require real evidence.

## Acceptance themes

### 6A: architecture truth

Documentation must fix the layered direction, explicit SQL, persistent-runtime
constraints, rejected magic, and non-implementation boundary without selecting
a package, provider, API, schema, or command.

### 6B: exact low-level contracts

Accepted architecture defines `kongono/database` under
`Kongono\Database\` and separate `kongono/database-pdo-pgsql` under
`Kongono\DatabasePdoPgsql\`, with the adapter depending on the neutral package.
It fixes immutable parameter-bound unparsed SQL, exact scalar/binary values and
bounds, explicit `Rows`/`Completion` expectations, session capabilities,
duplicate-safe ordered results, one active stream/transaction, exact transaction
states/options, reverse cleanup, primary-plus-cleanup failure retention, no
automatic retry, and non-disclosing failure categories. PostgreSQL through PDO
`pdo_pgsql` is only the first proof profile.

### 6C: real adapter evidence

Accepted. `kongono/database` implements the neutral Experimental contract and
`kongono/database-pdo-pgsql` implements the first adapter without exposing PDO.
Focused proof covers exact bounds, results, capabilities, lifecycle, diagnostics,
dependency direction, and native isolation. The external harness proves 59 cases
against caller-supplied PostgreSQL 17.2 with loopback-only ephemeral state and
complete cleanup. Unsupported behavior remains visible; no automatic retry,
query builder, migrations, ORM, composition integration, or production provider
startup enters this slice.

### 6D: immutable SQL plans

Accepted. `kongono/database-sql` implements explicit parameterized inspectable
plans under a closed version-1 capability vocabulary.
`kongono/database-sql-pgsql` implements stateless PostgreSQL 17 compilation,
quoted identifiers, deterministic occurrence-ordered parameters, explicit all-
row mutation intent, and non-disclosing failures. Raw SQL remains supported and
reviewable outside the plan model.

### 6E: schema and migration evidence

Accepted. `kongono/database-migration` implements the explicit caller-owned
catalog, operations, canonical identity, history, planning, policy, result, and
failure vocabulary. `kongono/database-migration-pgsql` implements PostgreSQL 17
DDL, a configured reserved history table, transaction-scoped advisory locking,
single-transaction execution, rollback and uncertainty behavior. No discovery,
live-schema authority, automatic retry/down, ORM, or mapping is added.

### 6F: generated Data Mapper support

Accepted. Optional Experimental `kongono/database-mapping` compiles one explicit
application-owned inert source before runtime. Build-time Reflection validates
exact final readonly scalar records; canonical provenance artifacts generate
readable typed mappers and four-operation repositories through caller-owned
executor/compiler capabilities. Generated output is derived and not checked in.
Raw SQL and typed plans remain first-class. Runtime Reflection, proxy generation,
hidden lazy loading, framework base models, production loading, and composition
integration remain prohibited.

### 6G: lifecycle integration

Accepted. `kongono/database-application` validates and loads current mapping
output before the accepted Composition workflow, opens one connector session
for each generated operation, exposes exactly one typed operation root, and
retains generated lease and reverse-cleanup behavior. The separate data-minimal
skeleton proves the supported build/check and explicit connector path without
moving Kernel, Web Application, Web Runtime, or PHP-CGI responsibilities.

### 6H: external closure

External acceptance proves provider behavior, isolation, transactions,
migrations, stale and tampered state, non-disclosure, measured performance
baseline, documentation, and the final checkpoint. The baseline is versioned,
local, observational, and threshold-free. It adds no runtime instrumentation.

## Prohibited scope and truthful non-claims

Phases 6A and 6B implement no code. Phases 6C through 6G implement eight bounded
packages, root validation integration, focused tests, and isolated external
proof. Phase 6H adds test evidence, documentation, and the checkpoint without
changing those production packages. No ORM, production provider service,
deployment behavior, telemetry, pooling, retry, or further provider is added.

The roadmap does not claim PHP 8.6 or PHP 9 compatibility, Stable API, Kongono
1.0, production readiness, a full framework, universal provider portability,
automatic rollback, distributed transactions, zero overhead, fastest
performance, or superiority over another framework.

## Related authoritative documents

- [ADR 0077: Layered Data Platform and Explicit SQL Boundary](../adr/0077-layered-data-platform-and-explicit-sql-boundary.md)
- [ADR 0078: Deterministic Data Plans and Operation-Scoped Persistence](../adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md)
- [ADR 0079: Neutral Database Session and Provider-Capability Contract](../adr/0079-neutral-database-session-and-provider-capability-contract.md)
- [ADR 0080: Explicit Database Result, Transaction, and Failure Lifecycle](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md)
- [ADR 0081: PostgreSQL PDO Adapter and Real Provider Proof](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md)
- [ADR 0082: Immutable Database SQL Plans and PostgreSQL Dialect Compilation](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md)
- [ADR 0083: Deterministic Schema Migrations and PostgreSQL Execution](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md)
- [ADR 0084: Compile-Time Data Mapping and Generated Repositories](../adr/0084-compile-time-data-mapping-and-generated-repositories.md)
- [ADR 0085: Generated Data Application and Operation-Owned Sessions](../adr/0085-generated-data-application-and-operation-owned-sessions.md)
- [ADR 0086: Phase 6 External Data Platform Acceptance and Checkpoint](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md)
- [Phase 6 external acceptance](phase-6-external-acceptance.md)
- [Database performance baseline](../engineering/database-performance-baseline.md)
- [Data Platform architecture](data-platform.md)
- [Low-level database contract](database-contract.md)
- [Immutable SQL plans](database-sql-plans.md)
- [Deterministic database migrations](database-migrations.md)
- [Compile-time database mapping](database-mapping.md)
- [Database application integration](database-application-integration.md)
- [Database contracts engineering guide](../engineering/database-contracts.md)
- [Architectural doctrine](../doctrine.md)
- [Phase 5 roadmap](phase-5-roadmap.md)
- [Application composition and build](application-composition-and-build.md)
