# Kongono Observability

`kongono/observability` provides the neutral immutable Phase 10B vocabulary for
explicit correlation, bounded structured scalar attributes, versioned signal
identity, observation kind and status, one observation envelope, and one
recorder capability. The package validates and carries values; it records,
exports, aggregates, propagates, and discovers nothing by itself.

## Dependency direction

The package depends only on `kongono/api-metadata`, `kongono/diagnostics`, and
PHP `>=8.5.0 <8.6.0`. The exact current production consumers are the separate
`kongono/tracing` and `kongono/metrics` packages. This keeps neutral signal
meaning inward of those explicit runtimes and later adapters.

## Use when

Use these contracts when an explicit caller needs to construct a bounded,
provider-neutral observation and pass it to an explicitly supplied
`ObservationRecorder`. `ObservabilityContext` is either explicitly
uncorrelated or carries one exact `CorrelationId`. `SignalIdentity` versions
application-owned signal meaning. `AttributeSet` canonicalizes no more than 32
unique scalar attributes by exact key value.

## Do not use when

Do not treat correlation as identity, a credential, authority, a trace ID, or a
uniqueness guarantee. Do not treat `ObservationKind` as a trace, metric,
operational-event, or security-audit pipeline. Use existing Diagnostics for
actionable failures; observability does not replace diagnostic exceptions or
their disclosure rules.

## Bounds and security

Attribute keys and signal names are exact 1-128-byte lowercase ASCII canonical
names. Attribute values are booleans, integers, finite floats, or at most 1,024
bytes of valid UTF-8 without C0/C1 controls. Correlation values are opaque,
non-empty, valid UTF-8 of at most 128 bytes without C0/C1 controls. No trimming,
case conversion, Unicode normalization, coercion, hashing, parsing, or lookup
occurs.

Invalid values produce package-owned eager diagnostics containing only fixed
Safe package, category, and remediation evidence. Rejected keys, values,
correlation IDs, signal names, versions, floats, arrays, objects, exception
arguments, and stack values never enter messages, contexts, or safe string
forms. Explicit value accessors do not make their bytes safe for disclosure.

## Lifecycle, concurrency, and failure

Values are final readonly objects with no ambient or mutable state. Each
`uncorrelated()` call creates fresh explicit state, and correlated contexts
retain the exact supplied immutable value. The package owns no operation
lifecycle, buffers, cleanup, I/O, clock, randomness, registry, or concurrency
mechanism.

`ObservationRecorder::record()` promises only one explicit call. An external
implementation defines its own failure behavior, and failures propagate under
that implementation's contract. This package supplies no default or no-op
implementation and makes no buffering, delivery, durability, retry, sampling,
aggregation, exporter, or backend promise. Construction and canonical sorting
are bounded by 32 attributes.

## Extension and agent contracts

External packages may implement `ObservationRecorder`; they may not reinterpret
neutral values as authorization or application truth. AI tools may explain or
inspect accepted contracts but gain no recording, provider, network,
application, security, or mutation authority.

All value, enum, envelope, recorder, and concrete invalid-value contracts are
Experimental. The package exception catch interface is Public. Diagnostic and
implementation helpers are Internal.

## Explicitly absent

This neutral package itself owns no trace/span lifecycle, propagation,
sampling, timestamp, metric instrument or aggregation, operational-event or
security-audit pipeline, exporter, OpenTelemetry or PSR-3 dependency, backend,
provider, application integration, Composition wiring, Kernel state, global
recorder, service locator, Reflection discovery, serialization hook, or debug
output. Tracing and Metrics consume the vocabulary without changing that
boundary.

Phase 14G's outer model-invocation inspection package may construct one inert
bounded Observation from this vocabulary. It supplies no recorder and performs
no recording, export, buffering, delivery, persistence, or live monitoring.

See [ADR 0116](../../docs/adr/0116-neutral-observability-contracts-and-package-boundary.md)
and the [Observability and Operational Evidence guide](../../docs/architecture/observability-and-operational-evidence.md).
