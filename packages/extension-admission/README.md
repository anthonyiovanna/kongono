# Kongono Extension Admission

`kongono/extension-admission` is the Experimental Phase 12D boundary for exact
application-owned extension selection, conflict resolution, attributable
Application Graph IR validation, and deterministic admission evidence.

The builder accepts one complete canonical declaration list, one plan bound to
that list's exact `DeclarationSetIdentity`, and canonical extension-owned Graph
contribution bundles. It admits only schema `kongono.application-graph-ir`
version 1, validates every bundle against `kongono.extension/1` provenance,
requires an explicit winner for every contribution-identity conflict, and
passes the complete winning record set once through the accepted Application
Graph builder.

The result contains fresh canonical IR records and bounded evidence. It is not
an authoritative Application Graph, grant, authorization decision, loaded
extension, Composition binding, host, or execution mechanism. The package does
no discovery, scanning, filesystem or network I/O, publication, loading,
fallback, priority selection, merging, service location, or ambient-state
access.

All public production types except the package exception marker are
Experimental or Internal. Kongono remains pre-1.0 and not production-ready.

Phase 12E consumes exact admission evidence through the separate
`kongono/extension-composition` build package. Admission itself still does not
grant capabilities, validate PHP implementation symbols, create Composition
bindings, load generated code, host extensions, or execute behavior.

See [ADR 0134](../../docs/adr/0134-deterministic-extension-selection-admission-and-graph-ir-projection.md)
and the [architecture guide](../../docs/architecture/extension-admission-and-graph-projection.md).
See also [ADR 0135](../../docs/adr/0135-explicit-extension-capability-grants-and-generated-wiring.md).
