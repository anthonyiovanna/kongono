# Application Graph Developer-Intelligence Evidence

Phase 19G's optional [asset-inspection leaf](asset-inspection-and-intelligence.md)
may accept one explicitly supplied same-role Graph intelligence view. It copies
only bounded Safe aggregate counts and fixed tokens, omits Graph identities and
facts, and always reports the asset-to-Graph relationship as
`not-established`. The Graph package has no reverse dependency on Asset
Inspection.

> **Current boundary:** Phase 11G implements the optional Experimental
> `kongono/application-graph-intelligence` package. It derives and independently
> verifies only bounded Safe aggregate evidence from a Phase 11F inspection.
> Phase 11H externally proves that boundary without adding a role adapter,
> production coupling, or role-execution authority.

Phase 14G's optional [model-invocation inspection leaf](model-invocation-inspection-and-evidence.md)
may consume one explicitly supplied current intelligence view that matches its
requested role. This creates no production dependency in the Graph package,
executes no role, and establishes no relationship between a model invocation
and the authoritative Graph.

## Purpose and context

This boundary gives later Chie, Hantei, and Oshie adapters one deterministic,
offline, language-neutral Graph evidence shape without exposing the authoritative
Graph. It sits above Application Graph Artifact and Contract and does not change
the existing role packages.

## Use when

Use the package when a caller already owns a structurally validated
`ApplicationGraphArtifactInspection` and needs a role-addressed aggregate view or
independent bounded comparison evidence.

Do not use it to inspect artifact bytes, enumerate facts, answer arbitrary Graph
questions, establish source or runtime currentness, grant authorization, execute
a developer-intelligence role, or integrate with production runtime behavior.

## Input and output

The deriver accepts exactly one Phase 11F inspection and one of `chie`, `hantei`,
or `oshie`. It copies only:

- artifact SHA-256 and typed Graph identity;
- contract version;
- total node and relation counts;
- one count for every closed `NodeType` and `RelationType` case.

The result adds fixed scope, visibility, currentness, authority, and uncertainty
tokens. All three roles receive identical Graph state and semantics; only the
role token changes. Neutral serialization contains scalars and detached arrays
only.

The verifier receives an inspection and candidate view. Its six ordered checks
cover identities, contract, totals, node vocabulary, relation vocabulary, and
visibility semantics. Failure output contains only `<check-id>-failed` tokens.
It never returns the mismatching value.

## Meaning and limits

Identities establish exact equality, not authenticity or authorization. Counts
are aggregate structural evidence, not individual facts. Omitted information is
unavailable or out of scope, not false. `not-established` means the package does
not prove currentness relative to Composition, source, generated wiring,
workspace, deployment, or runtime. `pass` means all bounded comparisons matched;
it does not authorize an operation or execute Hantei.

## Side effects, lifecycle, and performance

Derivation and verification have no side effects and retain no cross-call state.
They perform no filesystem, environment, clock, randomness, reflection, process,
network, cache, database, telemetry, registry, or global access. Work is linear
in the fixed closed vocabulary, so the bound is constant for contract version 1.
Repeated, nested, interleaved, and separate-process calls remain isolated.

## Security and disclosure

The package exposes no raw Graph, artifact bytes or path, individual fact,
field, provenance, source location, exception message, trace, or arbitrary
query. Its diagnostics contain one fixed Safe component token. Role, identity,
count, visibility, and verification evidence never grant authority.

There is no model, provider, prompt, retrieval, embedding, vector store, network,
account, training, retention, MCP/A2A, Agent Principal, extension, registry,
cache, watcher, mutation, auto-fix, Git, deployment, production, or runtime
integration.

## Dependencies and extension contract

The package depends directly on PHP, API Metadata, Application Graph Artifact,
Application Graph Contract, and Diagnostics. No Chie, Hantei, Oshie, Knowledge,
Console, runtime, security, observability, database, persistence, Kernel,
Dependency Injection, extension, or agent dependency exists.

Later separately accepted adapters may allow Chie to explain, Hantei to judge,
or Oshie to teach from this evidence. Phase 11G itself changes none of their
operations or response schemas.

## Stability and next step

The package and serialization schemas are Experimental. Phase 11H now
externally proves this bounded evidence without adding a production role
adapter. Kongono remains pre-1.0 and not production-ready.

See [ADR 0129](../adr/0129-visibility-safe-application-graph-developer-intelligence.md),
[bounded inspection](application-graph-inspection-and-consumption.md), and the
[authoritative Application Graph](authoritative-application-graph.md).
See also the [Phase 11 external acceptance](phase-11-external-acceptance.md).
