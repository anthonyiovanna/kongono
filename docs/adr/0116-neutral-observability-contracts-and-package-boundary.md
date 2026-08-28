# ADR 0116: Neutral Observability Contracts and Package Boundary

## Status

Accepted

## Context

Phase 10A accepted Kongono's observability and operational-evidence doctrine,
structured signal separation, explicit context, nondisclosure and cardinality
bounds, exporter neutrality, and the fixed Phase 10 roadmap. It deliberately
implemented no runtime vocabulary. Phase 10B needs the smallest provider-
neutral values that later, separately authorized runtimes can consume without
coupling lower packages to tracing, metrics, event/audit pipelines, exporters,
OpenTelemetry, applications, or providers.

## Decision

Create the Experimental `kongono/observability` package. It depends only on
`kongono/api-metadata`, `kongono/diagnostics`, and PHP
`>=8.5.0 <8.6.0`. No existing production package depends on it in Phase 10B,
and the production graph becomes exactly forty-five acyclic packages.

The package contains only these contracts:

- `AttributeKey`, using 1-128 ASCII bytes and the exact grammar
  `^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*$`;
- `AttributeValue`, tagged as Boolean, Integer, FloatingPoint, or Text, with
  finite floats and at most 1,024 bytes of valid control-free UTF-8 text;
- `SignalAttribute` and an `AttributeSet` of zero through 32 exact-key-unique
  attributes, returned in binary ordinal key order;
- opaque `CorrelationId`, containing 1-128 bytes of valid control-free UTF-8;
- explicit fresh uncorrelated or exact-object correlated
  `ObservabilityContext`;
- `SignalIdentity`, pairing the attribute-key name grammar with schema version
  1 through 65,535;
- closed Trace, Metric, OperationalEvent, and SecurityAudit kinds;
- closed Unset, Ok, and Error statuses;
- one immutable `Observation` envelope retaining exact supplied objects; and
- `ObservationRecorder`, whose only method records one observation and has no
  framework implementation.

Values preserve exact bytes and native scalar types. They do not trim,
normalize, case-fold, coerce, interpret, serialize, discover, or perform I/O.
Correlation is not identity, a credential, trace identity, authority, or a
uniqueness/security claim. Signal identity versions meaning; it is not a PHP
class, backend name, authorization identity, or OpenTelemetry semantic-
convention assertion.

## Diagnostics and failure

The package owns exactly three Internal diagnostic identities:

- `KGN-OBSERVABILITY-1001` for context values;
- `KGN-OBSERVABILITY-1002` for attributes; and
- `KGN-OBSERVABILITY-1003` for signal identity.

Diagnostic contexts are eager and contain only fixed Safe package, closed
failure-category, and remediation evidence. Rejected input is never retained
or disclosed. A Public package `ExceptionInterface` supplies the existing four
diagnostic accessors. The Internal base exception string form is exactly
`<id>: <title>`. The Experimental concrete exception has only closed factories
for context, attribute, and signal-identity failures.

Recorder implementations are external and define their own error contract.
The neutral package catches nothing and promises no buffering, asynchrony,
delivery, durability, retry, sampling, aggregation, exporter, backend, no-op
behavior, or error swallowing.

## Security, lifecycle, and performance

Final readonly values, copied canonical lists, exact object retention, and no
ambient/static state provide deterministic isolation. The package owns no
operation lifecycle or cleanup; an outer caller owns the supplied recorder and
all later runtime resources. Construction is bounded by 32 attributes, 128-byte
keys and correlation values, and 1,024-byte text values. No performance
superiority or production-readiness claim is made.

Accessors expose caller-supplied bytes but do not classify them Safe for logs,
diagnostics, display, or export. Observations grant no permission, establish no
identity, change no decision, repair no state, and become no business or policy
truth.

## API status

All production declarations are Experimental except the Public package catch
interface and the Internal diagnostic enum, exception base, and validation
helper. No contract is Stable or 1.0.

## Explicit non-goals

Phase 10B adds no trace/span lifecycle, parent, link, event, timestamp, clock,
identifier generation, propagation, sampling, current span, metric instrument,
measurement, aggregation, collection, log/event or audit recording pipeline,
severity, retention, durability, exporter, processor, batching, queue, retry,
flush, shutdown, OpenTelemetry or PSR-3 dependency, OTLP, backend, collector,
provider, environment discovery, auto-configuration, application adapter,
Composition wiring, Kernel state, generated code, command, deployment, UI,
global recorder, service locator, runtime Reflection, scanning, cache, hidden
shutdown behavior, or Phase 10C work.

## Consequences

Later phases can depend inward on an exact neutral vocabulary without forcing a
provider or runtime into lower packages. The narrow surface also means Phase
10B cannot trace an operation, aggregate a metric, persist an audit record, or
export anything. Each capability requires its own authenticated later
directive and explicit lifecycle, failure, disclosure, and dependency proof.

## Related material

- [ADR 0114: Explicit Observability and Operational Evidence Boundary](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115: Structured Signals, Explicit Context, and Exporter Neutrality](0115-structured-signals-context-and-exporter-neutrality.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Phase 10 Roadmap](../architecture/phase-10-roadmap.md)
- [`kongono/observability`](../../packages/observability/README.md)
