# ADR 0127: Canonical Application Graph Artifact, Currentness, and Publication

## Status

Accepted on 2026-08-28.

## Context

Phase 11D can build one canonical in-memory `AuthoritativeApplicationGraph`
from an exact Composition v2 artifact. Phase 11E needs durable deterministic
evidence without adding discovery, inspection, runtime integration, or ambient
authority.

## Decision

Create the Experimental leaf package `kongono/application-graph-artifact`.
It depends inward only on PHP, Hash, JSON, API Metadata, Application Graph
Contract, Application Graph Build, and Diagnostics. No lower production package
depends on it.

The builder accepts one already-built Graph, re-expresses its facts as Phase 11C
IR, and re-admits the complete record set through `ApplicationGraphBuilder`.
Only the fresh canonical result is serialized. Construction is pure, detached,
stateless, all-or-nothing, and free of filesystem access.

The compact UTF-8 artifact uses schema `kongono.application-graph-artifact`,
version 1, Graph contract version 1, fixed bounds, exact key and list order, and
one final LF. Strict decoding rejects duplicate keys, noncanonical bytes,
unknown members, floats, alternate versions, malformed identities, invalid
facts, and inconsistent counts or digests.

Graph identity is `kgn:graph:v1:graph:<sha256>`, where the digest covers only
the canonical `graph` object including its final LF. Artifact identity is the
SHA-256 of the complete canonical artifact. The builder record contains only
stable package `kongono/application-graph-artifact` and contract version `1`.
The artifact does not attest to source-code bytes. Source/build provenance
attestation, signing, and supply-chain verification are not Phase 11E behavior.

Structural validation rehydrates typed identities, enums, provenance, fields,
IR records, and the canonical builder result. Exact-current validation then
requires byte equality with a fresh artifact built from one caller-supplied
current Graph. It proves no Composition source or workspace currentness.

The loader accepts one explicit canonical absolute ordinary content-addressed
file and performs a bounded stable handle read. The publisher accepts one
explicit existing canonical absolute directory and performs same-directory,
atomic, initial-only, idempotent, no-overwrite publication. Links, junctions,
reparse traversal, aliases, changed reads, conflicts, and residue fail visibly.

Artifacts include exact Protected Graph facts and are application-sensitive.
Provenance is evidence, never permission. Diagnostics expose only closed Safe
tokens and bounded counts or positions.

## Consequences

Equivalent Graph facts produce byte-identical identities and artifacts across
sequential, nested, interleaved, overlapping, and concurrent use. Publication
cost is linear in artifact size and construction repeats bounded Graph
admission. No cache, registry, watcher, newest-file selection, retry, discovery,
query, traversal, inspection, CLI, application consumer, developer-intelligence
access, extension contribution, Agent Principal, frontend contract, security
authority, runtime integration, or production-readiness claim is added.

Phase 11F bounded inspection and application/CLI consumption is next and
unstarted. Kongono remains pre-1.0 and not production-ready.

## Related material

- [Artifact and currentness guide](../architecture/application-graph-artifact-and-currentness.md)
- [ADR 0126](0126-supported-composition-to-ir-projection-and-application-graph-build.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)
