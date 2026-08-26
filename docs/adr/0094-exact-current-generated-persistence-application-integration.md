# ADR 0094: Exact-current generated persistence application integration

Status: Accepted

## Context

Phase 7F produced deterministic persistence artifacts, state adapters, and
aggregate repositories, but applications still lacked one supported build
boundary that joined those outputs to generated Composition and the existing
database-operation lifecycle. The existing mapping compiler intentionally
rejects generated mapper or repository class collisions, so persistence must
be compiled before generated data-access PHP executes.

## Decision

Kongono adds the Experimental `kongono/persistence-application` package. Its
workflow prepares mapping and data-access outputs without executing generated
PHP, compiles and validates persistence evidence, loads generated persistence
state followed by aggregate repositories, then loads the prepared data access.
Only after all three exact generated sets are loaded does it invoke the existing
Composition workflow exactly once.

Preparation and loading remain one implementation in
`DataAccessBuildWorkflow`. Data-only applications continue to prepare and load
data access before Composition, preserving their observable behavior.

Every artifact and generated file is content addressed, contained, bounded,
current, and reverified. Build may publish complete immutable outputs; check is
non-mutating and requires them. Generated persistence loading rejects unsafe
paths, stale or mixed bytes, collisions, incorrect class origins or shapes, and
changed-during-read state. Generated code executes only after every mapping and
persistence output passes non-executing validation.

Runtime composition uses the existing `GeneratedDataApplication` boundary.
Database sessions, repositories, adapters, contexts, aggregates, and the typed
root are operation-owned or shorter-lived. Cleanup remains explicit and occurs
in generated reverse construction order. Application shutdown continues to
refuse live operations.

Provider selection and transaction authority remain with the host. The
integration package does not select a connector or repository, create a
persistence context, begin or complete a transaction, flush, cascade, retry,
reconnect, migrate, or pool resources automatically.

The optional copy-once `persistence-minimal` skeleton demonstrates one aggregate
and one bounded ordered relationship through explicit typed capability
adapters. It is an example, not production policy.

## Rejected alternatives

- Weakening mapping collision rejection or admitting already-loaded generated
  data classes during persistence compilation.
- Passing unverified mapping bytes around persistence validation.
- Runtime reflection, scanning, dynamic class strings, service location,
  automatic repository selection, ambient sessions, hidden transactions,
  recursive cascades, retries, or lazy loading.
- A second container, lifecycle, cleanup composer, or generated-output registry.

## Consequences

The supported build order is explicit and fail-closed. Applications gain one
deterministic path from mapping and persistence declarations to typed generated
wiring without granting runtime authority to build artifacts. Generated class
collisions intentionally make repeated same-process loading fail.

