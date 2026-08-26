# Phase 7 Roadmap

## Purpose and accepted entry checkpoint

Phase 7 is the optional Deterministic Persistence milestone above the completed
Phase 6 Data Platform. It provides the developer-facing role commonly called an
ORM while preserving plain PHP domain ownership, compile-time evidence, explicit
I/O and transaction authority, operation-scoped state, and direct Phase 6 SQL
and Data Mapper use.

The accepted entry is Phase 6 checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05`, tree
`1f00659b980bd0094cf80efcb37a59f1b89128e1`, with twenty-seven production
packages. Phase 7A accepts [ADR 0087](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
and this fixed roadmap as documentation architecture. Phase 7B accepts
[ADR 0089](../adr/0089-neutral-persistence-contracts-and-package-boundary.md)
and adds the Experimental neutral contract package. Phase 7C accepts
[ADR 0090](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md)
and adds Experimental `kongono/persistence-build`, bringing the current graph to
twenty-nine packages. Phase 7D accepts [ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md)
and adds deterministic ordinary PHP state evidence inside that existing package.
Phase 7E accepts [ADR 0092](../adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md)
and implements the first single-entity runtime boundary. Phase 7F accepts
[ADR 0093](../adr/0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md)
and implements explicit bounded aggregate repository generation. Phase 7G
accepts [ADR 0094](../adr/0094-exact-current-generated-persistence-application-integration.md)
and implements exact-current generated loading and Composition integration.
Phase 7H accepts [ADR 0095](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md)
and closes the milestone through PostgreSQL 18.6 external acceptance,
PostgreSQL 17.2 Phase 6 regression, documentation, and the exact checkpoint,
bringing the current production graph to thirty packages.

## Fixed Phase 7 sequence

| Phase | Fixed boundary |
|---|---|
| `7A` | Accept Deterministic Persistence doctrine, explicit ORM boundaries, persistent-runtime/security gates, and the fixed A-H roadmap. Documentation only. |
| `7B` | Implemented: minimal neutral persistence identity, state, context, change-set, flush-result, concurrency, and failure contracts. No mapping source, generated repository, relationships, provider code, or runtime integration. |
| `7C` | Implemented: one bounded versioned compile-time persistence source/model/artifact for entities, value fields, identities, concurrency tokens, aggregates, and explicit relationships, with build-time-only Reflection and no database/runtime persistence I/O. |
| `7D` | Implemented: deterministic ordinary PHP hydration, extraction, identity, snapshot, and change-set evidence with maximum static-analysis proof. No proxies, lazy loading, or database execution. |
| `7E` | Implemented: one operation-scoped persistence context and explicit single-entity unit-of-work execution over caller-owned Phase 6 database authority, including terminal failure and optimistic concurrency semantics. No relationships or cascades. |
| `7F` | Implemented: explicit bounded relationship/load-plan and aggregate repository generation with reviewed ownership, ordering, and one-level cascade policies. No hidden lazy I/O, recursive graph walk, or generic query language. |
| `7G` | Implemented: exact-current generated persistence and deferred data-access loading with Composition and an optional persistence-minimal workflow, preserving operation cleanup and explicit provider/transaction authority. |
| `7H` | Implemented: whole-platform PostgreSQL 18.6 acceptance, identity/isolation/concurrency/relationship/nondisclosure/performance proof, documentation reconciliation, and the exact Phase 7 checkpoint. |

The sequence fixes boundaries and order only. Each later letter requires its own
authenticated architect directive. Phase 7C authorizes only the exact build-time
source/model/artifact boundary. Phase 7D authorizes only the generated-state
evidence boundary; it does not pre-authorize commands, runtime loading, a
context implementation, or Phase 7E work.

## Dependency graph

```text
Phase 6 checkpoint: explicit Database, SQL, migrations, Data Mapper, Composition
    -> 7A doctrine and fixed boundaries
        -> 7B neutral persistence contracts
            -> 7C versioned source, model, and artifact
                -> 7D ordinary generated PHP evidence
                    -> 7E operation context and explicit unit of work
                        -> 7F relationships, load plans, and aggregates
                            -> 7G exact-current Composition integration
                                -> 7H external acceptance and checkpoint
