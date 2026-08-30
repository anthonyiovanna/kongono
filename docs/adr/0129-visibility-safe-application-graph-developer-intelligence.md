# ADR 0129: Visibility-Safe Application Graph Developer-Intelligence Evidence

## Status

Accepted and implemented by Phase 11G.

## Context

Phase 11F introduced the first supported bounded inspection of one structurally
validated Application Graph artifact. That inspection exposes only artifact and
Graph identities, contract version, totals, and complete closed-vocabulary
counts. It exposes no raw Graph or individual fact and establishes no external
currentness or authorization.

Chie, Hantei, and Oshie need a common visibility-safe evidence shape before any
later role-specific adapter can explain, judge, or teach from Graph evidence.
Combining those roles with raw artifacts, arbitrary queries, runtime access, or
tool authority would violate their accepted separation.

## Decision

The optional upper-level Experimental `kongono/application-graph-intelligence`
package consumes only an already validated Phase 11F
`ApplicationGraphArtifactInspection`. It deterministically derives one immutable
role-addressed view containing only the supplied identities, contract version,
aggregate totals, and one count for every closed version-1 node and relation
type.

The role is purpose metadata only. Chie, Hantei, and Oshie receive the same
Graph evidence and fixed semantics; their views differ only in the role token.
Role selection grants no read, write, tool, model, network, security, filesystem,
process, database, deployment, production, or runtime authority.

Every view states exact fixed semantics:

- scope `composition-v1`;
- visibility `safe-aggregate-only`;
- currentness `not-established`;
- authority `evidence-only`;
- uncertainties `individual-facts-omitted`, `currentness-not-established`, and
  `authorization-not-granted` in that order.

Omission means omitted, unavailable, or out of scope; it never means false.
Currentness relative to Composition source, generated wiring, workspace,
deployment, or runtime is not established.

An independent verifier compares every allowed identity, contract, total,
closed-vocabulary count, and fixed semantic token. It returns only six ordered
boolean check outcomes and fixed failure tokens. It does not call the deriver,
repair the candidate, execute Hantei, expose mismatching values, or grant
authority. A PASS is comparison evidence only.

The package depends directly only on API Metadata, Application Graph Artifact,
Application Graph Contract, Diagnostics, and PHP. Diagnostics is used solely for
the accepted structured exception convention. Existing Chie, Hantei, and Oshie
operations and response schemas do not change. Later separately accepted
adapters may let Chie explain, Hantei independently judge, or Oshie teach from
this evidence.

## Security and operational boundary

The package receives no artifact path, canonical bytes, raw Graph, individual
fact, field, provenance, source location, workspace reader, or runtime handle.
It provides no listing, lookup, query, filter, traversal, diff, impact analysis,
visualization, index, search, discovery, registry, cache, watcher, or mutation.

It introduces no model, provider, prompt, retrieval, embedding, vector store,
network, account, training, retention, MCP/A2A, Agent Principal, extension,
auto-fix, Git, deployment, production, or runtime integration. Derivation and
verification are offline, stateless, side-effect-free, deterministic, and
bounded by the closed vocabulary.

Diagnostics contain one fixed Safe component token only. Rejected values,
identities, paths, counts, facts, fields, provenance, messages, and traces are
excluded.

## Rejected alternatives

- Raw artifact or Graph access would bypass the Phase 11F visibility boundary.
- Arbitrary Graph queries or role-specific projections would prematurely create
  a general consumer API.
- Treating counts, identities, role, or PASS as authority would combine evidence
  with authorization.
- Calling Chie, Hantei, or Oshie directly would couple the role packages and
  permit cross-role authority.
- Inferring currentness from structural inspection would make stale evidence
  appear current.

## Consequences

Developer-intelligence roles now have one common bounded Safe aggregate evidence
shape and one independent comparison result suitable for later explicit
adapters. Individual facts remain inaccessible, currentness remains unresolved,
and existing role behavior is unchanged. Phase 11H is next and remains
unstarted. Kongono remains pre-1.0 and not production-ready.
