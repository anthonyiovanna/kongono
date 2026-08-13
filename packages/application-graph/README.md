# Kongono Application Graph Structural Core

`kongono/application-graph` implements Phase 0J's source-language-neutral,
immutable structural snapshot, exact direct-relation selection, and exact
whole-snapshot kind selection. It is a prerequisite for Kongono's future
authoritative Application Graph, not that complete representation and not a PHP
source-discovery or compiler package.

## Structural contract

A `Node` contains exactly an opaque graph-local `NodeKey` and an open
`NodeKind`. A `Relation` contains exactly a source key, an open `RelationKind`,
and a target key; direction is source to target. Keys and kinds reject only the
empty string and preserve every other string byte-for-byte. They are not parsed,
normalized, generated, or interpreted as PHP symbols.

`ApplicationGraph` accepts runtime lists of nodes and relations, validates the
complete input, and then owns an immutable snapshot. It rejects non-list arrays,
incorrect element types, duplicate node-key values, missing relation endpoints,
and duplicate source/kind/target triples. Self-relations, cycles, and relations
with different kinds between the same endpoints are valid.

Nodes are returned in binary `NodeKey::value()` order. Relations are returned in
binary source-value, kind-value, target-value order. Ordering and lookup use exact
string values with no locale behavior or Unicode normalization. Returned arrays
are copies under PHP array semantics, while their final readonly elements cannot
be mutated.

`outgoingRelations(NodeKey $source): array` and
`incomingRelations(NodeKey $target): array` compare endpoint-key values exactly,
not object identity. Selection is directional and does not filter by relation
kind. Missing keys, isolated nodes, and empty graphs return `[]`; use
`hasNode()` when missing-versus-isolated matters. A self-relation appears in
both selectors for its key.

Selectors scan the existing canonical source-kind-target relation list without
re-sorting. Results are fresh detached lists containing the same immutable
`Relation` objects. Each call performs `O(R)` comparisons for `R` relations and
allocates in proportion to the match count; no index, adjacency map, cache, or
memoized query state is retained.

`nodesOfKind(NodeKind $kind): array` and
`relationsOfKind(RelationKind $kind): array` compare exact kind-label values
across the whole stored node or relation list. The parameter types keep node-kind
and relation-kind domains separate even when the label bytes match. Absent kinds
and empty graphs return `[]`.

Kind selectors preserve the applicable canonical order without re-sorting and
return fresh lists containing the same immutable stored objects. Node selection
scans `N` nodes in `O(N)` time, relation selection scans `R` relations in `O(R)`
time, and result allocation is `O(k)` for `k` matches. No kind index, grouped
collection, memoized result, query cache, performance budget, or optimization
promise is retained.

## Identity, kinds, and language neutrality

`NodeKey` identifies a node only inside the snapshot and producer contract that
supplied it. It is not a stable cross-build Application Graph ID, Documentation
ID, database ID, URI, UUID, package coordinate, or PHP class name. Selecting a
stable identity system remains open.

Node and relation kinds are open, separate label domains. The package defines no module, service,
route, event, dependency, security, agent, or other built-in vocabulary. Open
labels prevent this structural phase from accidentally selecting a finalized
domain schema. The production model contains no reflection objects, PHP
attributes as data, callables, services, runtime state, or arbitrary metadata.

## Failures and API status

Every deliberate package failure is an `InvalidGraphException` catchable through
the Public `Kongono\ApplicationGraph\Exception\ExceptionInterface`. That marker
is deliberately separate from `Kongono\Kernel\Exception\ExceptionInterface`:
Graph validation failures are not Kernel lifecycle failures. Native engine errors
are not broadly wrapped.

The package exception marker is Public and exposes `diagnosticId()`,
`diagnosticTitle()`, and `diagnosticRemediation()` as strings.
`GraphException` is Internal, and all concrete model/value types plus
`InvalidGraphException` are Experimental. Public
does not mean Stable, and API status is compatibility metadata rather than Graph
metadata, authorization, visibility, or a security boundary.

The Internal `Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic` catalog owns
the eleven exact `GRAPH` cases. Every deliberate Graph throw emits its assigned
identifier. The identifier is stable machine identity; title and remediation
are diagnostic prose and may improve without changing that identity. Existing
messages remain unchanged and may include caller-supplied Graph keys or kind
labels; those values are not presumed safe and the messages must not be exposed
directly to an untrusted production surface. See [ADR
0026](../../docs/adr/0026-actionable-diagnostics-and-error-boundaries.md), [ADR
0027](../../docs/adr/0027-package-local-runtime-diagnostic-metadata.md), and the
[error-handling engineering guide](../../docs/engineering/error-handling.md).

## Explicit non-goals

This package does not provide stable identifiers, domain schemas, metadata bags,
provenance, security filtering, serialization, persistence, caching, hashing,
diffs, source discovery, reflection scanning, PHP Graph attributes, an IR
frontend, compilation, contribution or merge APIs, builders, mutation, combined
endpoint-and-kind filters, multiple-kind selection, generic queries, traversal,
reachability, paths, neighbor selection, predicates, dependency injection, HTTP,
or any later framework capability. The endpoint selectors return immediately
stored relations only; the kind selectors return exact whole-snapshot matches
only and establish no vocabulary or schema.

The temporary production dependency on `kongono/kernel: *` exists only for the
accepted API-status metadata while both unreleased packages are developed
atomically. Inter-package versioning remains unresolved.
