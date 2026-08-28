# ADR 0118: Bounded-Cardinality Metric Aggregation and Explicit Collection Lifecycle

- Status: Accepted
- Date: 2026-08-28
- Decision owners: Kongono architecture
- Scope: Phase 10D only

## Context

Phase 10A accepted the Observability and Operational Evidence architecture.
Phase 10B implemented the neutral `kongono/observability` vocabulary, and Phase
10C implemented operation-scoped tracing. Metrics are the next distinct signal
kind. They need useful aggregation without allowing dynamic labels, retained
measurement logs, ambient providers, or persistent-runtime leakage.

Metric labels can create unbounded memory and disclosure risk when keys or
values are discovered while recording. Process-global registries and implicit
collection finalization also make operation ownership unclear in long-lived
workers. A metric design therefore needs to prove its maximum series count
before measurement, update bounded aggregates immediately, and expose one
explicit terminal lifecycle.

Metrics remain evidence only. They cannot establish identity, grant permission,
define policy or business truth, repair state, hide primary failure, or imply
delivery, durability, export, alerting, or application behavior.

## Decision

Phase 10D implements one Experimental `kongono/metrics` package under
`Kongono\Metrics\`. It is the forty-seventh acyclic production package and
depends inward only on PHP, API Metadata, Diagnostics, and Observability. No
production package depends on Metrics in this phase.

### Exact values and instruments

`MetricNumber` retains one exact finite native integer or float. Native type is
part of equality. `MetricUnit` validates a deliberately small ASCII grammar and
does not claim UCUM or OpenTelemetry conformance.

The exact instrument kinds are Counter, UpDownCounter, Gauge, and Histogram.
Histogram boundaries are one through fifteen exact numbers in strictly
increasing numeric order. Equal mixed numeric types and signed-zero duplicates
are invalid. Histograms require boundaries; other instruments prohibit them.
No asynchronous, callback, observable, summary, timer, exponential histogram,
view, exemplar, quantile, or rate instrument is selected.

### Predeclared dimensions and hard cardinality

Every dimension declares one exact Observability attribute key and one through
eight exact allowed scalar values. Values are unique and canonicalized by kind
and value. A schema contains zero through eight unique canonical dimensions and
has at most 64 potential series. A plan contains one through sixteen uniquely
versioned instruments and has at most 256 potential series in total.

The `metric.*` and `trace.*` namespaces are reserved. Exact and dotted families
for tenant, principal, identity, user, resource, session, credential, secret,
token, CSRF, policy, request body, URL, query, route parameter, and exception
data are denied. No late-bound key or value, arbitrary label map, fallback,
hashing, truncation, redaction-as-admission, eviction, LRU, or probabilistic
cardinality control exists. Cardinality is bounded before recording, not
repaired after growth.

The plan owns exact instrument objects. A separately constructed equivalent
instrument is not admitted. Each measurement must provide exactly the declared
dimension keys and one allowed value for each.

### Deterministic bounded aggregation

Every accepted measurement updates one bounded series aggregate immediately;
no raw measurement list is retained. Series identity is derived only from
canonical dimension order and allowed-value ordinals. It contains no raw label
serialization, hash, object identifier, locale, clock, process identifier,
randomness, or ambient state.

Counter and UpDownCounter retain count and sum. Counter additionally requires a
nonnegative value. Gauge retains count and the last exact value in explicit call
order. Histogram retains count, sum, first-observed exact representation for
numerically equal minimum and maximum, and one count for each fixed inclusive
upper-bound bucket plus the final positive-infinity bucket.

Integer-only addition stays integer and detects overflow before PHP can widen
it. Float participation produces a float. Non-finite sum, count overflow,
bucket overflow, and impossible internal state fail visibly without partial
aggregate mutation. Aggregation never clamps, wraps, evicts, samples, retries,
or silently converts a failed measurement.

### Explicit collection lifecycle

`MetricRuntime` retains only one explicit `ObservationRecorder` and returns a
fresh isolated collection for each `begin()`. A collection transitions exactly
from Active to Collected or from Active to Retired.

`collect()` materializes observations only for populated series in canonical
plan-instrument then series order. It transitions to Collected and releases its
plan, context, recorder, dimension, and aggregate references before the first
external recorder call. An empty collection closes successfully without a call.
Recorder failure stops later calls, leaves the collection Collected, and wraps
the exact previous throwable without disclosure. Earlier recorder calls may
have succeeded; no atomicity, rollback, retry, exactly-once, durability, export,
or losslessness promise exists.

`retire()` is idempotent. From Active it releases all collection-owned state,
records nothing, and calls no collaborator. From Collected or Retired it is a
no-op. Recording or collecting after closure fails visibly, and closed
collections report zero active series.

Sequential, overlapping, and recorder-reentrant collections cannot observe or
mutate one another. Recorder reentry sees the source already closed and may
begin a new independent collection through the same runtime. There is no global
meter, provider, registry, current collection, static buffer, thread/fiber
local, request singleton, WeakMap, destructor, shutdown hook, or process-
teardown dependency.

### Neutral observation mapping

Each populated series emits one `ObservationKind::Metric` record using the exact
collection context and exact instrument `SignalIdentity`, with status Unset.
Attributes contain the canonical dimensions and only applicable reserved keys:
instrument kind, unit, measurement count, sum or last, histogram minimum and
maximum, and consecutive zero-padded fixed-bucket counts. The maximum is thirty
attributes, within Observability's neutral bound of thirty-two.

Timestamps, trace or span identifiers, tenant/principal/resource identities,
exception text, descriptions, exemplars, quantiles, percentiles, rates,
temporality, provider data, raw schemas, boundaries, and encoded payloads are
not emitted.

### Diagnostics and disclosure

Metrics owns five exact diagnostic identities for Value, Schema, Lifecycle,
Aggregation, and Recording failures. Their contexts contain only fixed Safe
package, category, and remediation tokens. Rejected values, keys, dimensions,
identities, aggregates, observations, recorder content, exception content,
paths, providers, and stack arguments cannot enter messages, contexts, or safe
strings. Recorder exceptions preserve exact previous throwable identity for
programmatic inspection without disclosure.

## Consequences

- Maximum aggregate memory is determined by the plan before recording and is
  independent of the number of accepted measurements.
- Callers must explicitly declare every label domain and own collection close
  or retirement.
- Dynamic-label convenience, implicit providers, callback instruments, and
  automatic export are deliberately unavailable.
- Recorder partial failure is visible and terminal for the source collection;
  callers cannot assume atomic delivery.
- Persistent runtimes can release operation-owned metric state deterministically
  without process teardown.

## Rejected alternatives

- A global meter/provider or registry was rejected because it creates ambient
  state, hidden configuration, and cross-operation lifecycle risk.
- Dynamic labels plus eviction, hashing, truncation, or redaction were rejected
  because they do not prove bounded cardinality or disclosure permission.
- Retaining raw measurements was rejected because memory would scale with call
  count rather than the declared series budget.
- Destructor, shutdown, timer, or process-driven collection was rejected because
  persistent runtimes require explicit operation ownership.
- OpenTelemetry SDK types were rejected as a lower-package dependency; it
  remains a future outer interoperability direction.

## Non-goals

Phase 10D adds no operational-event or security-audit pipeline, exporter,
backend, collector, provider, processor, queue, persistence, rollup, retention,
dashboard, alert, SLO product, OpenTelemetry dependency, PSR logging dependency,
HTTP/CGI/Console/database/persistence/security/application integration,
deployment behavior, AI authority, compliance claim, or production-readiness
claim. Phase 10E remains future work and does not begin automatically.

PHP support remains exactly `>=8.5.0 <8.6.0`. This ADR makes no PHP 8.6, PHP 9,
Stable, 1.0, losslessness, durability, compliance, or performance-superiority
claim.

## Related decisions

- [ADR 0011](0011-opentelemetry-observability-strategy.md)
- [ADR 0114](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115](0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0116](0116-neutral-observability-contracts-and-package-boundary.md)
- [ADR 0117](0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
- [Observability and Operational Evidence guide](../architecture/observability-and-operational-evidence.md)
- [Phase 10 roadmap](../architecture/phase-10-roadmap.md)
