# Kongono Metrics

## Status

`kongono/metrics` is Experimental. Phase 10D implements this package as
Kongono's bounded, provider-neutral metric aggregation boundary. It depends
only on API Metadata, Diagnostics, and Observability. It is the forty-seventh
production package and no production package currently depends on it.

## Purpose and context

Metrics describe bounded numeric evidence. They do not establish identity,
authorization, policy truth, business truth, durability, alert state, product
analytics, or application behavior. Use this package when an outer caller can
declare the complete instrument and dimension domains before recording begins
and can supply an explicit neutral `ObservationRecorder`.

Do not use it for traces, diagnostics, operational events, security audit
evidence, arbitrary labels, unbounded identities, asynchronous callbacks,
export, storage, dashboards, or alerting. Those concerns remain separate and,
where applicable, future work.

## Schema and cardinality contract

`MetricNumber` retains one exact finite `int|float`. `MetricUnit` accepts one
non-empty ASCII token of at most 63 bytes using only letters, digits, `%`, `*`,
`.`, `_`, `/`, `{`, `}`, and `-`; it does not claim UCUM or OpenTelemetry unit
validation.

The four exact instrument kinds are Counter, UpDownCounter, Gauge, and
Histogram. A Histogram has one through fifteen strictly increasing fixed
boundaries; other instruments cannot carry boundaries. A dimension declares
one exact Observability `AttributeKey` and one through eight exact allowed
values. Dimension values and keys are canonicalized deterministically.

Sensitive or unbounded identity families, including tenant, principal,
identity, user, resource, session, credential, secret, token, CSRF, policy,
request body, URL, query, route parameter, and exception data, cannot become
dimensions. The `metric.*` and `trace.*` namespaces are reserved. Redaction,
hashing, truncation, fallback values, and eviction do not make a value
admissible.

Each instrument has at most eight dimensions and at most 64 potential series.
A plan has one through sixteen exact instrument objects and at most 256
potential series in total. Instrument identity is name plus numeric schema
version. A collection accepts only the exact instrument object owned by its
plan; an equivalent separately constructed object is foreign.

## Recording and aggregation

Recording is synchronous and valid only while the collection is Active.
Dimensions must contain exactly the declared keys and allowed values. Counters
accept only nonnegative finite values; the other three kinds accept any finite
metric number.

Every accepted measurement updates its series aggregate immediately. No raw
measurement list is retained. Counter and UpDownCounter keep count and sum;
Gauge keeps count and the last exact call-ordered value; Histogram keeps count,
sum, first-observed representation for equal extrema, and one count per fixed
bucket. Integer addition remains integer and detects overflow before PHP can
widen it. Float, count, and bucket overflow fail visibly without partial
mutation. Aggregation never clamps, samples, evicts, or silently discards.

## Collection lifecycle and failure

`MetricRuntime::begin()` returns a fresh isolated collection. Its only
transitions are Active to Collected or Active to Retired. `collect()` builds
observations for populated series in canonical instrument and series order,
closes and releases collection-owned references, then invokes the explicit
recorder once per observation. Empty collection succeeds without recorder
calls.

Recorder failure leaves the source Collected, stops before later observations,
and preserves the exact cause in `MetricRecordingException`. Earlier calls may
have succeeded. There is no atomicity, rollback, retry, exactly-once, export,
or durability promise. `retire()` is idempotent, calls no collaborator, records
nothing, and releases all retained plan, context, dimension, and aggregate
state. Record or collect after closure fails visibly.

Sequential, overlapping, and recorder-reentrant collections share only the
immutable runtime's recorder reference. The source is already closed before
recorder reentry, and a new collection can complete independently. There is no
global meter, registry, provider, current collection, thread or fiber local,
request singleton, destructor cleanup, shutdown hook, clock, randomness, I/O,
background work, or process teardown dependency.

## Observation contract

Each populated series produces one neutral `ObservationKind::Metric` with the
exact collection context and instrument identity, `ObservationStatus::Unset`,
canonical dimensions, and only applicable `metric.*` aggregate attributes.
Histograms emit consecutive zero-padded fixed-bucket counts. At most thirty
attributes can be emitted. Timestamps, trace identifiers, exemplars, schemas,
boundaries, rates, quantiles, provider fields, arbitrary payloads, and sensitive
identity data are absent.

## Stability and extension contract

All ordinary package declarations are Experimental. Internal aggregation and
diagnostic machinery is not an extension surface. The package defines no
provider, exporter, backend, application integration, OpenTelemetry mapping,
service registration, dependency discovery, or production-readiness claim.
PHP support is exactly `>=8.5.0 <8.6.0`; PHP 8.6, PHP 9, Stable, 1.0,
compliance, losslessness, and performance-superiority claims are not made.

See [ADR 0118](../../docs/adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
and the [Observability and Operational Evidence guide](../../docs/architecture/observability-and-operational-evidence.md).
