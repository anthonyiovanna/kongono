# Kongono Application Graph Build

This Experimental package admits explicit version-1 language-neutral IR claims
against `kongono/application-graph-contract` and constructs one canonical,
immutable authoritative Graph value. The result is authoritative only for the
validated structural facts represented by the complete supplied record set; it
grants no security or operational authority.

Use `ApplicationGraphBuilder::build()` with complete node and relation record
lists. Input order is non-semantic. Construction is all-or-nothing and rejects
malformed records, unsupported versions, duplicate identities or relation
tuples, schema-incompatible fields, invalid endpoints, and fixed bounds through
bounded non-disclosing package failures.

The package performs no discovery, reflection, Composition projection,
filesystem or network I/O, serialization, publication, currentness validation,
inspection, logging, telemetry, or mutation. Records remain claims; returned
Graph nodes, relations, and fields are fresh fact objects. Phase 11D and later
capabilities remain outside this package.

See [ADR 0125](../../docs/adr/0125-language-neutral-application-graph-ir-and-deterministic-construction.md)
and the [IR and construction guide](../../docs/architecture/application-graph-ir-and-construction.md).
