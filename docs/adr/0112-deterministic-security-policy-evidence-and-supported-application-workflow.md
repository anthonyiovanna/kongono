# ADR 0112: Deterministic Security Policy Evidence and Supported Application Workflow

- Status: Accepted
- Date: 2026-08-27
- Decision owners: Kongono architecture
- Stability: Accepted architecture; Experimental implementation surfaces

## Context

Phase 9F can enter one browser-session security operation and enforce one typed
capability, but an application still needs exact current policy identity and a
supported composition of those existing boundaries. Authorization decisions
remain application-owned. Kongono must not turn a policy artifact, a command,
or authentication success into authority.

## Decision

Phase 9G adds `kongono/security-policy-build` as the forty-third production
package. It reads only `<application-root>/kongono.security-policy.json`, a
closed version-1 source containing policy name, version, and provenance. The
source and its canonical content-addressed artifact contain no rules, roles,
permissions, claims, evaluator code, or decision semantics. The artifact binds
the exact source bytes, detached model, builder implementation, and policy
reference. Structural validation and exact-current validation are separate.

`inspect()` and `check()` are write-free. `build()` publishes one artifact to
an existing strict descendant output directory using external temporary state,
atomic initial-only publication, idempotence for identical bytes, and
no-clobber rejection for conflicts. A loaded `CurrentSecurityPolicy` is
immutable for one running application; changed source or builder bytes require
a new build, check, and application start.

Phase 9G also adds `kongono/security-application` as the forty-fourth package.
`BrowserSessionProtectedApplication` begins exactly one existing browser-session
security operation, obtains that operation's exact immutable Security Context,
constructs one Authorization Request from the protected capability's exact
Action and Resource plus current policy and explicit environmental evidence,
calls the existing enforcer once, and closes the operation once with the
primary failure when applicable. Only exact Permit returned by the registered
application-owned evaluator at the enforcer executes the capability.

The copy-once `skeletons/security-minimal` demonstrates the build, check, load,
anonymous no-cookie request, exact Permit execution, exact Deny prevention, and
cleanup lifecycle. Its evaluator and no-session verifier are examples, not
providers or framework authority. The official `kongo` edge adds only
`security:inspect` and `security:check`; both are read-only, non-interactive,
and disclose bounded identities only. `application:build` is unchanged.

## Boundaries and consequences

The production graph is exactly forty-four acyclic packages. No lower security
package depends on either new upper package. No policy language, autonomous
policy, wildcard grant, role model, provider, session store, credential,
cryptographic primitive, second transport profile, middleware grant, ambient
current user, audit store, telemetry system, deployment manager, or agent-
delegation runtime is introduced.

Policy artifacts are evidence, never authority. Authentication remains distinct
from authorization. A command success does not prove production readiness. The
skeleton is copied and then application-owned; Kongono does not update copies.
Phase 9H remains the only next architect-review handoff and is not implemented.

## Alternatives rejected

- Framework-owned rules or roles: rejected because application policy meaning
  must remain explicit and reviewable at the evaluator.
- Runtime discovery or source recompilation per request: rejected because it
  creates ambient, stale, and mixed-version authority.
- Middleware authorization: rejected because enforcement must immediately wrap
  the typed protected capability.
- Mutable policy registries or a general security CLI: rejected because they
  widen authority and make currentness nondeterministic.

## Related material

- [ADR 0106](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0109](0109-deterministic-deny-by-default-authorization-evaluation.md)
- [ADR 0110](0110-operation-scoped-security-context-and-generated-composition-inputs.md)
- [ADR 0111](0111-secure-browser-session-transport-and-capability-enforcement.md)
- [Security application workflow](../architecture/security-application-workflow.md)
