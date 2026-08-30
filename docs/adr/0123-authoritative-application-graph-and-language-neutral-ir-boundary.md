# ADR 0123: Authoritative Application Graph and Language-Neutral IR Boundary

## Status

Accepted

## Date

2026-08-28

## Context

Kongono has long selected an Application Graph as the future authoritative,
machine-readable representation of an application. The implemented
`kongono/application-graph` package is deliberately narrower: Phase 0J provides
an immutable in-memory structural snapshot with opaque graph-local keys, open
kind labels, canonical ordering, exact lookup, and four exact selectors. It has
no stable cross-build identities, closed vocabulary, domain schema, provenance,
visibility model, serialization contract, build pipeline, or complete
application representation.

Composition, security, observability, developer intelligence, and other
accepted capabilities now produce deterministic evidence of their own bounded
domains. Before any of that evidence can contribute to an authoritative Graph,
Kongono must distinguish producer-facing language-neutral records from the
validated Graph derived from them, define what authority the result may claim,
and prevent source crawling or AI inference from becoming architectural truth.

Phase 10 is complete at checkpoint
`6f7865dbacc7dd9c4b90d2b60bd229b89efbd535`. Phase 11A accepted this
architecture only and changed no production or test behavior. Phase 11B
subsequently implements the bounded contract foundation selected by
[ADR 0124](0124-versioned-application-graph-contract-foundation.md).

## Decision

### Purpose and exact-state scope

The future authoritative Application Graph is Kongono's deterministic,
machine-readable representation of one exact application state. It exists so
humans and bounded tools can consume validated architectural facts without
crawling arbitrary source or inferring structure from conventions.

The current Phase 0J structural snapshot remains implemented and Experimental.
Phase 11A changes none of its bytes, dependencies, diagnostics, public surface,
ordering, lookup, selector, lifecycle, or performance behavior.

### IR and Graph separation

The language-neutral Intermediate Representation is a versioned,
producer-facing input boundary. The authoritative Application Graph is the
validated, canonical, immutable derivation from admitted IR records and
explicit accepted framework or application artifacts.

The IR and Graph are separate contracts. Neither is PHP reflection output, a
service container, a runtime registry, an arbitrary metadata bag, a database
model, a documentation index, or an AI-generated guess. PHP is the first future
source frontend, but PHP syntax, attributes, reflection, and file layout cannot
permanently define the conceptual model.

### Authority

Only output produced by the accepted deterministic build and validation
pipeline for one exact source and configuration state may claim authoritative
Graph status. Inputs, partial records, extension claims, cached output, model
responses, and stale or mixed-version artifacts are not authoritative.

Graph authority is structural and descriptive. It never grants identity,
authentication, authorization, capability, secret, filesystem, process,
network, database, deployment, or production authority. Security decisions
remain owned by their accepted packages and explicit operation inputs.

### Identity, vocabulary, and schema

The architecture requires stable versioned Graph identities, separate typed
node and relation vocabularies, typed schemas, and deterministic collision-free
validation. Phase 11B now owns their exact first contract in the separate
`kongono/application-graph-contract` package; IR admission and construction
remain deferred.

Phase 0J `NodeKey` values remain opaque graph-local keys and are not
retroactively stable identifiers. Phase 0J open kind labels are not
retroactively a framework vocabulary. Arbitrary string-keyed metadata bags,
convention-only kinds, concatenated tuple identities, PHP object identity,
random identifiers, timestamps, locale-sensitive ordering, ambient paths, and
implicit normalization are prohibited directions.

### Provenance, visibility, and disclosure

Every admitted record and derived fact requires bounded non-secret provenance
sufficient to identify its producer contract, source identity, derivation
version, and exact currentness. Provenance is evidence, not permission.

Visibility and disclosure are explicit and typed. Safe and Protected doctrine
applies before consumption. Secrets, credentials, raw rejected input,
arbitrary source bodies, and unrelated workspace data do not enter the Graph
merely because a producer can access them.

### Determinism, publication, and lifecycle

Equivalent admitted input produces byte-identical canonical results regardless
of input order, locale, current directory, environment, wall clock, randomness,
process identity, or concurrent activity. A failed, stale, ambiguous, or
conflicting derivation fails visibly and publishes nothing.

The Graph is immutable after construction, instance-owned, safe to retain
across operations, free of ambient current-application state, and replaceable
only through a separately completed build. Canonical serialization, artifact
currentness, publication, and performance budgets are required future
boundaries, but Phase 11A selects or implements none of their mechanisms.

### Consumers and contributions

