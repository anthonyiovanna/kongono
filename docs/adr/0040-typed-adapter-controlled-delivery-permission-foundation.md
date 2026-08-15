# ADR 0040: Typed Adapter-Controlled Delivery Permission Foundation

## Status

Accepted

## Date

2026-08-15

## Context

[ADR 0038](0038-fail-closed-trusted-developer-diagnostic-activation.md)
requires affirmative trusted bootstrap permission and independent adapter-
controlled delivery permission before trusted diagnostic presentation access.
[ADR 0039](0039-typed-trusted-developer-bootstrap-permission-foundation.md)
records Phase 1C's implementation of only the bootstrap input. Before Phase 1D,
the second policy input had no executable type.

The outer adapter must remain the sole owner of the evidence, evaluation, and
authority used to decide whether one current delivery boundary is eligible.
Diagnostic Activation needs only a neutral typed representation of the decision
after that evaluation. It must not inspect transport state, authenticate an
operator, infer trust, access presentation data, or combine the two policy
inputs.

## Decision

Add exactly one production declaration to the existing
`kongono/diagnostic-activation` package:

```php
Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission
```

It is an Experimental unbacked enum with ordered cases `Denied`, then `Allowed`,
and the sole explicit method `allowsTrustedPresentation(): bool`. The method
returns `true` only for `Allowed` by enum-case identity and returns `false` for
`Denied`.

`Denied` means the trusted path is prohibited regardless of the bootstrap value.
`Allowed` means only that a future outer adapter has affirmatively selected
permission for one current delivery boundary. It does not authenticate an
operator, establish a trusted listener or connection, prove transport trust,
authorize an audience, or permit exception access, diagnostic metadata or
context access, Safe or Protected field copying, projection, rendering,
persistence, logging, transmission, or delivery by itself.

The future outer adapter retains sole ownership of the evidence, mechanism,
evaluation, and authority used to choose a case. Missing, unavailable, failed,
uncertain, ambiguous, expired, or unverified evidence must fail closed to
`Denied` at that future boundary and must never become `Allowed`.

The enum is unbacked and exposes no `from()` or `tryFrom()` raw-value parser.
Case names are PHP source API names, not request, header, configuration,
authentication, network, or transport tokens. Selecting `Allowed` directly in
PHP proves only which language-level singleton was selected. It does not prove
delivery identity, freshness, evidence, or authorization.

Diagnostic Activation now owns exactly the two neutral typed inputs:

```text
TrustedDeveloperBootstrapPermission
AdapterControlledDeliveryPermission
```

The bootstrap value is fixed independently for a future adapter-instance
lifetime. The delivery value represents one current delivery decision and must
be evaluated independently for every delivery. An affirmative value cannot be
reused for another request, job, message, command, tenant, user, fiber, worker
operation, concurrent call, or later delivery. Neither value may change or
imply the other.

Both typed inputs now exist, but Phase 1D adds no combined eligibility
calculation, guard, policy service, enforcement boundary, or presentation call.
Diagnostic Presentation remains a separate directly callable leaf with no
dependency on Diagnostic Activation and cannot enforce the outer rule.

This decision adds no package or dependency because the existing package and
its API Metadata edge already own neutral activation-policy values. It adds no
exception, diagnostic identifier, configuration, transport, adapter,
presentation access, I/O, or ambient state.

ADR 0040 narrowly supersedes ADR 0039's broad rejection of any delivery-
permission value in Diagnostic Activation only to permit this neutral typed
representation. It does not supersede the future adapter's ownership of the
decision mechanism, the independence of the two permissions, or ADR 0039's
rejection of combined evaluation and enforcement in that phase.

## Rationale

A small immutable enum makes the second Accepted policy input explicit and
machine-checkable without stabilizing evidence, authentication, transport, or
adapter contracts. An unbacked type avoids an implicit raw parser surface.
Keeping only the neutral representation beside the bootstrap representation
preserves package neutrality while leaving the security-critical decision and
enforcement at the future outer adapter boundary.

## Security implications

The enum is not a capability, token, grant, authorization proof, authentication
result, or security boundary. No secret string, marker, constructor restriction,
package-visibility convention, or API attribute changes that fact. Trusted
presentation still requires both independent permissions to be affirmative
before any presentation access; `Allowed` on either enum is insufficient alone.

The enum does not inspect a request, route, header, cookie, body, session,
token, hostname, origin, referrer, IP address, socket, TLS state, client
certificate, proxy assertion, frontend value, or command-line input. No
environment label, truthy string, AI output, heuristic, exception value, API
status, database value, provider response, extension, agent, prior operation,
global, registry, container, thread-local, or fiber-local state may select or
upgrade it. It carries no delivery identifier, subject, audience, address,
credential, timestamp, expiry, evidence, reason, or mutable state.

