# Kongono Kernel

`kongono/kernel` is Kongono Framework's runtime-neutral lifecycle package. Its responsibility is intentionally narrow: it owns one Kernel instance's boot state, shutdown state, opaque operation scopes, active-operation accounting, lifecycle misuse detection, and exception classification. Neutral API-status metadata belongs to `kongono/api-metadata`.

## Lifecycle

A new Kernel is `Unbooted`. It may boot exactly once, begin and end many operations while `Booted`, and enter terminal `Shutdown` only when no operations remain active. Invalid transitions throw `LifecycleException` before changing valid state.

Each `beginOperation()` call returns a distinct `OperationScope` whose object identity is the lifecycle token. The creating Kernel owns that exact object until `endOperation()` succeeds. Multiple scopes may overlap, ending one does not affect another, and a foreign or already-ended scope is rejected. There is no global, static, thread-local, fiber-local, singleton, or otherwise ambient current operation.

These rules support persistent runtimes without choosing or integrating one. Kernel state belongs to the instance rather than process teardown. `OperationScope` is deliberately empty in Phase 0C: it has no identifier, metadata, request data, authorization data, callback, runtime adapter, or serialization contract. Future scoped-data propagation and cleanup contracts remain open.

## API status and failures

Every production class-like type is machine-classified as Internal, Experimental, or Public, except the two Public bootstrap metadata types `Api` and `ApiStatus`. Status describes supported external use and compatibility intent; it is not authorization, package visibility, or a security boundary.

- Internal types are unsupported outside Kongono internals and carry no compatibility promise.
- Experimental types may be evaluated with explicit awareness that they carry no backward-compatibility promise before promotion.
- Public types are supported consumer contracts governed by Kongono's release maturity and future versioning policy; Public does not mean Stable.

Phase 0S moved the bootstrap declarations atomically to
`Kongono\ApiMetadata\Api` and `ApiStatus` in the neutral
`kongono/api-metadata` package. Kernel depends directly on that package and no
longer owns compatibility metadata. The former `Kongono\Kernel\Api\*` FQCNs are
absent, with no alias or compatibility shim.

`ExceptionInterface` is the Public catch boundary for failures deliberately thrown by this package. It exposes `diagnosticId()`, `diagnosticTitle()`, and `diagnosticRemediation()` as strings plus one Public presentation-neutral `diagnosticContext()` value. Kernel lifecycle types and behavior remain Experimental, and no stable general framework API is implied.

The Internal `Kongono\Kernel\Diagnostic\LifecycleDiagnostic` catalog owns the
six exact `KERNEL` cases. Every deliberate Kernel throw emits its assigned
identifier. The identifier is stable machine identity; title and remediation
are diagnostic prose and may improve without changing that identity. Existing
messages remain unchanged, may contain caller-relevant lifecycle state, and must
not be exposed directly to an untrusted production surface. See [ADR
0026](../../docs/adr/0026-actionable-diagnostics-and-error-boundaries.md), [ADR
0027](../../docs/adr/0027-package-local-runtime-diagnostic-metadata.md), and the
[error-handling engineering guide](../../docs/engineering/error-handling.md).

The shared presentation-neutral schema and value types exist, and Phase 0T
integrates all six Kernel lifecycle failures. The Kernel-owned ordered Safe fields are `actual_state` then
`expected_state` for 1001, 1002, 1004, and 1005;
`active_operation_count` then `expected_active_operation_count` for 1003; and
`scope_condition` for 1006. It has no Protected field. Kernel owns those schema
instances and exact throw-site values. Kernel requires Diagnostics directly and
its Public marker exposes `diagnosticContext()` in addition to ID, title, and
remediation. Graph, Kernel, and DI depend on Diagnostics independently and retain
unrelated recovery boundaries. No renderer,
logging, telemetry, or transport behavior exists.

The implemented neutral metadata topology removes the former cycle, and Phase 0T
adds the exact one-way Kernel-to-Diagnostics edge without adding a reverse edge
or Graph dependency. Phase 0U independently adds DI context; universal exception
integration, runtime presentation, and transport remain unimplemented.

## Explicit non-responsibilities

This package does not provide dependency injection, services, configuration, application discovery, an Application Graph, HTTP or other transports, routing, middleware, commands, events, queues, workflows, data access, authorization, extensions, agents, runtime adapters, telemetry, logging, serialization, caching, or cleanup orchestration. Phase 2F's separate [Web Application package](../web-application/README.md) calls this unchanged lifecycle contract to own one scope around each application call; broader cleanup remains outside Kernel.

Phase 2G's separate [Web Runtime package](../web-runtime/README.md) is the
top-level outer delivery leaf. It may recognize Kernel package failures as one
supported trusted-diagnostic family, but it does not call Kernel or own a scope.
Web Application retains the sole operation scope. No concrete runtime, logging,
telemetry, authorization, or runnable fixture was added; Phase 2H remains next.
