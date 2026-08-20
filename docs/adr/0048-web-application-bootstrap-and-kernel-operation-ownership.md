# ADR 0048: Web Application Bootstrap and Kernel Operation Ownership

## Status

Accepted

## Date

2026-08-20

## Context

Routing and HTTP Server provide deterministic route selection and an explicit
PSR-15 pipeline, while the transport-neutral Kernel owns boot, shutdown, and
opaque operation scopes. Kongono needs one composition boundary between those
layers before an outer adapter can convert or deliver HTTP messages. Persistent
runtimes also require exact scope closure and fail-closed handling when cleanup
or lifecycle state becomes uncertain.

## Decision

Create the twelfth package, `kongono/web-application`, at
`packages/web-application` under `Kongono\Web\Application\`. It depends exactly
on API Metadata, Diagnostics, HTTP Server, Kernel, Routing, PSR-7 message
interfaces, and the PSR-15 handler and middleware interfaces. No existing
package depends on Web Application, so the graph remains acyclic.

`WebApplication::boot()` receives an explicit Kernel, route table, raw complete
handler map, unmatched handler, and ordered middleware. It constructs
`RoutingRequestHandler`, then `MiddlewarePipeline`, then boots the Kernel once.
This validates composition before Kernel mutation and invokes no application
work. There is no DI resolver, Graph traversal, discovery, tag, priority,
autowiring, service lookup, or framework substrate. Kernel and pipeline getters
are deliberately absent.

`WebApplication` is a PSR-15 request handler with exactly `boot()`, `handle()`,
`shutdown()`, and `isRetired()`. Every accepted `handle()` creates one Kernel
scope, retains it only in the local frame, invokes the pipeline once, and closes
that exact scope once. Sequential and reentrant calls may overlap; no ambient
current request or scope exists.

The observable failure matrix is:

| Work result | Scope end result | Observable result | Retired |
|---|---|---|---|
| response | success | exact response | no |
| primary `Throwable` | success | exact primary `Throwable` | no |
| response | cleanup `Throwable` | exact cleanup `Throwable` | yes |
| primary `Throwable` | cleanup `Throwable` | `OperationCleanupException` with both identities | yes |

Cleanup is never retried. The composite retains the exact primary and cleanup
objects and uses the primary as its native previous exception. Its package-owned
diagnostic surface exposes neither failure. Known Kernel `LifecycleException`
rejection during begin or shutdown remains non-retiring because the Kernel
contract guarantees rejection before mutation; another failure retires the
application because state is uncertain.

Retirement is irreversible fail-closed refusal by one application object. Later
handling and shutdown fail before dependency access. It is not process
termination, recovery, response mapping, recording, logging, or telemetry.
Shutdown is explicit, with no destructor or automatic cleanup.

The package owns separate Public exception-marker recovery semantics and two
fixed, empty-schema diagnostics: `KGN-WEB-APPLICATION-1001` for retired refusal
and `KGN-WEB-APPLICATION-1002` for simultaneous work and cleanup failure.

Phase 2F owns only application-call Kernel-scope cleanup. Phase 2G owns outer
request conversion, delivery evidence, response delivery, generic and trusted
failure delivery, and adapter-owned transport cleanup. The adapter must invoke
this boundary and must not open a second Kernel scope for the same call. Body
streams, uploads, cancellation, broader resources, incidents, logging, and
telemetry remain unselected. Phase 2H remains the first runnable milestone.

## Security considerations

The application retains no request, response, match, scope, or failure after a
call. Package diagnostics contain only fixed safe prose and empty contexts.
Raw failure objects are coordination data for a later trusted outer boundary,
never production presentation. This phase evaluates no delivery evidence and
does not integrate trusted diagnostic presentation or generic failure mapping.

## Performance implications

Composition has only the existing bounded route and middleware construction
cost. Each handle adds constant Kernel begin/end calls and local references.
There is no I/O, retained per-request cache, synchronization primitive, or
ambient registry.

## Rejected alternatives

- Putting PSR or web behavior in Kernel.
- Using Symfony, Laravel, a third-party dispatcher, or a runtime server as the foundation.
- Service location, autowiring, DI resolution, Graph traversal, tags, priorities, or ambient discovery.
- Booting before composition validation.
- Depending on process teardown or a destructor for scope cleanup.
- A global, static, or current request or operation scope.
- Swallowing or replacing the primary or cleanup failure.
- Retrying failed cleanup or continuing to serve after uncertain cleanup.
- Exposing raw failure text through package diagnostics.
- Selecting an adapter, emitter, error middleware, HTTP error response, or trusted diagnostic path in Phase 2F.
- Adding scoped services, cancellation, broader resource cleanup, logging, or telemetry.

## Consequences

Kongono has an explicit transport-free application boundary. Phase 2H's
test-owned fixture now proves it through the real outer delivery path: one
application call owns exactly one Kernel operation and returns to zero active
operations before the detached result is observed. Web Runtime and the fixture
open no second scope. See the [Web Application model](../architecture/web-application-model.md),
[fixture model](../architecture/phase-2-runnable-fixture.md), and
[package README](../../packages/web-application/README.md).

This proof does not make Web Application a server, emitter, authorization
boundary, or production-ready runtime. Those integration decisions remain open.
