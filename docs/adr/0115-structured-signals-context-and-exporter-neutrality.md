# ADR 0115: Structured Signals, Explicit Context, and Exporter Neutrality

## Status

Accepted

## Context

ADR 0114 accepts Phase 10 as the Observability and Operational Evidence
milestone while preserving ADR 0011's byte-identical Accepted OpenTelemetry
direction. A usable future runtime still requires explicit rules for signal
shape, operation context, disclosure, cardinality, lifecycle, concurrency,
export failure, and deterministic build evidence before any API or package is
selected.

Kongono is persistent-runtime-first. Ambient providers, mutable context bags,
unbounded labels, retained operation buffers, and hidden shutdown export would
leak state and authority between operations. Instrumentation also cannot be
allowed to hide the failure it was meant to explain.

## Decision

### Structured signals and versioned meaning

Framework-owned signals are typed structured records, not preformatted log
strings or arbitrary mutable context bags. Signal names, kinds, statuses,
severity, attribute keys, value domains, limits, and schema versions are
explicit and machine-checkable. Free-form text is bounded presentation evidence
and never the only semantic contract.

Unknown, malformed, oversized, unsupported, stale, mixed-version, or
unverifiable evidence fails visibly at its owning boundary. Signal schemas and
semantic-convention mappings are versioned. No exporter or backend may silently
reinterpret a Kongono concept.

### Explicit operation context

Correlation and trace state is immutable, explicit, and scoped to one
operation. It is never a global logger, static tracer, ambient current span,
thread or fiber local, service-located provider, request singleton, or hidden
container value. An outer adapter may validate inbound propagation evidence and
construct a Kongono context, but untrusted headers, request attributes,
environment variables, and process globals cannot manufacture trusted context.

Context moves inward through explicit typed collaborators or generated
Composition wiring and is fully released during cleanup. Sequential,
overlapping, and reentrant operations cannot observe each other's context or
buffers. Kernel remains observability-neutral and owns no logger, tracer, meter,
exporter, clock, identifier generator, or current signal state.

Wall and monotonic clocks, identifiers, randomness, and sampling decisions come
from explicit typed collaborators at the appropriate outer boundary. Trace and
correlation identifiers are opaque bounded values, not secrets or authority.
Sampling is explicit policy: an unsampled operation is not an unobservable
failure, authorization success, or permission to disclose more. Runtime times
and identifiers cannot contaminate deterministic content-addressed build
artifacts or accepted source identities. Kongono makes no unproved timing-
resistance, global-uniqueness, losslessness, or deterministic-runtime-timing
claim.

### Disclosure and cardinality

Existing Safe, Protected, and Internal diagnostic classification and trusted
presentation doctrine remain in force. Observability declassifies nothing.
Credentials, secrets, raw session or CSRF evidence, authorization policy
bodies, database values, request bodies, private paths, stack arguments,
personal data, and provider payloads are denied by default.

Attribute inclusion is schema-owned and allowlisted. Arbitrary application
arrays, exception traces, headers, environment variables, and object dumps are
prohibited. Metric dimensions and repeated attributes require explicit count,
key, value-size, and cardinality budgets before recording or aggregation.
Tenant, principal, resource, route parameter, exception text, URL query, and
other unbounded values cannot become metric labels. Redaction is not permission
to collect; purpose, disclosure class, retention, and export policy remain
explicit and separable.

### Lifecycle, concurrency, export, and failure

Recording and export have explicit ownership and bounded memory. Every success,
failure, denial, cancellation, and cleanup path retires operation-local context
and buffers. Exporters are outer adapters and may batch only under explicit
limits, flush and close contracts, and concurrency ownership.

Exporter slowness, backpressure, timeout, partial failure, and unavailability
remain visible as bounded operational evidence. They cannot silently widen
memory, block indefinitely, corrupt the primary result, or convert a failed
protected operation into success. Whether a deployment fails open or closed for
a particular audit or export obligation is a future explicit upper-policy
decision; Phase 10A selects none.

Diagnostics explain telemetry failures; telemetry does not replace diagnostic
exceptions or bounded context. Instrumentation failure never hides the primary
exception, and cleanup failure composition follows accepted lifecycle doctrine.
Security audit evidence remains distinct from ordinary operational events: it
is bounded, structured, nondisclosing, and never a replayable grant or
credential store. Audit storage, durability, retention, tamper evidence,
privacy, compliance, incident response, and legal policy remain future upper-
layer decisions. Dropped required audit evidence cannot be reported as a
successful durable audit, and Phase 10A promises no durability.

### Implementation and evidence limits

Future implementation should prefer final readonly typed values, enums for
closed domains, explicit collaborators, generated ordinary PHP where build-time
wiring helps, and bounded deterministic algorithms. Stringly global facades,
magic channel discovery, runtime Reflection or scanning, dynamic proxies,
property-hook service resolution, ambient containers, mutable global
registries, `eval`, hidden shutdown hooks, and environment-driven automatic
configuration are rejected.

Hot-path allocation, propagation, aggregation, batching, and backpressure need
named measurable budgets and benchmarks before performance claims. No zero-
overhead, free, lossless, fastest, competitor-superiority, or production-
readiness claim is accepted without evidence. Developer-facing failures must
identify the exact signal category, bounded likely cause, and safe next action
without exposing rejected values.

## Consequences

- Later runtime work has explicit security, lifecycle, and failure invariants
  without a prematurely fixed public API.
- Operation isolation and explicit cleanup apply equally to recorded and
  unrecorded operations.
- Export concerns stay outside neutral contracts and lower production packages.
- Audit, diagnostics, receipts, operational events, and analytics retain
  distinct truth and retention responsibilities.
- All future implementation starts Experimental unless separately promoted.

## Rejected alternatives

- Preformatted strings or arbitrary mutable bags as the framework signal model.
- Ambient context, process-global providers, automatic environment discovery,
  unbounded labels, or hidden shutdown export.
- Collecting first and relying on redaction to create permission later.
- Treating exporter success as application success or exporter failure as
  permission to conceal primary behavior.
- Reporting best-effort loss as durable audit success.

## Non-goals

This decision creates no signal type, schema, diagnostic identifier, recorder,
span, metric instrument, aggregator, event or audit pipeline, exporter,
propagator, sampler, processor, backend, storage, command, package, dependency,
application workflow, or deployment policy. Phase 10B is only the possible next
handoff after architect review.

## Related material

- [ADR 0114: Explicit Observability and Operational Evidence Boundary](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0028: Bounded Diagnostic Context and Disclosure](0028-bounded-diagnostic-context-and-disclosure.md)
- [ADR 0106: Fail-Closed Security Context and Policy Separation](0106-fail-closed-security-context-and-policy-separation.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Phase 10 roadmap](../architecture/phase-10-roadmap.md)
