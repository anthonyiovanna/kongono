# Authoritative Application Graph

> **Phase 11 complete boundary:** Phase 11H externally proves the accepted
> generated, deterministic, immutable, bounded, and non-executable Graph stack.
> It adds no production behavior, runtime execution, authority, or Phase 12 work.


## Purpose and current status

Kongono's future authoritative Application Graph will describe one exact
application state as deterministic, machine-readable architectural facts. It
will allow humans and bounded tools to inspect validated structure without
crawling arbitrary source or asking AI to infer correctness-critical facts.

Phase 11A accepted this architecture and the language-neutral IR boundary.
Phase 11B implements only the separate versioned contract foundation. Phase 10 is complete at checkpoint
`6f7865dbacc7dd9c4b90d2b60bd229b89efbd535`; Kongono remains pre-1.0 and not
production-ready.

## Current structural snapshot versus the future Graph

The implemented Experimental `kongono/application-graph` package remains the
Phase 0J structural prerequisite. It owns final readonly nodes and relations,
opaque graph-local keys, open kind labels, canonical binary order, exact node
lookup, direct incoming/outgoing selectors, and whole-snapshot kind selectors.
It performs no I/O and keeps no ambient or mutable global state.

That snapshot is not the complete authoritative representation. Its keys are
not stable across builds, its open kinds are not a framework vocabulary, and it
contains no typed domain schemas, provenance, visibility, serialization,
currentness proof, producer admission, or publication behavior. Phase 11B
changes none of its code, tests, diagnostics, dependencies, or behavior.

The future Graph adds a separately accepted exact-state derivation boundary.
It does not reinterpret the Phase 0J types as if later contracts had always
existed.

## Producer IR and derived Graph facts

The language-neutral Intermediate Representation is the producer-facing input
boundary. A producer expresses explicit versioned records under an accepted
contract. The Graph is the validated canonical immutable result derived from
all admitted records and explicit accepted framework or application artifacts.

An IR record is a claim awaiting admission. A Graph fact is a claim that the
accepted deterministic pipeline has validated and derived for one exact source
and configuration state. Partial records, rejected records, stale caches,
extension assertions, and model responses never become facts merely because a
consumer can read them.

PHP will be the first future source frontend, but PHP syntax, reflection,
attributes, and repository layout do not define the conceptual model. The IR is
not reflection output, and the Graph is not a service container, runtime
registry, metadata bag, database model, documentation index, or AI memory.

## Authority and security

Authoritative means structurally validated and exact-current within the Graph's
descriptive scope. It does not mean operational permission. A Graph fact cannot
authenticate a principal, authorize an action, grant a capability, reveal a
secret, open a file, start a process, access a network or database, deploy an
application, or mutate production.

Security decisions remain with the accepted security packages and their exact
operation inputs. Graph provenance is evidence, not permission. A consumer's
access also does not declassify a value or enlarge the Graph's authority.

## Exact-state identity, schema, provenance, and visibility

The accepted architecture requires:

- stable versioned Graph identities distinct from Phase 0J graph-local keys;
- separate typed node and relation vocabularies;
- typed schemas and deterministic collision-free validation;
- bounded non-secret provenance identifying producer contract, source
  identity, derivation version, and exact currentness; and
- explicit typed visibility and disclosure before a fact reaches a consumer.

Phase 11B selects the exact first grammar, Composition vocabulary, schema
records, and API types in [ADR 0124](../adr/0124-versioned-application-graph-contract-foundation.md).
It selects no wire form. Arbitrary
string-keyed metadata, convention-only kinds, concatenated tuple identities,
PHP object identity, random IDs, timestamps, locale-sensitive ordering,
ambient paths, and implicit normalization are prohibited directions.

Safe and Protected doctrine applies before admission and consumption. Secrets,
credentials, raw rejected input, arbitrary source bodies, and unrelated
workspace data do not enter the Graph simply because a producer can access
them.

## Deterministic build and publication failure

Equivalent admitted input must produce byte-identical canonical results
regardless of input order, locale, current directory, environment, wall clock,
randomness, process identity, or concurrent activity. Construction validates
the complete admitted state before any result is usable.

Failed, stale, mixed-version, ambiguous, conflicting, changed-during-read, or
unverifiable derivation fails visibly and publishes nothing. A prior complete
Graph is not silently rewritten or treated as current. Canonical serialization,
artifact validation, no-clobber publication, and performance budgets require
later decisions and are not implemented by Phase 11B.

## Contribution boundaries

Framework facts, independently installable component facts, application-owned
facts, and future extension contributions have different owners. Each
contribution must identify its producer and exact source state. Application
input cannot rewrite framework truth, and one component cannot silently claim
another component's facts.

