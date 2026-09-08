# Observability and Operational Evidence

## Phase 14G model-invocation relationship

The implemented [neutral contract foundation](model-invocation-contract-foundation.md)
under the Accepted [provider-neutral model-invocation architecture](provider-neutral-model-invocation.md)
keeps diagnostics, observability, Security Audit, invocation receipts, and
usage/cost evidence distinct. All are bounded claims and none grants authority,
proves billing truth, or makes provider content safe. Credentials, secrets, raw
prompts and responses, stream fragments, tool arguments, hidden reasoning,
provider payloads, and arbitrary caller content are denied from operational
evidence by default. Phase 14B adds no exporter, recorder, provider telemetry,
live inspection, serialization, billing truth, or runtime integration.

Phase 14G's optional inspection package can project one neutral
`kongono.model-invocation.inspection` version 1 Observation and one fixed
Security Audit evidence value from an already validated inspection. Both use
closed Safe aggregate attributes and remain inert. The projectors call no
recorder or Security Audit runtime and perform no export, buffering, sampling,
flush, retry, persistence, timestamping, or telemetry delivery.

## Phase 13G Agent Principal relationship

Phase 13C adds no Agent Principal instrumentation or runtime integration.
Declaration artifact bytes and digests are deterministic evidence but are not
automatically emitted as signals, audit records, or reusable authority.
Future receipts, traces, metrics, operational events, and security audit records
remain bounded evidence rather than identity, authorization, capability,
business truth, or perfect real-world truth. Agent identity grants no signal
visibility, and signal presence or absence grants no Agent Principal authority.

Any later Agent Principal evidence must preserve existing disclosure,
cardinality, provenance, retention-intent, lifecycle, and failure boundaries.
It may not claim durable audit, compliance, non-repudiation, or complete
observability without separately implemented and proven behavior. See
[ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md).

Phase 13G's optional Agent Inspection package can project one neutral
`kongono.agent.inspection` version 1 operational Observation and one fixed
`kongono.agent.inspection.security` version 1 Security Audit evidence value.
Both contain closed Safe aggregate/token attributes and remain inert. The
package calls no recorder or Security Audit runtime and performs no export,
buffer, sampling, flush, retry, persistence, or timestamping. Projection
success does not establish authentication, authorization, capability, external
effect, delivery, durability, retention duration, compliance, non-repudiation,
tamper evidence, or application security.

## Purpose and current status

Observability is bounded evidence about what an operation or framework
component did. It helps developers and operators understand behavior without
becoming application authority, policy truth, business state, or a replacement
for Kongono diagnostics.

