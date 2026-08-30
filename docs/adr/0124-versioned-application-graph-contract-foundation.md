# ADR 0124: Versioned Application Graph Contract Foundation

## Status

Accepted on 2026-08-28.

## Context

ADR 0123 separates versioned producer-facing records from the future
authoritative Application Graph and requires stable identities, typed
vocabularies and schemas, bounded provenance, visibility, disclosure, and
non-disclosing failures before IR construction begins.

The Experimental `kongono/application-graph` package remains the Phase 0J
immutable structural snapshot. Its opaque keys are graph-local and its kind
labels are intentionally open. Retrofitting stable Phase 11 meaning onto those
types would change an accepted package contract and force later producers to
depend on a Graph implementation when they need only neutral value contracts.

## Decision

### Separate package and direction

Create the Experimental `kongono/application-graph-contract` package under the
`Kongono\ApplicationGraphContract\` namespace. It depends only on PHP,
`kongono/api-metadata`, and `kongono/diagnostics`. No production package
consumes it in Phase 11B. The root requires it for validation and development.

The Phase 0J package remains byte-identical and does not depend on or expose the
new contract. This preserves its graph-local semantics while allowing later
producers and Graph construction to depend on the smaller neutral foundation.

### Version and identities

Contract version 1 is the sole supported version. Four distinct final readonly
types own node, relation, producer, and source identities. Each accepts exactly
one 64-byte lowercase ASCII hexadecimal SHA-256 digest and exposes that digest
plus its category-specific canonical value:

```text
kgn:graph:v1:node:<sha256>
kgn:graph:v1:relation:<sha256>
kgn:graph:v1:producer:<sha256>
kgn:graph:v1:source:<sha256>
```

Callers supply only the digest, cannot parse or choose the category prefix, and
receive no normalization. The identities are not Phase 0J keys, UUIDs, paths,
symbols, URLs, permissions, database keys, or authorization tokens. Phase 11B
does not select the later deterministic digest derivation inputs.

### Closed Composition vocabulary

Version 1 contains exactly four node types:

- `composition.contract`
- `composition.input`
- `composition.root`
- `composition.service`

It contains exactly four relation types:

- `composition.consumes_input`
- `composition.depends_on`
- `composition.exposes_root`
- `composition.implements_contract`

These values describe structural Composition facts only. There are no aliases,
custom types, registries, wildcards, metadata bags, or extension contributions.
They do not describe calls, events, routes, database ownership, security
permission, observability causality, or arbitrary application semantics.

### Typed schemas, visibility, and disclosure

Field names contain 1 through 64 ASCII bytes matching
`^[a-z][a-z0-9_]{0,63}$`. Version 1 supports only the `text` field type.
Disclosure is exactly `safe` or `protected`; secrets are excluded rather than
classified. Visibility is exactly `public`, `application`, or
`framework_internal`. Visibility limits eligible audiences and disclosure
limits presentation; neither grants authentication, authorization, or a tool
capability.

Node and relation schemas accept only canonical caller-supplied lists. Fields
are unique and binary-ordinal by field-name bytes, with at most 32 entries.
Relation source and target lists are non-empty, unique, binary-ordinal by enum
value, and limited to 16 entries each. Invalid order is rejected rather than
silently repaired.

The immutable version-1 catalog contains one schema for each vocabulary member.
Contract has Protected `symbol`; input has Safe `lifetime` and Protected
`symbol`; root has Protected `contract` and Safe `kind`; service has Protected
`class`, optional Protected `cleanup`, and Safe `lifetime`. Every field has
Application visibility. Relations carry no fields and permit only service to
input for consumes-input, service to service for depends-on, root to contract
for exposes-root, and service to contract for implements-contract.

### Bounded provenance

Graph provenance stores exactly producer identity, source identity, the sole
`kongono.composition/2` producer contract, derivation version 1, and one exact
lowercase source SHA-256 digest. It performs no currentness or filesystem check.
It contains no path, timestamp, host, process, user, environment, source body,
secret, credential, rejected input, or mutable handle. Provenance is evidence,
not permission. Composition projection and exact-current artifact validation
remain later boundaries.

### Diagnostics and failures

The package owns contiguous identifiers `KGN-GRAPH-1101` through
`KGN-GRAPH-1105` for invalid identity, field name, schema, provenance, and
contract-limit failures. Context schemas admit only Safe structural tokens and
counts or positions. Raw digests, field names, symbols, paths, source bodies,
and arbitrary caller values are excluded from messages, contexts, and
deterministic stringification.

The Public package exception marker exposes standard diagnostic accessors. The
Internal base validates its diagnostic/type pairing, retains an optional cause,
and supplies deterministic non-disclosing string output. The concrete invalid
contract exception remains Experimental. Native traces are not claimed to be
sanitized.

### Language neutrality and authority

The contracts describe values without PHP reflection, source discovery,
filesystem layout, locale-sensitive ordering, ambient environment, clock,
randomness, process state, registries, caches, or mutable globals. PHP is the
first implementation language, not the conceptual wire format.

Graph authority remains structural and descriptive. No identity, schema,
provenance, visibility, disclosure, or diagnostic value grants security or
operational authority. The package performs no I/O and gives no consumer Graph
access.

## Consequences

- Later IR and Graph construction can depend on one small neutral contract
  package without importing Phase 0J snapshot behavior.
- Invalid identity, schema, and provenance inputs fail visibly and without
  disclosing rejected material.
- Closed version-1 Composition vocabulary and schemas are inspectable and
  deterministic, while additional domains require separately accepted work.
- Callers must construct lists in canonical order; the package does not hide
  input mistakes by sorting.
- The production graph contains 53 acyclic packages and 184 local production
  edges. Only the root and this package gain Phase 11B dependency entries.
- Kongono remains pre-1.0 and not production-ready.

## Deferred work

Phase 11B creates no IR node, relation, or claim record; Graph construction,
merge, admission, traversal, indexing, or diff; Composition projection; wire
format or canonical serialization; artifact generation, validation,
publication, loading, or currentness; command, consumer, extension, agent, or
developer-intelligence integration. Phase 11C is the next unstarted boundary.

## Rejected alternatives

- Reinterpreting Phase 0J keys and open kinds was rejected because it would
  change an accepted Experimental contract by convention.
- A generic public identity was rejected because it permits category confusion.
- Caller-supplied prefixes, normalization, UUIDs, random values, and tuple
  concatenation were rejected because they weaken canonical identity.
- Open/custom vocabularies and metadata bags were rejected because version-1
  schema ownership would become implicit.
- A Secret disclosure case was rejected because secret material is prohibited
  from the Graph contract.
- Silent sorting was rejected because malformed producer input must remain
  visible.
- Paths, timestamps, hosts, process or user identities, and source bodies in
  provenance were rejected as unstable or sensitive.
- Combining contracts with IR, construction, artifacts, commands, or consumers
  was rejected because those later boundaries require separate acceptance.

## Related material

- [ADR 0123](0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
- [Authoritative Application Graph guide](../architecture/authoritative-application-graph.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)
- [Current structural snapshot](../engineering/application-graph-structure.md)
- [`kongono/application-graph-contract`](../../packages/application-graph-contract/README.md)
