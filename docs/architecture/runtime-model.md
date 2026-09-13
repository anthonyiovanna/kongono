# Runtime Model

## Phase 18 presentation lifecycle direction

Phase 18A accepts the future lifecycle boundary in
[ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md)
and the [presentation guide](server-rendered-presentation.md) as documentation
only. Phase 18D accepts [ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the optional exact-current [compiled-view runtime](compiled-view-runtime.md).
Phase 18E accepts the separate stateless [compiled-view HTTP adapter](compiled-view-http.md),
which retains only its two caller-selected PSR factories.
Phase 18F accepts the reusable stateless [compiled-view application workflow](compiled-view-application-workflow.md),
whose run boundary checks and loads exact-current evidence before each
application instance is constructed and never compiles implicitly.

Accepted rendering is synchronous, operation-scoped, deterministic, bounded,
and stateless across calls. Immutable exact-current compiled evidence may be
shared, but input values, traversal state, temporary output, failures, and
cleanup belong to one operation. Sequential and overlapping renders must not
leak state. Template globals, mutable singletons, implicit output buffers,
shutdown hooks, destructor cleanup, background workers, retained request state,
runtime parsing/compilation, and hot reload are prohibited.

Partial output is not success. The runtime releases temporary state on every
path and preserves primary plus cleanup failure truth. It parses or compiles no
source during rendering. Generated code and the deployment filesystem are
trusted inputs. Phase 18A-F are Accepted; Phase 18G-H remain Planned and
separately unauthorized.

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
Phase 3E PHP CGI bootstrap now derives that value from one literal
`CgiOperatorProfile` selected in trusted code after exact native prerequisite
validation under [ADR 0055](../adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md).
`Production` maps to `Denied`; `LoopbackDevelopment` maps to
`Allowed`. No raw string, file, environment, request parser, reload, or watcher
exists. The immutable result retains only the typed permission; changing it
requires explicit construction of a new bootstrap/runtime. Phase 3F now composes
that runtime with one fresh concrete delivery and retains no permission value on
the delivery.

Phase 1D implements the second decision value as the Experimental unbacked
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission` enum. A
Phase 3E's Internal PHP CGI evaluator selects this value from one explicit
current-delivery snapshot. Only exact `cgi-fcgi`, CGI/1.1 RESPONDER, canonical
`127.0.0.1` or `::1`, all-string keys, and absence of every forwarded marker
allow; every other input denies without throwing. The enum itself evaluates no
evidence, proves no freshness or boundary identity, and cannot authorize
presentation by itself.
The decision is not stored in a static, ambient current context or
previous-operation value, and one request, job, message, command, tenant, user,
fiber, worker operation, or concurrent delivery cannot authorize another.
Denial or uncertainty takes Web Runtime's fixed non-trusted path before
presentation access; it does not project or render and discard the result.
Phase 3F's concrete PHP CGI delivery captures fresh SAPI/projected-server
evidence and the thin entry supplies a fresh delivery to an already-composed
runtime.

Phase 1E adds the pure final readonly
`TrustedDeveloperPresentationEligibility` for one current delivery. It stores
only the two typed decisions and returns true only when both permit trusted
presentation. Web Runtime constructs and consumes it within that delivery's
explicit flow; neither it nor another adapter may retain or reuse the object or
its result as ambient, static, cached, thread-local, fiber-local, or
previous-operation authority. PHP CGI now implements the selected evidence
evaluation and outer-delivery composition.

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
incident, retry, or transport. For the selected path, PHP CGI establishes fresh
delivery evidence and Web Runtime combines the supplied permissions, creates
the eligibility value, invokes the handler for one current delivery, and owns
the non-trusted path and delivery. The handler evaluates no evidence and
performs no transport or delivery.

The required evaluation order is:

```text
PHP CGI supplies independent bootstrap and current-delivery permissions
    -> TrustedDeveloperPresentationEligibility
        -> Diagnostic Handler checks eligibility first
            false -> null without source access
            true -> projector creates detached trusted model
                -> pure HTML renderer creates complete escaped document
                    -> exact HTML unchanged to Web Runtime
```

An exception may already own its eager bounded context; denial leaves that
exception unchanged while prohibiting later presentation accessor reads and
Protected-field copying. The implemented renderer is stateless, retains nothing
after return, and consults no ambient or runtime state. Calling the projector or
renderer directly is explicit low-level composition, not authorization or a
runtime hook. The selected PHP CGI path now supplies literal bootstrap and exact
delivery-evidence evaluation, concrete delivery, response emission, and cleanup.
Other adapters, listener/process integration, deployment hardening, and external
acceptance remain unimplemented. This sequence implies no new Kernel lifecycle,
container, DI, Application Graph, general configuration, logging, or telemetry
integration.

## Historical Phase 2 web-delivery ownership and current integration

At the Phase 2A through 2E boundary, the roadmap and transport-free message,
routing, middleware, and terminal-handler values had no outer delivery boundary.
Phase 2G later implemented Web Runtime's explicit delivery ownership, and Phase
3F now supplies the selected concrete PHP CGI delivery. Each integration must
own its inbound delivery explicitly and pass one explicit framework request
value into Web Application's sole Kernel operation scope. Request, response,
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

The HTTP message factories are explicit composition dependencies, not ambient
singletons, registries, or service locators. The Kernel remains
transport-neutral and has no PSR dependency. PHP CGI now owns the selected SAPI
capture, conversion, parsing, response emission, and resource cleanup; any
other integration must keep those concerns outside the message package and
Kernel.

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
adds the outer delivery adapter and fixed failure responses. At the Phase 2
boundary concrete emission was absent; Phase 3D added the emitter and Phase 3F
now calls it from the concrete PHP CGI delivery.

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

## Phase 10C tracing operation lifecycle

`TraceRuntime` is a stateless factory over exact caller-owned clock, identifier,
sampler, and recorder references. Each `begin()` returns one fresh isolated
operation root with no registry, ambient current span, thread/fiber local,
request singleton, service lookup, or Kernel state. Sequential, overlapping,
and recorder-reentrant operations therefore share no runtime-owned mutable
state.

One operation owns its root and at most 64 simultaneously Active children.
Explicit child parents must be exact active Internal span objects registered to
that operation; ID equality alone never transfers ownership. Root completion
requires zero active children. Completion reads one monotonic end tick, becomes
final, releases retained context/identity/attributes and collaborators before
an external recorder call, and stays final when recording fails.

Retirement is idempotent. Active operation retirement retires every active
child, releases operation-local evidence, records nothing, calls no clock,
identifier source, sampler, or recorder, and cannot fail. Completed or retired
handles cannot propagate. This lifecycle adds no Composition wiring,
application integration, shutdown action, retry, buffer, exporter, or Phase
10D metric behavior within Tracing. See [ADR 0117](../adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md).

## Phase 10D metric collection lifecycle

`MetricRuntime` is a stateless factory over one exact caller-owned recorder.
Each `begin()` returns one fresh isolated Active collection retaining the exact
caller context and immutable plan. Record accepts only exact instrument objects
owned by that plan, validates an exact schema dimension set, and immediately
updates bounded aggregate state. No raw measurement log, global meter,
registry, ambient current collection, clock, identifier source, thread/fiber
local, request singleton, service lookup, or Kernel state exists.

Collect transitions Active to Collected once, constructs deterministic metric
observations in canonical plan/series order, then releases all collection
references before the first external recorder call. A recorder failure remains
visible with exact previous identity, is never retried, and leaves the
collection final and empty. Successful collect also remains final. Retirement
is idempotent; Active retirement releases state and records nothing, while
Collected retirement changes only the lifecycle state to Retired. Sequential,
overlapping, and recorder-reentrant collections share no runtime-owned mutable
state. See [ADR 0118](../adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md).

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

## Phase 10E recorder runtime boundary

Operational Events and Security Audit each expose a stateless runtime retaining
only one explicit neutral `ObservationRecorder`. Validation is complete before
any collaborator call; an invalid input records nothing and a valid input calls
the recorder exactly once. Recorder failure stops immediately, preserves the
exact previous throwable for trusted inspection, discloses no previous message,
and is never retried. Sequential, overlapping, and recorder-reentrant calls
share no per-call mutable state. No global provider, registry, buffer, shutdown
hook, destructor, thread/fiber local, store, exporter, application wiring, or
Kernel state is introduced. Phase 10F is the sole possible next handoff. See
[ADR 0119](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md).

ADR 0049 implements Web Runtime as an Experimental top-level leaf. Phase 2H's
test-owned delivery now proves its complete path: server-owned permission before
request access, one Web Application call, fixed generic or eligible trusted
output, one send, one close, and detached results only after cleanup. The exact
five-operation sequence proves no request, route, response, permission, failure,
or Kernel-operation state leaks in a persistent process.

Phase 3F selects no runtime server or listener, but it now implements the
bounded PHP CGI delivery that calls the existing emitter and one thin entry into
an already-composed Web Runtime. All delivery values remain on one fresh object;
capture state clears after conversion, cleanup detaches retained references
before external calls, and no ambient current request is introduced. The CLI
runner remains bounded black-box test infrastructure, not a production runtime
or framework CLI. Phase 3G adds a stateless PHP CGI front controller that owns
one entry attempt followed by one caller-supplied shutdown attempt. It retains
no runtime state, preserves simultaneous failure through 1019, and does not add
a direct PHP-CGI-to-Web-Application edge. Application composition and
partial-boot cleanup remain caller-owned. Uncertain emission or shutdown means
no reuse claim and requires external process retirement. Phase 3H externally
proves sequential reuse and direct retirement with test-owned infrastructure;
no production listener, server configuration, process manager, or deployment
artifact is added. Production trusted diagnostics remain denied, Phase 3 is
closed, and no later runtime work starts automatically. See the [fixture
model](phase-2-runnable-fixture.md).

## Phase 10F bridge runtime boundary

One `OpenTelemetryBridge` instance owns one bounded queue and explicit
Open/Flushing/Closing/Closed lifecycle. Record validates before retaining and
does no transport I/O. Flush and close are caller-invoked synchronous edges;
partial progress removes only successful batches, close continues cleanup after
failure, and all queued references are released before a combined failure is
reported. Reentry fails before mutation and separate instances share no
mutable runtime state. There is no destructor, shutdown hook, registry,
provider, timer, signal, background worker, thread/fiber local, retry, or
application lifetime owner. See
[ADR 0120](../adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md).
