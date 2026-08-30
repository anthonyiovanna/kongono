# Application Graph IR and Deterministic Construction

> **Phase 11E current boundary:** Experimental `kongono/application-graph-artifact`
> now provides pure canonical Graph artifacts, caller-relative exact-current
> validation, guarded explicit loading, and atomic content-addressed no-clobber
> publication. Phase 11F inspection and application/CLI consumption remain
> unstarted; no source-code-byte attestation, registry, AI access, runtime
> integration, or production-readiness claim exists.


## Purpose and current status

Phase 11C implements the explicit language-neutral IR record and deterministic
Graph construction boundary in Experimental `kongono/application-graph-build`.
Phase 11A and 11B remain accepted prerequisites. Phase 11D projection and Phase
11E artifact behavior are implemented in separate leaf packages; Phase 11F is
next and unstarted. Kongono remains pre-1.0 and not production-ready.

The package turns complete caller-supplied claims into validated immutable
structural facts:

```text
explicit version-1 node and relation records
                  |
                  v
complete schema, uniqueness, endpoint, and bound validation
                  |
                  v
canonical immutable Application Graph facts
```

## Claims versus facts

An IR record is a claim. Creating one verifies only its local typed shape,
canonical field order, text grammar, and bound. It does not prove schema
membership, endpoint existence, current provenance, or an authoritative Graph.

`ApplicationGraphBuilder::build()` receives the complete node and relation
lists. It validates every record against the exact version-1 catalog and every
cross-record invariant before returning. Returned fields, nodes, and relations
are fresh fact objects; input records are never returned as facts. No partial
Graph is exposed on failure.

## Inputs and canonical output

Node records carry version, node identity, node type, canonical fields, and
provenance. Relation records additionally carry relation identity and source and
target node identities. Field text is exact valid UTF-8, 1 through 4,096 bytes,
with ASCII C0, DEL, and Unicode C1 controls excluded.

Node and relation input order is arbitrary. Admitted nodes sort by canonical
node identity and relations by canonical relation identity using binary byte
comparison. Fields follow catalog order and include their exact type, required
flag, disclosure, and visibility metadata. Returned lists are detached copies.

## Validation and failure

Construction rejects non-list inputs, wrong record types, unsupported version,
more than 65,536 nodes or 262,144 relations, duplicate identities, duplicate
relation tuples, missing or unknown fields, missing endpoints, and endpoint
type mismatch. Empty Graphs, optional field omission, relation-free nodes,
service self-dependency, and service cycles are accepted where the catalog
permits them.

Failures are package-owned, deterministic, and non-disclosing. Their Safe
context contains only closed structural tokens and applicable non-negative
counts or positions. Raw identities, digests, field names, values, symbols,
paths, bodies, and caller arrays are excluded. Construction performs no I/O or
emission and retains no cross-build state.

## Authority and safety

The complete result is authoritative only for the version-1 structural facts
validated from that supplied record set. It does not prove provenance
currentness and grants no security or operational authority. Authentication,
authorization, policy, secrets, filesystem, processes, network, database,
deployment, and production behavior remain owned elsewhere.

Do not use this package for source discovery, PHP reflection, Composition
projection, serialization, publication, loading, inspection, traversal,
filtering, indexing, logging, metrics, events, runtime registries, or AI
authority. Phase 11D owns Composition projection and Phase 11E owns canonical
artifact behavior in separate leaf packages; Phase 11F inspection is unstarted.

## Package relationship

`kongono/application-graph-build` depends inward only on API Metadata,
Application Graph Contract, Diagnostics, and PHP. The earlier Experimental
`kongono/application-graph` package remains byte-identical and distinct: its
graph-local keys and open kind labels are not Phase 11 stable identities or
closed vocabulary.

Applications may continue using Kernel, Composition, HTTP, database,
persistence, security, observability, and Phase 0J Graph directly without an
authoritative Phase 11 Graph artifact.

## Related material

- [ADR 0125](../adr/0125-language-neutral-application-graph-ir-and-deterministic-construction.md)
- [ADR 0124](../adr/0124-versioned-application-graph-contract-foundation.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
- [Artifact and currentness](application-graph-artifact-and-currentness.md)
- [`kongono/application-graph-build`](../../packages/application-graph-build/README.md)
