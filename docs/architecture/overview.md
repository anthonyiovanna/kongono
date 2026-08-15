# Architecture Overview

## Current reality

Kongono's runtime implementation includes the presentation-neutral Diagnostics
value foundation, Phase 0Q exact bounded context for all 11 Graph failures, and
Phase 0T exact bounded context for all six Kernel lifecycle failures,
alongside package-local runtime diagnostic metadata for every current deliberate
Kernel, Graph, and DI failure. Its broader
production behavior remains Phase 0J: architectural policy, documentation, a
    reproducible PHP quality toolchain, `packages/api-metadata`, `packages/kernel`,
`packages/application-graph`, `packages/dependency-injection`, and
`packages/diagnostics`. The
Experimental Graph snapshot supports exact outgoing and incoming selection of
immediately stored relations by endpoint-key value and exact whole-snapshot
selection through `nodesOfKind(NodeKind): array` and
`relationsOfKind(RelationKind): array`. Kind selection compares open labels
exactly within separate node and relation domains, returns `[]` for absent kinds,
and preserves canonical order and stored-object identity in fresh lists. The
node selector is an `O(N)` scan and the relation selector an `O(R)` scan, with
`O(k)` result allocation and no retained kind index or query cache. These are
narrow selectors, not a built-in kind vocabulary, combined filtering,
predicates, neighbors, traversal, or a generic query system. The Experimental
resolver still uses explicit canonical interface definitions, validates complete
dependency graphs, and constructs transient objects on every edge.

Phase 0M gives the existing Kernel, Graph, and DI exception markers three string
accessors for exact ID, title, and remediation. Three Internal backed-enum
catalogs own the 42 mappings; no universal catch or diagnostic interface exists.
Existing messages remain unchanged protected detail, and optional native causes
are preserved by identity. Expected application outcomes remain distinct;
infrastructure, native, autoloader, and consumer failures are not wrapped merely
to gain Kongono metadata.

No stable Graph identity, domain schema, metadata, serialization, persistence,
mutation, discovery, compiler, Graph/framework integration, runtime adapter,
HTTP or CLI mapping, development error
page, production error surface, logging, telemetry, retry, cleanup, incident
system, extension host, agent runtime, query plan, performance budget, or CLI
executable is implemented.

The current package topology has six packages. `kongono/api-metadata` owns
`Kongono\ApiMetadata\Api` and `ApiStatus` and requires PHP only. Kernel,
Diagnostics, Application Graph, and Dependency Injection depend directly on it;
the framework root consumes all six packages and Graph retains its Diagnostics
dependency. Phase 0T also makes Kernel, and Phase 0U makes DI, depend directly
on Diagnostics. The graph is acyclic, and those three consumers have no
dependency on each other. The former
`Kongono\Kernel\Api\*` FQCNs are absent without a shim.

Phase 0X implements the leaf above the five originating packages:
`kongono/diagnostic-presentation`, namespace
`Kongono\DiagnosticPresentation\`. It depends directly on all five; no existing
package depends on it. The directory, manifest, four Experimental developer
concepts, and package-local context-free failure foundation exist. Phase 0X
added no renderer, trusted-mode selector, service binding, or runtime adapter.

`kongono/diagnostics` implements closed schemas, immutable context
and field snapshots, Safe/Protected disclosure vocabulary, exact scalar kinds,
deterministic bounded text representation, and its own package-local construction
failures. Graph is its first semantic consumer: the Graph catalog owns exact
schemas, Graph throw sites supply approved values, and the Graph marker adds
`diagnosticContext()`. Phase 0T gives Kernel's six failures the same value
accessor with independently owned schemas and exact Safe values. Phase 0U gives
DI's 25 diagnostics exact schemas across 26 throw sites and the same accessor;
all four catch markers remain unrelated, and Diagnostics construction failures remain
context-free. Capture performs no ambient lookup or I/O and retains no global
per-failure state. Safe and Protected perform no rendering or disclosure;
presentation and transport policy remain future.

The leaf's first implemented Experimental boundary is a pure trusted-developer
projection over exactly one contextual Kernel, Graph, or DI marker. It derives
the owner token, validates and copies catalog ID/title/remediation, fixed
operation and invariant, and canonically ordered bounded fields with exact kind,
Safe/Protected disclosure, and truncation state. It rejects an ambiguous multi-
marker source before accessor calls and excludes arbitrary failures, messages,
causes, traces, source, requests, environment, and transport data. The snapshot
is immutable and detached, performs no I/O, uses no ambient state or cache, and
retains nothing across operations.

Phase 0Z implements the Experimental
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`
directly below that projector in the same leaf. The pure renderer accepts only
the detached trusted model and returns one complete accessible, escaped,
bounded HTML5 document. This accepted presentation architecture adds no package
edge or runtime integration. The class, exact local stylesheet, and two golden
fixtures are implemented; trusted activation, an adapter, a served page, and a
production surface are not.

