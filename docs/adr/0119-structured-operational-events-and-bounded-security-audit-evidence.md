# ADR 0119: Structured Operational Events and Bounded Security Audit Evidence

- Status: Accepted
- Date: 2026-08-28
- Decision owners: Kongono architecture
- Scope: Phase 10E only

## Context

Phase 10B established a neutral observation vocabulary, Phase 10C added
operation-scoped tracing, and Phase 10D added bounded metric aggregation.
Operator-relevant events and security-relevant audit evidence are the next two
signal domains. They share the neutral recorder edge, but they do not share
meaning: an ordinary event cannot stand in for security evidence, and an audit
record cannot grant authority or prove durable retention.

Free-form messages and arbitrary context arrays would make disclosure,
cardinality, and compatibility impossible to bound. A combined event/audit
type would also permit accidental conversion between operational severity and
security outcome. Both domains therefore need explicit schemas, typed finite
values, deterministic observation mapping, and visible recorder failure while
remaining separate sibling packages.

## Decision

Phase 10E implements the separate Experimental `kongono/operational-events`
and `kongono/security-audit` packages. Each depends inward only on PHP, API
Metadata, Diagnostics, and Observability. Neither package depends on or
converts to the other, and no existing production package depends on either.
Together they bring the production graph to exactly forty-nine acyclic
packages and 166 local package edges.

### Finite schemas and disclosure

Each package owns a final immutable field declaration and schema. A field has
one exact Observability attribute key, one scalar value kind, required or
optional presence, one disclosure classification, and one through sixteen
exact allowed values. Allowed values are unique and canonicalized by kind and
value. Event schemas contain zero through thirty unique fields; audit schemas
contain one through twenty-eight.

Validation accepts only declared keys with the exact kind and one declared
value, requires every required field, rejects duplicates and unknown keys, and
returns canonical schema order. Event schemas reserve `event.*`; audit schemas
reserve `audit.*`. Credential, secret, token, session, policy, body, URL,
query, route-parameter, exception, principal, identity, tenant, resource, and
similar sensitive or unbounded families are denied rather than sanitized into
admission.

Record disclosure is derived from fields actually supplied. It is Protected
when at least one supplied field is Protected and Safe otherwise. An omitted
optional Protected field does not elevate the record. Existing disclosure
classification is preserved; neither package declassifies data.

### Ordinary operational events

An operational-event definition owns one exact versioned signal identity, one
of Debug, Info, Notice, Warning, Error, or Critical severity, and one exact
schema. A record owns the caller's explicit Observability context, exact
definition, neutral observation status, canonical validated attributes, and
derived disclosure.

Recording produces exactly one `ObservationKind::Event` observation. It adds
only fixed reserved `event.severity` and `event.disclosure` attributes after
the canonical caller attributes, for a maximum of thirty-two attributes. The
neutral status is caller supplied and is not inferred from severity.

### Security audit evidence

A security-audit definition owns one exact versioned signal identity, one of
Short, Standard, or Extended retention intent, and one exact schema. Evidence
owns the caller's explicit Observability context, exact definition, one of
Succeeded, Denied, or Failed outcome, canonical validated attributes, and
derived disclosure.

Recording produces exactly one `ObservationKind::Audit` observation. Succeeded
maps to neutral Ok; Denied and Failed map to neutral Error. It adds only fixed
reserved `audit.outcome`, `audit.retention_intent`, `audit.disclosure`, and
`audit.durability` attributes. Durability is always the exact Safe text
`not_guaranteed`. Successful recording therefore proves neither persistence
nor retention. The maximum remains thirty-two attributes.

Retention intent is descriptive evidence for a future outer storage policy. It
selects no duration, deletion behavior, legal hold, tamper evidence, signature,
privacy regime, compliance regime, or storage implementation.

### Recorder failure and concurrency

Each runtime retains only one explicit `ObservationRecorder`. Validation
completes before the recorder is called. A valid record causes exactly one
recorder call. Recorder failure stops immediately, is wrapped in the package's
recording exception, preserves exact previous throwable identity for trusted
programmatic inspection, and is never retried. Fixed exception messages,
stringification, and diagnostic context disclose no previous or rejected raw
value.

The runtimes retain no per-call mutable state. Sequential, overlapping, and
recorder-reentrant calls remain isolated. A failure in one call cannot mutate
another call or become success.

## Consequences

- Callers must predeclare every admitted field and finite value domain.
- Ordinary severity, neutral status, audit outcome, and retention intent remain
  explicit and semantically distinct.
- The neutral recorder edge can receive both domains without creating a shared
  logger, provider, conversion API, or authority meaning.
- Recording success means only that the supplied recorder returned normally;
  it makes no delivery, durability, storage, retention, or compliance claim.
- Protected supplied values make the entire record Protected while omitted
  optional fields do not affect disclosure.

## Rejected alternatives

- A generic logger facade was rejected because it would collapse typed signal
  domains into channels, handlers, or message conventions.
- Free-form message-first logs were rejected because they cannot provide a
  finite schema, bounded disclosure, or stable machine meaning.
- One combined event/audit type was rejected because ordinary severity and
  security outcome have different semantics and authority risks.
- Arbitrary context arrays were rejected because keys, values, size, and
  disclosure could grow without proof.
- Hidden buffering was rejected because ownership, memory, loss, and cleanup
  would be implicit.
- Silent best-effort loss was rejected because recorder failure must remain
  visible and cannot be converted to success.
- Process-global providers were rejected because they create ambient state and
  cross-operation coupling.
- Premature storage or compliance claims were rejected because Phase 10E owns
  neither a store nor policy enforcement.

## Non-goals

Phase 10E adds no logger facade, formatter, handler, channel, exporter,
processor, queue, buffer, retry, collector, OTLP transport, backend, durable
store, retention enforcement, deletion, legal hold, tamper-evidence chain,
signature, SIEM integration, incident system, dashboard, alerting, product
analytics, authorization decision, Permit, policy evaluation, HTTP/CGI/Console/
database/persistence/security/application integration, Composition wiring,
Kernel state, deployment behavior, AI authority, Stable API, performance
superiority, compliance, or production-readiness claim.

PHP support remains exactly `>=8.5.0 <8.6.0`. Phase 10F remains the sole
possible next handoff and requires separate authenticated authority.

## Related decisions

- [ADR 0011](0011-opentelemetry-observability-strategy.md)
- [ADR 0114](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115](0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0116](0116-neutral-observability-contracts-and-package-boundary.md)
- [ADR 0117](0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
- [ADR 0118](0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
- [Observability and Operational Evidence guide](../architecture/observability-and-operational-evidence.md)
- [Phase 10 roadmap](../architecture/phase-10-roadmap.md)
