# Operation-Scoped Security Context

## Status and purpose

Phase 9E implements the Experimental `kongono/security-runtime` package and
Composition version-2 operation inputs. It makes one explicit anonymous or
authenticated `SecurityContext` a real input to one generated operation while
keeping it out of generated application state.

Use this boundary when an application-owned generated operation root requires
the exact context for its invocation. Do not use it as authorization evidence,
a protected-capability guard, credential/session transport, login behavior, or
ambient current-user access. Authentication success grants no authorization.

## Construction flow

```text
explicit anonymous intent OR exact AuthenticationVerification
  -> SecurityContextFactory
  -> one fresh immutable SecurityContext
  -> GeneratedOperationInputs::of(the exact context)
  -> generated beginOperationWithInputs()
  -> exact count/order/type validation before lease or construction
  -> ordinary constructor injection into one operation graph
  -> one typed SecurityOperationRoot and GeneratedSecurityOperation owner
```

Anonymous construction is explicit. Authenticated construction accepts only an
exact `Authenticated` outcome with present facts whose principal reference is
the same exact object carried by the outcome. Absent, Invalid, Expired,
Replayed, Unsupported, Ambiguous, Unavailable, missing-facts, and inconsistent
success states fail closed; none silently becomes anonymous or authenticated.

The resulting context contains only its anonymous/authenticated state and
optional principal reference. Credential, mechanism, strength, timestamps,
provider state, claims, evidence, policy, action, resource, and decision are not
copied into it.

## Generated profile contract

Before generated startup, `GeneratedSecurityApplication` validates:

- the generated application implements
  `GeneratedOperationInputApplicationBoundary`;
- there is no generated application root;
- operation-input metadata is exactly `[SecurityContext::class]`;
- there is exactly one public, non-static, zero-argument `operationRoot1()`;
  and
- that root has a non-null named return type implementing
  `SecurityOperationRoot`.

All profile reflection completes before startup. Operation entry performs no
reflection, class lookup, scanning, autowiring, resolution, or service
location. The metadata is bounded startup evidence, not lookup or authority.

## Composition source contract

Version 1 remains compatible: an input record has only `contract`, and every
such input remains application-lifetime. Version 2 requires:

```json
{"contract":"Kongono\\IdentityAccess\\SecurityContext","lifetime":"operation"}
```

Application inputs are supplied at generated startup. Operation inputs are
supplied for one `beginOperationWithInputs()` call and validated before an
operation lease is acquired. An application service or root cannot reach an
operation input; an operation service may consume either input lifetime.

The generated application never stores operation inputs. Generated operations
receive them directly through explicit named constructor calls. The old
no-argument `beginOperation()` remains available and succeeds only when the
version-2 profile expects zero operation inputs.

## Lifecycle and concurrency

`GeneratedSecurityOperation` owns exactly one generated operation boundary,
one typed root, and the exact context passed into that operation graph. While
open, `root()` and `securityContext()` return those objects. `close()` marks the
wrapper closed and clears all three references before delegating once to
generated cleanup. Later access fails through a bounded non-disclosing
lifecycle exception; repeated close is inert.

Generated reverse cleanup and primary-plus-cleanup failure ordering remain
authoritative. Root-extraction failure closes the newly acquired operation with
that exact failure before rethrowing. Application shutdown refuses active
operations and succeeds after all operations close.

Sequential, overlapping, and reentrant operations receive distinct contexts
and roots. Closing or failing one does not alter another. The application
boundary retains neither principal nor anonymous operation state.

## Security and failure disclosure

Security Runtime diagnostics use the contiguous
`KGN-SECURITY-RUNTIME-1001` through `KGN-SECURITY-RUNTIME-1003` range. Context
contains fixed Safe package and failure-category fields only. Exception strings
do not include principal values, credentials, evidence, mechanisms, policy,
actions, resources, routes, requests, sessions, input values, class names,
paths, URLs, native messages, or stack arguments.

Invalid source, profile, verification, lifecycle, or cleanup state fails
visibly. No failure permits, authenticates, retries cleanup, or widens
authority.

## Package and stability boundary

`kongono/security-runtime` depends only on PHP, API Metadata, Authentication,
Composition, Diagnostics, and Identity Access. It is an outer package; only the
later outer `kongono/security-http` adapter depends on it. The production graph
is exactly forty-four acyclic packages.

The package and Composition version-2 APIs are Experimental. Phase 9F now
enters these operations through exactly one same-origin HTTPS browser-session
profile and places exact-Permit enforcement at a separate protected-capability
boundary. The HTTP adapter retains no context after return; the returned owner
delegates exact root/context access and close. Authentication success, routing,
middleware, and receipts grant no authority. No ambient current-user/session/
decision state or second profile/provider exists. Phase 9G's upper supported
workflow uses this exact operation context for one authorization request and
closes its owner once on every outcome; it does not move context into ambient
state. Phase 9H is the only next architect-review handoff.

## Related material

- [ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md)
- [ADR 0111](../adr/0111-secure-browser-session-transport-and-capability-enforcement.md)
- [ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Browser-session security and capability enforcement](browser-session-security-and-capability-enforcement.md)
- [Security application workflow](security-application-workflow.md)
- [Composition source](composition-source.md)
- [Generated Composition wiring](generated-composition-wiring.md)
- [Phase 9 roadmap](phase-9-roadmap.md)
