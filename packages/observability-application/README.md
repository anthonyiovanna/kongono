# Kongono Observability Application

`kongono/observability-application` provides the Experimental supported
application workflow over one exact current observability configuration and the
accepted tracing, metrics, operational-event, security-audit, and OpenTelemetry
runtime boundaries.

`ApplicationObservabilityFactory` receives one checked immutable configuration
plus caller-owned trace clock, identifier source, sampler, OpenTelemetry time
anchor and clock, and OTLP HTTP transport. It directly constructs one bounded
bridge and one of each signal runtime. It performs no discovery, environment
lookup, service location, Reflection, provider registration, or hidden
defaulting.

Each `execute()` reserves one of sixty-four operation slots before invoking any
caller collaborator, begins tracing before metrics with the plan's exact
context, and invokes the typed work object exactly once. Operational events and
security-audit evidence remain separate and must carry that same context object.
Successful or failed work is followed by metric collection, trace completion,
and synchronous bridge flush. Handles are retired and wrapper references are
released on every terminal path.

Work failure is rethrown by exact identity when cleanup succeeds. When cleanup
fails, `ApplicationObservabilityCleanupException` retains the optional exact
primary and every exact cleanup throwable in metric, trace, then export order.
Its message and diagnostic context disclose none of the underlying signal,
configuration, transport, payload, identifier, or exception values.

`flush()` is explicit and accepts only an open application with no active
operations. `close()` rejects active operations, closes the bridge exactly once,
and is final and idempotent even when transport cleanup fails. There is no
destructor, shutdown hook, retry loop, worker, timer, background drain, global
provider, ambient current operation, backend, collector, endpoint, credential,
dashboard, alerting, profiling, or durability promise.

Phase 10H externally exercises this workflow with root-test-owned clocks,
identifiers, sampler, work, and a local capturing transport. It proves typed
signal separation, operation isolation, failure ordering, explicit flush and
terminal close, protobuf parseability, and retained-state cleanup without
adding production collaborators or widening this package's authority. See the
[Phase 10 external acceptance guide](../../docs/architecture/phase-10-external-acceptance.md).