```

Lower Phase 6 packages never depend upward on Phase 7. Direct SQL, plans,
migrations, generated Data Mappers, and direct repositories remain available
throughout the sequence.

## Fixed milestone invariants

- Domain objects remain application-owned plain PHP classes with reviewed
  invariant-preserving construction or factory boundaries.
- Metadata is deterministic, bounded, versioned, reviewable, and compiled before
  runtime; generated bytes are derived evidence, not authority.
- No runtime mapping Reflection, scanning, proxies, `eval`, mutable registries,
  service location, private-state bypass, or remote generated code is accepted.
- Persistence contexts, identity maps, snapshots, change sets, and loaded domain
  references are operation-scoped, bounded, retired, and never reused.
- Flush and transaction decisions are explicit; no destructor, shutdown,
  property access, arbitrary call, retry, reconnect, or isolation widening hides
  authority.
- Relationships, aggregate boundaries, load plans, ordering, and cascades are
  explicit and bounded; no lazy proxy, hidden N+1, unbounded graph walk, or
  recursive magic is permitted.
- Optimistic concurrency, absence, provider failure, and terminal uncertainty
  remain distinguishable without private disclosure or automatic replay.
- Diagnostics exclude secrets, connection material, SQL, parameters, domain
  data, identities, versions, relationships, snapshots, change sets, generated
  bytes, paths, provider messages, stack arguments, and topology.
- Persistent workers release every operation-owned persistence and domain
  reference after every operation.
- Portability, performance, compatibility, and production-readiness claims
  require separately accepted evidence.

## Acceptance themes

### 7A: architecture truth

Accept the explicit persistence/ORM boundary, operation-scoped authority,
persistent-runtime and security rules, progressive lower-layer use, and this
fixed roadmap. Documentation only; no runtime or package exists.

### 7B: implemented minimal neutral contracts

The Experimental `kongono/persistence` package proves a deliberately small
identity, state, context-interface, change-set, flush-result, concurrency,
lifecycle, and failure vocabulary without choosing mapping, relationships,
provider execution, repositories, or integration.

### 7C: versioned compile-time evidence

Prove one bounded inert source, canonical model, provenance-bearing artifact,
safe reading, exact-current validation, and explicit entity/value/identity/
concurrency/aggregate/relationship declarations without runtime Reflection or
database I/O.

### 7D: generated state evidence

Implemented deterministic ordinary PHP hydration, extraction, identity,
snapshots, and change sets through syntax, maximum static analysis, exact
regeneration, and invariant-preserving public construction. Candidate validation
does not execute generated bytes. No proxy, lazy loading, or database execution.

### 7E: operation context and writes

Prove bounded identity, explicit state transitions and flush, caller-owned
transaction execution, optimistic conflicts, terminal uncertainty, reverse
cleanup, isolation, nondisclosure, and single-entity behavior only.

### 7F: bounded aggregates and relationships (implemented)

Prove declared ownership, cardinality, nullability, ordering, named load plans,
aggregate repositories, bounded cascades, visible I/O, and rejection of hidden
lazy loading, recursive traversal, and a general magic query language.

### 7G: supported application workflow (implemented)

Prove exact-current generated persistence loading through Composition, one
optional copy-once workflow, operation-owned cleanup, and explicit host/provider/
transaction authority without widening lower package ownership.

### 7H: external closure

Proves the whole platform against real PostgreSQL with identity, isolation,
concurrency, relationship, nondisclosure, cleanup, and named performance
evidence; reconcile current documentation and create the exact checkpoint.

## Prohibited scope and truthful non-claims

Phase 7A implemented no PHP. Phases 7B through 7G implement the neutral
contracts, build evidence, generated state, explicit single-entity context,
bounded aggregate repositories, exact-current loader, and optional application
workflow described above. Phase 7H adds only root-owned external test evidence,
documentation, and checkpoint mechanics. It adds no production command,
provider selection, SQL generation, transaction owner, deployment behavior, or
runtime telemetry.

The milestone rejects Active Record as its core model, magic/dynamic properties,
runtime subclasses, runtime mapping discovery, private-state bypass, ambient
sessions, hidden transactions or retries, automatic flush, lazy proxies, hidden
N+1 behavior, recursive cascades, automatic schema synchronization, and generic
stringly query magic. It makes no Stable API, full-framework, production-
readiness, deployment, universal portability, zero-overhead, fastest-ORM,
competitor-superiority, PHP 8.6/PHP 9, or migration-compatibility claim.

## Correction-suffix rule

If an accepted letter needs a narrowly authenticated correction, its directive
and report may use an ordinal suffix such as `7C.1` and a corresponding filename
suffix such as `_1_correction`. A correction repairs only its explicitly
authorized boundary. It does not create a new roadmap letter, reorder this
sequence, authorize later work, or rewrite historical directive/report evidence.

## Related authorities

- [ADR 0087: Explicit Persistence Model and Compile-Time ORM Boundary](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md)
- [ADR 0088: Operation-Scoped Identity, Change Tracking, and Transaction Authority](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md)
- [ADR 0089: Neutral Persistence Contracts and Package Boundary](../adr/0089-neutral-persistence-contracts-and-package-boundary.md)
- [ADR 0090: Deterministic Persistence Model and Artifact Compilation](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md)
- [ADR 0091: Deterministic Generated Persistence State Evidence](../adr/0091-deterministic-generated-persistence-state-evidence.md)
- [ADR 0092: Operation-Scoped Persistence Context and Explicit Unit of Work](../adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md)
- [ADR 0093: Explicit Load Plans, Bounded Cascades, and Generated Aggregate Repositories](../adr/0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md)
- [ADR 0094: Exact-current Generated Persistence Application Integration](../adr/0094-exact-current-generated-persistence-application-integration.md)
- [ADR 0095: Phase 7 External Persistence Acceptance and Checkpoint](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md)
- [Persistence build engineering guide](../engineering/persistence-build.md)
- [Persistence generation engineering guide](../engineering/persistence-generation.md)
- [Persistence performance baseline](../engineering/persistence-performance-baseline.md)
- [Phase 7 external acceptance](phase-7-external-acceptance.md)
- [ADR 0086: Phase 6 External Data Platform Acceptance and Checkpoint](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md)
- [Deterministic Persistence architecture](deterministic-persistence.md)
- [Data Platform architecture](data-platform.md)
- [Application Composition and Build](application-composition-and-build.md)
- [Progressive Architecture](progressive-architecture.md)
