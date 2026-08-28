# ADR 0122: Phase 10 External Observability Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-08-28
- Stability: Accepted architecture; test-owned evidence

## Context

Phase 10A through 10G established separate observability contracts, tracing,
metrics, operational events, security-audit evidence, one selected OTLP
HTTP/protobuf bridge, deterministic configuration evidence, and an explicit
application workflow. Those package tests prove their local contracts, but the
milestone also needs one root-owned proof across real process, filesystem,
copy-once application, command, and cleanup boundaries.

External proof must not turn test infrastructure into a production exporter,
provider, collector, audit store, authorization mechanism, deployment system,
or ambient observability registry.

## Decision

Kongono accepts `composer acceptance:phase10` as the root-owned Phase 10
external acceptance profile. The harness runs from fresh process state, owns
one unique system-temporary application root whose path contains a space, and
copies `skeletons/observability-minimal` without links. Its offline install uses
exact local package mirrors with Composer path installation configured not to
symlink.

The harness exercises the public package and application boundaries with
application-owned deterministic clocks, identifiers, sampler, work, and a
bounded local capturing transport. That transport performs no network export.
When the selected profile emits payloads, the harness parses them with the
pinned generated protobuf request classes. It does not infer correctness from
string shapes.

Trace, metric, operational-event, security-audit, and export evidence remain
typed and distinguishable. In particular, security-audit evidence is not
converted into an ordinary operational event and never grants identity,
permission, policy, enforcement, or authorization authority.

The profile externally proves explicit immutable operation context and typed
propagation, bounded signal values and cardinality, lifecycle isolation,
deterministic failure composition, visible backpressure and export failure,
explicit flush and close, exact-current configuration, no-clobber publication,
read-only inspect/check behavior, stale and tampered evidence rejection,
copy-once workflow repeatability, Phase 8 and Phase 9 compatibility,
nondisclosure, repository preservation, and complete owned cleanup.

The harness snapshots repository status before its first child process and
requires byte-identical status after cleanup. Success is emitted only after
cleanup as one canonical LF-terminated JSON document. Failure emits only the
fixed `phase10_acceptance_failed` token.

After all pre-checkpoint gates pass, the Phase 10 checkpoint is one normal local
commit containing exactly the authenticated 325-path Phase 10 content delta.
Directive and report caller records remain outside the checkpoint. There is no
push, tag, release, second commit, or Phase 11 work.

## Consequences

The acceptance harness is test evidence, not production behavior. It adds four
root test PHP files and no production package, runtime API, dependency,
exporter, provider, backend, collector deployment, dashboard, alert manager,
durable audit store, retry worker, profiler, autonomous tuning, credential, or
environment discovery.

Windows, PHP, Composer, Git, PHP-CGI, and PostgreSQL versions recorded by the
checkpoint report are evidence for the executed profile only. They are not
universal portability, performance, production-readiness, security
certification, or complete OpenTelemetry support claims. Kongono remains
pre-1.0 and all Phase 10 APIs retain their accepted stability.

## Rejected alternatives

- A production collector or network exporter in the harness: rejected because
  external proof does not grant product runtime authority.
- Treating audit evidence as logs or authorization receipts: rejected because
  signal separation and non-authority are locked invariants.
- Parsing protobuf payloads through string matching: rejected because the
  selected profile is defined by pinned generated message classes.
- Ambient providers, registries, environment configuration, shutdown hooks,
  daemons, or watchers: rejected because lifecycle and authority must remain
  explicit and bounded.
- Including caller directives or reports in the checkpoint: rejected because
  they are external governing and completion records, not milestone content.

## Related material

- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Observability Application Workflow](../architecture/observability-application-workflow.md)
- [Phase 10 External Acceptance](../architecture/phase-10-external-acceptance.md)
- [Phase 10 Roadmap](../architecture/phase-10-roadmap.md)
- [ADR 0120](0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md)
- [ADR 0121](0121-deterministic-observability-configuration-and-supported-application-workflow.md)
