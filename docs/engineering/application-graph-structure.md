# Application Graph Structural Snapshot

## Purpose and boundary

`kongono/application-graph` supplies the smallest immutable in-memory structure
needed before Kongono can design its future authoritative Application Graph. Use
it when a producer already has opaque node keys, open kind labels, and directed
relations that need deterministic validation, lookup, and exact immediate
relation selection. Do not use it as a source scanner, schema registry,
serializer, metadata store, mutable builder, traversal engine, or generic query
system.

All model types are final and readonly. Construction and access perform no
filesystem, network, environment, time, randomness, process, logging, telemetry,
reflection, or global-state access.

## Public constructors and accessors

```php
new NodeKey(string $value);
$key->value(): string;

new NodeKind(string $value);
$kind->value(): string;

new RelationKind(string $value);
$kind->value(): string;

new Node(NodeKey $key, NodeKind $kind);
$node->key(): NodeKey;
$node->kind(): NodeKind;

new Relation(NodeKey $source, RelationKind $kind, NodeKey $target);
$relation->source(): NodeKey;
$relation->kind(): RelationKind;
$relation->target(): NodeKey;

new ApplicationGraph(array $nodes, array $relations);
$graph->nodes(): array;
$graph->relations(): array;
$graph->hasNode(NodeKey $key): bool;
$graph->node(NodeKey $key): ?Node;
$graph->outgoingRelations(NodeKey $source): array;
$graph->incomingRelations(NodeKey $target): array;
```

The constructor arrays have static contracts `list<Node>` and `list<Relation>`.
Runtime checks still reject associative or gapped arrays and incorrect elements,
because PHP's native `array` type does not enforce those contracts.

## Equality, validation, and order

Node identity is exact byte-for-byte equality of `NodeKey::value()`. Relation
identity is exact equality of the source-key, relation-kind, target-key value
triple. No part is concatenated, hashed, normalized, or coerced to form an
internal ID.

Construction rejects:

- an empty key or kind value;
- non-list node or relation arrays;
- an element of the wrong runtime type;
- duplicate node-key values;
- a relation whose source or target does not exist;
- an exact duplicate relation triple.

Empty graphs, self-relations, cycles, and multiple relation kinds between the
same endpoints are valid. Failure occurs before the object exposes usable state.
Deliberate failures use `InvalidGraphException` through the package's own Public
exception marker, not the Kernel exception marker.

Canonical node order uses PHP binary `strcmp()` semantics on key values.
Canonical relation order compares source value, kind value, then target value.
No locale-aware comparison or Unicode normalization occurs. Lookup compares key
values rather than `NodeKey` object identity.

Both selectors compare the supplied `NodeKey::value()` exactly with the stored
source or target value. They do not normalize values or consider relation kind,
and they never reverse direction. A self-relation appears in both results for
its key. An empty graph, a missing key, or an existing node with no relation in
that direction returns `[]`; use `hasNode()` when missing-versus-isolated matters.

Selection scans the existing canonical relation list and does not re-sort it.
Outgoing results are therefore kind-then-target ordered, while incoming results
preserve global source-kind-target order. Each call returns a fresh list holding
the same immutable `Relation` objects. It performs `O(R)` endpoint comparisons
for `R` stored relations, allocates in proportion to matches, and retains no
index, adjacency map, memoized result, or query cache. This is direct selection
only, not neighbor selection, reachability, paths, recursion, traversal,
predicates, relation-kind filtering, or a query DSL.

## Correct usage

```php
$service = new Node(new NodeKey('producer-local:1'), new NodeKind('open-label'));
$consumer = new Node(new NodeKey('producer-local:2'), new NodeKind('open-label'));
$relation = new Relation(
    $service->key(),
    new RelationKind('producer-defined'),
    $consumer->key(),
);

$graph = new ApplicationGraph([$consumer, $service], [$relation]);
$outgoing = $graph->outgoingRelations(new NodeKey('producer-local:1'));
```

The returned node order is deterministic even though the input was not. The
producer owns the meaning and lifetime of the supplied strings.

Incorrect usage includes treating keys as stable IDs, defining framework-wide
kind constants by convention, placing PHP reflection or arbitrary metadata in
the model, accepting dangling relations, or persisting the objects as an implied
wire format.

## Testing and dependency contract

Production tests must cover exact value preservation, runtime list and element
validation, structural failures, valid cycles/self-relations, collision-free
tuple comparison, canonical order, value-based lookup, exact directional
selection, missing and isolated keys, detached results, object identity,
immutability, exception separation, and API status. The root architecture suite
owns complete production type discovery across every `packages/*/src` tree.

The package requires PHP 8.5 or newer and temporarily requires
`kongono/kernel: *` only for `Api` and `ApiStatus`. The unbounded constraint is
authorized while both packages are unreleased and developed atomically; package
version coordination remains an open architectural decision. No external
dependency or Composer plugin is part of this package.

## Non-goals and stability

Stable IDs, kind vocabularies, domain schemas, metadata, provenance, visibility,
security filtering, source frontends, PHP discovery, compilation, contributions,
merging, serialization, persistence, caching, fingerprints, diffs, queries,
traversal beyond the two exact direct selectors, neighbor selection, kind
filtering, and mutation remain unresolved. The model is Experimental and is not
yet the full authoritative application representation.
