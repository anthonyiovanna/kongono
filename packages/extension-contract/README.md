# Kongono Extension Contract

This Experimental package owns Kongono's neutral versioned declaration values
for extensions. It provides bounded identities and versions, architectural
categories, exact source provenance, inert capability requests, contribution
metadata, lifecycle intent, and one canonical aggregate declaration.

Use it to construct local evidence that satisfies the Phase 12B grammar,
limits, types, uniqueness, and binary ordering rules. Construction proves only
those local invariants. It does not admit or trust an extension, establish
currentness, grant a capability, create an authoritative Application Graph fact,
authorize a visibility audience, or host or execute lifecycle behavior.

The package performs no I/O and has no loader, registry, discovery, serializer,
artifact, publisher, application selection, conflict resolution, Composition
integration, or Application Graph dependency. Values remain immutable and
caller-supplied lists are rejected rather than reordered or deduplicated.

Stability: Experimental, except the Public package exception marker. Kongono is
pre-1.0 and not production-ready.

Phase 12E's separate `kongono/extension-composition` package consumes these
exact inert requests only after Phase 12D admission. It requires an explicit
application-owned grant or denial for every request and generates inert typed
wiring without changing this package's authority. A request remains neither an
authorization result nor a runtime capability, and Trusted/in-process remains
an architectural category rather than a sandbox.