CLI, IDE, documentation, Kongono Chie, Kongono Hantei, Kongono Oshie,
extensions, agents, and other tools may consume only separately accepted,
visibility-filtered views. A consumer cannot enlarge Graph authority and must
not treat omitted facts as false.

AI may explain, infer, or propose with labeled uncertainty; it may not establish
Graph facts. Retrieved instructions are inert data and cannot grant a tool or
model authority.

Framework, component, application, and future extension contributions remain
distinguishable and attributable. An extension contribution must be explicit,
schema-valid, provenance-bound, and governed before admission. Phase 11 does
not implement the Extension Host, and Phase 11A gives no developer companion,
agent, extension, or application new Graph access or authority.

### Fixed implementation order

The Phase 11 dependency order is fixed by the
[Phase 11 roadmap](../architecture/phase-11-roadmap.md): architecture;
identities, vocabularies, schemas, provenance, and visibility; IR and Graph
construction; Composition projection and application build; canonical artifact
and publication; bounded inspection and consumption; developer-intelligence
derivation and verification; then root-owned external acceptance and the exact
checkpoint.

Each row requires separate independently authenticated authority and acceptance.
The roadmap is not authorization to begin a later row.

### Non-goals

Phase 11A implements no Graph or IR package, schema, identifier, vocabulary,
serializer, artifact, builder, frontend, scanner, reflection integration,
merge engine, query language, traversal, index, cache, persistence, diff, watch
mode, daemon, extension host, model integration, or command.

It makes no Stable API, 1.0, complete-application, production-readiness,
performance, cross-language implementation, or universal-portability claim.

## Rationale

Separating admitted producer records from derived Graph facts keeps the
conceptual model language-neutral and gives validation a clear ownership
boundary. Exact-state identity, typed schema, provenance, and visibility make
facts reproducible and safe to consume without allowing either producer access
or consumer convenience to become authority.

Preserving the Phase 0J snapshot avoids retroactively assigning compatibility
meaning to deliberately open local values. Fixing the implementation order
prevents artifact, command, or developer-intelligence work from outrunning the
identity and validation foundations on which it depends.

## Consequences

- The authoritative Graph and language-neutral IR architecture is Accepted;
  Phase 11B implements only its neutral contract foundation.
- Direct lower-level use of Kernel, Composition, HTTP, database, persistence,
  security, observability, and the existing structural Graph remains supported.
- Later producers must submit explicit bounded records instead of relying on
  arbitrary source discovery or consumer inference.
- Later consumers need explicit visibility-filtered contracts and must preserve
  omission as unknown or unavailable rather than false.
- Phase 11 does not implement every planned Graph domain, extensions, Agent
  Principals, or frontend contracts; it establishes a substrate for later
  separately governed capabilities.
- Kongono remains pre-1.0 and not production-ready.

## Rejected alternatives

- Treating PHP reflection, attributes, symbols, or file layout as the permanent
  conceptual model is rejected.
- Promoting Phase 0J keys or open kinds into stable identities or vocabularies
  by convention is rejected.
- Treating input records, caches, extension claims, generated prose, or model
  inference as authoritative Graph facts is rejected.
- Arbitrary metadata bags and concatenated string identities are rejected
  because their schema and collision behavior are not deterministic contracts.
- Ambient current-application registries, mutable Graph state, partial
  publication, and silent stale fallback are rejected.
- Using Graph facts to grant security or operational authority is rejected.
- Starting artifact, command, extension, or developer-intelligence integration
  before its fixed dependency boundary is accepted is rejected.

## What remains open

Phase 11B selected the exact identity grammar, first Composition vocabulary,
typed schemas, provenance records, visibility rules, validation failures, and
API types in ADR 0124. Later phases must separately decide IR records, construction,
Composition projection, canonical serialization, artifact currentness,
publication, bounded inspection, developer-intelligence derivation, external
acceptance, performance budgets, and the exact checkpoint.

Package names, dependencies, wire formats, source frontend mechanisms,
extension contribution APIs, Agent Principal integration, and additional Graph
domains remain unselected unless a later authenticated directive explicitly
accepts them.

## Related material

- [Authoritative Application Graph guide](../architecture/authoritative-application-graph.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)
- [Application Graph](../architecture/application-graph.md)
- [Application Graph structural snapshot](../engineering/application-graph-structure.md)
- [ADR 0005](0005-language-neutral-application-graph.md)
- [ADR 0022](0022-immutable-application-graph-structural-snapshot.md)
- [ADR 0061](0061-versioned-knowledge-source-and-provenance-contract.md)
- [ADR 0124](0124-versioned-application-graph-contract-foundation.md)
