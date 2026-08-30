# ADR 0125: Language-Neutral Application Graph IR and Deterministic Construction

## Status

Accepted on 2026-08-28.

## Context

ADR 0123 separates producer-facing claims from authoritative Graph facts, and
ADR 0124 supplies the version-1 identities, vocabulary, schema catalog,
provenance, visibility, and disclosure contracts. Phase 11C needs the next
bounded layer: explicit records and complete deterministic admission. It must
not discover records from PHP or Composition and must not serialize, publish,
load, inspect, or consume a Graph.

The Experimental Phase 0J `kongono/application-graph` structural snapshot is a
different accepted package. Its graph-local keys and open kind labels remain
unchanged and do not become Phase 11 identities or vocabulary.

## Decision

### Separate build package

Create Experimental `kongono/application-graph-build` under
`Kongono\ApplicationGraphBuild\`. It depends only on PHP, API Metadata,
Diagnostics, and Application Graph Contract. No production package depends on
it in Phase 11C; the root consumes it for atomic development and validation.

### Claims and facts

`GraphFieldRecord`, `GraphNodeRecord`, and `GraphRelationRecord` are explicit
version-1 IR claims. Records preserve exact identities, types, provenance, and
canonical fields but establish no authoritative fact by themselves.

The builder admits one complete pair of node and relation lists. It derives
fresh `GraphField`, `GraphNode`, and `GraphRelation` facts and returns one
`AuthoritativeApplicationGraph` only after every invariant passes. Standalone
fact values are representation objects; only the complete builder result may
claim authoritative structural status for that admitted record set.

### Record and fact shapes

A field record carries `FieldName` and exact text. Text is 1 through 4,096
bytes, valid UTF-8, and contains no ASCII C0, DEL, or Unicode C1 control. No
normalization, trimming, case folding, parsing, interpolation, or symbol
interpretation occurs.

Node and relation records carry contract version, category-specific identity,
closed type, canonical fields, and bounded provenance. Relations additionally
carry exact source and target node identities. Field lists are typed lists of
at most 32 entries, unique and binary-ordinal by field name.

Derived fields additionally carry the exact catalog field type, required flag,
disclosure, and visibility. Derived nodes and relations carry fresh detached
canonical field lists and the exact supplied provenance. The complete Graph
carries contract version plus detached canonical node and relation lists.

### Complete schema admission

Construction validates list shape and entry type, fixed node and relation
bounds, version, identity uniqueness, relation-tuple uniqueness, exact fields,
required fields, endpoints, and endpoint types. Duplicate relation tuples are
rejected even when their relation identities differ. Missing or unknown facts
are never inferred as false.

Zero records, relation-free node sets, optional field omission, service
self-dependency, and permitted service dependency cycles are valid. Records may
carry more than one bounded provenance source. Provenance currentness and
identity derivation remain later boundaries.

### Determinism and lifecycle

Input order is non-semantic. Admitted nodes sort by `NodeIdentity::value()` and
relations by `RelationIdentity::value()` using binary `strcmp` semantics;
fields retain catalog order. Equivalent permutations produce observably
identical ordered fact values. No serialization, digest, artifact name, time,
randomness, locale, environment, current directory, cache, or ambient registry
participates.

The builder retains no current records or Graph. Results are immutable,
instance-owned, detached, and safe across sequential, nested, interleaved, or
overlapping use. Failure returns no partial result and emits no callback, log,
event, metric, mutation, or side effect.

### Bounds and failures

One build accepts at most 65,536 nodes and 262,144 relations. Package
diagnostics are exactly `KGN-GRAPH-1106` through `KGN-GRAPH-1110` for invalid
input, invalid field sets, duplicates, endpoints, and bounds. Context contains
only a closed violation token, exact record-kind token, and applicable
non-negative count or position. Rejected identities, digests, names, values,
symbols, paths, bodies, and arrays do not enter messages, context, or
deterministic stringification. An exact native previous throwable is retained;
native trace arguments are not claimed to be sanitized.

### Authority and dependency direction

Graph authority is structural and descriptive. Records, facts, schemas,
provenance, and complete Graph values grant no identity, authentication,
authorization, capability, secret, filesystem, process, network, database,
deployment, or production authority.

The new package does not depend on Phase 0J Graph, Composition, Dependency
Injection, Kernel, build/filesystem packages, security, observability, CLI,
knowledge, or developer intelligence. Lower packages gain no reverse edge.

## Consequences

- Explicit version-1 claims can now be admitted into deterministic immutable
  Graph facts without source-language coupling.
- Malformed, mixed, incomplete, ambiguous, conflicting, or oversized state
  fails visibly and all-or-nothing.
- Producer order cannot affect canonical facts.
- Direct use of lower-level framework packages and the Phase 0J Graph remains
  supported.
- Kongono now has 54 acyclic production packages and 187 local production
  dependency edges, and remains pre-1.0 and not production-ready.

## Deferred work

Phase 11D must separately select supported Composition-to-IR projection,
identity derivation inputs, and application Graph build. Phase 11E must
separately select canonical serialization, Graph identity, currentness,
artifact loading, and no-clobber publication. Inspection, CLI/application
consumption, developer-intelligence use, extension contributions, Agent
Principals, frontend contracts, runtime integration, performance claims, and
external acceptance remain Phase 11F or later work under separate authority.

## Rejected alternatives

- Returning IR records as Graph facts was rejected because claims and admitted
  facts require separate contracts and object identity.
- Partial results and best-effort admission were rejected because omitted or
  rejected records cannot silently become false facts.
- Silent sorting inside records was rejected because malformed producer claims
  must remain visible; only complete node and relation record order is
  non-semantic.
- Open metadata bags, inferred fields, concatenated public tuple identities,
  locale ordering, timestamps, random values, and ambient current Graph state
  were rejected as non-deterministic or weakly governed.
- Combining discovery, Composition projection, serialization, publication,
  inspection, or AI consumption with construction was rejected because those
  boundaries require later separate acceptance.
- Modifying or depending on Phase 0J Graph was rejected because it preserves a
  distinct accepted structural snapshot contract.

## Related material

- [ADR 0123](0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
- [ADR 0124](0124-versioned-application-graph-contract-foundation.md)
- [IR and construction guide](../architecture/application-graph-ir-and-construction.md)
- [Authoritative Application Graph](../architecture/authoritative-application-graph.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)
