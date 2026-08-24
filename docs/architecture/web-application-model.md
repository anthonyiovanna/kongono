# Web Application Model

## Purpose and boundary

Phase 2F implements `kongono/web-application`, the twelfth first-party package
and the explicit transport-free composition boundary above Routing, HTTP Server,
and Kernel. It exposes one in-memory PSR-15 handler. It does not accept SAPI
input, convert messages, emit responses, evaluate delivery evidence, map
failures to HTTP, integrate trusted presentation, or create a runnable fixture.

The exact dependency graph is:

```text
web-application -> api-metadata
web-application -> diagnostics
web-application -> http-server
web-application -> kernel
web-application -> routing
web-application -> psr/http-message
web-application -> psr/http-server-handler
web-application -> psr/http-server-middleware
```

No existing package depends on Web Application. It has no dependency on the
concrete HTTP Message package, DI, Application Graph, diagnostic activation,
presentation, or handler packages, configuration, an adapter, an emitter,
logging, or telemetry.

## Boot sequence

`WebApplication::boot()` performs exactly:

1. construct `RoutingRequestHandler` from the route table, raw handler map, and unmatched handler;
2. construct `MiddlewarePipeline` from that terminal handler and ordered middleware;
3. call Kernel `boot()` once; and
4. return the application only after successful boot.

Routing and HTTP Server validation therefore precede Kernel mutation. Invalid
composition propagates its exact existing failure, does not boot, and invokes no
middleware or handler. Kernel boot failure also propagates unchanged.

## Handling and cleanup

For every non-retired call, the application begins one Kernel operation scope,
keeps the exact token only in the call frame, invokes the compiled pipeline with
the exact request, and ends that exact scope once. End is attempted after every
pipeline return or failure and is never retried.

| Work result | Scope end result | Observable result | Retired |
|---|---|---|---|
| response | success | exact response | no |
| primary Throwable | success | exact primary Throwable | no |
| response | cleanup Throwable | exact cleanup Throwable | yes |
| primary Throwable | cleanup Throwable | `OperationCleanupException` with both identities | yes |

The composite uses the primary failure as its native previous exception and has
explicit accessors for both objects. Its fixed package diagnostic does not
contain either message, class, trace, request, or other caller value.

A known begin `LifecycleException` propagates unchanged without retirement and
without pipeline or end access. Another begin failure retires and propagates
unchanged because no safely closeable scope is known to exist.

## Retirement and shutdown

Retirement is a local irreversible boolean. A retired instance rejects later
`handle()` and `shutdown()` before consulting Kernel or the pipeline. It retains
no causing failure and does not terminate a worker, emit a response, recover,
reset Kernel, log, or record an incident.

`shutdown()` delegates once to Kernel. Known `LifecycleException` rejection
propagates without retirement; another failure retires and propagates unchanged.
Successful shutdown returns `void`. There is no destructor, automatic shutdown,
second lifecycle enum, or cleanup retry.

## Persistent-runtime and concurrency behavior

The long-lived object retains only its Kernel, compiled pipeline, and retirement
boolean. Sequential calls receive distinct scopes. Reentrant calls keep nested
scope identity in local frames and close inner before outer. No request, response,
match, scope, failure, principal, permission, or tenant enters global, static,
singleton, container-current, thread-local, or fiber-local state. Consumer
middleware and handlers remain responsible for their own concurrency safety.

## Security, performance, and stability

The package diagnostics have fixed prose and empty field schemas. Raw failure
objects are not safe output. The package performs no I/O or authorization and
adds constant lifecycle-call overhead beyond existing bounded route/middleware
work. `WebApplication` and its concrete failures are Experimental; its marker is
Public and its catalog/base are Internal.

Phase 2G owns the outer adapter, delivery evidence, response delivery, generic
and trusted failure delivery, and adapter-owned transport cleanup. It opens no
second Kernel scope. Phase 2H's test-owned fixture proves this ownership through
the Public delivery port. Broader production conversion and cleanup remain open.

Related: [ADR 0048](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md),
[Kernel model](microkernel.md), [HTTP Server model](http-server-model.md), and
[package README](../../packages/web-application/README.md).

## Phase 5F constructor seam

`WebApplication` is now directly constructible from one `KernelInterface` and
one explicit `RequestHandlerInterface`; construction boots the Kernel once.
The existing `boot()` factory still constructs `RoutingRequestHandler` and
`MiddlewarePipeline`, then delegates without a second boot. Handle, per-request
Kernel operation, cleanup, failure, retirement, and shutdown semantics are
unchanged. Generated integration declares `WebApplication::shutdown()` as its
cleanup and never separately declares Kernel cleanup.

See [ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md) and
[generated runtime integration](generated-runtime-integration.md).

## Phase 2G integration

Web Runtime now calls `WebApplication::handle()` exactly once with the converted
request. This does not move scope ownership: Web Application still begins and
ends the sole Kernel operation. The outer adapter maps failures to fixed generic
or eligible trusted responses and owns delivery send/close orchestration only.
It adds no second scope, direct Kernel/Router/HTTP Server call, concrete emitter,
logging, telemetry, or authorization. The Phase 2H fixture now exercises this
path externally and proves zero active operations after each delivery; it does
not make the package a production server. See the
[fixture model](phase-2-runnable-fixture.md).
