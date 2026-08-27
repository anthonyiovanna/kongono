# ADR 0111: Secure Browser-Session Transport and Capability Enforcement

- **Status:** Accepted
- **Date:** 2026-08-27
- **Decision type:** Phase 9F implementation boundary

## Context

Phases 9B through 9E established neutral identity values, explicit authentication, deny-by-default authorization, and operation-scoped Security Context construction. They intentionally did not select an HTTP credential transport or place authorization directly around a protected read or effect. Phase 9F must supply those two outer boundaries without making authentication, routing, middleware, or an authorization receipt into authority.

## Decision

Kongono implements exactly one Experimental HTTP transport profile in `kongono/security-http`: `browser.session.v1`. It accepts only the host-only cookie `__Host-kongono_session`, uses the request header `X-Kongono-CSRF` for state-changing requests, and selects the authentication mechanism `browser.session.v1`. Session identifiers and CSRF proofs are exactly 43 ASCII base64url characters with no padding or normalization.

The adapter reads only a supplied PSR-7 server request and an explicit authentication instant. A reserved cookie is accepted only from the exact configured HTTPS origin. `GET`, `HEAD`, and `OPTIONS` are the only safe methods. Every other cookie-bearing request requires an exact matching `Origin` and one structurally valid CSRF proof, which the selected verifier remains responsible for binding to a live session. An otherwise valid request without the reserved cookie begins one anonymous operation. Any malformed, ambiguous, untrusted, or non-authenticated cookie path fails closed without anonymous downgrade.

Cookie issuance and expiration are deterministic header-value operations. They do not generate an identifier, establish session truth, perform cryptography, access storage, mutate a response, or implement login or logout.

Kongono also implements the Experimental `kongono/security-enforcement` package. A `ProtectedCapability` names its exact Action and Resource Reference and exposes one execution method. `ProtectedCapabilityEnforcer` proves identical operation context and equal action/resource bindings, evaluates authorization exactly once, and invokes the capability exactly once only for exact `Permit`. It returns the exact result. Mismatch, `Deny`, and `Indeterminate` do not execute. Authorization and capability failures retain their accepted semantics and are never retried.

Both boundaries are explicit and stateless. They retain no current user, session, request, context, decision, receipt, capability, result, or failure between calls. A receipt remains evidence only and cannot execute a capability.

## Package boundaries

`kongono/security-http` depends only on PHP, API Metadata, Authentication, Diagnostics, Security Runtime, and PSR HTTP Message interfaces. `kongono/security-enforcement` depends only on PHP, API Metadata, Authorization, Diagnostics, and Identity Access. The resulting production graph contains exactly forty-two acyclic packages.

## Security and failure behavior

Transport and enforcement diagnostics contain only fixed Safe labels. They do not disclose cookie, CSRF, origin, principal, action, resource, policy, environmental, capability, result, or failure values. Request attributes, routing, middleware, authentication success, and receipts grant no authority. Only exact `Permit` at the protected-capability boundary permits the effect.

## Consequences

Applications must create, store, rotate, expire, and invalidate session identifiers and must implement the exact verifier, including reviewed constant-time CSRF/session binding where required. Applications must also construct exact authorization requests and place protected effects behind capabilities. Kongono adds no provider, policy language, account workflow, session store, second HTTP profile, skeleton, command, or deployment behavior.

## Stability and next handoff

The two packages are Experimental. Phase 9F is complete after its full acceptance gates pass. Phase 9G is the only next architect-review handoff and is not implemented here.
