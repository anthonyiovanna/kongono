# ADR 0039: Typed Trusted-Developer Bootstrap Permission Foundation

## Status

Accepted

## Date

2026-08-15

## Context

[ADR 0038](0038-fail-closed-trusted-developer-diagnostic-activation.md)
requires affirmative trusted bootstrap permission and independent adapter-
controlled delivery permission before trusted diagnostic presentation access.
Before Phase 1C, that policy was Accepted but neither permission had an executable
type. The first implementation must carry only an already-validated bootstrap
decision without parsing configuration, proving transport trust, or implementing
the combined rule.

That statement records Phase 1C's bootstrap-only implementation boundary. Phase
1D later adds the neutral delivery-decision type through [ADR
0040](0040-typed-adapter-controlled-delivery-permission-foundation.md) without
moving evidence evaluation or combined enforcement into Diagnostic Activation.

Diagnostic Presentation remains a pure leaf whose projector and renderer cannot
authorize their caller. The typed decision therefore needs neutral ownership that
does not create a reverse dependency or put activation policy inside presentation.

## Decision

Create the seventh Composer package, `kongono/diagnostic-activation`, under the
namespace `Kongono\DiagnosticActivation\`. It depends only on
`kongono/api-metadata` and PHP. No existing package depends on Diagnostic
Activation, and Diagnostic Presentation specifically does not depend on it. The
framework root consumes both packages for atomic monorepo validation while the
production package graph remains acyclic.

The package owns exactly one production declaration:

```php
Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission
```

It is an Experimental unbacked enum with ordered cases `Denied`, then `Allowed`,
and the sole explicit method
`allowsTrustedPresentation(): bool`. The method returns `true` only for
`Allowed`, using enum-case identity, and returns `false` for `Denied`.

`Denied` means the trusted presentation path is prohibited regardless of any
future delivery value. `Allowed` means only that the already-validated bootstrap
decision permits the trusted path to be considered. It does not establish the
future adapter's independent delivery permission and does not authorize source
access, projection, field copying, rendering, or delivery.

The enum is unbacked so it exposes no implicit raw-string or integer parser
surface through `from()` or `tryFrom()`. Its case names are PHP source API names,
not configuration tokens. Selecting `Allowed` directly in PHP does not prove that
an audience, connection, operator, listener, request, or delivery is authorized.

Absence does not construct `Allowed`. A future trusted composition boundary must
supply `Denied` when no enabling permission was configured. Invalid or ambiguous
raw configuration remains a future validation failure that rejects adapter startup
or construction before serving; it is not a third enum case, an implicit-denial
parser, or a fallback to `Allowed`.

Phase 1C implements no raw configuration, parser, configuration failure, delivery-
permission value, combined eligibility calculation, enforcement boundary, adapter,
request/response behavior, diagnostic access, projector call, renderer call, or
delivery. It adds no exception or diagnostic identifier.

## Rationale

A small immutable enum makes the first policy input explicit and machine-checkable
without stabilizing raw configuration syntax or transport authorization. Unbacked
cases prevent accidental treatment of source-level case names as accepted external
values. A separate acyclic package preserves Diagnostic Presentation's purity and
allows a future adapter to consume independently owned activation and presentation
capabilities under a later directive.

## Security implications

The enum is a validated decision value, not a capability token, proof of trust,
authorization grant, or security boundary. Both Accepted permissions must remain
affirmative before presentation access. `Allowed` alone never authorizes diagnostic
metadata or context access, Safe or Protected field copying, projection, rendering,
storage, logging, transmission, or delivery.

No request, route, header, cookie, body, session, token, hostname, origin, referrer,
frontend value, environment label, truthy string, AI output, heuristic, exception,
API status, database value, provider response, extension, agent, previous operation,
mutable global, registry, thread-local, or fiber-local state may authorize trusted
output through this enum. Raw configuration is not read or validated here.

## Lifecycle and concurrency

The enum is immutable, stateless, and safe for persistent runtimes. It retains no
per-operation data and performs no I/O or ambient lookup. A future adapter must
retain one already-validated bootstrap decision for its own lifetime; changing raw
configuration requires future restart or reconstruction semantics. Phase 1C
creates no adapter, reload behavior, delivery value, or persistence rule.

Adapter-controlled delivery permission remains per delivery and adapter-owned. It
must not leak through static, ambient, previous-operation, thread-local, or fiber-
local state. Exact concurrency and adapter lifecycle mechanisms remain future.

## Performance implications

The identity comparison is constant bounded work and allocates no retained state.
The package performs no filesystem, environment, network, logging, telemetry,
event, persistence, serialization, cache, container, Kernel, Graph, DI,
Diagnostics, projector, renderer, or exception access.

## Consequences

Kongono now has the exact typed trusted-bootstrap permission API and ownership
required by the first half of the Accepted two-gate policy. The framework has
seven production packages, and the new package has only the one-way edge to API
Metadata. Diagnostic Presentation remains unchanged and directly callable; it
cannot enforce this outer rule.

Configuration, delivery evidence evaluation, combined enforcement, adapters,
transports, and production diagnostic behavior remain absent. The package adds no new failure
family, identifier, runtime binding, service, Graph node, Kernel hook, DI wiring,
or presentation dependency.

## Rejected alternatives

- A backed enum is rejected because it would expose raw-value parsing APIs and
  prematurely select configuration tokens.
- A boolean, truthy string, environment label, or raw parser in this package is
  rejected because configuration ownership and validation remain future.
- A third case for missing or invalid configuration is rejected because absence
  maps to future composition of `Denied`, while invalid input must fail before
  serving.
- A capability token, hidden constructor, marker, attribute, or package visibility
  trick is rejected because PHP callability cannot prove transport authorization.
- Phase 1C rejected adding a delivery-permission value or combined gate because
  it implemented only the bootstrap foundation. ADR 0040 narrowly supersedes
  that broad value rejection to permit a neutral typed representation; the
  adapter still owns independent evidence, evaluation, and later enforcement.
- A Diagnostic Presentation dependency on Diagnostic Activation is rejected
  because presentation remains a pure low-level leaf and cannot enforce its caller.
- Configuration, adapter, HTTP, CLI, logging, telemetry, serialization, Kernel,
  DI, Graph, extension, frontend, or agent integration is rejected as later scope.

## What remains open

- Raw server configuration key, source, schema, parser, and validation failure.
- Construction or composition API that chooses the enum.
- Adapter package, composition contract, and lifecycle ownership.
- Adapter-controlled delivery evidence and evaluation mechanism.
- Outer-adapter composition and enforcement using the Phase 1E pure eligibility
  result before any presentation access.
- Non-trusted failure behavior.
- HTTP, CLI, and other transport behavior.
- Production diagnostic model and explicit allowlists.
- Logging, telemetry, incidents, serialization, localization, and reload behavior.
- Kernel lifecycle, DI wiring, Application Graph, extension, frontend, and agent
  integration.

ADR 0040 narrowly supersedes only this ADR's broad rejection of a delivery-
permission value in Diagnostic Activation. The original bootstrap API decision,
adapter ownership of delivery evidence and mechanism, separation of the two
permissions, and absence of combined evaluation or enforcement remain in force.

Phase 1E implementation note: [ADR
0041](0041-pure-two-permission-trusted-presentation-eligibility.md) resolves the
pure combined truth-table calculation only. External evidence evaluation,
outer-adapter composition, and enforcement remain absent and open.
