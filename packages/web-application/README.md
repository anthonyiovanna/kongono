# Kongono Web Application

`kongono/web-application` is Kongono's explicit, transport-free application
composition boundary. It validates Routing and HTTP Server inputs, compiles one
PSR-15 pipeline, and only then boots the supplied Kernel. The package is the
twelfth first-party package and owns one Kernel operation scope around each
accepted in-memory `handle()` call.

## Use and boundaries

Use `WebApplication::boot()` at an explicit composition root when a caller
already has a `KernelInterface`, `RouteTable`, complete route-handler map,
unmatched handler, and declaration-ordered middleware. The returned
`WebApplication` is a PSR-15 `RequestHandlerInterface`.

Do not use this package for SAPI conversion, parsing, response emission,
delivery evidence, trusted diagnostics, generic HTTP failures, configuration,
DI, Application Graph traversal, logging, telemetry, or resource cleanup. It
depends only on API Metadata, Diagnostics, HTTP Server, Kernel, Routing, and the
PSR interfaces listed in its manifest. No existing package depends on it.

## Lifecycle contract

Composition constructs `RoutingRequestHandler`, then `MiddlewarePipeline`, then
calls Kernel `boot()` once. Invalid composition fails before Kernel mutation and
does not invoke middleware or handlers.

Each accepted `handle()` call begins one exact local `OperationScope`, invokes
the pipeline once with the exact request, and ends that exact scope once. A
successful call returns the exact response. A primary failure is rethrown
unchanged after successful cleanup. Cleanup-only failure is rethrown unchanged;
simultaneous work and cleanup failure becomes `OperationCleanupException`, which
preserves both objects and uses the primary as its native previous exception.
Every cleanup failure retires the application, and cleanup is never retried.

Known Kernel `LifecycleException` rejection during begin or shutdown propagates
without retirement because Kernel guarantees pre-mutation rejection. Any other
begin or shutdown failure retires the application because lifecycle state is
uncertain. A retired application rejects later handling and shutdown before
consulting dependencies. Retirement means refusal by this object only; it does
not terminate a worker, emit a response, recover, log, or record an incident.

`shutdown()` is explicit. There is no destructor or automatic shutdown.
Sequential and reentrant calls keep scope, request, response, and failure values
only in their local call frames; the application retains only its Kernel,
compiled pipeline, and retirement boolean.

## Diagnostics and security

The package owns `KGN-WEB-APPLICATION-1001` for retired-instance refusal and
`KGN-WEB-APPLICATION-1002` for simultaneous work and cleanup failure. Both have
empty diagnostic schemas and fixed package prose. Failure objects remain
available only through the explicit composite accessors and are not safe output.
No request data, failure text, class name, trace, route, scope, or arbitrary
caller context enters package-owned diagnostic surfaces.

`WebApplication` and its concrete failures are Experimental. The package marker
is Public, the diagnostic catalog and abstract base are Internal, and no Kongono
API is Stable. See [ADR 0048](../../docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md)
and the [Web Application model](../../docs/architecture/web-application-model.md).

## Phase 2 outer integration

The separate [Web Runtime package](../web-runtime/README.md) now calls this
application exactly once per accepted delivery. This package continues to own
the sole Kernel operation scope and exact scope closure. Web Runtime owns only
outer failure-response selection plus one send and close attempt; it adds no
second scope, direct routing call, concrete SAPI/server/listener/emitter, logging,
telemetry, or authorization. Phase 2H's root-owned fixture now proves this path
and zero active operations after sequential deliveries without making fixture
behavior package-owned. See the [fixture model](../../docs/architecture/phase-2-runnable-fixture.md).
