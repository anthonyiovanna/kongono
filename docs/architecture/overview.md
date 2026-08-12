# Architecture Overview

## Current reality

Kongono is in Phase 0D. The repository contains architectural policy, documentation, a reproducible PHP quality toolchain, `packages/kernel`, and `packages/application-graph`. The Graph package implements only an Experimental immutable structural snapshot with graph-local opaque keys, open kinds, directed relations, structural validation, canonical order, and exact node lookup. Its exception marker is Public but not Stable and is separate from the Kernel marker. No stable Graph identity, domain schema, serialization, discovery, compiler, capability package, runtime adapter, extension host, agent runtime, or CLI executable is implemented.

## Intended architecture

Kongono Framework is designed for humans, software clients, and AI agents. Its architecture combines:

1. A small, runtime-independent microkernel.
2. Independently installable components contributing explicit capabilities.
3. A language-neutral IR and authoritative Application Graph.
4. Progressive application architecture, with MVC as a friendly edge rather than a business-logic trap.
5. Persistent-runtime-safe lifecycle and operation scopes.
6. Human, Software/API, and Agent experiences derived from coherent application capabilities.
7. Secure extension governance and standards-based adapters.

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

The Kernel owns only boot and shutdown state, operation-scope identity and ownership, active-operation accounting, lifecycle misuse detection, its exception family, and API-status metadata. The Application Graph package owns only the immutable structural snapshot and its exception family; it has no runtime-lifecycle role. Capabilities such as HTTP, data, authorization, events, workflows, agents, and frontend integration remain planned components. The monorepo retains atomic root validation across both independently owned packages.

## Interoperability boundaries

Composer, PSRs, OpenAPI, OpenTelemetry, OAuth/OIDC, MCP, A2A, and web standards are likely interoperability tools. Their protocols do not supersede Kongono's internal concepts. Kongono-owned abstractions should preserve portability and prevent an exporter, provider, adapter, or runtime from becoming the architecture.

## Related documentation

- [Application Graph](application-graph.md)
- [Microkernel](microkernel.md)
- [Progressive architecture](progressive-architecture.md)
- [Runtime model](runtime-model.md)
- [Agent model](agent-model.md)
- [Extension platform](extension-platform.md)
