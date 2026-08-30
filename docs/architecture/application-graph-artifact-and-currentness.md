# Application Graph Artifact and Currentness

## Purpose and current status

Phase 11E implements Experimental `kongono/application-graph-artifact`, the
canonical durable evidence form for one already-built Phase 11 Graph. Phase 11F
adds its bounded neutral inspection and the separate upper supported CLI
consumer without changing artifact currentness semantics.

## Deterministic construction

The caller supplies one `AuthoritativeApplicationGraph`. The artifact builder
recreates Phase 11C IR claims from its public facts and runs the existing Graph
builder once. Serialization occurs only after fresh complete admission, retains
no cross-call state, and performs no filesystem I/O.

The `graph` object contains the exact contract version, counts, canonical nodes,
relations, fields, disclosure, visibility, and provenance. Its canonical bytes
produce the Graph SHA-256 and typed Graph identity. The whole artifact bytes
produce the artifact SHA-256.

Builder metadata records only the stable package name and artifact contract
version. It does not identify runtime source-code bytes and is not a signature,
build attestation, or supply-chain proof.

## Validation and currentness

Structural validation strictly decodes the exact schema, rejects duplicate or
unknown keys and noncanonical JSON, reconstructs typed records, repeats Graph
admission, and verifies every count and identity. No partial state is returned.

Current validation first proves structure, then builds a fresh artifact from
the caller-supplied current Graph and requires exact byte equality. This says
nothing about whether Composition source, project files, or a workspace are
current; those claims require their own accepted evidence.

## Loading and publication

Loading accepts one explicitly named canonical absolute ordinary file matching
`kongono-application-graph-v1-<artifact-sha256>.json`. It does not enumerate,
select, retry, fall back, watch, or cache. Publication accepts one explicit
existing canonical absolute directory and writes a temporary ordinary file in
that directory before atomic no-overwrite publication. Identical existing bytes
are idempotent; conflicting targets are never replaced.

Every existing path ancestor is checked against links, junctions, reparse
aliases, and resolved-path divergence. Reads are bounded and compared before
and after through the opened handle. Only temporary files created by the active
publication call may be removed.

## Security, lifecycle, and use

The artifact may contain Protected application facts and must not be exposed as
public presentation. Graph provenance remains non-authorizing evidence.
Instances are immutable, detached, and operation-neutral. Concurrent callers
share no package state.

Use the package when an already-built Graph needs deterministic structural
evidence, exact comparison, guarded explicit loading, or no-clobber publication.
Use `inspection()` only for the bounded aggregate structural summary documented
in the [inspection guide](application-graph-inspection-and-consumption.md). Do
not use the package for source discovery, individual Graph facts, authorization, registries,
runtime service location, AI knowledge, deployment, or production certification.

Kongono remains pre-1.0 and not production-ready.

## Related material

- [ADR 0127](../adr/0127-canonical-application-graph-artifact-currentness-and-publication.md)
- [ADR 0128](../adr/0128-bounded-application-graph-inspection-and-supported-cli-consumption.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [IR and construction](application-graph-ir-and-construction.md)
- [Composition projection](composition-application-graph-projection.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
