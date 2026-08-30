# Composition Application Graph Projection

## Purpose and status

Phase 11D implements the first supported source frontend for the authoritative
Graph. Experimental `kongono/application-graph-composition` consumes one exact
in-memory Composition version-2 artifact and returns fresh Phase 11C records or
one complete Graph.

## Trust boundary and mapping

The artifact is admitted without filesystem I/O or PHP Reflection. Inputs,
services, binding/root contracts, and roots become the four closed node types.
Service input and service dependencies, binding/root implementations, and root
exposure become the four closed relation types. Direct and binding resolution
must be complete; default arguments create no fact; duplicate semantic
relations collapse to one relation.

## Identity, provenance, and lifecycle

All digests use distinct domains and length-framed exact bytes. Semantic node
and relation identities remain stable when their facts are unchanged. Source
identity and provenance bind to the exact artifact SHA-256 and confer no
permission. Records are binary ordered, detached, fresh, stateless, and
all-or-nothing. Failure emits nothing and returns no partial result.

## Exclusions

This boundary has no source reader, scanner, Reflection, serializer, artifact,
currentness validator, publisher, loader, cache, registry, command, query,
inspection consumer, developer-intelligence access, extension contribution,
Agent Principal, frontend contract, AI authority, or operational authority.
Phase 11E implements those artifact operations in the separate
`kongono/application-graph-artifact` leaf without changing this package. Phase
11F is next and unstarted; Kongono remains pre-1.0 and not production-ready.

## Related material

- [ADR 0126](../adr/0126-supported-composition-to-ir-projection-and-application-graph-build.md)
- [IR and construction](application-graph-ir-and-construction.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
- [Artifact and currentness](application-graph-artifact-and-currentness.md)
