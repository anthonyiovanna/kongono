# Kongono Authorization

## Purpose and current scope

`kongono/authorization` implements deterministic deny-by-default authorization
evaluation over explicit caller-supplied values and opaque evidence. It is
Experimental. Its only production dependencies are API Metadata, Diagnostics,
Identity Access, and PHP `>=8.5.0 <8.6.0`.

The package evaluates one request and returns inert bounded evidence. It does
not enforce a protected capability or make a read or side effect proceed. Only
an exact `AuthorizationDecision::Permit` is a permit value; every other outcome
or failure remains fail closed.

## Exact inputs

- `AuthorizationPolicyName`, `AuthorizationPolicyVersion`, and
  `AuthorizationPolicyProvenance` form one exact policy reference. Version has
  no ordering or SemVer meaning, and provenance is not a loader or trust proof.
- `AuthorizationPolicyEvidence` exposes only that reference. Concrete policy
  rules and currentness validation remain application-owned.
- `AuthorizationEnvironmentProfile` identifies one exact application-owned
  environmental facts shape. `AuthorizationEnvironmentEvidence` exposes only
  that profile, not its payload.
- `AuthorizationRequest` preserves the exact explicit Security Context, Action,
  Resource Reference, expected policy reference, policy evidence, and
  environmental evidence supplied by the caller.
- `AuthorizationEvaluator` identifies one exact complete policy reference and
  evaluates the identical request object.

The package does not inspect, clone, serialize, stringify, log, persist, load,
or discover policy or environmental payloads.

## Evaluation algorithm

An engine registers one through thirty-two evaluators with unique exact policy
references. Registration is explicit and ordered, with no discovery, priority,
`supports()` probe, fallback, downgrade, retry, service location, or mutable
registry.

For each call, the engine reads the policy-evidence reference exactly once. A
mismatch with the independently expected reference returns Indeterminate with a
PolicyMismatch receipt and invokes no evaluator. Matching evidence without an
exact registered evaluator returns Indeterminate with an UnsupportedPolicy
receipt. Otherwise, the sole exact evaluator receives the identical request
exactly once, and its Permit, Deny, or Indeterminate result is preserved.

If evaluation throws, `AuthorizationEvaluationException` retains the exact
cause only as native previous. No evaluation is returned and failure never
becomes Permit.

## Receipt and disclosure boundary

`AuthorizationDecisionReceipt` contains only the decision, reason, expected
policy reference, Action, Resource Type, Security Context state, and environment
profile. PolicyMismatch and UnsupportedPolicy receipts are always
Indeterminate. `AuthorizationEvaluation` derives its decision from its sole
receipt.

A receipt retains no principal/resource identifier, policy or environment
payload, evaluator, exception, message, trace, path, URL, tenant, role,
permission, claim, credential, key, token, session, or provider value. It has no
guard, enforcement, replay, or serialization behavior and is not transferable
authority.

Diagnostics expose only Safe package, failure-category, and remediation fields.
Rejected input and opaque evidence do not enter bounded exception strings or
diagnostic context.

## Lifecycle and calling contract

The engine retains only immutable evaluator registration. Requests, evidence,
environmental facts, decisions, receipts, and failures remain local to one
call. Sequential and reentrant calls share no current-call state. Callers must
supply a fully constructed Security Context and independently expected current
policy identity; this package does not construct or validate either lifecycle.

The engine reads no clock, randomness, globals, request, session, environment
variables, filesystem, network, cache, container, Kernel, or ambient current-
user state.

## Not implemented here

This package implements no policy language, parser, compiler, schema, generated
policy class, publisher, loader, filesystem or remote discovery, provider SDK,
database policy, roles, permissions, groups, claims, tenancy, ownership,
relationships, ACL/RBAC/ABAC/ReBAC semantics, Security Context construction,
operation lifecycle integration, Composition wiring, HTTP/session/cookie/token
transport, ambient guard, protected-capability enforcement, audit, telemetry,
command, UI, AI authority, application workflow, deployment behavior, or Phase
9E capability.

See [ADR 0109](../../docs/adr/0109-deterministic-deny-by-default-authorization-evaluation.md),
the [Identity and Access Security guide](../../docs/architecture/identity-and-access-security.md),
and the [Phase 9 roadmap](../../docs/architecture/phase-9-roadmap.md).
