# Kongono Authentication

## Purpose and current scope

`kongono/authentication` implements deterministic explicit authentication
orchestration over caller-supplied detached evidence and typed verifier adapters.
It is provider-neutral and Experimental. Its only production dependencies are
API Metadata, Diagnostics, Identity Access, and PHP `>=8.5.0 <8.6.0`.

Authentication establishes bounded facts; it never grants authorization. No
existing production package depends on Authentication, and the package does not
depend on Kernel, Dependency Injection, Composition, Application Graph, HTTP,
Routing, Web Runtime, PHP CGI, database, persistence, Console, developer-
intelligence packages, telemetry, application code, security libraries, or a
provider SDK.

## Values and adapter contracts

- `AuthenticationMechanism` is one exact canonical 1-64 byte mechanism name.
- `AuthenticationInstant` carries one explicit non-negative Unix second.
- `AuthenticationStrength` carries an unnamed ordinal level from 1 through 255.
- `AuthenticationRequirement` sets minimum strength and a maximum age from zero
  through 31,536,000 seconds.
- `AuthenticationEvidence` exposes only `mechanism()`; the evidence object and
  every sensitive payload remain caller/adapter-owned.
- `AuthenticationFacts` carries exact principal, mechanism, strength,
  authentication instant, and validity limit.
- `AuthenticationRequest` detaches and reindexes an ordered list of zero through
  eight evidence objects plus explicit `now` and requirement values.
- `AuthenticationVerification` carries one neutral Identity Access outcome and
  facts only when Authenticated.
- `AuthenticationVerifier` begins one operation for its exact mechanism.
- `AuthenticationVerificationOperation` verifies once and is retired once.

The package defines no credential representation and does not inspect, copy,
deduplicate, parse, normalize, serialize, log, or expose evidence payloads.

## Synthetic use

An application adapter implements only the neutral evidence method:

```php
use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationMechanism;

final readonly class SyntheticEvidence implements AuthenticationEvidence
{
    public function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism('synthetic.v1');
    }
}
```

The caller supplies time and strength/freshness requirements explicitly:

```php
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationRequest;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;

$request = new AuthenticationRequest(
    [new SyntheticEvidence()],
    new AuthenticationInstant(1_800_000_000),
    new AuthenticationRequirement(new AuthenticationStrength(2), 300),
);

$verification = $orchestrator->authenticate($request);
```

The example is synthetic and contains no secret or provider value. The caller
constructs the orchestrator from one through thirty-two explicit verifier
objects with unique mechanisms.

## Selection and outcomes

Zero evidence returns Absent. More than one evidence object returns Ambiguous.
Neither case invokes a verifier. With exactly one evidence object, only the
verifier registered under the same exact canonical mechanism may begin. An
unmatched mechanism returns Unsupported. There is no discovery, priority,
fallback, downgrade, or `supports()` probe.

A matched verifier begins exactly once, its operation verifies exactly once,
and retirement runs exactly once even when verification throws. A begin failure
propagates unchanged and has no operation to retire. A lone verification failure
is rethrown unchanged after successful retirement. A lone cleanup failure is
retained by `AuthenticationCleanupException`. If both fail,
`AuthenticationOperationAndCleanupException` preserves both exact objects and
uses the verification failure as native previous.

Non-authenticated results pass through unchanged after cleanup. Authenticated
facts must match the selected mechanism, may not be dated after explicit `now`,
must still be valid, must be fresh enough, and must satisfy the minimum strength.
Mechanism mismatch, a future authentication instant, or insufficient strength
becomes Invalid. Expired validity or excessive age becomes Expired.

Comparisons are inclusive: `now == validUntil`, age equal to maximum age, and
strength equal to minimum strength are accepted. There is no hidden clock,
timezone conversion, grace period, skew, retry, or best effort.

## Security, disclosure, and lifecycle

Diagnostics contain only Safe bounded package, category, and remediation fields.
Evidence, mechanisms supplied by callers, principal/resource identifiers,
timestamps, provider responses, rejected values, causes, messages, traces,
arguments, and secrets never enter bounded exception stringification or
diagnostic context. Exact cleanup causes remain available only through typed
exception accessors.

The orchestrator retains only immutable verifier registration. Request,
evidence, operation, result, facts, principal, and failure state stay local to
one call. Sequential and reentrant calls share no current-call state. Evidence
and verifier objects remain caller-owned; destructors and process termination
are not cleanup.

## Not implemented here

This package implements no concrete verifier/provider, credentials, passwords,
tokens, keys, cryptography, session or cookie transport, CSRF behavior, HTTP or
header/request adapter, login/logout, MFA, passkey, federation, recovery,
authorization evaluator, policy, decision receipt, protected-capability
enforcement, Security Context construction, persistence, audit, telemetry,
Composition wiring, command, application workflow, deployment behavior, or
Phase 9D capability.

See [ADR 0108](../../docs/adr/0108-deterministic-explicit-authentication-orchestration.md),
the [Identity and Access Security guide](../../docs/architecture/identity-and-access-security.md),
and the [Phase 9 roadmap](../../docs/architecture/phase-9-roadmap.md).
