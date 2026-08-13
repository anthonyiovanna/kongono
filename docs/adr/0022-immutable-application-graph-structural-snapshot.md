# ADR 0022: Immutable Application Graph Structural Snapshot

## Status

Accepted

## Date

2026-08-12

## Context

The accepted language-neutral Application Graph direction needs a smallest
executable structure before domain schemas, stable identities, discovery,
compilation, or serialization can be selected. That structure must be useful to
future source frontends without making PHP reflection, class names, or attributes
the conceptual model. It also needs deterministic construction failures and one
package-specific catch boundary.

## Decision

Create `kongono/application-graph` with immutable nodes and directed relations.
A node contains exactly a non-empty opaque graph-local `NodeKey` and non-empty
open `NodeKind`. A relation contains exactly a source key, open non-empty
`RelationKind`, and target key. All supplied strings are preserved exactly.

`ApplicationGraph` accepts runtime lists, validates all elements, requires unique
node-key values, requires both endpoints to exist, and rejects exact duplicate
relation triples. Self-relations, cycles, and different relation kinds between
the same endpoints are valid. Nodes use binary key order; relations use binary
source, kind, target order. Lookup uses exact key-value equality rather than PHP
object identity.

All structural objects are final and readonly, with no static or ambient Graph.
Deliberate failures use the package's Public `ExceptionInterface` through an
Internal abstract `GraphException` and Experimental final
`InvalidGraphException`. Graph exceptions do not implement the Kernel exception
marker. API-status metadata remains the only production dependency on the Kernel
package, temporarily constrained as `*` while no inter-package version policy
exists.

## Rationale

Opaque local keys and open kinds provide a portable structural vocabulary without
choosing deferred identity and schema systems. Exact tuple comparison prevents
collisions for arbitrary strings. Immutable snapshots and canonical order make
the same structure inspectable and reproducible regardless of caller input order.
Early validation prevents incomplete or silently rewritten structures from
becoming usable.

A package-specific marker lets consumers distinguish deliberate structural
failures without misclassifying them as Kernel lifecycle failures. Root ownership
of the production inventory test prevents the Kernel package suite from acquiring
knowledge of every sibling package.

## Consequences

Producers must supply locally unique keys and define the temporary meanings of
their open labels. Consumers receive deterministic lists and exact lookup, but no
compatibility promise for key syntax, kind vocabulary, exception text, or model
API before Experimental promotion. The snapshot is a structural prerequisite,
not yet Kongono's complete authoritative application representation.

## Rejected alternatives

- Binding the model to PHP reflection or attributes is rejected because PHP is a
  source frontend, not the language-neutral conceptual boundary.
- Using PHP class names as canonical node identity is rejected because not every
  application concept is a PHP class and other frontends must remain possible.
- Selecting UUIDs, hashes, URIs, or Documentation IDs now is rejected because
  stable cross-build identity and its compatibility rules are unresolved.
- Arbitrary metadata bags are rejected because they would create an unvalidated
  schema and language-specific object channel.
- Domain-specific node and relation enums are rejected because no finalized
  Graph vocabulary is authorized in this phase.
- Mutable Graph objects are rejected because partial structures and shared
  mutation would weaken validation and persistent-runtime reasoning.
- Input-order-dependent iteration is rejected because identical structure must
  produce deterministic observation.
- Silently accepting dangling relations is rejected because endpoints are a
  structural invariant.
- Silently replacing duplicate nodes is rejected because it hides producer
  conflicts and makes results input-order-dependent.
- Implementing serialization before schema and versioning decisions is rejected
  because an accidental format would create premature compatibility obligations.
- Placing repository-wide package inventory tests inside the Kernel package is
  rejected because sibling-package knowledge is a root architecture concern.

## What remains open

Stable cross-build identifiers; node and relation kind vocabularies;
domain-specific schemas; metadata and provenance; visibility and security
filtering; source frontends; PHP attributes and discovery; compilation and later
validation phases; merging and contribution rules; serialization and schema
versioning; persistence and caching; hashes and fingerprints; Graph diffs;
query and traversal APIs; inter-package version constraints; and API promotion
remain open. This decision authorizes none of those capabilities.

## Later decision

[ADR 0024](0024-exact-direct-relation-selection.md) later supersedes this
decision's query prohibition only for exact `outgoingRelations()` and
`incomingRelations()` selection over immediately stored relations. The original
Phase 0D rationale and every other deferred query, traversal, schema, identity,
mutation, persistence, discovery, compilation, and integration decision remain
unchanged.
