# Kongono Architecture

## Status and scope

This document is an index of Kongono's authorized architectural direction.
Kongono is pre-alpha. Phase 0M implements package-local runtime diagnostic
metadata for current deliberate package failures. Current production behavior
otherwise remains the Phase 0J implementation: the PHP quality toolchain, the narrow
`kongono/kernel` lifecycle package, the immutable structural core with exact
direct-relation and whole-snapshot kind selectors in
`kongono/application-graph`, and explicit typed constructor resolution in
`kongono/dependency-injection`. Other statements about framework behavior
describe planned architecture unless explicitly identified as current
repository behavior.

Kongono Framework targets PHP 8.5+ and is designed for three first-class consumption surfaces: Human Experience, Software/API Experience, and Agent Experience. The framework is not a Laravel clone. It aims to preserve strong developer productivity while favoring explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

## Foundational model

The planned Application Graph is the authoritative, machine-readable representation of an application. It will derive from a language-neutral Intermediate Representation so that PHP, the first source-language frontend, does not permanently define the conceptual model. Phase 0J retains the source-language-neutral in-memory structural snapshot introduced in Phase 0D, the exact outgoing and incoming direct-relation selectors from Phase 0H, and adds only exact whole-snapshot node-kind and relation-kind selection. `nodesOfKind(NodeKind): array` and `relationsOfKind(RelationKind): array` compare kind values exactly within separate domains, return `[]` for absent kinds, and preserve canonical order and stored-object identity in fresh lists. Node selection scans all nodes in `O(N)` time, relation selection scans all relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open kind labels define no built-in vocabulary or schema. This is not combined endpoint-and-kind filtering, multiple-kind selection, predicates, traversal, reachability, neighbor selection, or a generic query system. Stable identifiers, domain schemas, metadata, provenance, visibility, serialization, discovery, compilation mechanics, persistence, mutation, framework integration, optimization promises, and the complete authoritative representation remain unresolved.

Kongono uses `packages/kernel` as its first physical monorepo package. Its Experimental state machine boots once, owns overlapping opaque operation scopes by object identity, rejects shutdown while work is active, and shuts down terminally. It is transport-, runtime-, and container-neutral. Independently installable capabilities and application architecture beyond this boundary remain planned.

`packages/dependency-injection` is a separate Experimental component. It maps canonical existing interfaces to explicit concrete implementations and ordered constructor dependencies, validates complete definition sets, and constructs transient instances on every dependency edge. It does not make the Kernel a container, provide PSR-11 or autowiring, select shared/scoped lifetimes, or integrate with the Application Graph.

The lifecycle is persistent-runtime-first: framework design may not assume all application state disappears after each request or operation. Runtime-specific integrations must remain adapters rather than kernel dependencies.

Phase 0M realizes the 42 package-owned diagnostic assignments through three
Internal backed-enum catalogs. The three separate Public package exception
markers expose `diagnosticId()`, `diagnosticTitle()`, and
`diagnosticRemediation()` as strings; no universal catch interface exists.
Existing messages remain unchanged, protected detail, and native causes retain
their identity. No structured context, renderer, development error page,
production error surface, HTTP or CLI mapping, logging, telemetry, retry,
cleanup orchestration, incident system, or localization exists. See the [error-handling engineering
guide](docs/engineering/error-handling.md) and the [ADR
index](docs/adr/README.md).

## Boundaries

- PHP 8.5+ is the primary application language.
- TypeScript and JavaScript belong to browser/frontend integration.
- Rust may later support high-performance framework tooling.
- Go may be selected later for specific infrastructure or runtime integrations.
- Vite is the intended default bundler integration; Kongono will own an Asset Platform abstraction, not a bundler.
- OpenTelemetry is the intended instrumentation model; exporters will remain replaceable.
- OpenAPI is intended to be generated from authoritative application metadata.
- MCP and A2A are adapters around Kongono concepts and do not define its domain model.

No runtime adapter, protocol adapter, domain Graph schema, complete Graph compiler, capability package, or framework API for those planned boundaries exists yet. The implemented structural snapshot and Kernel lifecycle must not be read as selecting any of them.

## Detailed documentation

- [Architecture overview](docs/architecture/overview.md)
- [Application Graph](docs/architecture/application-graph.md)
- [Microkernel and capabilities](docs/architecture/microkernel.md)
- [Explicit typed dependency resolution](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Progressive architecture](docs/architecture/progressive-architecture.md)
- [Runtime model](docs/architecture/runtime-model.md)
- [Agent model](docs/architecture/agent-model.md)
- [Extension platform](docs/architecture/extension-platform.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture Decision Records](docs/adr/README.md)
