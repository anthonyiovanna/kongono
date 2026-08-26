# ADR 0093: Explicit load plans, bounded cascades, and generated aggregate repositories

## Status

Accepted for Kongono Phase 7F. The contracts remain Experimental and pre-1.0.

## Context

Phase 7E proves one explicit single-entity persistence operation, but it does not
define relationship loading or cascade orchestration. Adding those capabilities
through runtime discovery, proxies, dynamic finders, or an entity manager would
hide database authority and make I/O, bounds, and transaction ownership difficult
to audit.

The accepted version-1 `kongono.persistence.json` source is already authoritative
for persistence model evidence. Changing its schema silently would mix accepted
model authority with a new repository policy contract.

## Decision

Applications declare repository policy in the separate inert source
`kongono.persistence-repositories.json`, schema
`kongono.persistence-repositories`, version `1`. It references one relative
current `kongono.persistence.json` source. Unknown keys, duplicate members,
unsafe paths, unstable reads, invalid references, incomplete cascade coverage,
and values outside the public bounds fail closed.

Each selected aggregate has one generated final repository class. Repository
classes implement neutral metadata and add concrete source-named load methods.
Every load call receives an `EntityIdentity`, a caller-owned `DatabaseExecutor`,
and a caller-owned `AggregateLoadExecutor`. Explicit `persist()` and `remove()`
calls receive `LoadedAggregate`, the caller database, and a caller cascade
executor. Each operation forwards those exact objects once and retains none.

`LoadedAggregate` is detached explicit evidence: one root plus an ordinal
relationship map. Repositories validate root type, key set, target type,
cardinality, nullability, per-relationship bounds, and total bounds. They do not
attach relationships to domain objects, manufacture proxies, or reorder to-many
evidence.

Cascade policy is explicit for every relationship of a selected aggregate.
Only aggregate-owned relationships may enable persist or remove. Plans are
one-level and bounded; they do not discover or recursively traverse object
graphs. The caller retains begin, commit, rollback, close, conflict policy, and
retry decisions.

Generated PHP is derived evidence, never authority. Its metadata binds the
repository source, persistence artifact and model, generator identities,
namespace, class map, and generated-file digest. Validation tokenizes without
execution; current validation recompiles and regenerates exact bytes. Optional
publication is content-addressed, atomic, idempotent for identical bytes,
initial-only, and no-clobber.

## Consequences

- Database I/O authority is visible at every generated operation call.
- Named methods replace stringly query magic and generic criteria.
- Load and cascade work has deterministic public upper bounds.
- Absence, optimistic conflict, executor failure, and uncertain outcomes remain
  distinct caller-visible results and are never retried automatically.
- No hidden lazy I/O, N+1 planning, recursive cascade, automatic flush,
  transaction ownership, runtime Reflection, scanning, provider selection,
  production loader, or Composition registration is introduced.

## Rejected alternatives

- Rewriting persistence source version 1.
- Runtime relationship discovery, proxies, or transparent collections.
- Dynamic finders, generic query languages, and arbitrary join planning.
- Reference-owned cascades or recursive graph walking.
- Automatic transactions, flush, retry, or provider selection.

Phase 7G Composition and exact-current runtime loading and Phase 7H external
PostgreSQL closure remain planned and have not begun.
