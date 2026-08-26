# ADR 0090: Deterministic Persistence Model and Artifact Compilation

- Status: Accepted
- Date: 2026-08-25
- Phase: 7C

## Context

Phase 7B supplies neutral persistence contracts but deliberately owns no mapping
source, relationship vocabulary, reflection, persistence runtime, or database
authority. The next bounded step needs reviewable application persistence intent
without duplicating the physical storage truth already accepted in the Phase 6F
data-mapping source and artifact.

Runtime metadata discovery would make production behavior depend on reflection,
autoload state, scanning, and partial current evidence. It would also blur the
boundary between application domain intent and the physical record, table,
column, scalar-kind, and nullability facts owned by data mapping.

## Decision

Accept the Experimental `kongono/persistence-build` package and the sole
application-owned `kongono.persistence.json` version-1 source. The source names
entities, existing mapped storage records, explicit construction, entity-field
bindings, identity and optional concurrency roles, aggregates, and declarative
relationships. It references one guarded `kongono.data-mapping.json`; it does not
repeat tables, columns, database scalar kinds, or database nullability.

The package rebuilds current mapping evidence through the public
`MappingArtifactBuilder` boundary. Build-time reflection validates explicit
public typed construction and zero-argument accessors, then records normalized
domain-source path, byte count, and SHA-256. Reflection objects and filesystem
handles are not retained in the detached model or artifact.

Compilation produces canonical LF-terminated JSON with a detached persistence
model and bounded provenance for root Composer files, both application sources,
current mapping artifact and mapping model identities, mapping and persistence
builder identities, every reflected domain source, generated namespace, and
canonical persistence-model identity. Raw source bytes remain part of source
identity; object-key order and line-ending changes therefore change provenance
even when the normalized semantic model is equal.

Structural validation proves canonical internal consistency. Exact-current
validation separately rebuilds from all explicit current inputs and compares
canonical bytes. Malformed evidence and stale evidence remain distinct failures.

Publication is explicit, content-addressed, atomic, idempotent for identical
bytes, and no-clobber for conflicting bytes. The application-owned destination
root must already exist. Only the fixed `persistence-artifacts/v1` child
structure may be created after path admission. Traversal, aliases, alternate
streams, links, junctions, reparse points, and destination escape fail closed.

Relationship declarations are evidence only. Cardinality, ownership, and
ordering do not provide loading, collections, cascades, synchronization, joins,
or other runtime behavior.

## Consequences

- Application persistence intent is explicit, versioned, bounded, inspectable,
  deterministic, and reviewable.
- Physical storage truth remains authoritative in data mapping.
- Generated persistence namespace is reserved as evidence, but no PHP is
  generated in Phase 7C.
- Diagnostics and exceptions are package-owned, fixed, actionable, and do not
  disclose rejected source text, private paths, entity values, SQL, or secrets.
- Lower database, mapping, and neutral persistence packages do not depend on the
  new upper build package.

## Deferred capabilities

Phase 7C does not implement generated PHP, runtime artifact loading, entity
hydration, extraction, repositories, persistence contexts, identity maps, units
of work, dirty checking, flush orchestration, optimistic-lock execution,
relationships at runtime, database access, SQL, transactions, migrations,
Composition integration, provider selection, deployment behavior, or Phase 7D.

## Security and file safety

All paths are explicit and contained beneath an admitted root. Source reads are
bounded and reverified after compilation so concurrent byte or metadata changes
fail with a distinct authenticated-input diagnostic. JSON is inert untrusted
data with duplicate-key rejection and closed schemas. Artifact error output and
Safe diagnostic context contain only fixed categorical evidence.

## Related decisions

- [ADR 0084](0084-compile-time-data-mapping-and-generated-repositories.md)
- [ADR 0087](0087-explicit-persistence-model-and-compile-time-orm-boundary.md)
- [ADR 0088](0088-operation-scoped-identity-change-tracking-and-transaction-authority.md)
- [ADR 0089](0089-neutral-persistence-contracts-and-package-boundary.md)
