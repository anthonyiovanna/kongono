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
accept further work. These are locked principles, not an implemented mechanism.

The exact cleanup mechanism, runtime adapter, cancellation and control-signal
types, recovery API, telemetry behavior, and representation of simultaneous
primary and cleanup failures remain open. No cleanup orchestration exists in the
current Kernel or `OperationScope` implementation.

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
- [ADR 0006](../adr/0006-persistent-runtime-first.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