Phase 10A accepts this architecture through
[ADR 0114](../adr/0114-explicit-observability-and-operational-evidence-boundary.md)
and [ADR 0115](../adr/0115-structured-signals-context-and-exporter-neutrality.md).
Phase 10B accepts
[ADR 0116](../adr/0116-neutral-observability-contracts-and-package-boundary.md)
and implements the separate Experimental `kongono/observability` neutral
contract package. Phase 10C accepts
[ADR 0117](../adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
and implements the separate Experimental `kongono/tracing` runtime. Phase 10D
accepts [ADR 0118](../adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
and implements the separate Experimental `kongono/metrics` runtime. Phase 10E
accepts [ADR 0119](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md)
and implements separate Experimental Operational Events and Security Audit
sibling packages. The production graph is exactly forty-nine acyclic packages
and 166 local edges. There is still no generic log pipeline, exporter, backend,
store, command, OpenTelemetry dependency, or application integration.

Use this guide to reason about current neutral contracts and bounded signal
runtimes. Do not route business decisions, authorization, identity, analytics,
or application data through telemetry.

## Current Phase 10B neutral contracts

`AttributeKey` and `SignalIdentity` use exact 1-128-byte lowercase ASCII names.
`AttributeValue` supports exact booleans, integers, finite floating-point
values, and at most 1,024 bytes of valid UTF-8 text without C0/C1 controls.
`AttributeSet` accepts zero through 32 unique keys and returns exact supplied
attribute objects in binary ordinal key order.

`CorrelationId` is an opaque 1-128-byte valid control-free UTF-8 value.
`ObservabilityContext` is explicitly uncorrelated or correlated with that exact
immutable object; it contains no trace/span, sampling, attributes, security, or
ambient lookup state. `Observation` retains an explicit context, versioned
identity, closed kind and status, and attribute set. Kind selection creates no
trace, metric, operational-event, or security-audit behavior.

`ObservationRecorder` is one interface with one `record()` method. The package
supplies no implementation and promises no buffering, delivery, durability,
retry, aggregation, exporter, backend, no-op, or swallowed failure. Package-
owned diagnostics contain only fixed Safe category and remediation evidence;
rejected values are never retained or disclosed.

## Current Phase 10C tracing runtime

`TraceId` accepts exactly 32 lowercase ASCII hexadecimal characters and
`SpanId` exactly 16; both reject all-zero values. They are opaque correlation
values without secrecy, randomness, uniqueness, timing-resistance, or W3C-
conformance claims. `TracePropagation` retains exact typed IDs and a Record/Drop
decision after caller-owned outer validation. It parses and formats no carrier,
header, `traceparent`, or baggage and elevates no trust or authority.

`TraceRuntime` receives an explicit caller-owned monotonic nanosecond clock,
identifier source, sampler, and `ObservationRecorder`. A root obtains one new
trace ID unless exact typed inbound propagation supplies it, one new span ID,
one sampling decision, and one start tick. Children inherit the trace ID and
decision, use the root or an exact active same-operation child as parent, and do
not resample. Parent ownership is exact object identity, not ID equality.

Each operation permits no more than 64 simultaneously Active child spans and
rejects every duplicate span ID, including its inbound parent and root. Root
and child callers may provide no more than 25 attributes and may not use the
seven reserved `trace.*` keys. For Record, Ok/Error completion creates exactly
one Trace observation containing the exact context, identity, status, caller
attribute objects, IDs, optional parent, sampled `true`, start/end ticks, and
nonnegative duration. New roots omit only parent. Drop constructs and records
no observation.

Completion releases retained lifecycle evidence before the external recorder
call and remains final if recording fails. Root completion requires no Active
children. Propagation from completed or retired handles is rejected. Retirement
is idempotent; active operation retirement retires all active children,
releases operation-local references, records nothing, calls no collaborator,
and cannot fail. Sequential, overlapping, and reentrant operations share no
runtime-owned mutable state.

Tracing owns four closed non-disclosing failure categories for values,
lifecycle, collaborators, and recording. Collaborator and recorder wrappers
preserve exact previous throwable identity for programmatic inspection, while
messages, contexts, and safe strings expose no rejected IDs, attributes, ticks,
exception content, carriers, paths, or provider data. There is no retry,
fallback, hidden logger, global tracer/provider, ambient current span,
thread/fiber local, or production collaborator implementation.

## Current Phase 10D metrics runtime

Metrics defines exact finite `MetricNumber` values, bounded ASCII units,
schema-owned dimensions, one-to-fifteen strictly increasing histogram
boundaries, and Counter, UpDownCounter, Gauge, and Histogram instruments. A
dimension permits one through eight canonical allowed values; one schema has
zero through eight dimensions and at most 64 Cartesian series. A canonical
plan contains one through sixteen unique instrument identities and at most 256
potential series. Reserved, sensitive, or unbounded dimension families are
rejected rather than normalized, hashed, truncated, redacted, evicted, or
sampled into admission.

`MetricRuntime` starts one fresh Active collection for an exact context and
plan. Recording requires exact instrument object ownership and exact schema
dimensions. Counter values are nonnegative; UpDownCounter, Gauge, and
Histogram accept any finite metric number. Aggregation is immediate: counters
and up/down counters retain count and exact int-or-float sum, gauges retain the
last exact value, and histograms retain count, sum, first-observed min/max, and
fixed inclusive-upper-bound bucket counts. Numeric and bucket overflow fails
before partial mutation. Raw measurements are never retained.

Collect creates canonical Metric observations from populated series in plan
and series order. Each uses the exact caller context and instrument identity,
caller dimension objects, and only the applicable fixed `metrics.*` aggregate
keys, never more than 30 attributes. The collection becomes Collected and
releases every retained runtime reference before the first recorder call. A
recorder failure is final, visible, preserves the exact previous throwable,
and is never retried. Retirement is idempotent, and fresh sequential,
overlapping, and recorder-reentrant collections share no runtime-owned mutable
state.

## Keep evidence domains separate

These domains can correlate, but they do not share meaning or authority:

- **Diagnostics** explain actionable failures through existing identifiers,
  bounded context, disclosure classes, and trusted presentation rules.
- **Traces** describe causal operation structure and timing evidence. A trace
  identifier is correlation, not identity or permission.
- **Metrics** describe bounded numeric measurements and aggregates. Dimensions
  are controlled schema, not arbitrary application labels.
- **Operational events or logs** describe structured occurrences for operators.
  They are not preformatted strings as a primary semantic contract.
- **Security audit evidence** records bounded security-relevant facts and needs
  a stricter explicit retention and integrity policy than ordinary events.
- **Authorization receipts** remain non-authoritative evidence for one exact
  authorization evaluation and cannot replay a Permit.
- **Product analytics** remain application-owned and are not framework
  telemetry.

No signal grants permission, changes a decision, establishes identity, repairs
state, suppresses a primary failure, or silently becomes a second source of
truth. A successful export does not make the observed operation successful.

## Structured-first signal meaning

Future framework signals must be typed structured records. Names, kinds,
statuses, severity, attribute keys, value domains, count and size limits, and
schema versions are explicit and machine-checkable. Bounded free-form text may
support presentation, but it cannot be the sole meaning of a record.

Unknown, malformed, oversized, unsupported, stale, mixed-version, and
unverifiable evidence fails visibly at the boundary that owns it. Signal
schemas and semantic-convention mappings are versioned. An exporter or backend
cannot silently reinterpret a Kongono concept.

This direction favors final readonly values, enums for closed vocabularies,
explicit collaborators, ordinary generated PHP where deterministic wiring is
useful, and bounded algorithms. It rejects stringly global logging facades,
magic channel discovery, runtime Reflection or scanning, dynamic proxies,
property-hook service resolution, ambient containers, mutable global
registries, `eval`, hidden shutdown hooks, and automatic environment-driven
behavior.

## Explicit operation context

Correlation and trace state is immutable, explicit, and scoped to one
operation. An outer adapter may validate inbound propagation evidence and
construct a context. Untrusted headers, request attributes, environment
variables, or process globals cannot manufacture trusted context.

Context moves inward through explicit typed collaborators or generated
Composition wiring. It is fully released with operation cleanup. Sequential,
overlapping, and reentrant operations cannot observe each other's context or
buffers. There is no global logger, static tracer, ambient current span, thread
or fiber local, service-located provider, request singleton, or hidden container
lookup.

Kernel remains observability-neutral. It owns no logger, tracer, meter,
exporter, clock, identifier generator, or current signal state. Composition
remains the explicit construction and lifecycle owner.

## Time, identifiers, sampling, and deterministic evidence

Wall and monotonic clocks, trace and span identifiers, randomness, and sampling
decisions come from explicit typed collaborators at an appropriate outer
boundary. Identifiers are opaque bounded correlation values. They are neither
secrets nor authority.

Sampling is explicit policy. Unsampled does not mean that a failure cannot be
diagnosed, that authorization succeeded, or that more data may be disclosed.
Runtime timestamps and identifiers cannot contaminate deterministic build/check
artifacts or accepted source identities. Timing resistance, global uniqueness,
losslessness, and deterministic runtime timing require separate accepted proof;
Phase 10C claims none.

## Disclosure, privacy, and cardinality

Existing Safe, Protected, and Internal diagnostic classification and trusted
projection doctrine continue to apply. Observability does not declassify a
value. Credentials, secrets, raw session or CSRF evidence, policy bodies,
database values, request bodies, private paths, stack arguments, personal data,
and provider payloads are denied by default.

Every included attribute is schema-owned and allowlisted. Arbitrary application
arrays, exception traces, headers, environment variables, and object dumps are
prohibited. Repeated attributes and metric dimensions require explicit budgets
for count, key, value size, and cardinality before recording or aggregation.
Tenant, principal, resource, route parameter, exception text, URL query, and
other unbounded values cannot become metric labels.

Redaction is not permission to collect. Collection purpose, disclosure class,
retention, and export policy are explicit and separable. Security Context and
authorization govern access to sensitive operational evidence. Application
Agent Principals gain no visibility merely because they are agents.

## Lifecycle, concurrency, and backpressure

Recording and export have explicit owners and bounded memory. Persistent
runtimes retire operation-local context and buffers after success, failure,
denial, cancellation, and cleanup. Exporters are outer adapters. They may batch
only with explicit limits, flush and close contracts, and concurrency ownership.

Slowness, backpressure, timeout, partial failure, and unavailability must remain
visible as bounded operational evidence. They cannot silently widen memory,
block indefinitely, corrupt a primary result, or convert a failed protected
operation into success. Whether a deployment fails open or closed for a
particular audit or export obligation is an explicit future upper-policy
decision, not a Phase 10A default.

Instrumentation failures do not hide primary exceptions. Existing combined-
failure and reverse-cleanup doctrine remains authoritative. Diagnostics explain
telemetry failures; telemetry does not replace diagnostic exceptions or
context.

## Security audit truth

Security audit evidence is separate from ordinary operational events. It is
bounded, structured, nondisclosing, and never a credential store, policy source,
authorization decision, or replayable grant. Exact audit storage, durability,
retention, tamper evidence, privacy, compliance, incident response, and legal
policy remain future upper-layer decisions.

Dropping required audit evidence cannot be reported as a successful durable
audit. Phase 10A creates no durability promise and selects no fail-open or fail-
closed deployment policy for audit/export obligations.

## OpenTelemetry interoperability and dependencies

OpenTelemetry is the selected external interoperability direction, consistent
with the byte-identical Accepted strategic direction in
[ADR 0011](../adr/0011-opentelemetry-observability-strategy.md). Kongono-neutral
contracts remain independent of OpenTelemetry SDKs, exporters, global
providers, resource discovery, environment configuration, and backend vendors.

A later outer bridge may map exact supported Kongono schemas to exact pinned
OpenTelemetry API and semantic-convention versions. Phase 10D selects no SDK
package, Composer dependency, OTLP transport, collector, backend, endpoint,
resource detector, propagator, environment variable, sampling algorithm, retry
policy, or deployment configuration.

Neutral observability contracts depend inward on accepted foundation packages
and PHP. Signal runtimes may depend inward on those contracts. HTTP, CGI,
Console, database, persistence, security, and application adapters may depend
inward on them. Lower packages never depend outward on adapters or exporters.
Runtime implementation begins Experimental; export adapters, codecs,
propagation parsers, batching, backend mappings, and host details remain
Internal unless explicitly promoted. Public schemas and mappings create
compatibility and privacy obligations and cannot become Stable accidentally.

## Developer and agent contract

A developer-facing failure identifies the exact signal category, a bounded
likely cause, and a safe next action without exposing rejected values. Chie may
explain accepted observability doctrine, Hantei may independently verify
deterministic evidence, and Oshie may teach it. They cannot emit production
telemetry, change sampling, repair exporters, certify their own output, or
become authority.

Kongono remains buildable, testable, operable, and diagnosable without an AI
provider, observability backend, account, collector, or network.

## Performance and stability truth

Hot-path allocation, propagation, aggregation, batching, and backpressure need
measurable budgets and named reproducible benchmarks before performance claims.
No zero-overhead, free, lossless, fastest, competitor-superiority, or
production-readiness claim is accepted without evidence.

The active PHP compatibility window remains `>=8.5.0 <8.6.0`. Phase 10E makes
no PHP 8.6, PHP 9, prerelease, future-minor, LTS, Stable, 1.0, compliance,
certification, or production-readiness claim.

## Explicit non-implementation

Phase 10E adds the separate bounded Operational Events and Security Audit
sibling runtimes to the existing neutral contracts, tracing runtime, and
metrics runtime. It provides no PSR-3 logger,
OpenTelemetry SDK/API integration, W3C Trace Context parser or formatter,
baggage, carrier/header adapter, span links/events, global meter/provider,
asynchronous or observable instrument, dynamic label, generic log pipeline,
conversion between events and audit evidence, log processor, exporter, OTLP,
collector, backend, dashboard, alert, audit store, retention enforcement,
tamper evidence, incident system, profiler, APM, application workflow, service
discovery, deployment configuration, compliance claim, or framework product
analytics. Implemented finite schemas derive disclosure from supplied fields;
events retain severity and neutral status, while audit evidence retains outcome,
retention intent, and explicit `not_guaranteed` durability. Each valid call
records one bounded observation, and recorder failure is visible and unretried.

The [Phase 10 roadmap](phase-10-roadmap.md) fixes order and boundaries only.
Phase 10F is the sole possible next handoff after architect review; it is not
implemented or pre-authorized by this guide. See
[ADR 0119](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md).

## Phase 10F selected interoperability profile

Phase 10F implements one outer `kongono/opentelemetry` bridge under
[ADR 0120](../adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md).
It directly constructs parseable generated OTLP protobuf requests for explicit
trace, metric, operational-event, and security-audit observations. The sole
profile uses `application/x-protobuf`, the fixed `/v1/traces`, `/v1/metrics`,
and `/v1/logs` suffixes, scope version `10F-profile-1`, and stable 1.38.0 schema
evidence. Resource input, enqueue clock, time anchor, batch bounds, and transport
are explicit; the bridge performs no resource or environment discovery.

Recording validates before a bounded FIFO append and performs no I/O. Explicit
flush uses maximal contiguous same-signal batches, removes only successful
batches, retains failed/current/later evidence, calls transport flush once, and
never retries. Close attempts drain, flush, and close, releases references, and
reports combined failure after reaching Closed. Reentry fails before mutation;
separate bridges share no mutable state.

Histogram aggregates cannot be a native OTLP Histogram because the neutral
evidence has no numeric bucket boundaries. Profile version 1 therefore emits a
lossless count/sum/min/max/ordinal-bucket gauge family with a fixed mapping
attribute and fabricates no boundary or quantile. Events and audits use distinct
log scopes; export does not create audit durability or authority.

No SDK, provider, global registry, auto-configuration, HTTP client, endpoint,
headers, TLS, credentials, retry, collector/backend, application workflow,
dashboard, alerting, or Stable API is implemented. Phase 10G remains separately
authorized future work.

## Phase 10G supported application workflow

Phase 10G accepts [ADR 0121](../adr/0121-deterministic-observability-configuration-and-supported-application-workflow.md)
and implements deterministic closed configuration/currentness evidence plus one
explicit application workflow. The selected resource and batch values are
immutable; endpoint, credentials, provider, transport, clocks, identifiers, and
sampling remain caller-owned. Operations coordinate tracing and metrics while
keeping operational events and security audit evidence distinct, then flush and
close explicitly with preserved failure identity and bounded state release.

The copy-once skeleton performs zero network access. Read-only inspect/check
commands expose only bounded canonical identities and booleans. The graph is 52
acyclic packages and 182 local edges. Dashboard, alerting, collector deployment,
profiling, and autonomous tuning remain unimplemented.

## Phase 10H external acceptance and checkpoint

Phase 10H accepts
[ADR 0122](../adr/0122-phase-10-external-observability-acceptance-and-checkpoint.md)
and adds only root-test-owned external proof and the exact local Phase 10
checkpoint. `composer acceptance:phase10` uses fresh child processes and a
unique temporary copied application to prove exact-current build/check/run,
typed signal separation, bounded lifecycle and backpressure, parseable pinned
protobuf payloads, nondisclosure, compatibility, and complete cleanup. Its
capturing transport is local test evidence and performs no network export.

The harness does not widen production authority or promise production
readiness, certified observability or security, complete OpenTelemetry support,
universal portability, zero overhead, durable audit storage, autonomous
operations, or Phase 11 behavior. Kongono remains pre-1.0. See the
[Phase 10 external acceptance guide](phase-10-external-acceptance.md).

Phase 14H constructs only pure model-invocation Observation and Security Audit
values. It performs no recording, sampling, export, delivery, persistence,
backend access, monitoring, or live-state claim. See [Phase 14 external
acceptance](phase-14-external-acceptance.md).

## Phase 15A egress evidence boundary

Phase 15A accepts documentation architecture only for [governed outbound HTTP
and network egress](outbound-http-and-network-egress.md). Planned operational
evidence may contain allowlisted aggregate lifecycle and correlation facts, and
Security Audit may receive bounded decisions without request or response
payloads. Neither channel authorizes an origin, connection, redirect, retry, or
other external effect, and neither proves delivery or live endpoint state.
URLs, queries, credentials, headers, cookies, bodies, private addresses, proxy
secrets, certificates, paths, and foreign exception text remain excluded.
[ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md)
adds no exporter, transport, recording, network call, or Phase 15B behavior.
