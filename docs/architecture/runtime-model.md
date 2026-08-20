# Runtime Model

## Purpose

Kongono is persistent-runtime-first. Its core lifecycle must be safe when a process handles multiple requests, jobs, commands, messages, workflow steps, or other operations.

## Lifecycle principle

Framework design may not rely on process teardown to clear mutable state or release operation-specific resources. The Phase 0C Kernel makes operation lifetime explicit but deliberately carries no identity, authorization, tenant, request, error, telemetry, or other scoped data. Future state must have explicit ownership and cleanup.

This principle matters even when an application runs under PHP-FPM, because runtime-independent lifecycle contracts prevent assumptions that block long-running adapters later.

The Phase 0F resolver may be retained across operations because it stores only immutable definitions and constructs a new service object on every root and dependency edge. It contains no current-operation state and has no Kernel lifecycle relationship. Consumer constructors remain responsible for their own side effects, cleanup, and concurrency safety; shared and scoped service lifetimes remain open.

## Runtime adapters

Future adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes. No runtime is a Kernel dependency, and Phase 0C selects or implements no adapter.

## Concurrency and isolation

The implemented Kernel permits overlapping operations and owns each opaque scope by object identity in an instance-local collection. Ending one scope cannot affect another. There is no global, static, thread-local, fiber-local, singleton, or other ambient current-operation state. This establishes lifecycle accounting without selecting concurrency primitives, reset hooks, propagation, cancellation, or adapter contracts.

## Failure

Invalid lifecycle actions throw a deliberate Kernel exception before state
mutation. Shutdown fails while active work remains, leaving the Kernel booted
and counts intact.

Every future operation boundary must guarantee scope and resource cleanup when
application work throws. Successful cleanup must not erase or replace the
primary failure, and cleanup failure must not be silently discarded. If cleanup
leaves runtime state uncertain, that worker or runtime instance must not quietly
accept further work. Phase 2F now implements those rules for one Web Application
Kernel scope around an in-memory application call.

The Kernel and `OperationScope` remain unchanged lower layers. Web Application
selects exact-once scope end and `OperationCleanupException` for simultaneous
application and scope-cleanup failure. Runtime adapters, transport and resource
cleanup, cancellation and control signals, recovery, logging, telemetry, and
worker termination remain open.

## HTTP message lifetime

Phase 2C message values retain only explicitly supplied operation data. They
consult no ambient current request, SAPI global, Kernel scope, DI container,
static registry, thread-local, or fiber-local state. Copy-on-change is shallow:
body streams and consumer objects may be shared and remain stateful. Factories
are stateless, and no message or factory is registered globally. Runtime
adapters, request conversion, cleanup coordination, and delivery remain later
phase responsibilities.

## Performance

Persistent processes may reduce repeated boot cost, but performance cannot justify unsafe shared state or runtime coupling. Measurement and adapter-specific optimization belong to later implementation phases.

## Diagnostic-context lifetime

The Diagnostics package implements eager bounded structural context construction.
A
`DiagnosticContext` owns one immutable detached snapshot created synchronously
from a closed schema and explicit values. It consults no global request,
superglobal, container, current user, thread-local, fiber-local, environment,
clock, random source, filesystem, network, database, logger, tracer, or AI
system and will perform no I/O.

Snapshots enter no static registry or global cache, and values cannot accumulate
across operations through this package. Memory and work are bounded by the
16-field maximum plus represented input bytes; stored text is at most 512 bytes.
Phase 0Q Graph exceptions, Phase 0T Kernel lifecycle exceptions, and Phase 0U DI
exceptions eagerly construct and own exactly one context at their deliberate
failure boundaries.
Repeated access returns that same context object; the exception retains no value
map, and context enters no Graph snapshot, Kernel state, operation scope, DI
definition/resolver/service state, static registry, or global accumulation.
Diagnostics construction failure propagates unchanged; simultaneous-failure
representation remains unresolved.

## Trusted diagnostic projection lifetime

Phase 0X implements a pure trusted-developer projection in the leaf
`kongono/diagnostic-presentation`; it implements no runtime adapter. The
projector reads one accepted Kernel, Graph, or DI exception through local values,
calls each source accessor at most once, copies bounded metadata and fields into
an immutable detached snapshot, and retains no exception, context, field, input
list, registry, or cache. Returned lists are detached and preserve canonical
schema order.