Phase 1A accepts the outer activation policy but implements no enforcement. A
future composition boundary must provide affirmative **trusted bootstrap permission**
fixed for one adapter instance, and the adapter must independently provide
affirmative **adapter-controlled delivery permission** for the current delivery. Missing,
invalid, ambiguous, failed, denied, or uncertain decisions prohibit the trusted
path. Client data, raw environment labels, heuristics, AI output, and ambient or
previous-operation state cannot activate or widen either permission.

The conceptual presentation sequence is:

```text
trusted bootstrap permission AND adapter-controlled delivery permission
    -> presentation access -> pure projection -> pure rendering -> future delivery
```

The two permissions and future delivery remain outside Diagnostic Presentation.
The policy is Accepted, while its typed decisions, raw-configuration validation,
adapter, HTTP behavior, served page, and runtime enforcement are absent.
Projection remains separate from rendering, and production requires its own
deny-by-default model rather than redaction of the trusted developer model.

## Intended architecture

Kongono Framework is designed for humans, software clients, and AI agents. Its architecture combines:

1. A small, runtime-independent microkernel.
2. Independently installable components contributing explicit capabilities.
3. A language-neutral IR and authoritative Application Graph.
4. Progressive application architecture, with MVC as a friendly edge rather than a business-logic trap.
5. Persistent-runtime-safe lifecycle and operation scopes.
6. Human, Software/API, and Agent experiences derived from coherent application capabilities.
7. Secure extension governance and standards-based adapters.
8. Actionable, package-owned, securely disclosed diagnostics and explicit error
   boundaries.
9. Presentation-neutral bounded diagnostic values that do not merge package
   recovery ownership.

## Conceptual flow

```text
PHP source frontend (first)
        |
        v
Language-neutral IR              [exact mechanism: EXPERIMENTAL]
        |
        v
Application Graph structural core [implemented, Experimental]
        |
        v
Authoritative domain Graph       [schema/serialization/compiler: EXPERIMENTAL]
        |
        +--> Human Experience
        +--> Software/API Experience
        +--> Agent Experience
        +--> Tooling / infrastructure / documentation
```

Only the structural core is implemented. The surrounding flow expresses intended responsibility, not an implemented compiler pipeline or public contract.

## Framework shape

The Kernel owns boot and shutdown state, operation-scope identity and ownership, active-operation accounting, lifecycle misuse detection, its exception family, and six exact diagnostic-context schemas and values. API Metadata neutrally owns compatibility classification. The Application Graph package owns only the immutable structural snapshot, its local exception family, and its 11 diagnostic-context schemas and values. Dependency Injection separately owns explicit definitions, definition-graph validation, transient construction, and 25 diagnostic schemas across 26 throw sites. Diagnostics owns presentation-neutral bounded values and its construction-failure boundary. Diagnostic Presentation owns the implemented pure trusted-developer projection core and pure HTML renderer, but neither trusted bootstrap permission nor adapter-controlled delivery permission. The exact Graph-, Kernel-, and DI-to-Diagnostics dependencies create neither a universal exception nor cross-package lifecycle integration. Phase 1A adds no package edge, service, configuration mechanism, or adapter. Capabilities such as HTTP, data, authorization, events, workflows, agents, and frontend integration remain planned. The monorepo retains atomic root validation across all six currently implemented packages.

## Interoperability boundaries

Composer, PSRs, OpenAPI, OpenTelemetry, OAuth/OIDC, MCP, A2A, and web standards are likely interoperability tools. Their protocols do not supersede Kongono's internal concepts. Kongono-owned abstractions should preserve portability and prevent an exporter, provider, adapter, or runtime from becoming the architecture.

## Related documentation

- [Application Graph](application-graph.md)
- [Microkernel](microkernel.md)
- [Progressive architecture](progressive-architecture.md)
- [Runtime model](runtime-model.md)
- [Agent model](agent-model.md)
- [Extension platform](extension-platform.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
