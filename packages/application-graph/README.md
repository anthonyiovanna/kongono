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
`diagnosticTitle()`, and `diagnosticRemediation()` as strings plus
`diagnosticContext(): DiagnosticContext`.
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

Phase 0Q implements the exact 11 Graph-owned context schemas and throw-site
values. Safe fields are `actual_length`, `entry_count`, `entry_position`,
`actual_value_kind`, and `relation_position`. Protected fields are optional
`first_unexpected_key` plus `node_key`, `source_key`, `target_key`, and
`relation_kind`. Schema keys, labels, scalar kinds, disclosures, required flags,
and order are closed in `GraphDiagnostic`; throw sites supply values only.

| ID | Operation | Violated invariant | Ordered fields |
| --- | --- | --- | --- |
| `KGN-GRAPH-1001` | `Create node key` | `Node-key value must be non-empty` | `actual_length` / `Actual length` / Integer / Safe / required |
| `KGN-GRAPH-1002` | `Create node kind` | `Node-kind value must be non-empty` | `actual_length` / `Actual length` / Integer / Safe / required |
| `KGN-GRAPH-1003` | `Create relation kind` | `Relation-kind value must be non-empty` | `actual_length` / `Actual length` / Integer / Safe / required |
| `KGN-GRAPH-1004` | `Construct Application Graph` | `Nodes input must be a PHP list` | `entry_count` / `Entry count` / Integer / Safe / required; `first_unexpected_key` / `First unexpected key` / Text / Protected / optional |
| `KGN-GRAPH-1005` | `Construct Application Graph` | `Relations input must be a PHP list` | `entry_count` / `Entry count` / Integer / Safe / required; `first_unexpected_key` / `First unexpected key` / Text / Protected / optional |
| `KGN-GRAPH-1006` | `Validate node entry` | `Every nodes-list entry must be a Node` | `entry_position` / `Entry position` / Integer / Safe / required; `actual_value_kind` / `Actual value kind` / Text / Safe / required |
| `KGN-GRAPH-1007` | `Validate relation entry` | `Every relations-list entry must be a Relation` | `entry_position` / `Entry position` / Integer / Safe / required; `actual_value_kind` / `Actual value kind` / Text / Safe / required |
| `KGN-GRAPH-1008` | `Validate node uniqueness` | `Node keys must be unique within a snapshot` | `node_key` / `Node key` / Text / Protected / required |
| `KGN-GRAPH-1009` | `Validate relation source` | `Relation source must reference an existing node` | `relation_position` / `Relation position` / Integer / Safe / required; `source_key` / `Source key` / Text / Protected / required |
| `KGN-GRAPH-1010` | `Validate relation target` | `Relation target must reference an existing node` | `relation_position` / `Relation position` / Integer / Safe / required; `target_key` / `Target key` / Text / Protected / required |
| `KGN-GRAPH-1011` | `Validate relation uniqueness` | `Source/kind/target triples must be unique` | `source_key` / `Source key` / Text / Protected / required; `relation_kind` / `Relation kind` / Text / Protected / required; `target_key` / `Target key` / Text / Protected / required |

Each deliberate Graph failure constructs context eagerly and synchronously,
owns that immutable detached context for its exception lifetime, and returns the
same object on repeated access. It retains no caller value map and enters no
Graph snapshot, static registry, global cache, operation scope, Kernel state, or
service container. Construction performs no I/O or ambient lookup. Work is
linear in the small schema and represented input, bounded by 16 fields and 512
represented text bytes; schema objects may be freshly allocated and no cached or
constant-time lookup is promised.

For non-list input, Graph inspects keys in insertion order and stops at the first
mismatch from expected integer zero. An integer key is captured as canonical
base-10 Protected text. A string key is omitted entirely, and later keys are not
inspected. Invalid entries expose only `null`, `boolean`, `integer`, `float`,
`string`, `array`, `object`, `resource`, `closed_resource`, or defensive
`unknown`; object classes, resource types, paths, and value content never enter
the token.

Callers may read context only after catching the Graph marker. Safe means
eligible for a future explicit policy, not automatically disclosed. Protected
Graph structural values require a future trusted boundary. This package performs
no rendering, disclosure, redaction, sanitization, transport, logging, telemetry,
source access, or documentation lookup. Passwords, credentials, tokens, cookies,
authorization material, bodies, headers, provider payloads, file/source content,
serialized objects, memory dumps, and uncertain secret material remain forbidden
from capture. Diagnostics construction failures remain context-free. Phase 0T
independently integrates Kernel's six lifecycle contexts, and Phase 0U
independently integrates DI's 25 contexts across 26 throw sites.

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

The production dependency on `kongono/diagnostics: *` supplies only bounded
presentation-neutral values; Diagnostics has no reverse Graph dependency or
Graph semantic ownership. `kongono/api-metadata: *` supplies only `Api` and
`ApiStatus`. Application Graph has no Kernel dependency. Inter-package
versioning remains unresolved.

Phase 0S completed the atomic metadata migration to
`Kongono\ApiMetadata\Api` and `ApiStatus`; the former Kernel FQCNs are absent
without a shim. Phase 0T makes Kernel the second package family with bounded
exception context, and Phase 0U makes DI the third, through their own direct
Diagnostics dependencies. Graph behavior and dependencies remain unchanged; no universal exception boundary,
runtime integration, renderer, transport, logging, or telemetry exists.
