# Phase 7 External Persistence Acceptance

## Purpose and completed boundary

Phase 7A through 7H form Kongono's completed Deterministic Persistence milestone
above the Phase 6 Data Platform. The thirty-package graph keeps application
domain objects plain, compiles mapping and persistence metadata before runtime,
and loads ordinary generated PHP only after exact-current validation.

The upper persistence workflow is optional. Applications may continue to use
parameter-bound SQL, immutable SQL plans, migrations, generated Data Mappers,
and direct typed repositories without a persistence context.

## Acceptance profiles

Phase 7 uses PostgreSQL 18 as its primary current major and PostgreSQL 18.6 as
the exact accepted checkpoint minor:

```text
composer acceptance:phase7 -- "C:\laragon\bin\postgresql\postgresql-18.6-1-windows-x64-binaries\bin"
```

Phase 6 regression remains separate historical PostgreSQL 17.2 evidence:

```text
composer acceptance:phase6 -- "C:\laragon\bin\postgresql\postgresql-17.2-3-windows-x64-binaries\bin"
```

Within PostgreSQL 18, a newer stable minor becomes the maintained profile only
after this complete acceptance is rerun. Another minor or major requires fresh
evidence. The accepted 18.6 result is not a claim of universal PostgreSQL
compatibility.

## What the Phase 7 command proves

The root-owned harness creates a loopback-only private provider and a temporary
copy of the persistence-minimal application. It preserves the application's
authoritative Composer and source bytes, performs an offline locked local
install, and reproduces the seven accepted mapping, persistence, and Composition
identities before loading generated PHP.

The runtime matrix proves:

- one operation reuses one domain object for one explicit identity;
- sequential and overlapping operations have distinct sessions, roots,
  repositories, contexts, snapshots, and domain instances;
- aggregate loading uses a declared ordered plan with a fixed statement bound
  and no I/O after the load returns;
- cascades follow one reviewed level and reject undeclared evidence;
- callers explicitly control transactions, commit, rollback, and flush;
- optimistic conflict, absence, provider failure, and terminal uncertainty stay
  distinct, with no automatic retry or replay;
- operation failure retires state and leaves an independent peer usable;
- generated repositories retain no runtime authority or cross-call state; and
- provider messages, credentials, SQL, parameters, domain values, identities,
  versions, relationship contents, paths, and sentinels remain undisclosed.

Cleanup completes before success output: every owned session and provider is
closed, test database authority is removed, and temporary generated evidence is
deleted.

## Evidence and authority

Mapping sources and application code remain authoritative. Canonical artifacts,
generated state, generated repositories, generated wiring, Hantei verdicts, and
acceptance JSON are derived evidence tied to exact current bytes. They cannot
select a provider, authorize a transaction, mutate a schema, stage a path, or
certify changed inputs.

Phase 7 does not provide Active Record, transparent persistence, lazy loading,
automatic flush, automatic schema synchronization, hidden transactions,
recursive cascade, runtime mapping discovery, a generic magic query language,
production readiness, Stable API, PHP 8.6/PHP 9 support, or competitor
superiority.

## Related guides

- [ADR 0095](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md)
- [Deterministic Persistence](deterministic-persistence.md)
- [Persistence application integration](persistence-application-integration.md)
- [Phase 7 roadmap](phase-7-roadmap.md)
- [Persistence performance baseline](../engineering/persistence-performance-baseline.md)
