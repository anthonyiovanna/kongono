# ADR 0108: Deterministic Explicit Authentication Orchestration

## Status

Accepted

## Date

2026-08-27

## Context

Phase 9A separated identity, authentication, authorization, transport,
enforcement, disclosure, audit, and telemetry. Phase 9B then implemented only
provider-neutral identity and access vocabulary. Phase 9C needs deterministic
authentication orchestration without selecting a credential format, provider,
protocol, session transport, authorization policy, HTTP adapter, or ambient
runtime authority.

Authentication evidence may carry sensitive adapter-owned state, and a verifier
attempt may own resources that must be retired even when verification fails.
Selection, freshness, strength, failure composition, disclosure, and persistent-
runtime isolation therefore need exact framework-owned rules while provider and
secret semantics remain outside the neutral package.

## Decision

Create the Experimental `kongono/authentication` package under
`Kongono\Authentication\`. It depends in production only on
`kongono/api-metadata`, `kongono/diagnostics`, `kongono/identity-access`, and PHP
`>=8.5.0 <8.6.0`. No existing production package depends on Authentication. The
addition brings the graph to exactly thirty-eight acyclic production packages.

The package owns the following values and adapter SPIs:

- `AuthenticationMechanism` is one exact 1-64 byte canonical ASCII name matching
  `^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$`;
- `AuthenticationInstant` is one explicit non-negative Unix second and reads no
  clock;
- `AuthenticationStrength` is an unnamed ordinal from 1 through 255;
- `AuthenticationRequirement` combines one minimum strength with a maximum age
  from 0 through 31,536,000 seconds;
- `AuthenticationEvidence` exposes only its mechanism and leaves every payload
  and secret lifecycle adapter-owned;
- `AuthenticationFacts` contains exactly principal, mechanism, strength,
  authentication instant, and validity-until instant, with validity not before
  authentication;
- `AuthenticationRequest` contains an ordered detached list of zero through
  eight evidence objects, explicit current instant, and explicit requirement;
- `AuthenticationVerification` contains one existing neutral
  `AuthenticationOutcome` and facts only for Authenticated;
- `AuthenticationVerifier` identifies one exact mechanism and begins one
  attempt from explicit evidence and time; and
- `AuthenticationVerificationOperation` verifies once and exposes explicit
  `retire()` cleanup.

These contracts assign no semantic name to strength, define no credential,
select no protocol or provider, and grant no authorization.

## Exact orchestration

`AuthenticationOrchestrator` is constructed with one through thirty-two
explicit verifiers whose mechanism names are unique. Registration is detached
and exact; there is no discovery, priority, `supports()` probe, or fallback.
Each call follows this order:

1. zero evidence returns Absent;
2. more than one evidence object returns Ambiguous without invoking a verifier;
3. one evidence object selects only the verifier with the same exact mechanism;
4. no match returns Unsupported;
5. matched `begin()` runs exactly once, and a thrown failure propagates unchanged
   because no operation exists;
6. `verify()` runs exactly once;
7. `retire()` then runs exactly once whether verification returned or threw;
8. a lone verification failure is rethrown unchanged;
9. a lone cleanup failure becomes `AuthenticationCleanupException`, retaining
   the exact cleanup cause;
10. operation and cleanup failures become
    `AuthenticationOperationAndCleanupException`, retaining both exact objects
    and using the operation failure as native previous;
11. a non-authenticated verification passes through unchanged after cleanup;
12. authenticated facts with a missing or mismatched mechanism, a future
    authentication instant, or insufficient strength become Invalid;
13. a current instant after validity or an age greater than the requested
    maximum becomes Expired; and
14. otherwise the authenticated verification passes through unchanged.

The bounds are inclusive. `now == validUntil`, age equal to maximum age, and
strength equal to minimum strength remain valid. No grace, skew, downgrade,
retry, best effort, priority, or provider fallback exists.

## Lifecycle and persistent-runtime behavior

The orchestrator retains only immutable verifier registration. Request,
evidence, operation, verification, facts, principal, and failure state remain
local to one call and become unreachable after the call and cleanup. Sequential
and reentrant calls therefore do not share current-call state. Evidence and
verifier objects remain caller-owned; the orchestrator does not clone,
serialize, log, or persist them. Destructors and process termination are not
cleanup.

## Diagnostics and failures

The package owns Internal diagnostics `KGN-AUTHENTICATION-1001` through
`KGN-AUTHENTICATION-1008` for validation, registration, evidence, verification,
freshness, strength, cleanup, and combined failure. Diagnostic context contains
only Safe `package`, `failure_category`, and `remediation` fields. Mechanisms,
principal or resource identifiers, evidence, rejected values, provider details,
timestamps, causes, messages, traces, and secrets never enter safe presentation.

The package exception marker is Public. The abstract diagnostic base and catalog
are Internal. Validation, cleanup, and combined-failure exceptions are
Experimental. Cleanup exceptions preserve exact causes without exposing their
details through the bounded string form.

## Security and authority consequences

Authenticated facts are evidence, not permission. The package neither creates a
`SecurityContext` nor evaluates an `AuthorizationDecision`. Application-owned
authorization and later protected-capability enforcement remain separate and
deny-by-default. Evidence implementations must enforce their own sensitive-data
lifecycle under later adapter authority; the neutral interface deliberately
offers no payload, stringification, serialization, debug, erasure, request,
header, cookie, password, token, provider-claim, or network method.

The package reads no globals, process state, environment, request, session,
clock, randomness, configuration registry, container, filesystem, or network.
It performs no persistence, audit, telemetry, presentation, or application
composition.

## Rejected alternatives

- Boolean or nullable authentication results that erase bounded outcome meaning.
- Runtime verifier discovery, mutable registries, `supports()` probing,
  priorities, fallback, downgrade, or retry.
- Provider objects, credential formats, passwords, tokens, keys, sessions,
  cookies, cryptography, or SDK dependencies in the neutral package.
- Ambient clocks, request inspection, current-user globals, service location,
  container access, runtime Reflection discovery, or static current state.
- Treating authenticated facts, strength, mechanism, principal kind, or a
  successful verifier response as authorization.
- Construction of Security Context, policy evaluation, HTTP integration,
  Composition wiring, audit, telemetry, commands, or application workflows in
  Phase 9C.

## Current boundary

Phase 9C implements only deterministic provider-neutral authentication
orchestration and verifier-attempt lifecycle. It implements no concrete verifier
or provider, credential, password, token, key, cryptography, session, cookie,
CSRF behavior, HTTP adapter, login/logout, MFA, passkey, federation, recovery,
authorization evaluator, policy, receipt, protected-capability enforcement,
Security Context construction, persistence, audit, telemetry, Composition
wiring, command, deployment, or application workflow. Phase 9D remains
unimplemented and is the only next architect-review handoff.

## Related decisions

- [ADR 0105](0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0107](0107-neutral-identity-and-access-contracts-and-package-boundary.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Phase 9 roadmap](../architecture/phase-9-roadmap.md)
