# ADR 0126: Supported Composition-to-IR Projection and Application Graph Build

## Status

Accepted on 2026-08-28.

## Context

Phase 11C admits explicit language-neutral IR but deliberately owns no source
frontend. Composition is the first accepted application structure source, and
its source/build/reflection authority must not leak into the neutral Graph
contract or construction packages.

## Decision

Create the separate Experimental leaf package
`kongono/application-graph-composition`. It consumes only one already-built
in-memory `CompositionArtifact`, admits only the exact version-2 model, and
projects the closed input, service, contract, and root nodes plus the exact
consumes-input, depends-on, implements-contract, and exposes-root relations.
Default arguments project no relation; identical semantic relations are
deduplicated.

Producer, source, node-category, and relation identities use SHA-256 over
distinct ASCII domains followed by unsigned 32-bit big-endian length-framed
fields. Node and relation identities depend only on their semantic tuples;
source identity and every fresh provenance value bind to the exact artifact
identity. Provenance remains evidence and grants no authority.

Projection is pure, all-or-nothing, binary ordered by canonical identity,
detached, stateless, and fresh across sequential, nested, interleaved, and
overlapping calls. The supported application builder performs one projection
and one call to the existing Phase 11C builder. Existing Graph build failures
are not caught or translated.

The package adds diagnostics `KGN-GRAPH-1111` through `KGN-GRAPH-1115`. The
production graph is 55 acyclic packages and 192 local production edges. Root
PHPUnit now includes the Phase 11B, 11C, and 11D suites and coverage sources.

## Rejected alternatives

Reflection, repository scanning, arbitrary metadata, source I/O, runtime
registries, a hidden current Graph, AI inference, serialization, publication,
loading, currentness, commands, consumers, and operational authority are
rejected from this boundary.

## Consequences

Composition version 2 is the sole supported first projection source. Phase 11E
canonical Graph artifact and publication work is next and unstarted. There is
no Graph artifact, loader, inspection consumer, developer-intelligence access,
extension contribution, Agent Principal, frontend contract, or production-
readiness claim.

## Related material

- [Composition projection guide](../architecture/composition-application-graph-projection.md)
- [ADR 0125](0125-language-neutral-application-graph-ir-and-deterministic-construction.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)