No exception, diagnostic metadata or context, projector, renderer, rendered
document, configuration source, filesystem, network, logger, telemetry system,
event system, persistence layer, serializer, cache, Kernel, Application Graph,
Dependency Injection service, Diagnostics value, request, response, transport,
or adapter is accessed.

## Lifecycle and concurrency

The enum is immutable, stateless, deterministic, and I/O-free. PHP enum cases
are language-level singletons, so object identity identifies only the chosen
case and never a delivery, its freshness, or its authority. The future adapter
must evaluate its evidence independently for each delivery and pass the value
only within that delivery's explicit call flow. It must not retain an
affirmative decision as static, ambient, global, previous-operation, thread-
local, or fiber-local current state.

The separately fixed bootstrap value cannot be changed or upgraded by a
delivery decision. Phase 1D selects no adapter lifetime, operation-scope field,
reset hook, container lifetime, concurrency primitive, or persistence rule.

## Performance implications

The identity comparison is constant bounded work and allocates no retained
state. The type performs no parsing, evidence evaluation, I/O, environment or
configuration lookup, authentication, network access, logging, telemetry,
serialization, caching, projection, rendering, or delivery.

## Consequences

Both inputs to the Accepted fail-closed policy have exact Experimental types,
and the pure combined evaluation and neutral Handler sequencing are implemented.
Future adapter work can receive an already-determined per-delivery value without
moving transport evidence or authority into Diagnostic Activation.

The current runtime has eight packages with the one-way Handler dependencies.
Diagnostic Presentation remains an independent lower layer, directly callable, and unable to
authorize its caller. No configuration loader, adapter, request, response,
transport, served page, production model, runtime integration, failure family,
or diagnostic identifier is created.

## Rejected alternatives

- A backed enum, string or integer value, raw parser, `from()`, or `tryFrom()` is
  rejected because external tokens and parsing ownership remain unselected.
- A boolean, generic permission collection, shared interface, trait, abstract
  type, helper, alias, factory, serializer, or mutable permission object is
  rejected because the exact neutral decision has a closed typed surface.
- Evidence evaluation, authentication, operator identity, listener or network
  policy, proxy assertions, IP/CIDR logic, TLS or certificate logic, and request
  inspection are rejected because they belong to a future outer adapter.
- A combined evaluator, eligibility service, guard, capability, token, grant,
  registry, service locator, or ambient current state is rejected because
  Phase 1D implements only the second independent value.
- Configuration keys, environment variables, files, loaders, schemas,
  validation exceptions, reload, and watchers are rejected as future bootstrap
  composition work.
- Exception, diagnostic, projector, renderer, document, source, trace, request,
  response, middleware, routing, server, transport, HTTP, CLI, served-page, and
  production-presentation access are rejected as later boundaries.
- Logging, telemetry, traces, metrics, incidents, events, retry, recursion,
  cleanup, persistence, serialization, localization, and multi-failure behavior
  are rejected as deferred operational policy.
- Kernel lifecycle, operation scope, DI wiring, Application Graph integration,
  extensions, plugins, frontend, agents, ORM, database, and third-party
  dependencies are rejected as unrelated later capabilities.
- A dependency from Diagnostic Presentation to Diagnostic Activation is
  rejected because low-level presentation remains separate and directly
  callable.

## What remains open

- Exact outer adapter package and composition contract.
- Trusted-delivery evidence and evaluation mechanism.
- Authentication, listener, connection, proxy, and transport trust policy.
- Outer-adapter evidence, eligibility construction, and composition with the
  implemented Diagnostic Handler boundary.
- Non-trusted and activation-failure behavior.
- Raw bootstrap configuration source, parser, validation failure, and reload.
- HTTP, CLI, and other transport behavior.
- Production diagnostic model and explicit allowlists.
- Simultaneous primary, activation, projection, rendering, transport, and
  cleanup failure representation.
- Logging, telemetry, incidents, serialization, localization, and persistence.
- Kernel lifecycle, DI wiring, Application Graph, extensions, frontend, and
  agents.

The exact typed delivery-decision API and its neutral ownership by Diagnostic
Activation are resolved by this decision.

Phase 1E implementation note: [ADR
0041](0041-pure-two-permission-trusted-presentation-eligibility.md) adds the
final readonly pure combined eligibility value. It evaluates only the two typed
decisions; evidence evaluation and all enforcement remain outer-adapter work.

Phase 1H implementation note: [ADR
0042](0042-transport-neutral-trusted-diagnostic-handler.md) implements the
Diagnostic Handler as the eligibility consumer. It receives only the adapter's
already-established per-delivery decision through the combined value, denies
before source access, and returns exact renderer HTML only when allowed. It does
not inspect, validate, or retain delivery evidence, catch failures, or deliver
output. The future adapter still owns evidence, authentication, permission
construction, composition, the non-trusted path, transport, and delivery.
