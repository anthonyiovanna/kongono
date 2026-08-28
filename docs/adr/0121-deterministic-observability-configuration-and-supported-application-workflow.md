# ADR 0121: Deterministic Observability Configuration and Supported Application Workflow

- Status: Accepted
- Date: 2026-08-28
- Decision semantics: LOCKED configuration and workflow boundaries; Experimental public API

## Context

Phases 10B through 10F provide neutral signal values, explicit tracing and
metric lifecycles, distinct operational-event and security-audit runtimes, and
one explicit OTLP HTTP/protobuf bridge. Applications still need a deterministic
way to select the bridge's bounded resource and batch values, prove that
configuration current before startup, and coordinate all four signal domains
without ambient framework state.

Endpoints, credentials, providers, transports, sampling, clocks, identifiers,
and deployment policy cannot be configuration-file side effects. They remain
explicit caller-owned collaborators because they perform I/O, carry secrets, or
select operational policy.

## Decision

Phase 10G creates the Experimental `kongono/observability-build` and
`kongono/observability-application` packages, a copy-once
`skeletons/observability-minimal` example, and the read-only
`observability:inspect` and `observability:check` commands.

### Closed configuration and evidence

The sole source is `<application-root>/kongono.observability.json`. Its exact
version-1 shape selects only the locked OTLP HTTP/protobuf profile, bounded
OpenTelemetry resource values, and bounded queue, batch, and timeout values.
Unknown keys, alternate inputs, endpoint or credential material, environment
lookups, and provider selection are rejected.

The build package compiles immutable canonical evidence containing separate
source, semantic-model, implementation, and artifact identities. Structural
validation proves canonical shape; exact-current validation additionally
rebuilds from the same explicit application root and implementation. The
content-addressed artifact is published atomically, initial-only, and
no-clobber into one explicit existing directory strictly inside the application
root. Inspect and check are write-free. A loaded
`CurrentObservabilityConfiguration` is immutable and never hot-reloads.

### Explicit application construction

One factory receives exact current configuration plus caller-owned trace clock,
trace identifier source, sampler, OpenTelemetry time anchor and clock, and OTLP
transport. It directly constructs exactly one bridge and one tracing, metrics,
operational-events, and security-audit runtime. There is no discovery,
Reflection, autowiring, service location, global provider, environment lookup,
or hidden default.

Each execution owns one explicit operation. It begins tracing then metrics,
invokes typed work once, records events and audit evidence through their
distinct runtimes, and completes metrics, tracing, and bridge flush in that
order. Partial construction retires only created handles in reverse order.
Primary and cleanup throwable identities remain exact and nondisclosing.
Operations become final when completion starts and release direct retained
state on every terminal path.

The application permits at most 64 active operations. Sequential, overlapping,
and callback-reentrant operations remain isolated. Explicit flush requires an
open application with no active operations. Close refuses active operations,
closes the bridge once, becomes terminal even on cleanup failure, and is
idempotent thereafter. No destructor, shutdown hook, timer, retry worker, or
background drain exists.

### Signal and ownership boundaries

Trace, metric, operational-event, and security-audit evidence retain their
accepted meanings. Audit evidence is never converted to an ordinary event.
Configuration and export do not grant identity, authorization, durability,
retention, delivery, or compliance.

The observability skeleton is a repository-owned copy-once example. After copy,
its non-generated files are application-owned. Its deterministic identifiers,
clocks, sampler, and capturing transport are plainly non-production examples;
the transport performs no network access and no backend or collector is
implied.

The two CLI commands disclose only canonical identities, the fixed profile,
and publication/currentness booleans through one neutral result. They neither
write nor expose paths, source/resource/batch values, payloads, endpoints,
credentials, providers, or exception text.

## API status

Package exception markers are Public catch boundaries. Operational build and
application contracts and concrete failures are Experimental. Validators,
codecs, path/publication helpers, diagnostic catalogs, and other implementation
details are Internal. No API is Stable.

## Consequences

The production graph contains exactly 52 acyclic packages and 182 local
production dependency edges. Applications gain one supported explicit
observability workflow without making configuration a provider or signal state
ambient. Startup must build or check exact evidence again after relevant source
or implementation changes.

## Rejected alternatives

- Globals, ambient current operations, environment auto-configuration, runtime
  scanning, service location, container magic, property-hook resolution, and
  dynamic proxies.
- Endpoint, credential, provider, HTTP-client, collector, or backend selection
  in the configuration artifact.
- Hot reload, retry, timers, workers, shutdown hooks, background flushing, or
  implicit cleanup.
- A generic logger or conversion between operational events and security audit
  evidence.
- Dashboard, alerting, SLO, incident, collector-deployment, profiling,
  autonomous tuning, remediation, or broad operations-platform behavior.

## Related decisions

- [ADR 0114](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115](0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0116](0116-neutral-observability-contracts-and-package-boundary.md)
- [ADR 0117](0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
- [ADR 0118](0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
- [ADR 0119](0119-structured-operational-events-and-bounded-security-audit-evidence.md)
- [ADR 0120](0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md)
- [Observability application workflow](../architecture/observability-application-workflow.md)
- [Phase 10 roadmap](../architecture/phase-10-roadmap.md)