Projection performs no I/O and consults no global, superglobal, container,
service locator, thread-local, fiber-local, static, mutable process-wide, or
previous-operation state. It stores no cross-operation value. An accessor-thrown
native `Throwable` propagates unchanged. These rules make projection bounded and
persistent-runtime-safe but do not authorize its caller.

Phase 1A accepts two independent decisions outside Diagnostic Presentation.
Phase 1C implements the first decision value as the Experimental unbacked
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission` enum. A
future server-controlled composition boundary must choose the already-validated,
immutable value when constructing one adapter instance. Raw configuration cannot
mutate that live shared decision; changed bootstrap permission requires explicit
restart or construction of a new adapter instance. The exact raw source,
validation failure, composition API, reload lifecycle, and adapter remain
unselected and unimplemented.

Phase 1D implements the second decision value as the Experimental unbacked
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission` enum. A
future adapter must select this value from its already-evaluated evidence for
the current delivery boundary. The enum evaluates no evidence, proves no
freshness or boundary identity, and cannot authorize presentation by itself.
The decision is not stored in a static, ambient current context or
previous-operation value, and one request, job, message, command, tenant, user,
fiber, worker operation, or concurrent delivery cannot authorize another.
Denial or uncertainty takes the future non-trusted path before presentation
access; it does not project or render and discard the result. The evidence
sources, evaluation mechanism, and outer-adapter composition remain unimplemented.

Phase 1E adds the pure final readonly
`TrustedDeveloperPresentationEligibility` for one current delivery. It stores
only the two typed decisions and returns true only when both permit trusted
presentation. A future adapter must construct and consume it within that
delivery's explicit flow; it must not retain or reuse the object or its result
as ambient, static, cached, thread-local, fiber-local, or previous-operation
authority. Evidence evaluation and outer-adapter composition remain unimplemented.

Phase 1H implements a stateless Diagnostic Handler for this exact per-delivery
sequence:

```text
eligibility is checked before any source accessor
    false -> return null immediately
    true -> project source -> render trusted HTML -> return exact HTML unchanged
```

The handler retains neither the eligibility value, source, projection,
HTML, nor any derived result. It catches nothing, so every `Throwable` propagates
unchanged. `null` means only that trusted developer HTML was not produced because
combined eligibility was false; it does not select a response, status, fallback,
incident, retry, or transport. The future outer adapter still establishes both
permissions, validates evidence, creates the eligibility value, invokes the
handler for one current delivery, and owns the non-trusted path and delivery.
The handler evaluates no evidence and performs no transport or delivery.

The required evaluation order is:

```text
future outer adapter establishes both typed permissions
    -> TrustedDeveloperPresentationEligibility
        -> Diagnostic Handler checks eligibility first
            false -> null without source access
            true -> projector creates detached trusted model
                -> pure HTML renderer creates complete escaped document
                    -> exact HTML unchanged to future adapter
```

An exception may already own its eager bounded context; denial leaves that
exception unchanged while prohibiting later presentation accessor reads and
Protected-field copying. The implemented renderer is stateless, retains nothing
after return, and consults no ambient or runtime state. Calling the projector or
renderer directly is explicit low-level composition, not authorization or a
runtime hook. External evidence evaluation, adapter composition, transport,
delivery, response behavior, and every adapter remain unimplemented.
This sequence implies no Kernel lifecycle, container, DI, HTTP, Application
Graph, configuration, cleanup, logging, or telemetry integration.

## Current and planned Phase 2 web-delivery ownership

Phases 2A and 2B record requirements for future web work; they implement no web
delivery or scoped storage. Phases 2C through 2E add explicit transport-free
message, routing, middleware, and terminal-handler values without an outer
delivery boundary. A future outer adapter must own each inbound
delivery explicitly, convert it into one explicit framework request value, and
open one Kernel operation scope for that delivery. Request, response,
permission, route-match, middleware, exception, principal, tenant, and
application state must remain in explicit call flow rather than ambient
global, static, singleton, container-current, thread-local, fiber-local, or
previous-operation state.

