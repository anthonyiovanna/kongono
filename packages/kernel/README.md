# Kongono Kernel

`kongono/kernel` is Kongono Framework's first production package. Its responsibility is intentionally narrow: it owns one Kernel instance's boot state, shutdown state, opaque operation scopes, active-operation accounting, lifecycle misuse detection, exception classification, and API-status metadata.

## Lifecycle

A new Kernel is `Unbooted`. It may boot exactly once, begin and end many operations while `Booted`, and enter terminal `Shutdown` only when no operations remain active. Invalid transitions throw `LifecycleException` before changing valid state.

Each `beginOperation()` call returns a distinct `OperationScope` whose object identity is the lifecycle token. The creating Kernel owns that exact object until `endOperation()` succeeds. Multiple scopes may overlap, ending one does not affect another, and a foreign or already-ended scope is rejected. There is no global, static, thread-local, fiber-local, singleton, or otherwise ambient current operation.

These rules support persistent runtimes without choosing or integrating one. Kernel state belongs to the instance rather than process teardown. `OperationScope` is deliberately empty in Phase 0C: it has no identifier, metadata, request data, authorization data, callback, runtime adapter, or serialization contract. Future scoped-data propagation and cleanup contracts remain open.

## API status and failures

Every production class-like type is machine-classified as Internal, Experimental, or Public, except the two Public bootstrap metadata types `Api` and `ApiStatus`. Status describes supported external use and compatibility intent; it is not authorization, package visibility, or a security boundary.

- Internal types are unsupported outside Kongono internals and carry no compatibility promise.
- Experimental types may be evaluated with explicit awareness that they carry no backward-compatibility promise before promotion.
- Public types are supported consumer contracts governed by Kongono's release maturity and future versioning policy; Public does not mean Stable.

`ExceptionInterface` is the Public catch boundary for failures deliberately thrown by this package. Kernel lifecycle types and behavior remain Experimental, and no stable general framework API is implied.

## Explicit non-responsibilities

This package does not provide dependency injection, services, configuration, application discovery, an Application Graph, HTTP or other transports, routing, middleware, commands, events, queues, workflows, data access, authorization, extensions, agents, runtime adapters, telemetry, logging, serialization, caching, or cleanup orchestration. Those capabilities are not scaffolded here.
