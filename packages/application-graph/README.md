# Kongono Application Graph Structural Core

`kongono/application-graph` implements Phase 0D's source-language-neutral,
immutable structural snapshot. It is a prerequisite for Kongono's future
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

## Identity, kinds, and language neutrality

`NodeKey` identifies a node only inside the snapshot and producer contract that
supplied it. It is not a stable cross-build Application Graph ID, Documentation
ID, database ID, URI, UUID, package coordinate, or PHP class name. Selecting a
stable identity system remains open.

Node and relation kinds are open labels. The package defines no module, service,
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

The package exception marker is Public, `GraphException` is Internal, and all
concrete model/value types plus `InvalidGraphException` are Experimental. Public
does not mean Stable, and API status is compatibility metadata rather than Graph
metadata, authorization, visibility, or a security boundary.

## Explicit non-goals

This package does not provide stable identifiers, domain schemas, metadata bags,
provenance, security filtering, serialization, persistence, caching, hashing,
diffs, source discovery, reflection scanning, PHP Graph attributes, an IR
frontend, compilation, contribution or merge APIs, builders, mutation, queries,
traversal, dependency injection, HTTP, or any later framework capability.

The temporary production dependency on `kongono/kernel: *` exists only for the
accepted API-status metadata while both unreleased packages are developed
atomically. Inter-package versioning remains unresolved.
