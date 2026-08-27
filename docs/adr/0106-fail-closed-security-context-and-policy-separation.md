# ADR 0106: Fail-Closed Security Context and Policy Separation

## Status

Accepted

## Date

2026-08-27

## Context

Identity proof and access permission have different inputs, failure modes, and
lifecycle requirements. Combining them into a current-user object, middleware
flag, role check, session value, route declaration, or provider result would
hide authority and permit state to leak across operations in a persistent
runtime. It would also make failure and disclosure behavior depend on ambient
host state instead of exact validated evidence.

Kongono therefore needs a fail-closed security boundary before any neutral
contracts or adapters are designed. The boundary must preserve application
ownership of policy meaning, provider neutrality, deterministic build evidence,
explicit protected-capability enforcement, and operation-scoped cleanup.

## Decision

Authentication and authorization remain separate operations. Authentication
accepts caller-supplied detached evidence through one explicitly selected typed
verifier adapter. Success carries only validated authentication facts.
Invalid, absent, expired, replayed, unsupported, ambiguous, infrastructure, and
other non-success outcomes remain distinct and cannot be represented as a
boolean, `null`, untyped array, raw provider object, or exception-driven normal
outcome. No fallback may silently weaken the selected mechanism.

Authorization consumes one immutable operation-scoped security context, one
exact application-owned action, one exact application-owned resource, explicit
current policy evidence, and any explicit environmental facts. Its bounded
decision vocabulary is `Permit`, `Deny`, and `Indeterminate`. Only exact
`Permit` grants progress. Missing, stale, partial, mixed, ambiguous,
unsupported, conflicting, unverifiable, or failed policy evidence must fail
closed. A principal kind, role name, route match, middleware flag, session,
Dependency Injection binding, Application Graph node, repository method,
Structured CLI command discovery, or diagnostic mode is never permission.

Enforcement belongs at each protected capability and must occur before its
protected read, database or external effect, secret disclosure, file
publication, or mutation-capable command apply. Transport middleware may adapt
evidence and reject invalid requests, but it is not the sole authorization
boundary. A decision is bounded evidence for its exact evaluation; it is not an
ambient or transferable capability. Later implementation must address
time-of-check/time-of-use risk where the protected operation can outlive or
invalidate its inputs.

The security context is immutable, explicit, and scoped to one operation. An
outer adapter constructs it only from validated evidence, and generated typed
wiring or explicit collaborators carry it inward. It is never process-global,
static, thread-local, resolver-provided, request-singleton authority, or Kernel
state. Anonymous is explicit rather than `null`. Retirement and reverse cleanup
release all operation-owned authentication, authorization, and credential
references so sequential and overlapping operations cannot inherit state.

Policy truth must be deterministic, versioned, provenance-bound build evidence
validated before runtime. Runtime scanning, Reflection-based policy discovery,
mutable registries, dynamic proxying, `eval`, newest-file selection, remote
policy loading, and network compilation are rejected. This decision selects no
policy source, schema, language, compiler, digest, package, cache, or generated
class. Any future cache must have complete explicit keys, bounded lifetime,
safe invalidation, and no authority widening.

Existing diagnostic disclosure rules apply. Public outcomes may deliberately
collapse distinctions that would reveal credential state, policy structure,
principal or resource existence, tenancy, provider details, keys, replay
material, or secrets. Trusted internal evidence may retain exact bounded
diagnostic identity but still grants no authorization. Authentication outcomes,
authorization decisions, diagnostics, exceptions, audit records, and telemetry
are distinct. Exceptions do not represent ordinary `Permit` or `Deny`.

Credentials and cryptographic material remain opaque. They must not be
stringified, logged, retained beyond their lifecycle, emitted through CLI or
machine output, or copied into diagnostics. Kongono invents no cryptographic
primitive. Any future implementation must use reviewed current PHP facilities,
standards, or explicitly authorized libraries behind typed adapters and define
the evidence lifecycle. This architecture makes no concrete browser-session,
cookie, bearer-token, passkey, federation, route, form, status-code, or provider
selection.

## Rejected alternatives

- A boolean or nullable authentication result that erases why evidence did not
  establish authenticated facts.
- Permit-by-default evaluation, exception-to-permit conversion, or fallback to
  stale, partial, ambiguous, or weaker evidence.
- Middleware-only authorization, route-as-permission, and current-user globals.
- Runtime policy scanning, mutable policy registries, remote policy loading,
  invented cryptography, or provider objects as neutral framework contracts.
- Treating diagnostics, receipts, audit records, telemetry, or developer mode
  as authorization authority.

## Consequences

Persistent-runtime reuse is safe only when every operation constructs and
retires its own context and no ambient security state survives cleanup.
Application code receives understandable typed collaborators and safe bounded
diagnostics; deterministic tests can remain independent of network, clock,
globals, and real secrets by supplying explicit evidence and adapters.

Audit receipts, when later authorized, will be bounded non-secret evidence.
They cannot replay a grant, become policy authority, or turn OpenTelemetry into
an authorization engine. Storage, retention, privacy, and operational policy
remain later decisions.

Phase 9A implements none of these runtime forms. New implementation contracts
remain Experimental unless a later decision explicitly promotes them. Internal
implementation detail remains Internal by default, and any future Public
security contract carries deliberate compatibility and security obligations.

## Open decisions

Later separately authorized phases must choose the exact neutral vocabulary,
verifier lifecycle, policy source/model/artifact, evaluator mechanics,
Composition integration, selected HTTP/session transport, protected-capability
profile, receipt shape, audit/telemetry policy, and acceptance evidence. Phase
9A selects none of them.
