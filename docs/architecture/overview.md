# Architecture Overview

## Current reality

Kongono is in Phase 0M. The repository now implements package-local runtime
diagnostic metadata for every current deliberate package failure. Its broader
production behavior remains Phase 0J: architectural policy, documentation, a
reproducible PHP quality toolchain, `packages/kernel`,
`packages/application-graph`, and `packages/dependency-injection`. The
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
structured diagnostic context, renderer, HTTP or CLI mapping, development error
page, production error surface, logging, telemetry, retry, cleanup, incident
system, extension host, agent runtime, query plan, performance budget, or CLI
executable is implemented.

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

The Kernel owns only boot and shutdown state, operation-scope identity and ownership, active-operation accounting, lifecycle misuse detection, its exception family, and API-status metadata. The Application Graph package owns only the immutable structural snapshot and its exception family. Dependency Injection separately owns explicit definitions, definition-graph validation, and transient construction. None has a framework-owned integration with the others beyond the Graph and resolver packages using Kernel API-status metadata. Capabilities such as HTTP, data, authorization, events, workflows, agents, and frontend integration remain planned. The monorepo retains atomic root validation across all three independently owned packages.

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