PSR-7 message `with*()` operations use copy-on-change semantics, but this
must not be described as deep immutability. A body stream remains stateful, and
multiple message values may share it. Cursor position, underlying contents,
detach, and close state are therefore observable resource behavior. Message,
stream, URI, upload, server-parameter, cookie, query, parsed-body, and attribute
values must remain within explicit operation call flow and must not be retained
as scoped-storage substitutes.

The HTTP message factories are explicit composition dependencies, not
ambient singletons, registries, or service locators. The Kernel remains
transport-neutral and has no PSR dependency. SAPI conversion, parsing, response
emission, and resource ownership belong to the future outer adapter rather than
the message package or Kernel.

Phase 2D routing follows the same explicit-operation rule. A long-lived
`RouteMatcher` retains only its immutable route table and compiled declaration
data. Each call receives one PSR-7 request explicitly, reads only its method and
URI path, and returns a fresh match or `null`. It retains no request, URI,
method, path, parameters, match, exception, principal, tenant, permission, or
operation state. Matching is bounded, encoded-byte exact, free of I/O and
ambient state, and safe for alternating calls under ordinary PHP value
semantics. A match selects no handler and establishes no authentication or
authorization.

Phase 2E retains the same rule. A long-lived `MiddlewarePipeline` holds only a
detached immutable list of at most 128 consumer-supplied middleware objects and
its compiled PSR-15 handler chain. A long-lived `RoutingRequestHandler` holds
only its immutable matcher, complete canonical route-name handler map, and
unmatched handler. Each request and response remains in explicit call flow.
Selected matches are copied to the request under `RouteMatch::class`, but that
attribute remains untrusted and non-authorizing; unmatched output is delegated.
Consumer middleware owns the lifecycle safety of any objects it captures. No
ambient request cursor or supplied error middleware is implemented. Phase 2F
wraps each accepted application call in one exact local Kernel scope. Phase 2G
adds only the outer delivery adapter and fixed failure responses; concrete
emission remains absent.

Successful and failing Web Application paths close the owned Kernel operation
scope exactly once. Successful cleanup preserves the exact primary failure;
cleanup-only failure propagates unchanged; simultaneous failure preserves both
objects; and uncertain lifecycle failure retires the application object before
later dependency access. This does not select scoped services, reset hooks,
transport cleanup, storage, worker termination, or recovery.

End-of-Phase-2 black-box tests must exercise at least two sequential deliveries
within one process and prove that no request, response, permission, route match,
middleware, exception, or application state leaks between them. Canonical
tests must remain deterministic and require no external network listener.

## Stability

Persistent-runtime-first design, runtime-independent Kernel boundaries, and the
failure-cleanup principles above and the no-ambient diagnostic-capture
rules are **LOCKED**. The minimal
boot/operation/shutdown lifecycle is implemented but **Experimental**. Scoped
data, cleanup APIs and orchestration, propagation, concurrency models beyond
overlapping identity ownership, cancellation, reset behavior, recovery, and
runtime adapters remain open.

## Related concepts

- [Microkernel](microkernel.md)
- [Architecture overview](overview.md)
- [Phase 2 roadmap](phase-2-roadmap.md)
- [Routing model](routing-model.md)
- [HTTP server model](http-server-model.md)
- [Web Application model](web-application-model.md)
- [ADR 0046](../adr/0046-deterministic-route-declaration-and-matching.md)
- [ADR 0047](../adr/0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [ADR 0048](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [ADR 0006](../adr/0006-persistent-runtime-first.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)

## Phase 2 runnable delivery proof

ADR 0049 implements Web Runtime as an Experimental top-level leaf. Phase 2H's
test-owned delivery now proves its complete path: server-owned permission before
request access, one Web Application call, fixed generic or eligible trusted
output, one send, one close, and detached results only after cleanup. The exact
five-operation sequence proves no request, route, response, permission, failure,
or Kernel-operation state leaks in a persistent process.

No runtime server or emitter is selected. The CLI runner is bounded black-box
test infrastructure, not a production runtime or framework CLI. See the
[fixture model](phase-2-runnable-fixture.md).
