# ADR 0214: Phase 21 external storage acceptance and checkpoint

- Status: Accepted for Phase 21H; separate local validation, audit, architect acceptance and exact checkpoint complete
- Date: 2026-09-25
- Phase: 21H

## Context

Phases 21A-G define optional neutral storage contracts, deterministic profile
evidence, a contained local adapter, exact-current operations, an explicit
application workflow, and offline inspection. Their separate package tests do
not by themselves prove that the assembled local path preserves authorization,
integrity, finite resource ownership, nondisclosure, and cleanup across real
process and filesystem boundaries.

## Decision

Add one root-owned, deterministic external acceptance harness under
`tests/External/Storage`. The root `acceptance:phase21` Composer script invokes
its fixed runner. The harness uses the actual public Storage APIs, installed
autoload, a contained ordinary temporary filesystem, and closed fake
collaborators only where an external provider or injected failure is required.
It has twelve named groups covering profile contract/build, publication,
authorization, local operations, outcome truth, stream lifecycle, runtime
isolation, application parity, inspection, nondisclosure, process integrity,
and cleanup. Its isolated capture proof covers success, failure, overflow,
burst output, timeout and retirement, malformed output, retained failed
assertions, cleanup refusal, and repeatability.

The harness distinguishes actual contained local-adapter behavior from
scripted uncertainty and failures. An application's authorizer remains the
source of operation permission. Profile evidence, adapter possession, and
test success grant no authority. A direct adapter caller owns its source;
the guarded runtime retires sources it accepts. A read closed early is not
verified complete. Uncertain mutation truth is retained. The runner publishes
only bounded fixed success JSON; failure details remain in private owned
captures, with no private values in public diagnostics.

The acceptance is run with an exact local PHP profile and offline dependencies.
The local gate includes repository analysis, configured tests, regression
profiles, Composer validation, platform and knowledge checks, exhaustive
style, documentation consistency, and preservation evidence. The security
audit required separate owner approval; H.15 obtained it and audited the
then-current 189-entry lock without advisories at that time. Architect
acceptance and an exact checkpoint remained separate decisions; they
subsequently completed. Implementation or local test success alone did not
close Phase 21. The accepted proof retains the limitations in the
[Phase 21 acceptance guide](../architecture/phase-21-external-storage-acceptance.md).

## Consequences

This proof concerns one host, one contained local filesystem adapter, and
closed synthetic collaborators. It does not certify cloud providers,
cross-process atomicity, durability, live credentials or networking,
performance, deployment, production readiness, Stable/1.0, or future PHP
versions. Link creation is tested only when host permissions allow it;
containment and no-clobber tests do not depend on that capability. All owned
success resources are retired, while failure captures remain available for
review. The authorized exact checkpoint subject was
`Complete Kongono Phase 21 storage platform milestone`.

See the [Phase 21 external acceptance guide](../architecture/phase-21-external-storage-acceptance.md)
and the [Phase 21 roadmap](../architecture/phase-21-roadmap.md).
