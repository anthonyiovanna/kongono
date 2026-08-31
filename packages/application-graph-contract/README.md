# Kongono Application Graph Contract

This Experimental package owns the versioned, language-neutral contract
foundation for Kongono's future authoritative Application Graph. It defines
stable typed identities, the closed Composition version-1 vocabulary, typed
schema descriptors and catalog, bounded provenance, visibility and disclosure
classifications, and non-disclosing diagnostic failures.

Use it when producing or validating values that must conform to the accepted
Phase 11B Graph contract. Do not use it as a Graph implementation, source
frontend, serializer, artifact, registry, security policy, or operational
capability. It constructs no IR records, projects no Composition state, performs
no I/O, and grants no consumer access.

The package is deliberately separate from the Experimental
`kongono/application-graph` Phase 0J structural snapshot. Phase 0J graph-local
keys and open kind labels retain their original meaning and are not stable
Phase 11 identities or vocabularies.

All values are immutable and instance-owned. Canonical ordering is binary and
caller input is rejected rather than silently reordered. Provenance is bounded
evidence, not permission; secret material is excluded from the contract.

Stability: Experimental, except the Public package exception marker. Kongono is
pre-1.0 and not production-ready.

Phase 12D adds only `ProducerContract::ExtensionV1` with value
`kongono.extension/1` for attributable extension-owned IR. Contract version 1,
the closed Graph vocabulary and schemas, and `CompositionV2` behavior remain
unchanged. The producer contract grants no authority.
