# ADR 0113: Phase 9 External Security Acceptance and Checkpoint

## Status

Accepted and implemented by Phase 9H.

## Context

Phases 9A through 9G established the Identity and Access Security doctrine,
neutral identity values, explicit authentication and authorization
orchestration, operation-scoped Security Context construction, the selected
browser-session transport, protected-capability enforcement, deterministic
policy evidence, and one supported application workflow. Package and
integration tests prove those boundaries in process, but milestone closure also
requires independent evidence through fresh processes and application state.

That proof must exercise the public boundaries without turning test fixtures
into providers or policy truth. It must preserve the authenticated repository,
keep browser-session material opaque, distinguish authentication from
authorization, prove exact-Permit enforcement, and remove every owned resource
after both success and failure.

## Decision

Phase 9H adds one root-owned, test-only profile under `tests/External/Security`.
It launches child PHP and Composer processes only with explicit argument
arrays, closed stdin, captured bounded streams, and a finite deadline. It owns
one uniquely named system-temporary application root whose final path contains
a space, copies `skeletons/security-minimal`, installs from exact local package
mirrors without symlinks, and performs the supported build, check, run, and
repeat-check workflow offline.

The harness owns only synthetic application fixtures. It invents no provider,
policy semantics, credential primitive, session truth, cryptography, or
authority. Synthetic browser-session, CSRF, principal, policy, and failure
values are never emitted. Authentication, authorization, policy identity,
enforcement, transport, lifecycle, disclosure, audit, and telemetry remain
separate concerns.

The matrix proves that Deny and Indeterminate never execute protected effects.
Permit is necessary but is effective only for the exact request, immutable
operation Security Context, resource, action, policy evidence, and environment
supplied to one enforcement call. Authentication success alone grants no
authority. Policy currentness, tamper rejection, read-only inspection, and the
copy-once workflow remain deterministic, contained, no-clobber, and
cleanup-owned.

The root Composer script is exactly `acceptance:phase9`, and Hantei records the
same command. Success is one canonical LF-terminated JSON object containing
only `status`, `profile`, the observed positive `case_count`, and `cleanup`.
Failure emits only `phase9_acceptance_failed` to stderr.

The checkpoint contains exactly the authenticated Phase 9 content plus this
bounded acceptance work. Every directive and task report remains a caller-owned
record outside the commit. The milestone is one local commit with no push, tag,
release, second commit, or Phase 10 work.

## Consequences

Phase 9A through 9H are implemented and checkpointed as one Identity and Access
Security milestone. Phase 9H adds no production PHP, package, dependency,
policy language, credential/session provider, cryptography, account product,
audit store, telemetry, deployment behavior, or Stable API claim.

The checkpoint records Windows, PHP 8.5, PostgreSQL 18.6, and retained
PostgreSQL 17.2 regression evidence. Those are exact observations, not claims
of universal portability, performance, production readiness, or certified
security. Kongono remains pre-1.0.
