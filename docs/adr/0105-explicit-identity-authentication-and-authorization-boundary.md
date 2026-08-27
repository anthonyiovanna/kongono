# ADR 0105: Explicit Identity, Authentication, and Authorization Boundary

## Status

Accepted

## Date

2026-08-27

## Context

Kongono's accepted runtime, Composition, HTTP, persistence, and Structured CLI
boundaries already require explicit authority, operation-scoped state, bounded
diagnostics, and deterministic evidence. They do not yet define an Identity and
Access Security runtime. Treating a route, middleware position, session value,
role label, dependency binding, principal kind, or provider claim as implicit
permission would weaken those boundaries and would be unsafe in persistent
runtimes.

Phase 9 needs a fixed architecture before any security contract or behavior is
introduced. Identity, authentication, authorization, credential transport,
enforcement, disclosure, audit, and telemetry are related but semantically
different concerns. A successful authentication establishes only accepted
facts about an identity claim; it cannot itself authorize an action against a
resource.

## Decision

Phase 9 is the Identity and Access Security milestone. Phase 9A accepts the
[Identity and Access Security guide](../architecture/identity-and-access-security.md)
and the fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md) as
documentation architecture only. Phase 9A adds no package, PHP, dependency,
schema, public API, command, provider, authenticator, policy engine, session,
HTTP integration, runtime enforcement, audit store, or telemetry behavior.

The milestone keeps these boundaries explicit:

- a **Principal** is an immutable reference, not an application user record,
  provider object, credential, role, tenant, actor, subject, or permission;
- **Authentication** evaluates explicit detached evidence through an explicitly
  selected verifier and produces typed facts or a distinct non-success outcome;
- **Authorization** evaluates an immutable security context, exact action,
  exact resource, current policy evidence, and explicit environmental facts;
- credential and session transports carry evidence or continuity but grant no
  authorization;
- enforcement occurs at the protected capability before protected reads or
  effects, not merely because routing or middleware ran;
- disclosure, audit, and telemetry observe bounded outcomes without becoming
  policy authority or leaking secrets.

Authorization is deny by default and fail closed. Only an exact `Permit`
decision may proceed. `Deny`, `Indeterminate`, missing or stale evidence,
ambiguity, unsupported input, and evaluation failure never become permission.
The application owns the meaning of actions, resources, tenancy, subject/actor
relationships, and policy. Kongono may later provide deterministic mechanics
without inventing application policy.

Security state is immutable, explicit, and operation-scoped. It is constructed
at an outer boundary from validated evidence and passed through generated typed
wiring or explicit operation collaborators. There is no static current user,
ambient authentication singleton, thread-local context, service location, or
Kernel-owned security lifecycle. Anonymous access, where permitted by policy,
is explicit rather than represented by `null`.

The fixed sequence is exactly Phase 9A through Phase 9H. Each later letter
requires a separate independently authenticated architect directive. Phase 9B
is the only next handoff after Phase 9A review; Phase 9A does not implement or
pre-authorize it.

## Fixed Phase 9 sequence

| Phase | Fixed boundary |
|---|---|
| `9A` | Accept Identity and Access Security doctrine, provider-neutral separation, fail-closed lifecycle/security invariants, and the fixed A-H roadmap. Documentation only. |
| `9B` | Implement the minimal neutral principal, authentication-outcome, authorization-decision, action/resource, and immutable security-context contracts. No authenticator, policy engine, session, HTTP adapter, provider, or application integration. |
| `9C` | Implement deterministic explicit authentication orchestration over caller-supplied evidence and typed verifier adapters, with ambiguity, strength/freshness, lifecycle, and non-disclosure rules. No session transport, authorization engine, HTTP integration, or concrete external provider. |
| `9D` | Implement deterministic deny-by-default authorization evaluation over explicit immutable policy evidence, exact action/resource inputs, and bounded decision receipts. No HTTP/session integration, provider policy language, database enforcement, or ambient guard. |
| `9E` | Implement operation-scoped security-context construction and generated Composition integration with exact cleanup and sequential/reentrant isolation. No HTTP credential transport, login flow, cookie/session profile, or deployment behavior. |
| `9F` | Implement one explicitly selected supported HTTP authentication/session transport profile and protected-capability enforcement integration, including CSRF/origin boundaries where applicable. No second provider/profile, UI, account recovery, administration console, or federation expansion. |
| `9G` | Implement one supported application identity/access workflow, deterministic policy build/currentness evidence, a minimal copy-once security skeleton, and narrowly bounded inspect/check tooling. No autonomous policy, broad identity management product, agent delegation runtime, or deployment manager. |
| `9H` | Externally prove authentication/authorization separation, deny-by-default enforcement, non-disclosure, lifecycle isolation, selected-profile security behavior, policy currentness, regressions, documentation, and the exact Phase 9 checkpoint. |

The sequence is ordered and closed. A later letter cannot begin until its own
authenticated directive authorizes it.

## Rejected alternatives

- Treating successful authentication, a role, a principal kind, a session, a
  route, or middleware presence as permission.
- Selecting provider, token, session, policy-language, or package mechanics in
  the architecture-only phase.
- Keeping security context in ambient process state or locating it through a
  container.
- Letting runtime discovery, external services, audit, telemetry, or AI become
  policy authority.

## Consequences

The accepted architecture can support Human, Service, Organization, System,
and Agent principal kinds without making kind membership an authorization
grant. It preserves provider neutrality and leaves domain identity records
application-owned. Actor, subject, tenant, principal, and future delegation
evidence remain distinct; this decision creates no delegation runtime.

Future implementations must use explicit typed contracts, deterministic and
provenance-bound policy evidence, ordinary generated PHP where generation is
authorized, operation cleanup, and existing diagnostic disclosure rules.
Framework-owned runtime policy scanning, ambient guards, provider object
leakage, invented cryptography, hidden fallback, runtime Reflection discovery,
mutable policy registries, remote policy loading, and AI authority are rejected.

Kongono remains pre-1.0. This decision makes no standards-compliance,
production-readiness, provider-support, cryptographic-strength, performance, or
deployment claim. The production package topology remains exactly thirty-six
acyclic packages at the accepted Phase 8 checkpoint.

## Open decisions

All runtime design within the later fixed letters remains open until separately
authorized, including exact neutral types, package names, policy source and
artifact formats, provider adapters, session and HTTP profiles, enforcement
integration, audit storage, telemetry fields, and the final checkpoint.
