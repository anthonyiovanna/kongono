# ADR 0114: Explicit Observability and Operational Evidence Boundary

## Status

Accepted

## Context

The exact Phase 9 checkpoint completes Kongono's Identity and Access Security
milestone with forty-four acyclic production packages. Kongono already has
bounded diagnostics, operation lifecycles, generated Composition, explicit
security context, non-authoritative authorization receipts, and external
acceptance evidence. It does not yet have an observability runtime.

ADR 0011 remains byte-identical Accepted strategic direction for native
OpenTelemetry-oriented observability. Phase 10 needs a more precise modern
boundary before any signal contract, recorder, exporter, dependency, or
application integration is designed. Operational evidence must improve
explanation and operation without becoming application authority, policy truth,
business state, or a second diagnostic system.

## Decision

Phase 10 is the Observability and Operational Evidence milestone. Phase 10A
accepts the [Observability and Operational Evidence guide](../architecture/observability-and-operational-evidence.md)
and the fixed [Phase 10 roadmap](../architecture/phase-10-roadmap.md) as
documentation architecture only. It adds no package, PHP, dependency, schema,
API, command, recorder, exporter, backend, store, or runtime integration. The
production graph remains exactly forty-four acyclic packages.

Observability is bounded evidence about what an operation or framework
component did. Diagnostics, traces, metrics, operational events, security audit
evidence, authorization receipts, and product analytics remain semantically
separate:

- diagnostics explain actionable failures under the existing disclosure
  contract;
- traces describe causal operation structure and timing evidence;
- metrics describe bounded numeric measurements and aggregates;
- operational events describe structured occurrences for operators;
- security audit evidence records bounded security-relevant facts under a
  stricter retention and integrity policy;
- authorization receipts remain non-authoritative evidence of one decision;
  and
- product analytics remain application-owned rather than framework telemetry.

No signal may establish identity, grant permission, change an authorization
result, define policy, repair application state, suppress a primary failure, or
silently become business truth. Security Context and authorization remain the
only accepted route to sensitive operational evidence; an Agent Principal gains
no visibility merely by being an agent.

OpenTelemetry is the selected external interoperability direction, consistent
with ADR 0011. Kongono-neutral concepts remain independent of OpenTelemetry
SDKs, exporters, global providers, resource discovery, environment
configuration, and backend vendors. A later outer adapter may map exact
supported Kongono schemas to exact pinned OpenTelemetry API and semantic-
convention versions. Lower packages never depend on an exporter or proprietary
backend. Phase 10A selects no SDK, Composer package, OTLP transport, collector,
backend, endpoint, resource detector, propagator, environment variable,
sampling algorithm, retry policy, or deployment configuration.

Neutral observability contracts must depend only inward on accepted foundation
packages and PHP. Signal-specific runtimes may depend inward on neutral
contracts. HTTP, CGI, Console, database, persistence, security, and application
adapters may later depend inward on those contracts; lower packages never
depend outward on adapters or exporters. Composition remains construction and
lifecycle owner. New runtime contracts begin Experimental, while exporters,
codecs, propagation parsers, batching, backend mappings, and host details remain
Internal unless separately promoted. Signal schemas and semantic mappings
cannot become Stable accidentally because they carry compatibility and privacy
obligations.

Kongono must remain buildable, testable, operable, and diagnosable without an
AI provider, observability backend, account, collector, or network. Chie may
explain accepted doctrine, Hantei may verify deterministic evidence, and Oshie
may teach it; none gains production signal, sampling, exporter-repair, or
self-certification authority.

## Fixed Phase 10 sequence

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

The sequence fixes order and boundaries only. Every later letter requires a new
independently authenticated architect directive. A correction suffix repairs
only its named boundary and neither creates nor reorders a roadmap letter.
Phase 10B is only the possible next handoff after architect review.

## Consequences

- Phase 10 has an Accepted architectural boundary without a signal runtime.
- Existing diagnostics, security decisions, receipts, policy evidence, and
  application-owned analytics retain their meanings and authority boundaries.
- Future signals must be structured, explicit, operation-scoped, bounded,
  nondisclosing, exporter-neutral, and safe for persistent runtimes.
- The exact Phase 9 checkpoint and forty-four-package topology remain unchanged.
- Kongono remains pre-1.0 on PHP `>=8.5.0 <8.6.0` and makes no PHP 8.6, PHP 9,
  Stable, production-readiness, compliance, performance, or portability claim.

## Rejected alternatives

- Treating logging, telemetry, audit, receipts, or analytics as one interchangeable
  event stream.
- A global logger, tracer, meter, provider, current span, ambient context, or
  service-located signal registry.
- Making an exporter, backend, OpenTelemetry SDK, environment configuration, or
  AI system an inward dependency or source of authority.
- Letting signal evidence authorize, repair, redefine, or conceal primary
  application behavior.
- Selecting runtime APIs, schemas, packages, protocols, vendors, storage, or
  deployment policy during the documentation-only phase.

## Non-goals

Phase 10A implements no signal API or behavior. It claims no PSR-3 logger,
OpenTelemetry SDK/API integration, W3C Trace Context parser, baggage, span,
meter, counter, histogram, gauge, event processor, audit sink, exporter, OTLP,
collector, backend, dashboard, alert, incident system, profiler, APM,
distributed tracing, service discovery, deployment configuration, compliance,
certification, production readiness, Stable API, or 1.0 support.

## Related material

- [ADR 0011: OpenTelemetry observability strategy](0011-opentelemetry-observability-strategy.md)
- [ADR 0115: Structured Signals, Explicit Context, and Exporter Neutrality](0115-structured-signals-context-and-exporter-neutrality.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Phase 10 roadmap](../architecture/phase-10-roadmap.md)
