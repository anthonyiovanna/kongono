# Application Graph

## Purpose

The Application Graph is intended to become Kongono's authoritative, machine-readable representation of an application. It gives developers, infrastructure, tooling, AI agents, and other applications a deterministic source of architectural knowledge rather than requiring them to infer structure by crawling arbitrary source files.

## Context

The Graph will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, but PHP syntax must not permanently define the model. Future source frontends or tooling may contribute through the IR without changing the conceptual architecture.

## Implemented Phase 0J subset

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

`ApplicationGraph::nodesOfKind(NodeKind $kind): array` and
`ApplicationGraph::relationsOfKind(RelationKind $kind): array` select across the
whole stored snapshot by exact `kind()->value()` equality. Node kinds and
relation kinds remain separate domains even when their labels have identical
bytes. An empty graph or absent kind returns `[]`; open labels do not establish
a built-in vocabulary, hierarchy, alias, compatibility rule, or domain schema.

Each kind selector scans its existing canonical node or relation list without
re-sorting. Results are fresh detached lists containing the same final readonly
objects held by the snapshot. Node selection performs `O(N)` exact comparisons,
relation selection performs `O(R)` exact comparisons, and result allocation is
`O(k)` for `k` matches. No kind index, grouped collection, memoized result, query
cache, performance budget, or optimization promise is retained. These two
selectors do not authorize endpoint-and-kind combinations, multiple kinds,
predicates, callbacks, neighbors, traversal, or a generic query system.

`NodeKey` is deliberately weak and opaque. It identifies a node only in the
snapshot and producer contract that supplied it; it is not a stable cross-build
Graph ID, Documentation ID, database ID, URI, UUID, package coordinate, or PHP
symbol. Open node and relation kinds likewise do not establish a domain schema or
compatibility vocabulary.

## Implemented Phase 0Q diagnostic context

All 11 deliberate Graph failures retain their package-local identifiers, titles,
remediation, native messages, zero codes, and optional causes while also
returning one eager immutable `DiagnosticContext` through the Public Graph
exception marker. The Internal Graph catalog owns the exact closed schemas and
throw sites provide values only. Diagnostics owns structural value mechanics and
has no reverse Graph dependency or Graph semantic ownership.

Safe fields are counts, positions, lengths, and broad non-identifying PHP value-
kind tokens. Protected fields are Graph keys, relation kinds, and an optional
numeric first-unexpected-key representation. For a non-list input, only the
first mismatching key is inspected: an integer is captured as canonical base-10
text, while an arbitrary string key is omitted entirely. Invalid value-kind
tokens are exactly `null`, `boolean`, `integer`, `float`, `string`, `array`,
`object`, `resource`, `closed_resource`, or defensive `unknown`; they never
expose a class or resource type.

Context is exception-owned, detached, schema ordered, bounded, and free of I/O,
ambient state, caches, or per-operation accumulation. It observes failure only
and changes no successful construction, lookup, selector, ordering, identity,
validation priority, or complexity behavior. Safe and Protected classifications
do not render or disclose anything. Phase 0T independently adds Kernel exception
context, and Phase 0U independently adds DI context. Diagnostics recursive
context, renderers, disclosure policy, transports, logging, telemetry,
source access, and documentation lookup remain future.

## Planned coverage

The Graph is intended to describe relationships among modules, services, dependencies, routes, actions, models or entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation.

It should eventually provide authoritative relationships such as calls, called-by, dependencies, module usage, emitted events, and consumed events. Human documentation should explain conceptual context instead of duplicating those derived lists.

## Correctness boundary

Application structure, dependency legality, API contracts, security metadata, and module boundaries must be established by deterministic framework mechanisms. AI may suggest or explain changes, but an LLM may not authoritatively infer these facts.

## Language neutrality

The IR and Graph must represent Kongono concepts independently of PHP surface syntax. This protects the architecture from coupling to a single parser, attribute system, reflection model, or language version.

## Stability

**EXPERIMENTAL.** Phase 0J selects only the structural snapshot and four exact selectors described above; Phase 0Q adds only exact bounded context to its existing failures. Phase 0T changes no Graph behavior or dependency. None of these phases selects stable Graph identifiers, kind vocabularies, domain schemas, arbitrary metadata, provenance, visibility or security filtering, serialization or schema versions, persistence, caching, hashing, diffs, discovery, source frontends, PHP Graph attributes, compilation, contributions, merging, mutation, combined or generic queries, neighbors, traversal, query planning, performance budgets, framework integration, or the complete authoritative representation. The conceptual authority and language-neutral foundation are locked; those exact mechanisms require later architect approval.

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
- [ADR 0025](../adr/0025-exact-whole-snapshot-kind-selection.md)
