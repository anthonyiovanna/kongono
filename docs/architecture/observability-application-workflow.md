# Observability Application Workflow

## Purpose and status

Phase 10G implements one supported, explicit workflow for building and checking
observability configuration and coordinating the accepted signal runtimes. The
mechanism is Experimental. It is not a backend, provider, collector, deployment
manager, dashboard, alerting system, or production-readiness claim.

## Build before startup

An application owns exactly one `kongono.observability.json` file. The closed
version-1 source contains only the locked interoperability profile, three
bounded resource fields, and three bounded batching fields. It cannot contain
an endpoint, URL, header, credential, token, provider, transport class,
sampler, signal schema, audit policy, include, extension, or environment lookup.

`ObservabilityBuildWorkflow::inspect()` derives canonical identities and
whether publication is required without writing. `build()` atomically publishes
the one content-addressed artifact, rereads it, and proves exact currentness.
`check()` admits only that exact ordinary contained artifact and is write-free.
Publication is idempotent for identical bytes and never overwrites conflicting
bytes. Source, output, and artifact paths reject escapes, links, junctions,
reparse points, alternate streams, case aliases, and directory substitution.

The returned `CurrentObservabilityConfiguration` exposes immutable typed
`OpenTelemetryResource` and `OpenTelemetryBatchPolicy` values plus bounded
identities. It performs no I/O and never reloads disk state. Changing source or
implementation requires another build/check/start cycle.

## Explicit construction

The application factory requires current configuration and explicit
caller-owned `TraceClock`, `TraceIdentifierSource`, `TraceSampler`,
`OpenTelemetryTimeAnchor`, `OpenTelemetryClock`, and `OtlpHttpTransport`
instances. It constructs one `OpenTelemetryBridge` and one runtime for tracing,
metrics, operational events, and security audit. All signals reach the same
bounded bridge, but their types and semantics remain distinct.

There is no global tracer or meter, ambient current operation, environment
configuration, Reflection discovery, container lookup, hidden default, or
automatic instrumentation.

## One operation

`ApplicationObservabilityPlan` carries one exact context, root trace identity
and attributes, metric plan, and optional typed propagation. Typed
`ApplicationObservabilityWork<TResult>` receives the exact operation once.

Execution reserves one of 64 active slots, begins trace then metric state, and
invokes work. Successful completion collects metrics, completes the trace with
`Ok`, flushes the bridge, releases operation state, and returns the exact result.
A primary failure follows the same cleanup with trace status `Error` and is
rethrown by identity when cleanup succeeds. Cleanup continues after failures;
combined failure retains the exact primary and ordered cleanup throwables
without disclosing their text.

Events must carry the operation's exact context and use
`OperationalEventRuntime`. Audit evidence must carry the same context and use
`SecurityAuditRuntime`; it is never routed as an event. Partial begin retires
only created handles in reverse order. Completion is final and no retry,
fallback, swallowing, or success conversion occurs.

## Flush, close, and persistent runtimes

Public `flush()` is allowed only while open with zero active operations.
`close()` refuses active operations, closes the bridge exactly once, and becomes
terminal even when transport cleanup fails. A successful or failed close is
idempotent. Execute and flush after close fail visibly. No destructor, shutdown
hook, timer, signal handler, worker, watcher, or background drain participates.

## CLI inspection

`kongo observability:inspect` calls only `inspect()` and reports the artifact
basename and SHA-256, source SHA-256, fixed profile, and publication-required
boolean. `kongo observability:check` calls only `check()` and reports the same
identities plus `current=true`. Both commands require only `application-root`
and `output-directory`, are non-interactive and read-only, and share neutral
human/machine presentation.

Neither command builds, publishes, creates a directory, repairs evidence,
scans for alternatives, runs a process, accesses a network, or reveals paths,
resource/batch values, source bytes, payloads, endpoints, credentials,
providers, or exception messages.

## Copy-once example

`skeletons/observability-minimal` demonstrates local dependency installation,
explicit build/check/run, one metric/event/audit operation, flush, and close.
Its clocks, identifiers, sampler, and capturing transport are application-owned
examples and must be replaced for production. The transport stores only bounded
payloads for one run and performs zero network access. Generated artifacts and
`vendor` are derived and ignored.

## Security and failure

Configuration grants no authority. Observability declassifies nothing. Fixed
package diagnostics disclose only bounded Safe labels and permitted canonical
identities. They never contain source or resource values, rejected keys,
paths, trace/correlation identifiers, signal data, payloads, endpoints,
credentials, environment state, native messages, traces, or arguments.

See [ADR 0121](../adr/0121-deterministic-observability-configuration-and-supported-application-workflow.md),
the [observability guide](observability-and-operational-evidence.md), and the
[Phase 10 roadmap](phase-10-roadmap.md).

Phase 10H externally proves this workflow through
`composer acceptance:phase10`: a fresh copied application installs from exact
local package mirrors, builds/checks/runs/repeats without network export,
rejects stale or tampered evidence, exercises read-only inspect/check output,
parses emitted selected-profile protobuf messages, and removes all owned state.
The proof is test-owned and adds no production provider, endpoint, credential,
backend, collector, retry, deployment, or ambient lifecycle behavior. See the
[Phase 10 external acceptance guide](phase-10-external-acceptance.md).
