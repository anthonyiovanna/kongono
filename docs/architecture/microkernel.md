# Microkernel and Capabilities

## Purpose

Kongono will use a small microkernel plus independently installable capabilities so the framework can remain modular without fragmenting the developer experience.

## Kernel boundary

The implemented `kongono/kernel` package coordinates only its boot state, terminal shutdown state, operation-scope ownership and completion, active-operation accounting, lifecycle misuse detection, and exception classification. Neutral API compatibility metadata is owned by `kongono/api-metadata`; Kernel depends on that package and does not own the metadata declarations. It does not hard-code a transport, runtime, container, exporter, AI provider, frontend framework, or application architecture style.

A Kernel begins `Unbooted`, may enter `Booted` once, and may enter terminal `Shutdown` only with no active operations. While booted, it owns multiple overlapping opaque `OperationScope` objects by identity. No global or ambient current-operation pointer exists. The lifecycle types are Experimental; the Public `ExceptionInterface` is the supported catch boundary for deliberate package failures.

Operation-scope contents, scoped-service cleanup, context propagation, cancellation, runtime adapters, and later extension points remain unresolved. The empty scope is a deliberate Phase 0C boundary, not a convention for future data design.

Phase 0F dependency resolution belongs to the separate `kongono/dependency-injection` component. Phase 0S replaces its former metadata-only Kernel dependency with API Metadata. The Kernel supplies no resolver property, lifecycle binding, special service definition, or preconfigured construction behavior. A consumer-selected Kernel interface may be treated as an ordinary service contract without creating framework-owned integration.

Phase 0T makes Kernel depend directly on Diagnostics only to construct and
expose the six accepted lifecycle contexts. It adds no Diagnostics service,
container binding, scope data, owner metadata, cleanup behavior, renderer,
transport, or shared exception boundary.

Phase 0U makes Dependency Injection consume Diagnostics independently for its 25
closed failure schemas. That edge does not make the resolver a Kernel service,
add lifecycle integration, or create a universal exception family.

## Capability model

Potential capability areas include HTTP, Data, ORM, Migrations, Authentication, Authorization, Events, Queues, Workflows, Cache, Storage, Mail, Notifications, Realtime, Scheduling, AI, Agents, Delegation, MCP, A2A, Frontend integration, Testing, and Developer tooling.

This list records product direction. It does not authorize more component directories, Composer packages, dependencies, public APIs, or implementations. A component may eventually provide one or more capabilities; exact composition and dependency rules remain open.

## Coherent framework experience

Independent installation must not force developers to assemble unrelated conventions. Components should participate in shared framework invariants, metadata, lifecycle, documentation, structured CLI behavior, security, and observability where applicable.

## Extension distinction

A framework component is not automatically a third-party extension. Components provide framework capabilities; extensions contribute through a governed platform and trust model. Exact packaging boundaries remain open.

## Stability

The microkernel-plus-capabilities direction is **LOCKED**. The Phase 0C Kernel lifecycle contract is implemented but **Experimental**. The five current package boundaries and the Phase 0U acyclic dependency graph are implemented; capability metadata, additional component boundaries beyond them, composition mechanics, and all broader Kernel responsibilities remain open.

## Related concepts

- [Architecture overview](overview.md)
- [Runtime model](runtime-model.md)
- [Extension platform](extension-platform.md)
- [ADR 0004](../adr/0004-microkernel-capability-architecture.md)
