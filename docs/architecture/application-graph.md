# Application Graph

## Purpose

The Application Graph is intended to become Kongono's authoritative, machine-readable representation of an application. It gives developers, infrastructure, tooling, AI agents, and other applications a deterministic source of architectural knowledge rather than requiring them to infer structure by crawling arbitrary source files.

## Context

The Graph will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, but PHP syntax must not permanently define the model. Future source frontends or tooling may contribute through the IR without changing the conceptual architecture.

## Implemented Phase 0H subset

`kongono/application-graph` now provides one immutable in-memory structural
snapshot. A node contains exactly an opaque graph-local key and an open kind
label. A directed relation contains exactly a source key, open relation-kind
label, and target key. Keys and kinds reject only the empty string and preserve
all other values exactly.

Construction requires runtime lists and correct element types, unique node-key
values, existing source and target endpoints, and unique exact relation triples.
Empty graphs, self-relations, cycles, and different relation kinds between the
same endpoints are valid. Nodes use binary key order; relations use binary
source, kind, target order. Lookup uses exact key-value equality, not PHP object
identity. The final readonly model is instance-owned and performs no I/O or
ambient-state access.

`ApplicationGraph::outgoingRelations(NodeKey $source): array` and
`ApplicationGraph::incomingRelations(NodeKey $target): array` select immediate
stored relations by exact endpoint-key value rather than `NodeKey` object
identity. Selection is directional, relation kind does not filter the result,
and a self-relation appears in both selectors for its key. Empty graphs,
existing nodes isolated in that direction, and missing keys return an empty
list without a new exception. Callers use `hasNode()` when they must distinguish
a missing key from an isolated node.

Each selector scans the canonical relation list in its existing global
source-kind-target order. The returned list is detached from the graph's array
storage and contains the same final readonly `Relation` objects. Each call costs
`O(R)` endpoint comparisons for `R` stored relations and allocates in proportion
to the match count; the snapshot retains no adjacency index or query cache.
Direct selection is not traversal, reachability, path finding, neighbor-node
selection, relation-kind filtering, predicates, or a generic query language.

`NodeKey` is deliberately weak and opaque. It identifies a node only in the
snapshot and producer contract that supplied it; it is not a stable cross-build
Graph ID, Documentation ID, database ID, URI, UUID, package coordinate, or PHP
symbol. Open node and relation kinds likewise do not establish a domain schema or
compatibility vocabulary.

## Planned coverage

The Graph is intended to describe relationships among modules, services, dependencies, routes, actions, models or entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation.

It should eventually provide authoritative relationships such as calls, called-by, dependencies, module usage, emitted events, and consumed events. Human documentation should explain conceptual context instead of duplicating those derived lists.

## Correctness boundary

Application structure, dependency legality, API contracts, security metadata, and module boundaries must be established by deterministic framework mechanisms. AI may suggest or explain changes, but an LLM may not authoritatively infer these facts.

## Language neutrality

The IR and Graph must represent Kongono concepts independently of PHP surface syntax. This protects the architecture from coupling to a single parser, attribute system, reflection model, or language version.

## Stability

**EXPERIMENTAL.** Phase 0H selects only the structural snapshot and the two exact direct-relation selectors described above. It does not select stable identifiers, domain schemas, arbitrary metadata, provenance, visibility or security filtering, serialization or schema versions, persistence, caching, hashing, diffs, discovery, source frontends, PHP Graph attributes, compilation, contributions, merging, mutation, generic queries, traversal, or the complete authoritative representation. The conceptual authority and language-neutral foundation are locked; those exact mechanisms require later architect approval.

## Documentation relationship

Kongono intends to connect source symbols, Graph nodes, documentation concepts, IDEs, CLI tools, and AI tools through stable documentation identities. The conceptual example `principals.resolution` illustrates intent only. No ID syntax or annotation is authorized.

## Security and extension considerations

Graph visibility must eventually respect sensitive metadata, trust boundaries, and authorization. Extension contributions must be attributable and validated rather than allowed to mutate application knowledge invisibly. These contracts remain open.

## Related concepts

- [Architecture overview](overview.md)
- [Documentation standard](../documentation-standard.md)
- [Agent model](agent-model.md)
- [ADR 0005](../adr/0005-language-neutral-application-graph.md)
- [ADR 0022](../adr/0022-immutable-application-graph-structural-snapshot.md)
- [ADR 0024](../adr/0024-exact-direct-relation-selection.md)
