# ADR 0107: Neutral Identity and Access Contracts and Package Boundary

## Status

Accepted

## Date

2026-08-27

## Context

Phase 9A fixed the separation among identity, authentication, authorization,
transport, enforcement, disclosure, audit, and telemetry. Phase 9B needs the
smallest provider-neutral vocabulary that later work can consume without
introducing an authenticator, policy evaluator, session, transport, or ambient
security authority.

## Decision

Create the Experimental `kongono/identity-access` package under
`Kongono\IdentityAccess\`. It depends in production only on
`kongono/api-metadata`, `kongono/diagnostics`, and PHP `>=8.5.0 <8.6.0`.
No existing production package depends on it, and its addition brings the
current graph to exactly thirty-seven acyclic production packages.

The package owns these exact neutral values:

- `PrincipalKind` has only Human, Service, Organization, System, and Agent;
- `PrincipalId` is an exact opaque 1-512 byte valid-UTF-8 value without C0 or
  C1 controls, and `PrincipalReference` pairs it with one kind;
- `AuthenticationOutcomeKind` has Authenticated, Absent, Invalid, Expired,
  Replayed, Unsupported, Ambiguous, and Unavailable, while
  `AuthenticationOutcome` carries a principal only for Authenticated;
- `AuthorizationDecision` has only Permit, Deny, and Indeterminate and
  evaluates nothing;
- `Action` and `ResourceType` are 1-128 byte canonical application-owned ASCII
  names matching `^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$`;
- `ResourceId` has the same opaque bounds as `PrincipalId`, and
  `ResourceReference` pairs it with one resource type; and
- `SecurityContext` is explicitly Anonymous without a principal or
  Authenticated with exactly one principal reference.

All those values are final readonly classes or backed enums and Experimental.
They expose only the exact accessors, equality methods, and factories needed by
their contracts. They perform no I/O, global reads, discovery, Reflection,
serialization, verification, authorization, domain loading, normalization,
or lookup.

The package owns five Internal diagnostics, `KGN-ACCESS-1001` through
`KGN-ACCESS-1005`, with only Safe bounded `package`, `failure_category`, and
`remediation` context. The package exception marker is Public; the abstract
base is Internal; the concrete invalid-value exception is Experimental.
Rejected values never enter messages, diagnostic context, safe string form, or
retained state. The base's final string form is only the diagnostic identifier
and title.

## Security and lifecycle consequences

Principal and resource identifiers are opaque even though callers can request
their exact value. Their accessors do not classify the bytes as Safe diagnostic
or display data. Authentication success grants no authorization. Permit is
inert vocabulary until a later separately authorized evaluator and protected
capability use it. An authenticated Security Context validates shape only and
does not prove how authentication occurred.

The package retains no mutable or static state and owns no cleanup behavior.
Later operation lifecycle integration remains outside Phase 9B.

## Rejected alternatives

- Provider objects, credentials, password or token types, cryptographic
  primitives, roles, policy, receipts, or sessions in the neutral package.
- Boolean authentication or authorization results and nullable fallthrough.
- Ambient current-user state, service location, container access, runtime
  discovery, or Reflection.
- Making identifier bytes stringifiable, serializable, debug-exportable, or
  diagnostic data.
- Adding HTTP, Composition, Console, persistence, provider, audit, telemetry,
  or application integration dependencies.

## Current boundary

Phase 9B implements neutral inert contracts only. It implements no
authenticator, verifier adapter, credential, cryptography, policy source,
evaluator, decision receipt, session, cookie, CSRF behavior, middleware, HTTP
adapter, Composition wiring, enforcement, provider, command, schema, cache,
audit, telemetry, login, recovery, delegation, or application workflow.
Phase 9C remains unimplemented and is the only next architect-review handoff.

## Related decisions

- [ADR 0105](0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106](0106-fail-closed-security-context-and-policy-separation.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Phase 9 roadmap](../architecture/phase-9-roadmap.md)
