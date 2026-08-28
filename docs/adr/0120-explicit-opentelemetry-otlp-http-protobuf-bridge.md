# ADR 0120: Explicit OpenTelemetry OTLP HTTP Protobuf Bridge

- Status: Accepted
- Date: 2026-08-28
- Decision semantics: LOCKED profile and boundaries; Experimental public API

## Context

ADRs 0011, 0114, and 0115 select OpenTelemetry as Kongono's outer
interoperability direction while keeping neutral observation contracts free of
SDK, provider, exporter, transport, collector, and backend authority. Phases
10B through 10E now produce bounded typed observations, but no implemented
adapter turns that evidence into a standard wire request.

The adapter must preserve Kongono's explicit lifecycle, nondisclosure, bounded
memory, and package-direction rules. It must not infer configuration from
process state, promote correlation to trace identity, confuse operational
events with security-audit evidence, or claim delivery guarantees that an HTTP
transport cannot prove.

## Decision

Create the Experimental `kongono/opentelemetry` outer package. It depends only
on PHP, API Metadata, Diagnostics, Observability, generated OTLP protobuf
classes 1.10.0, and stable semantic-convention constants 1.38.0. Earlier
production packages do not depend on it.

The sole supported profile is LOCKED as follows:

```text
name: kongono-otlp-http-protobuf
profile version: 1
content type: application/x-protobuf
traces: /v1/traces
metrics: /v1/metrics
logs: /v1/logs
scope version: 10F-profile-1
schema: stable OpenTelemetry 1.38.0 schema URL
```

The bridge constructs generated protobuf request messages directly and passes
serialized bytes to one explicit `OtlpHttpTransport`. The transport receives
the signal, fixed endpoint suffix, fixed content type, payload bytes, and
bounded timeout. Endpoint construction, headers, TLS, credentials, HTTP client,
retry, collector, backend, and deployment remain caller-owned.

## Mapping contract

Every request contains one explicit resource and one signal-specific
instrumentation scope. Resource attributes are limited to explicit service
name, optional service version, optional deployment environment name, and fixed
Kongono/PHP telemetry SDK identity. No runtime resource detector is used.

Trace observations become internal spans with exact decoded lowercase-hex
trace/span/optional parent IDs, checked explicit time-anchor conversion,
exact status, and no inferred parent, event, link, exception, stack, or ambient
context.

Counter and UpDownCounter observations become cumulative OTLP sums; Gauge
becomes an OTLP gauge. Point time is the explicit enqueue epoch. Kongono's
neutral Histogram evidence includes aggregate count, sum, min, max, and ordinal
bucket counts but intentionally omits numeric bucket boundaries. Profile
version 1 therefore emits a deterministic OTLP Gauge family:

```text
<identity>.count
<identity>.sum
<identity>.min
<identity>.max
<identity>.bucket.00.count ... <identity>.bucket.NN.count
```

Each member carries `kongono.metric.mapping=histogram_aggregate_v1`. This is a
lossless representation of the admitted neutral evidence, not a native OTLP
Histogram. The bridge must not fabricate boundaries, quantiles, temporality,
or raw measurements.

Operational events and security-audit evidence become OTLP LogRecords in the
distinct `kongono.operational-events` and `kongono.security-audit` scopes.
Audit outcome controls its closed severity mapping and
`audit.durability=not_guaranteed` remains structured evidence. Export never
becomes authorization, identity, durable audit, tamper evidence, retention
enforcement, or compliance proof.

## Queue and lifecycle contract

One bridge owns one bounded FIFO queue. `record()` fully validates and maps an
observation and explicit enqueue timestamp before retaining it and performs no
transport I/O. Backpressure is visible and never drops, blocks, sleeps,
expands, spills, samples, retries, or implicitly flushes.

`flush()` synchronously sends maximal contiguous same-signal batches within the
configured bound. A successful batch is removed exactly once; a failed batch
and all later records remain. Transport flush occurs exactly once after all
batches, including an empty flush. There is no automatic retry.

`close()` is idempotent after Closed. From Open it attempts drain, transport
flush, and transport close even when an earlier cleanup step fails. It releases
all queued references and becomes Closed before reporting a combined failure.
Overlapping or reentrant lifecycle calls fail before mutation. No destructor,
shutdown hook, global owner, timer, signal, fiber, thread, or background worker
owns cleanup.

## Failure and disclosure

The package owns diagnostic identities `KGN-OTEL-1001` through
`KGN-OTEL-1005` for configuration, mapping, backpressure, export, and lifecycle
failures. Messages and stringification contain only fixed diagnostic identity
and title. Context is bounded to fixed safe/protected category, signal, queue,
stage, and remediation values. Raw attributes, IDs, resource values, protobuf
bytes, endpoints, provider text, native messages/traces, credentials, headers,
bodies, paths, and environment state are never disclosed. Exact previous
throwables are retained only for trusted programmatic inspection.

## Consequences

Kongono gains a deterministic parseable OTLP HTTP/protobuf bridge without an
SDK, provider, global registry, auto-configuration, ambient context, HTTP
client, or backend dependency. The topology becomes exactly 50 acyclic local
packages and 169 local production edges.

Callers must explicitly own transport behavior, endpoint construction,
credentials, network policy, collector/backend configuration, retry decisions,
application composition, startup/shutdown order, and operational guarantees.
Successful return proves only that the supplied transport returned
successfully.

Phase 10F adds no supported application workflow, configuration artifact,
currentness evidence, skeleton, inspect/check command, collector deployment,
dashboard, alerting, profiler, or autonomous tuning. Those boundaries remain
outside this decision. Phase 10G requires separately authenticated architect
authority.
