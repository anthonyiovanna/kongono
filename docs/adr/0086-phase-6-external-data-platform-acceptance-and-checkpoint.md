# ADR 0086: Phase 6 External Data Platform Acceptance and Checkpoint

- **Status:** Accepted
- **Decision type:** Phase 6H external evidence and checkpoint boundary
- **Stability:** Test evidence; no production API

## Context

ADRs 0079–0085 and Phases 6C–6G implement the eight-package Deterministic Data
Platform slice. Package tests and the accumulating provider harness prove its
parts, but Phase 6 needs one whole-platform external owner, a bounded measured
baseline, and an exact repository checkpoint before the milestone can close.

## Decision

The root-owned `composer acceptance:phase6` harness is the Phase 6
whole-platform acceptance owner. It starts a fresh private local cluster from a
caller-selected PostgreSQL binary directory, exercises all eight packages and
the copied data-minimal workflow, then stops the provider and deletes its owned
temporary state before reporting success. PostgreSQL 17 is the only provider
profile evidenced by this milestone.

The harness also records a local, observational, versioned, per-run measurement
baseline. Version 1 uses `hrtime(true)` integer nanoseconds, 10 warmup
iterations and 50 measured samples per scenario, deterministic nearest-rank
summaries, and machine-readable output. Scenario order is immutable plan
compilation, prepared Select round trip, generated-repository find, and
transaction begin/rollback. Timing values are evidence outputs, never exact
reproducibility identities.

The baseline establishes no SLA, performance budget, regression threshold,
universal portability, production-readiness, fastest-framework, zero-overhead,
or competitor claim. Output excludes secrets, DSNs, hosts, users, database
values, SQL, parameters, rows, absolute paths, provider messages, and stack
arguments. The proof adds no runtime telemetry or instrumentation.

The Phase 6 checkpoint contains the exact authorized framework and Phase 6
implementation manifest. Every architect/executor directive, correction, and
task report remains a caller-owned record and is excluded from that checkpoint.

## Consequences

Success is one canonical LF-terminated JSON object after complete cleanup.
Failure is bounded, non-disclosing, and nonzero. Compatible measurements may be
compared cautiously, but a material hardware, operating-system, PHP, provider,
configuration, or framework change requires a fresh run and a new profile-aware
interpretation.

The checkpoint does not promote Experimental APIs, add another provider,
install a service, select deployment behavior, or begin Phase 7.

## Rejected alternatives

- Timing equality as a reproducibility identity or a fixed duration ceiling.
- A benchmark ranking, SLA, budget, automatic regression decision, or
  production-readiness certification.
- Runtime metrics, tracing, profiling hooks, or generated instrumentation.
- Reporting raw samples, credentials, connection material, private data, SQL,
  provider-native failures, host identity, timestamps, usernames, or paths.
- Including caller-owned directives and reports in the framework checkpoint.

## Related material

- [Phase 6 external acceptance](../architecture/phase-6-external-acceptance.md)
- [Database performance baseline](../engineering/database-performance-baseline.md)
- [Data Platform architecture](../architecture/data-platform.md)
- [Phase 6 roadmap](../architecture/phase-6-roadmap.md)
