# Kongono OpenTelemetry

`kongono/opentelemetry` is the Experimental outer interoperability bridge from
Kongono's neutral `Observation` evidence to the locked Phase 10F
`kongono-otlp-http-protobuf` profile. It builds directly generated OTLP
protobuf requests and hands their bytes to one caller-supplied transport.

## Use when

Use this package when an upper composition owner has explicit resource,
batching, time-anchor, clock, and OTLP HTTP transport capabilities and must
export bounded Kongono trace, metric, operational-event, or security-audit
observations. Call `record()` to validate and enqueue evidence, `flush()` to
synchronously send it, and `close()` to drain and release the bridge.

## Do not use when

This package is not an OpenTelemetry SDK, provider, global registry,
auto-instrumentation system, HTTP client, collector, backend, retry service,
durable audit store, or application workflow. It does not construct endpoints,
headers, TLS, credentials, or deployment configuration. Successful transport
return is not collector receipt, backend persistence, durability, compliance,
or operation success.

## Profile and mapping

Profile version 1 uses `application/x-protobuf`, generated protocol classes
from `open-telemetry/gen-otlp-protobuf` 1.10.0, stable semantic conventions from
`open-telemetry/sem-conv` 1.38.0, and only `/v1/traces`, `/v1/metrics`, and
`/v1/logs`. Resource detection is prohibited; admitted resource attributes are
explicit and bounded.

Trace observations map to internal spans with checked explicit timestamps and
exact decoded IDs. Counter and UpDownCounter map to cumulative sums, Gauge maps
to a gauge, and each point uses its explicit enqueue epoch. Because Kongono's
neutral Histogram observation carries aggregate counts but not numeric bucket
boundaries, profile version 1 emits a lossless deterministic gauge family for
count, sum, min, max, and ordinal bucket counts. It does not claim a native
OTLP Histogram or fabricate boundaries or quantiles.

Operational events and security-audit evidence use distinct log scopes. Audit
export preserves `audit.durability=not_guaranteed`; it never converts evidence
into authority or durable compliance proof.

## Lifecycle, backpressure, and failure

The queue is bounded and FIFO. Recording performs no transport I/O. A full
queue fails visibly without dropping, blocking, expanding, spilling, retrying,
or implicitly flushing. Explicit flush sends maximally sized contiguous
same-signal batches and removes only successfully sent records. Close attempts
drain, transport flush, and transport close, releases retained observations,
then reports any combined failure. Reentry and invalid lifecycle transitions
fail before mutation; separate bridge instances share no mutable state.

Diagnostics disclose only fixed bounded categories and never raw evidence,
identifiers, payloads, transport/provider text, endpoints, credentials, paths,
or native failure details. Exact previous throwables remain available only for
trusted programmatic inspection.

## Stability

The bridge, values, capability interfaces, signal enum, lifecycle state, and
exception marker are Experimental. Concrete exceptions, diagnostics, queue
records, validation, mappers, and request construction are Internal. Nothing in
this package is Stable.

See [ADR 0120](../../docs/adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md)
and the [Observability and Operational Evidence guide](../../docs/architecture/observability-and-operational-evidence.md).

Phase 10H's root-owned external harness parses captured profile payloads with
the pinned generated protobuf classes and proves the bounded queue,
backpressure, failure, flush, close, cleanup, and signal-separation contracts.
Its capturing transport is test-only and local; the proof adds no HTTP client,
network export, provider, backend, collector, deployment, durability, or Stable
API. See the
[Phase 10 external acceptance guide](../../docs/architecture/phase-10-external-acceptance.md).
