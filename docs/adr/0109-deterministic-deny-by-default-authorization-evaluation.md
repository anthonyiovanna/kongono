# ADR 0109: Deterministic Deny-by-Default Authorization Evaluation

## Status

Accepted

## Date

2026-08-27

## Context

Phase 9A separated identity, authentication, authorization, transport,
protected-capability enforcement, disclosure, audit, and telemetry. Phase 9B
implemented neutral identity and access values, and Phase 9C implemented
provider-neutral authentication orchestration. Phase 9D needs the first real
authorization evaluation boundary without selecting an application policy
language, role or permission model, policy loader, transport, enforcement
mechanism, or ambient current-user authority.

Policy currentness must fail closed. The application must independently supply
the expected policy identity and opaque evidence identifying what it actually
provided. Evaluator selection, error handling, bounded result evidence, and
persistent-runtime isolation therefore require exact framework-owned rules.

## Decision

Create the Experimental `kongono/authorization` package under
`Kongono\Authorization\`. It depends in production only on
`kongono/api-metadata`, `kongono/diagnostics`, `kongono/identity-access`, and PHP
`>=8.5.0 <8.6.0`. No existing production package depends on Authorization. The
addition brings the graph to exactly thirty-nine acyclic production packages.

The package owns exact immutable policy identity through
`AuthorizationPolicyName`, `AuthorizationPolicyVersion`,
`AuthorizationPolicyProvenance`, and `AuthorizationPolicyReference`. Name uses
canonical ASCII syntax within 1-64 bytes; version and provenance use the same
syntax within 1-128 bytes. Version has no SemVer, ordering, recency, or
compatibility meaning. Provenance is a caller-assigned identity, not a path,
URL, signature, key, digest algorithm, loader, or trust decision.

`AuthorizationPolicyEvidence` exposes only `reference()`. Concrete policy data
and its build or validation lifecycle remain application-owned.
`AuthorizationEnvironmentProfile` identifies one exact application-owned facts
shape, and `AuthorizationEnvironmentEvidence` exposes only `profile()`. Neither
interface exposes raw rules, roles, claims, arrays, sessions, provider objects,
serialization, discovery, loading, or mutation.

`AuthorizationRequest` contains exactly one explicit `SecurityContext`,
`Action`, `ResourceReference`, expected policy reference, policy evidence, and
environmental evidence. It preserves the exact supplied object identities and
reads no ambient state.

## Exact evaluation

`AuthorizationEngine` is constructed from an ordered list of one through
thirty-two explicit `AuthorizationEvaluator` objects with unique complete
policy references. Registration preserves caller order but defines no priority,
discovery, `supports()` probe, fallback, sorting, provider lookup, or mutable
registry. Each call follows this order:

1. read the policy-evidence reference exactly once;
2. if it differs from the independently expected full reference, return
   Indeterminate with a PolicyMismatch receipt and invoke no evaluator;
3. select only the evaluator whose name, version, and provenance all equal the
   expected reference;
4. if no evaluator matches, return Indeterminate with an UnsupportedPolicy
   receipt;
5. invoke the selected evaluator exactly once with the identical request;
6. preserve its exact Permit, Deny, or Indeterminate decision in an Evaluated
   receipt; and
7. if evaluation throws, wrap the exact cause as native previous in
   `AuthorizationEvaluationException` and return no evaluation.

Only exact Permit is a permit value. Mismatch, unsupported policy, Deny,
Indeterminate, and evaluator failure never become permission. The engine does
not retry, downgrade, consult another evaluator, or enforce the result.

## Receipt boundary

`AuthorizationDecisionReceipt` records exactly decision, receipt reason,
expected policy reference, action, resource type, Security Context state, and
environment profile. Mismatch and UnsupportedPolicy factories always produce
Indeterminate. `AuthorizationEvaluation` contains exactly one receipt and
derives its decision from that receipt, so inconsistent state is impossible.

Receipts retain no principal or resource identifier, evidence payload,
environmental fact payload, evaluator, exception, message, trace, path, URL,
tenant, role, permission, claim, key, credential, token, session, or provider
data. They have no permit, guard, enforcement, replay, serialization, or bearer-
capability behavior. A receipt is bounded evidence about one call, never
transferable authority for another action, resource, policy state, tenant,
actor, subject, or operation.

## Diagnostics and failures

The package owns Internal diagnostics `KGN-AUTHORIZATION-1001` through
`KGN-AUTHORIZATION-1006` for validation, registration, policy evidence,
currentness, evaluation, and receipt failures. Each fresh diagnostic context
contains only Safe `package`, `failure_category`, and `remediation` fields.
Rejected values, policy identities, environment profiles or payloads,
principal/resource identifiers, evaluators, causes, messages, traces, paths,
URLs, roles, tenants, permissions, and secrets never enter bounded diagnostics
or exception stringification.

The empty package exception marker is Public. The abstract diagnostic base and
diagnostic catalog are Internal. Validation and evaluator-failure exceptions
are Experimental. Evaluator failure is retained only as native previous and is
never converted into Permit.

## Lifecycle and security consequences

The engine retains only immutable evaluator registration. Request, evidence,
environment, decision, receipt, and failure state are local to one call.
Sequential and reentrant evaluations share no current-call state. The package
reads no clock, randomness, globals, process state, request, session,
environment variables, filesystem, network, cache, container, Kernel, or
mutable registry.

Evaluation is not enforcement. The package performs no protected read,
database/external effect, secret disclosure, filesystem publication, mutation,
Security Context construction, operation cleanup, or later-use authorization.
Protected-capability enforcement remains unimplemented.

## Rejected alternatives

- Boolean authorization results or treating non-Permit states as permission.
- Partial policy matching, latest-version selection, priority, fallback,
  downgrade, retry, or `supports()` probing.
- Policy languages, parsers, compilers, schemas, generated policy classes,
  publishers, loaders, filesystem or remote discovery, caches, provider SDKs,
  database policy, or ORM behavior in Phase 9D.
- Roles, permissions, claims, groups, tenancy, ownership, relationship,
  ACL/RBAC/ABAC/ReBAC, or other application policy semantics in the framework.
- Ambient guards, current-user globals, service location, middleware authority,
  HTTP/session transport, Composition wiring, or enforcement in this package.
- Audit storage, telemetry, commands, UI, federation, delegation, or AI
  authority as policy or permission.

## Current boundary

Phase 9D implements only deterministic deny-by-default evaluation and bounded
receipts. It implements no policy language/build/currentness artifact or loader,
role/permission model, Security Context construction or lifecycle, Composition
wiring, protected-capability enforcement, HTTP/session transport, database
enforcement, audit, telemetry, provider integration, application workflow, or
Phase 9E behavior. Phase 9E remains unimplemented and is the only next
architect-review handoff.

## Related decisions

- [ADR 0105](0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0107](0107-neutral-identity-and-access-contracts-and-package-boundary.md)
- [ADR 0108](0108-deterministic-explicit-authentication-orchestration.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Phase 9 roadmap](../architecture/phase-9-roadmap.md)
