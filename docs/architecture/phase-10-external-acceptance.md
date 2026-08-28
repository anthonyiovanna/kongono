# Phase 10 External Observability Acceptance

## Purpose

`composer acceptance:phase10` proves the accepted Phase 10A-H milestone across
real process, copied application, deterministic build evidence, signal runtime,
selected OTLP mapping, command, failure, and cleanup boundaries. It is
root-owned test evidence. It is not a production exporter, provider, backend,
collector, audit store, deployment workflow, or operations platform.

## What the profile executes

The no-argument command creates one uniquely owned system-temporary root whose
path contains a space. It copies `skeletons/observability-minimal` as ordinary
files, mirrors exact local framework and locked third-party packages, and runs
Composer offline with path installations configured with `symlink: false`.

The copied application is installed, built, checked, run, checked and built
again. The profile verifies deterministic output identities, idempotence,
write-free checks, stale and tampered evidence rejection, closed configuration,
and absence of generated, vendor, lock, cache, coverage, process, stream, or
transport residue after cleanup.

The profile also exercises explicit observability work with test-owned clocks,
identifiers, sampler, work, and local capturing transport. Trace, metric,
operational-event, security-audit, and export paths remain distinct. Emitted
trace, metric, and log payloads are parsed with the pinned generated protobuf
request classes. Operational-event and security-audit logs must retain their
separate instrumentation scopes; audit evidence never grants authority.

Bounded package behavior is run in fresh PHP children. The profile covers
context and propagation, cardinality and field schemas, queue and batch bounds,
backpressure, partial export progress, transport failure, flush, close,
combined cleanup failure, post-close refusal, and sequential, overlapping, and
reentrant isolation. Phase 8 Console and Phase 9 security acceptance run as
compatibility checks.

## Output contract

Success is exactly one canonical LF-terminated JSON object with keys in this
order:

```json
{"status":"ok","profile":"kongono-phase10-observability","case_count":1,"cleanup":"complete"}
```

`case_count` is the observed positive count, not a fixed promised value. The
example above illustrates shape only.

On failure, stdout contains no success document, stderr contains only
`phase10_acceptance_failed` plus LF, and the process exits nonzero. Native
exceptions, child output, paths, identifiers, attributes, event/audit payloads,
protobuf bytes, endpoints, environment values, usernames, hostnames, and stack
text are not disclosed.

## Ownership and limitations

Every child uses argv arrays rather than shell strings. Standard input is
closed; output is captured as bounded raw bytes; execution has finite timeouts;
and all streams, processes, operations, and application state are explicitly
closed. The owned temporary root is removed in `finally` after success or
failure, and repository status must match its original raw bytes.

The profile performs no live network export and makes no claim of complete
OpenTelemetry support, production readiness, certified observability or
security, universal portability, zero overhead, autonomous operation, PHP
8.6/9 execution, or Phase 11 behavior. Kongono remains pre-1.0.

## Milestone closure

Phase 10A through 10H are complete at the exact Phase 10 checkpoint. The
production graph remains 52 acyclic packages and 182 local edges; production
PHP, package-test PHP, and the five skeleton manifests remain unchanged. Phase
10H adds four root test PHP files, bringing the root-test inventory from 60 to
64, plus this guide and ADR 0122.

See [ADR 0122](../adr/0122-phase-10-external-observability-acceptance-and-checkpoint.md),
the [Phase 10 roadmap](phase-10-roadmap.md), and the
[observability application workflow](observability-application-workflow.md).
