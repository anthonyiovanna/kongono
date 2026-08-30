# Kongono Application Graph Artifact

Experimental `kongono/application-graph-artifact` serializes one already-built
`AuthoritativeApplicationGraph` into strict canonical JSON. It re-admits every
fact through the Phase 11C builder before producing a detached artifact.

Graph identity is the SHA-256 identity of the canonical `graph` object. Artifact
identity covers the complete artifact, including stable builder package and
contract-version metadata. It does not attest to source-code bytes, signing, or
supply-chain provenance.

Structural validation rehydrates typed records and repeats canonical admission.
Currentness means byte equality with a fresh artifact built from the exact Graph
supplied by the caller; it does not prove Composition source or workspace state.

The loader accepts one explicit canonical absolute content-addressed file. The
publisher accepts one explicit existing canonical absolute directory and uses
same-directory atomic no-overwrite publication. Artifacts may contain Protected
application facts and are not safe for public presentation.

`inspection()` returns only artifact/Graph identities, version, totals, and one
count for every closed node/relation type from the freshly re-admitted Graph.
It never reads field values or exposes individual facts. The summary is Safe
structural evidence but proves no external currentness and grants no authority.

The package provides no discovery, general query, registry, cache, command,
runtime integration, developer-intelligence access, authorization, or other
operational authority. The upper Console Application is its sole supported
consumer. Phase 11G remains unstarted. Kongono is pre-1.0 and not production-ready.
