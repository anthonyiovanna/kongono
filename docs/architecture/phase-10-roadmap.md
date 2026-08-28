# Phase 10 Roadmap

## Status

Accepted sequencing for the Observability and Operational Evidence milestone.
Phase 10A architecture, Phase 10B neutral contracts, Phase 10C tracing, and
Phase 10D metrics, Phase 10E structured operational events and bounded
security audit evidence, and Phase 10F selected OpenTelemetry interoperability
and Phase 10G deterministic configuration/application integration are
implemented. The production graph is exactly 52 acyclic packages and 182 local
edges.

## Fixed sequence

| Phase | Fixed boundary |
|---|---|
| `10A` | Accept Observability and Operational Evidence doctrine, structured-signal separation, operation-context, disclosure/cardinality/lifecycle invariants, OpenTelemetry interoperability direction, and the fixed A-H roadmap. Documentation only. |
| `10B` | Implement minimal neutral immutable observability-context, attribute/value, signal-identity, status, and recording contracts. No tracing runtime, metric aggregation, log/audit pipeline, exporter, OpenTelemetry dependency, or application integration. |
| `10C` | Implement operation-scoped tracing lifecycle and explicit validated propagation over caller-supplied clock, identifier, and sampling collaborators, with cleanup and isolation. No metric runtime, log/audit pipeline, exporter, backend, or ambient current span. |
| `10D` | Implement bounded-cardinality metric instruments, measurements, deterministic aggregation, and explicit collection lifecycles. No exporter/backend, dynamic labels, process-global registry, application workflow, or alerting system. |
| `10E` | Implement structured operational-event recording and distinct bounded security-audit evidence with disclosure, retention-intent, and failure semantics. No durable audit store, incident product, product analytics, exporter, or authorization authority. |
| `10F` | Implement one explicitly selected OpenTelemetry interoperability bridge and supported export profile at the outer adapter boundary, with exact version mapping, backpressure, failure, flush, and cleanup behavior. No second profile/backend, auto-configuration, environment discovery, deployment manager, or lower-package SDK coupling. |
| `10G` | Implement one supported application observability workflow, deterministic configuration/build/currentness evidence, a minimal copy-once observability skeleton, and narrowly bounded inspect/check tooling. No dashboard, alert manager, collector deployment, autonomous tuning, profiler, or broad operations platform. |
| `10H` | Externally prove signal separation, propagation and lifecycle isolation, nondisclosure/cardinality bounds, exporter failure behavior, audit distinction, selected-profile interoperability, regressions, documentation, and the exact Phase 10 checkpoint. |

## Sequence contract

The sequence fixes order and boundaries only. Every later letter requires a new
independently authenticated architect directive. A correction suffix repairs
only its named boundary and does not create or reorder a roadmap letter.

Phase 10H remains separately authorized external acceptance work. This roadmap
does not pre-authorize or claim any Phase 10H behavior.

## Related material

- [ADR 0114: Explicit Observability and Operational Evidence Boundary](../adr/0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115: Structured Signals, Explicit Context, and Exporter Neutrality](../adr/0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0116: Neutral Observability Contracts and Package Boundary](../adr/0116-neutral-observability-contracts-and-package-boundary.md)
- [ADR 0117: Operation-Scoped Tracing Lifecycle and Typed Propagation](../adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
- [ADR 0118: Bounded-Cardinality Metric Aggregation and Explicit Collection Lifecycle](../adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
- [ADR 0119: Structured Operational Events and Bounded Security Audit Evidence](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md)
- [ADR 0120: Explicit OpenTelemetry OTLP HTTP Protobuf Bridge](../adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
- [ADR 0121: Deterministic Observability Configuration and Supported Application Workflow](../adr/0121-deterministic-observability-configuration-and-supported-application-workflow.md)
- [Observability Application Workflow](observability-application-workflow.md)
- [ADR 0122: Phase 10 External Observability Acceptance and Checkpoint](../adr/0122-phase-10-external-observability-acceptance-and-checkpoint.md)
- [Phase 10 External Acceptance](phase-10-external-acceptance.md)

Phase 10G is implemented through the two explicit outer packages, copy-once
skeleton, and two read-only commands fixed above. Phase 10H adds only root-owned
external acceptance and the exact local checkpoint. Phase 10A through 10H are
complete at that checkpoint. The production graph remains 52 acyclic packages
and 182 local edges; Phase 11 is not authorized or implied.
