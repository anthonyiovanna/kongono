# ADR 0041: Pure Two-Permission Trusted-Presentation Eligibility

## Status

Accepted

## Date

2026-08-15

## Context

ADRs 0039 and 0040 implemented independent typed bootstrap and delivery decisions.
Their open combined evaluation-and-enforcement boundary mixed a pure calculation
with future security-critical integration. Phase 1E resolves only the calculation.

## Decision

Diagnostic Activation owns the Experimental final readonly
`Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility`. Its
constructor promotes exactly private readonly `bootstrapPermission`, then
`deliveryPermission`, typed as `TrustedDeveloperBootstrapPermission` and
`AdapterControlledDeliveryPermission`. Its sole non-constructor public method is
`allowsTrustedPresentation(): bool`.

The method is the pure short-circuit conjunction of the two permission methods:

| Bootstrap permission | Delivery permission | Result |
| --- | --- | --- |
| `Denied` | `Denied` | `false` |
| `Denied` | `Allowed` | `false` |
| `Allowed` | `Denied` | `false` |
| `Allowed` | `Allowed` | `true` |

Every instance is scoped to one current delivery. It stores only the two enum
cases, not the result, evidence, delivery identity, external context, or failure.
Direct construction and a true result prove neither input provenance nor
authorization. A future outer adapter must supply both already-established
decisions and enforce the result before any diagnostic presentation access.

The existing package and dependency graph do not change. Diagnostic Presentation
remains separate and directly callable. This class accesses no configuration,
evidence, exception, diagnostic, projector, renderer, adapter, transport, or I/O.

## Rationale

A small immutable conjunction makes the Accepted truth table deterministic and
testable without moving evidence evaluation or enforcement into a neutral value.

## Security implications

Any denial returns false. Missing, failed, uncertain, ambiguous, expired, or
unverified external evidence must fail closed before construction. A true result
is not a capability and must not be stored, logged, serialized, transmitted,
cached, persisted, or reused. The value authenticates no operator, listener,
connection, request, or audience and never inspects ambient or client-controlled
state.

## Lifecycle and concurrency

Create the value only within one current delivery flow. Do not retain it in a
singleton, global, registry, container-wide service, worker cache, thread-local,
fiber-local, static, or previous-operation slot. One delivery's result cannot
authorize another request, job, message, command, tenant, user, operation, or
concurrent call.

## Performance implications

The calculation is constant bounded work with no I/O, retained derived result, or
side effect.

## Consequences

The pure calculation half of the combined boundary is resolved. Diagnostic
Activation now owns two permission enums and one eligibility class. External
provenance validation, presentation access, and enforcement remain absent.

## Rejected alternatives

- A boolean result property, capability, token, grant, registry, guard, policy
  service, or ambient authorization is rejected.
- A shared permission interface, trait, base class, helper, factory, alias,
  collection, parser, accessor, mutator, static API, or serializer is rejected.
- Evidence evaluation, authentication, configuration, request inspection,
  adapter or transport logic, diagnostic access, projection, rendering, response
  behavior, logging, telemetry, persistence, fallback, retry, and cleanup are
  rejected from this pure value.
- Package, dependency, exception, diagnostic identifier, Kernel, DI, Application
  Graph, extension, frontend, agent, database, or third-party integration changes
  are rejected.

## What remains open

- Outer adapter package, construction, and composition contract.
- Where and how the eligibility object is created and consumed.
- Enforcement before exception, diagnostic, or presentation access.
- Trusted-delivery evidence and its evaluation mechanism.
- Bootstrap configuration source, parser, validation failure, and reload.
- Non-trusted and activation-failure behavior.
- Authentication, listener, connection, proxy, and transport trust policy.
- HTTP, CLI, and other transport behavior.
- Production diagnostic model and allowlists.
- Simultaneous primary, activation, projection, rendering, transport, and cleanup
  failure representation.
- Logging, telemetry, incidents, serialization, localization, and persistence.
- Kernel lifecycle, DI wiring, Application Graph, extensions, frontend, and agents.
