# Kongono Architecture

## Status and scope

This document is an index of Kongono's authorized architectural direction. Kongono is pre-alpha. Phase 0H implements governance, documentation, the PHP quality toolchain, the narrow `kongono/kernel` lifecycle package, the immutable structural core and exact direct-relation selectors in `kongono/application-graph`, and explicit typed constructor resolution in `kongono/dependency-injection`. Other statements about framework behavior describe planned architecture unless explicitly identified as current repository behavior.

Kongono Framework targets PHP 8.5+ and is designed for three first-class consumption surfaces: Human Experience, Software/API Experience, and Agent Experience. The framework is not a Laravel clone. It aims to preserve strong developer productivity while favoring explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

## Foundational model

The planned Application Graph is the authoritative, machine-readable representation of an application. It will derive from a language-neutral Intermediate Representation so that PHP, the first source-language frontend, does not permanently define the conceptual model. Phase 0H retains the source-language-neutral in-memory structural snapshot introduced in Phase 0D and adds only exact outgoing and incoming selection of immediately stored relations by endpoint-key value. Missing and isolated keys return empty lists, self-relations appear in both applicable directions, and results preserve canonical global source-kind-target order in detached arrays containing the same immutable relations. Each selector scans all relations in `O(R)` time and retains no index or cache. This is not traversal, reachability, neighbor selection, kind filtering, or a generic query system. Stable identifiers, domain schemas, metadata, provenance, visibility, serialization, discovery, compilation mechanics, persistence, mutation, framework integration, and the complete authoritative representation remain unresolved.

Kongono uses `packages/kernel` as its first physical monorepo package. Its Experimental state machine boots once, owns overlapping opaque operation scopes by object identity, rejects shutdown while work is active, and shuts down terminally. It is transport-, runtime-, and container-neutral. Independently installable capabilities and application architecture beyond this boundary remain planned.

`packages/dependency-injection` is a separate Experimental component. It maps canonical existing interfaces to explicit concrete implementations and ordered constructor dependencies, validates complete definition sets, and constructs transient instances on every dependency edge. It does not make the Kernel a container, provide PSR-11 or autowiring, select shared/scoped lifetimes, or integrate with the Application Graph.

The lifecycle is persistent-runtime-first: framework design may not assume all application state disappears after each request or operation. Runtime-specific integrations must remain adapters rather than kernel dependencies.

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
- [Progressive architecture](docs/architecture/progressive-architecture.md)
- [Runtime model](docs/architecture/runtime-model.md)
- [Agent model](docs/architecture/agent-model.md)
- [Extension platform](docs/architecture/extension-platform.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture Decision Records](docs/adr/README.md)
