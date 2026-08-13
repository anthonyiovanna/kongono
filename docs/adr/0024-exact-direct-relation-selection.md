# ADR 0024: Exact Direct-Relation Selection

## Status

Accepted

## Date

2026-08-12

## Context

The immutable structural snapshot can validate and expose its complete canonical
relation list, but consumers otherwise have to repeat endpoint filtering for the
common case of inspecting relations immediately connected in one direction. A
small read boundary is useful without selecting traversal, a query language, an
indexing strategy, stable identity, or the schema of the future authoritative
Application Graph.

## Decision

Add exactly these Experimental methods to `ApplicationGraph`:

```php
/** @return list<Relation> */
public function outgoingRelations(NodeKey $source): array;

/** @return list<Relation> */
public function incomingRelations(NodeKey $target): array;
```

Each method selects immediately stored relations by exact equality between the
supplied `NodeKey::value()` and the stored source or target value. Matching does
not depend on `NodeKey` object identity, normalization, relation kind, or an
inferred domain meaning. Selection remains directional, and a self-relation
appears in both selectors for its key.

An empty graph, missing endpoint key, or existing endpoint with no relation in
that direction returns an empty list without a new exception. Callers use
`hasNode()` or `node()` when they need to distinguish missing from isolated.

Both methods scan the existing canonical relation list and append exact matches
without re-sorting. Results therefore preserve global source-kind-target order,
are detached arrays, and contain the same immutable `Relation` objects stored by
the snapshot. Each call performs `O(R)` endpoint comparisons for `R` relations,
allocates in proportion to the match count, and retains no index or query cache.

No production type, exception, API status, property, dependency, package, or
framework integration is added or changed.

## Rationale

Exact direct selection removes duplicated consumer filtering while keeping the
snapshot deterministic, immutable, language-neutral, and mechanically simple.
Scanning the canonical list preserves established order and state ownership and
avoids selecting an optimization or retained adjacency representation before a
measured need and broader Graph query contract exist.

## Consequences

Consumers can inspect outgoing or incoming stored relations with value-equal
graph-local keys. Missing and isolated keys intentionally have the same empty
selector result. Work is linear in the total relation count per call, and
allocations scale with matches. The methods provide no performance budget or
optimization promise.

This decision supersedes Phase 0D's query prohibition only for these two exact
selectors. Every construction, validation, ordering, lookup, exception,
immutability, language-neutrality, and API-status invariant remains unchanged.

## Rejected alternatives

- Traversal, reachability, path finding, recursion, breadth-first or depth-first
  search, neighbor-node queries, and subgraphs are deferred because they require
  broader semantics.
- Relation-kind filters, predicates, callbacks, criteria, query objects, and a
  query DSL are deferred because they would expand the public query surface.
- Adjacency maps, endpoint or kind indexes, memoization, and query caches are
  rejected for this boundary because they add retained state and an unselected
  optimization contract.
- Mutation, builders, projections, synthesized relations, and reverse-relation
  generation are rejected because the snapshot remains immutable and exposes
  only stored structure.
- Stable Graph identity, domain schemas, metadata, discovery, compilation,
  serialization, persistence, Kernel integration, and Dependency Injection
  integration are deferred because direct selection does not settle them.

## What remains open

Stable cross-build identity; node and relation vocabularies; domain schemas;
metadata and provenance; visibility and authorization; source discovery and
frontends; compilation and validation phases; serialization and versioning;
persistence, mutation, diffs, merging, and contribution rules; generic queries,
filters, traversal, neighbor selection, paths, and reachability; indexing,
caching, performance budgets, and query planning; Kernel, Dependency Injection,
runtime, extension, and other framework integration; inter-package versioning;
and API promotion all remain open. This decision authorizes none of them.