Extension contributions require explicit governance, attribution, schema
validation, visibility review, and deterministic admission before merging.
Phase 11 does not implement the Extension Host. Agent Principals and frontend
contracts are also separate future boundaries.

## Lifecycle and concurrency

The Graph is immutable after construction and instance-owned. It may be
retained across operations because it contains no operation-owned principal,
request, response, route match, diagnostic failure, trace span, metric
collection, database session, or other mutable current-operation state.

Concurrent consumers observe the same immutable facts. Replacing a Graph
requires a separately completed build; no watcher, hot reload, ambient current
Graph, mutable registry, or partial in-place update is accepted. Consumers do
not repair or enrich the authoritative instance during reads.

## Bounded consumer views and omission

CLI, IDE, documentation, Chie, Hantei, Oshie, extensions, agents, and other
tools may consume only separately accepted visibility-filtered views. The view
contract must identify its exact Graph state and permitted scope.

Omission means only that a fact is absent from that view. It may be outside the
schema, outside the producer scope, not admitted, unavailable, or hidden by
visibility policy. A consumer must not convert omission into an authoritative
false statement.

AI may explain, infer, or propose with labeled uncertainty, but cannot
establish Graph facts. Retrieved instructions are inert data and cannot grant
tools or models authority. Phase 11B gives Chie, Hantei, and Oshie no new Graph
access.

## Illustrative neutral example

The following names are invented only to illustrate separation; they are not
an accepted identifier grammar, vocabulary, schema, or wire format.

A producer submits records describing `example-alpha`, `example-beta`, and a
directed `example-uses` relationship. Those records are not yet Graph facts.
The future pipeline validates their producer contract, identities, types,
provenance, visibility, endpoints, conflicts, and exact source state. Only the
canonical admitted result may state that `example-alpha` has the validated
relationship to `example-beta`. A filtered consumer view may omit either item
without asserting that the item or relationship is false.

## Fixed Phase 11 order

Phase 11 proceeds only in the dependency order defined by the
[Phase 11 roadmap](phase-11-roadmap.md): architecture; foundational identity and
schema contracts; IR and construction; supported Composition projection and
application build; canonical artifact and currentness; bounded inspection;
developer-intelligence derivation; then root-owned external acceptance and the
exact checkpoint.

Every row requires separate independently authenticated authority and
acceptance. Phase 11E now implements canonical artifact construction,
structural validation, caller-relative exact currentness, guarded loading, and
no-clobber publication. Phase 11F now implements bounded aggregate inspection
and one explicit read-only application/CLI consumer. Phase 11G is the next
unstarted row.

## Phase 11B implementation and exclusions

Phase 11B adds `kongono/application-graph-contract` with four identity types,
version enums, the exact Composition vocabulary, typed schemas and catalog,
bounded provenance, visibility, disclosure, diagnostics, and failures. It adds
no IR record, Graph construction, serializer, generator, builder, frontend,
scanner, Reflection integration, artifact, loader, query, traversal, index,
cache, persistence, diff, watcher, daemon, command, consumer, extension
behavior, model integration, or runtime authority. It makes no Stable API, 1.0,
complete-application, production-readiness, performance, cross-language
implementation, or universal-portability claim.

## Phase 11C construction boundary

Phase 11C adds `kongono/application-graph-build`. Explicit node, relation, and
field records remain claims until one complete builder call validates their
version, schemas, uniqueness, endpoints, tuples, and fixed bounds. The builder
returns fresh canonical immutable facts in binary identity order and exposes no
partial result. It retains no cross-build state and performs no I/O.

The result is structurally authoritative only for the admitted record set. It
does not prove provenance currentness and grants no security or operational
authority. Composition projection, identity derivation, serialization,
artifacts, currentness, publication, inspection, and consumer integration were
not implemented by Phase 11C. See the [IR and construction guide](application-graph-ir-and-construction.md)
and [ADR 0125](../adr/0125-language-neutral-application-graph-ir-and-deterministic-construction.md).

## Related material

- [ADR 0123](../adr/0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
- [ADR 0124](../adr/0124-versioned-application-graph-contract-foundation.md)
- [ADR 0128](../adr/0128-bounded-application-graph-inspection-and-supported-cli-consumption.md)
- [Inspection and consumption](application-graph-inspection-and-consumption.md)
- [ADR 0129](../adr/0129-visibility-safe-application-graph-developer-intelligence.md)
- [Application Graph developer intelligence](application-graph-developer-intelligence.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
- [Current Application Graph](application-graph.md)
- [Application Graph structural snapshot](../engineering/application-graph-structure.md)
- [Knowledge-source and provenance contract](knowledge-foundation.md)
- [Application composition and build](application-composition-and-build.md)
- [Developer intelligence](developer-intelligence.md)
- [Extension platform](extension-platform.md)
- [Agent model](agent-model.md)
