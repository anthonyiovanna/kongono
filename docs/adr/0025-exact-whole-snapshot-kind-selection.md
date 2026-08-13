# ADR 0025: Exact Whole-Snapshot Kind Selection

## Status

Accepted

## Date

2026-08-13

## Context

The immutable structural snapshot exposes canonical node and relation lists and
exact endpoint-based direct-relation selection. Consumers still have to repeat
kind filtering when inspecting all stored objects with one exact open kind
label. Exact whole-snapshot selection is the next minimal read boundary because
it removes that repetition without defining a vocabulary, combining criteria,
or selecting a general query mechanism.

## Decision

Add exactly these Experimental methods to `ApplicationGraph`:

```php
/** @return list<Node> */
public function nodesOfKind(NodeKind $kind): array;

/** @return list<Relation> */
public function relationsOfKind(RelationKind $kind): array;
```

`nodesOfKind()` selects stored nodes whose `NodeKind::value()` exactly equals
the supplied `NodeKind::value()`. `relationsOfKind()` independently selects
stored relations whose `RelationKind::value()` exactly equals the supplied
`RelationKind::value()`. Both comparisons use strict string value equality and
do not depend on kind-object identity, normalization, parsing, aliases, or
inferred meaning. The parameter types keep node-kind and relation-kind domains
separate even when their label bytes are equal.

An empty graph or a kind absent from its applicable domain returns `[]` without
a new exception. Each method scans the existing canonical list and appends
matches without re-sorting. Results are fresh detached lists that retain global
node-key or source-kind-target order and contain the same final readonly objects
stored by the snapshot.

`nodesOfKind()` performs `O(N)` exact comparisons for `N` stored nodes;
`relationsOfKind()` performs `O(R)` exact comparisons for `R` stored relations.
Result allocation is `O(k)` for `k` matches. The snapshot retains no kind index,
map, grouped collection, memoized result, query cache, or other selector state,
and this decision makes no performance-budget or optimization promise.

No production type, exception, property, dependency, package, API status,
autoload rule, or framework integration is added or changed.

## Rationale

Direct scans preserve the snapshot's established deterministic ordering,
immutability, object ownership, and language-neutral open-label contract. Exact
value equality gives callers a small inspectable operation while avoiding an
index representation, schema, or broader query design before those decisions
have evidence and explicit authorization.

## Consequences

Consumers can select every stored node or relation with one exact, producer-
defined kind label. Equal node-kind and relation-kind labels never cross domains.
Absent kinds return empty results. Work is linear in the applicable snapshot
size, allocations scale with matches, and callers receive the same immutable
objects in fresh canonically ordered lists.

This decision narrowly supersedes Phase 0D's query prohibition and Phase 0H's
kind-filter deferment only for these two exact whole-snapshot selectors. Every
construction, validation, ordering, lookup, direct-relation, exception,
immutability, language-neutrality, and API-status invariant remains unchanged.

## Rejected alternatives

- Endpoint-and-kind combined selectors, multiple-kind arrays, callbacks,
  predicates, criteria objects, query objects, and a generic query DSL are
  deferred because they would select broader composition semantics.
- Neighbor selection, subgraphs, recursion, traversal, reachability, and path
  finding are deferred because whole-snapshot kind matching settles none of
  their semantics.
- Kind indexes, maps, grouped collections, memoization, caches, constructor
  preprocessing, and lazy mutable state are rejected for this boundary because
  they add retained state and an unselected optimization contract.
- Built-in or reserved kind vocabularies, enums, registries, hierarchies,
  aliases, validation rules, schema, and compatibility rules are rejected
  because kinds remain open producer-defined labels.
- Stable Graph identity, metadata, provenance, visibility, authorization,
  serialization, persistence, mutation, discovery, compilation, and framework
  integration are deferred because exact kind selection does not settle them.

## What remains open

Stable cross-build Graph and documentation identity; node and relation
vocabularies; domain schemas and versioning; metadata, payloads, provenance,
visibility, authorization, and security filtering; source discovery, reflection
frontends, attributes, Intermediate Representation compilation, generated
Graphs, and contribution rules; serialization, persistence, storage, hashing,
diffs, merging, mutation, and builders; combined endpoint-and-kind selection,
multiple-kind selection, predicates, callbacks, query objects, generic queries,
neighbors, subgraphs, traversal, reachability, and paths; indexing, caching,
query planning, performance budgets, and optimization thresholds; Kernel,
Dependency Injection, runtime, extension, capability, and other framework
integration; inter-package versioning; and API promotion all remain open. This
decision authorizes none of them.
