# ADR 0110: Operation-Scoped Security Context and Generated Composition Inputs

- Status: Accepted
- Date: 2026-08-27
- Decision owners: Kongono architects
- Scope: Phase 9E only

## Context

Composition version 1 supplies every declared input when the generated
application starts. That remains correct for application-owned connectors and
configuration, but it cannot safely represent a principal or anonymous
Security Context. Security state belongs to exactly one operation and must not
be retained by the application assembly or leak between sequential,
overlapping, or reentrant work.

Phase 9B provides an immutable neutral `SecurityContext`, Phase 9C provides
authentication verification evidence, and Phase 9D provides independent
authorization evaluation. None of those phases constructs operation contexts
or integrates them with generated Composition. Authentication success remains
evidence and grants no authorization.

## Decision

### Composition source version 2

Composition version 1 is preserved: every version-1 input remains an
application-lifetime input. Version 2 requires every input to declare exactly
one `contract` and one `lifetime`, where lifetime is `application` or
`operation`.

Application services and application roots must not directly or transitively
reach operation inputs. Operation services may receive either input lifetime.
Invalid, duplicate, unknown, mixed, cyclic, cross-scope, or over-bound state
fails closed during build.

### Explicit operation input boundary

`GeneratedOperationInputs` is an immutable positional carrier for zero through
thirty-two caller-supplied objects. It preserves identity and order and exposes
only bounded count and generated-code validation. It is not a container,
registry, resolver, iterable, serialized value, or class-name lookup service.

Version-2 generated applications implement
`GeneratedOperationInputApplicationBoundary`. Their startup receives and
retains only application inputs. `beginOperationWithInputs()` validates exact
count, order, and native type compatibility before acquiring a lease or
constructing an operation. The validated objects flow directly through
ordinary named PHP constructor calls. The existing no-argument
`beginOperation()` remains compatible and delegates with an empty carrier.

Generated artifact metadata binds the Composition source version and generator
implementation identity. Generated operation roots and services are cleared
before cleanup begins; the application assembly retains no operation input.
Runtime reflection, autowiring, service location, containers, proxies, and
ambient state remain prohibited.

### Security Runtime outer package

The Experimental `kongono/security-runtime` package is the fortieth acyclic
production package. It depends only on PHP, API Metadata, Authentication,
Composition, Diagnostics, and Identity Access. No inward package depends on it.

`SecurityContextFactory` explicitly constructs either a fresh anonymous
context or an authenticated context from an exact successful
`AuthenticationVerification`. Authenticated construction requires present
facts and the same exact principal reference object in the outcome and facts.
Every non-success or inconsistent result fails through bounded package-owned
diagnostics and never downgrades to anonymous.

`GeneratedSecurityApplication` validates the exact version-2 generated profile
before startup: no application root, one public typed operation root, the new
operation-input boundary, and exactly one `SecurityContext` operation input.
Profile reflection is confined to this pre-start validation. Each explicit
operation entry creates one fresh context, passes that exact object into the
generated operation, and returns a `GeneratedSecurityOperation` owning the
boundary, typed marker root, and context.

Close permanently clears wrapper access before delegating generated reverse
cleanup. It runs once, remains idempotent, preserves primary-plus-cleanup
failure composition, and does not weaken active-operation shutdown refusal.

## Consequences

- Security Context identity is explicit, operation-scoped, and inspectable in
  generated constructor wiring.
- Version-1 applications retain their accepted source and runtime semantics.
- Version-2 callers must supply operation inputs for each operation that
  requires them.
- Build and startup reject stale or malformed profiles before operation work.
- The package graph contains exactly forty acyclic production packages.
- The added contracts remain Experimental while Kongono is pre-1.0.

## Rejected alternatives

- Capturing Security Context at application startup would widen its lifetime.
- Ambient current-user state, request singletons, thread/fiber locals, and
  static registries would hide authority and risk persistent-runtime leakage.
- A runtime container, resolver, reflection-based injector, or service locator
  would violate generated explicit-wiring doctrine.
- Treating authentication success as permission would collapse the required
  authentication/authorization separation.
- Inferring input lifetime from dependency reachability would hide a
  security-sensitive declaration.

## Non-goals

Phase 9E adds no authorization enforcement, protected-capability guard, policy
language/build/currentness, HTTP credential or session transport, login,
logout, cookie profile, provider adapter, workflow, skeleton, command, audit,
telemetry, database enforcement, or Phase 9F behavior.

## Related material

- [ADR 0069](0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0073](0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [ADR 0106](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0108](0108-deterministic-explicit-authentication-orchestration.md)
- [ADR 0109](0109-deterministic-deny-by-default-authorization-evaluation.md)
- [Operation-scoped Security Context](../architecture/operation-scoped-security-context.md)
