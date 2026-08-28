# ADR 0117: Operation-Scoped Tracing Lifecycle and Typed Propagation

## Status

Accepted

## Context

Phase 10B established a neutral immutable observability vocabulary and an
external recorder capability. Phase 10C needs the smallest tracing runtime that
can describe causal operation structure and monotonic duration without ambient
state, carrier parsing, provider coupling, application integration, or metric,
event, audit, and export behavior.

## Decision

Create the Experimental `kongono/tracing` package. It depends only on
`kongono/api-metadata`, `kongono/diagnostics`, `kongono/observability`, and PHP
`>=8.5.0 <8.6.0`. The production graph becomes exactly forty-six acyclic
packages. No existing production package depends on Tracing.

`TraceId` accepts exactly 32 lowercase ASCII hexadecimal characters and
`SpanId` exactly 16; both reject all-zero values. Identifiers remain opaque and
make no secrecy, randomness, uniqueness, timing-resistance, or W3C-conformance
claim. `TracePropagation` retains exact typed trace/span identifiers and a
Record/Drop decision supplied after caller-owned outer validation. It is not a
carrier, codec, header, baggage value, trust grant, or authority grant.

`TraceRuntime` receives one exact caller-owned monotonic clock, identifier
source, sampler, and `ObservationRecorder`. A new root obtains one trace ID;
an inbound root reuses the exact propagated trace ID. Every root obtains one
new span ID, invokes the sampler exactly once, and reads one nonnegative start
tick. Child spans inherit the trace ID and sampling decision and do not resample.

One operation owns its root and no more than 64 simultaneously Active child
spans. An omitted child parent means the root. An explicit parent must be the
exact Active Internal span object registered to that operation; matching IDs do
not transfer ownership. IDs cannot repeat within an operation, including the
inbound parent and root. The runtime owns no operation registry, current span,
global provider, thread/fiber local, request singleton, or other mutable ambient
state, so sequential, overlapping, and reentrant operations remain isolated.

Root and child callers may supply no more than 25 attributes and may not use
the seven reserved trace keys. For Record, completion emits one
`ObservationKind::Trace` observation containing the exact context, identity,
status, caller attribute objects, IDs, optional parent, sampled `true`, start
and end ticks, and nonnegative duration. A new root omits only parent. For Drop,
no observation is constructed or recorded.

Completion accepts only Ok or Error. It reads and validates one end tick,
becomes final, unregisters or releases retained operation evidence before the
external recorder call, and never retries. Root completion requires zero Active
children. Propagation is rejected after completion or retirement. Retirement
is idempotent; active operation retirement retires all active children, releases
operation-local evidence, records nothing, makes no collaborator call, and
cannot fail.

## Failure and disclosure

The package owns four Internal diagnostic identities: Value
`KGN-TRACING-1001`, Lifecycle `KGN-TRACING-1002`, Collaborator
`KGN-TRACING-1003`, and Recording `KGN-TRACING-1004`. Their eager contexts
contain only fixed Safe package, closed category, and remediation evidence.
Rejected values, IDs, attributes, ticks, class names, exception messages,
traces, paths, carriers, context bytes, and provider data are not retained or
disclosed.

Clock, identifier-source, and sampler throws become collaborator exceptions;
recorder throws become a recording exception. Both preserve the exact previous
throwable identity for programmatic cause inspection. Recording failure remains
visible after final completion. There is no retry, fallback, hidden logger,
swallowing, or default/no-op implementation.

## Security and authority

Typed propagation is trusted input only in the sense that parsing and carrier
validation occurred outside this package. It authenticates and authorizes
nothing. Trace identifiers, sampling decisions, observations, and successful
recording grant no access, identity, policy truth, disclosure, repair, mutation,
or business authority. Existing diagnostic disclosure rules remain unchanged.

## API status

All production declarations are Experimental except the Public package catch
interface; the Internal diagnostic, exception base, and all `Internal`
implementations. No declaration is Stable or 1.0.

## Consequences

Callers can create deterministic bounded trace evidence over explicit
collaborators without coupling lower packages to infrastructure. They remain
responsible for clock monotonicity, identifier uniqueness, sampling policy,
recorder behavior, outer propagation validation, and collaborator concurrency.
The package provides no exporter, durability, losslessness, batching, flush,
performance, compliance, or production-readiness guarantee.

## Explicit non-goals

Phase 10C adds no W3C Trace Context parsing or formatting, baggage, HTTP/header
adapter, OpenTelemetry API/SDK or semantic conventions, OTLP, exporter,
processor, queue, batcher, provider, collector, backend, wall clock, random
source, default sampler, global tracer, ambient current span, span links/events,
metric instrument or aggregation, operational-event/log pipeline, security-
audit pipeline/store, application or Composition integration, Kernel state,
database/persistence/security integration, generated wiring, CLI, configuration,
deployment, retry, async I/O, buffering, durability, flush, shutdown, dashboard,
alerting, profiler/APM, product analytics, or Phase 10D behavior.

## Related material

- [ADR 0011: OpenTelemetry Observability Strategy](0011-opentelemetry-observability-strategy.md)
- [ADR 0114: Explicit Observability and Operational Evidence Boundary](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115: Structured Signals, Explicit Context, and Exporter Neutrality](0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0116: Neutral Observability Contracts and Package Boundary](0116-neutral-observability-contracts-and-package-boundary.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Phase 10 Roadmap](../architecture/phase-10-roadmap.md)
- [`kongono/tracing`](../../packages/tracing/README.md)
