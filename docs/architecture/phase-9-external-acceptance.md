# Phase 9 External Security Acceptance

## Purpose

Phase 9H closes the Identity and Access Security milestone with external proof
of the selected pre-1.0 boundaries. The profile verifies behavior visible to an
application, operator, automation client, or governed agent without adding
production authority.

The authoritative decision is
[ADR 0113](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md).

## Running the profile

From the repository root run:

```text
composer acceptance:phase9
```

The command accepts no arguments. Success is exactly one canonical JSON line
with profile `kongono-phase9-security`, a positive observed case count, and
`cleanup` equal to `complete`. Failure is nonzero and emits only
`phase9_acceptance_failed` to stderr.

## Process and fixture boundary

The root-owned harness:

- derives the repository root from its own fixed location;
- invokes PHP and Composer with explicit argument arrays rather than a shell;
- closes stdin, captures bounded raw stdout and stderr, and applies a finite
  deadline;
- owns one unique system-temporary application root whose final path contains a
  space;
- copies the security-minimal skeleton and exact local packages without
  junctions or symlinks;
- performs a local/offline install, build, check, run, and repeat check;
- keeps every additional verifier, evaluator, capability, source, generated
  file, Composer home, and cache below the owned root;
- compares repository Git status byte-for-byte before and after execution; and
- closes every operation and process and removes every owned path in `finally`
  before success is emitted.

The fixtures are application-owned test values. They are not framework
providers, policy semantics, credentials, session truth, cryptography, audit,
telemetry, deployment logic, or authorization.

## Accepted matrix

The profile externally proves:

1. the copied skeleton installs from exact local packages without symlinks;
2. build publishes only the expected contained policy, Composition, and wiring
   outputs, while check and repeat check are exact-current and write-free;
3. the supported example executes once only for exact Permit and closes its
   operation once;
4. Deny, Indeterminate, policy mismatch, and successful authentication without
   authorization prevent protected execution;
5. absent, invalid, unsupported, ambiguous, expired, replayed, and unavailable
   authentication never create implicit authority;
6. exact HTTPS origin, reserved session-cookie evidence, and required CSRF
   evidence are enforced before protected execution;
7. insecure, cross-origin, malformed transport, duplicate or invalid cookie,
   and absent or mismatched CSRF inputs fail closed;
8. each operation receives one immutable Security Context and generated
   application state retains no principal or decision after close;
9. cleanup is exactly-once, every-attempt, retire-before-release, and preserves
   primary plus cleanup failures;
10. reentrancy is rejected without breaking later sequential use, and
    sequential permitted, denied, and authentication-failed calls share no
    request state;
11. stale source, tampered artifact, and wrong policy identity reject before
    runtime execution;
12. supported CLI inspect/check and repeated check are read-only, while mutation
    intent is rejected;
13. human and machine help/results are deterministic, bounded, and omit
    Protected values;
14. the Phase 8 external application-build profile remains compatible; and
15. captured output, stringified failures, the repository, environment, and
    temporary filesystem retain no sensitive or owned residue.

## Security, disclosure, and current evidence

Authentication and authorization remain distinct. A verified principal does
not grant access; only exact Permit over the exact immutable inputs can cause
one protected-capability call. The harness compares bounded projections and
never exposes child exceptions, raw credentials, session or CSRF values,
principal identifiers, policy payloads, owned paths, username, hostname,
environment secrets, stack text, or terminal escapes.

The checkpoint is current Windows evidence on PHP 8.5, with PostgreSQL 18.6 as
the Phase 7 regression profile and PostgreSQL 17.2 retained for Phase 6. It is
not universal portability or performance evidence.

## Stability and non-claims

The harness is root-owned test evidence, not a Public framework API. Phase 9A
through 9H are complete only at the exact Phase 9 checkpoint. Kongono remains
pre-1.0 and does not claim production readiness, certified security, complete
identity management, built-in cryptography, autonomous remediation, PHP 8.6 or
PHP 9 execution proof, a release, deployment, or Phase 10 behavior.
