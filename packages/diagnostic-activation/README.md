# Kongono Diagnostic Activation

## Purpose and package boundary

`kongono/diagnostic-activation` owns the two typed permission values and their
pure combined eligibility value used by Kongono's Accepted fail-closed
diagnostic-activation architecture.
It depends on API Metadata for compatibility classification and has no dependency
on Diagnostic Presentation, Diagnostics, Kernel, Application Graph, or Dependency
Injection. Diagnostic Handler and PHP CGI depend on Diagnostic Activation;
Activation has no reverse dependency on either package.

The package is not an authorization engine, access-control system, capability
system, or security boundary. It does not decide whether a transport audience is
trusted and does not enforce the two-permission presentation rule.

## Typed permission decisions

Phase 1C added the Experimental unbacked enum
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission`. Its exact
cases are `Denied`, then `Allowed`.

`Denied->allowsTrustedPresentation()` returns `false` and
`Allowed->allowsTrustedPresentation()` returns `true`. The method describes only
the already-validated bootstrap decision. Phase 3E PHP CGI maps literal
`Production` to `Denied` and `LoopbackDevelopment` to `Allowed` after exact
native validation. `Allowed` remains insufficient until independent current-
delivery permission is affirmative.

Phase 1D adds the Experimental unbacked enum
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission` with the
same ordered cases and method. It represents only an outer adapter's already-
determined decision for one current delivery. Phase 3E PHP CGI evaluates one
explicit closed snapshot; Phase 3F's concrete delivery now owns fresh bounded
capture and supplies that snapshot to the evaluator. `Denied`
prohibits the trusted path; `Allowed` does not authenticate an operator, prove
transport trust, identify a delivery, establish freshness, or authorize any
presentation action by itself.

Both enums are immutable, stateless, deterministic, unbacked, and I/O-free.
Neither `Allowed` case is sufficient alone. The delivery decision must be
evaluated independently for every delivery and an affirmative value must not be
reused across deliveries. Missing, unavailable, failed, uncertain, ambiguous,
expired, or unverified adapter evidence must fail closed rather than become
`Allowed`.

Because the enum is unbacked, it exposes no `from()` or `tryFrom()` raw-value
surface. Case names are PHP source API names, not configuration tokens. Selecting
`Allowed` directly in PHP does not prove trust, transport authorization, or
eligibility to access a source exception, project, render, or deliver diagnostics.

Phase 1E adds the Experimental final readonly
`TrustedDeveloperPresentationEligibility`. It receives the bootstrap permission
and delivery permission in that order and returns true only for `Allowed` plus
`Allowed`. It stores only those two decisions and is scoped to one current
delivery. Direct construction and a true result do not prove authorization or
either input's provenance. The class evaluates no external evidence and does not
enforce presentation access; Web Runtime retains enforcement, while PHP CGI's
concrete delivery owns selected-profile evidence evaluation. Neither
responsibility belongs to this enum or eligibility value.

## Configuration and failure boundary

Raw configuration remains outside this package. It reads no environment variable,
configuration file, command-line option, request, container, registry, or ambient
state. Phase 3E selection lives in trusted PHP code outside this package and has
no scalar parser or default profile.

Invalid or ambiguous raw configuration is not a third case and is not coerced to
either case. A future configuration layer must reject it before an adapter serves
work. Phase 1C selects no configuration key, source, schema, parser, exception,
reload behavior, constructor, or composition API.

## Security, lifecycle, and concurrency

The enums are immutable, stateless, deterministic, and I/O-free. They retain no
per-operation value and is safe to retain for the lifetime of a future persistent
adapter instance after validation. It performs no filesystem, network, environment,
logging, telemetry, event, persistence, serialization, cache, exception, projector,
renderer, Kernel, Graph, or DI access.

Trusted presentation still requires both independent affirmative permissions
before presentation access:

1. this trusted-bootstrap decision must be `Allowed`; and
2. the adapter must grant adapter-controlled delivery permission for the
   current delivery.

`Denied` prohibits the trusted path regardless of the current delivery decision.
Client-controlled data, environment labels, truthy strings, heuristics, AI output,
exception values, API status, extensions, agents, mutable globals, and prior
operations cannot turn this value into authorization.

## Stability and non-goals

The two enums and eligibility class are Experimental. The package implements no
raw configuration, validation failure, delivery evidence evaluation, enforcement,
adapter, request or response behavior, transport,
diagnostic access, projection, rendering, delivery, production model, logging,
telemetry, localization, or serialization.

## Related documentation

- [ADR 0038: Fail-Closed Trusted-Developer Diagnostic Activation](../../docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md)
- [ADR 0039: Typed Trusted-Developer Bootstrap Permission Foundation](../../docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md)
- [ADR 0040: Typed Adapter-Controlled Delivery Permission Foundation](../../docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md)
- [ADR 0041: Pure Two-Permission Trusted-Presentation Eligibility](../../docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md)
- [ADR 0055: Immutable PHP CGI Operator Bootstrap and Trust Inputs](../../docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md)
- [Security policy](../../SECURITY.md)
- [Error handling and actionable diagnostics](../../docs/engineering/error-handling.md)
- [Package layout](../../docs/engineering/package-layout.md)
- [Diagnostic Presentation package](../diagnostic-presentation/README.md)
- [Diagnostic Handler package](../diagnostic-handler/README.md)

## Phase 2G consumer

Web Runtime now consumes both typed permissions. Bootstrap denial skips the
delivery evaluator; otherwise server-owned, request-independent evidence returns
the second permission before request conversion. Neither enum authenticates a
delivery by itself. Trusted HTML also requires a supported Kernel, Application
Graph, or Dependency Injection failure; all other paths use the separate fixed
generic model. Phase 3E supplies PHP CGI's literal bootstrap and closed evidence
evaluation sources. Phase 3F's Internal PHP CGI delivery now supplies the exact
captured SAPI/projected-server evidence before lazy request-input access. The
two permissions remain independent: `Production` denies, and
`LoopbackDevelopment` still needs direct canonical loopback evidence with no
forwarded marker. The Experimental entry merely accepts an already-composed Web
Runtime. Phase 3G adds a separate stateless PHP CGI front controller that calls
the entry once and a supplied application-shutdown closure once. It does not
change, combine, retain, or infer either permission. `Production` remains
denied; development still requires direct canonical loopback with no forwarded
marker. Diagnostic 1019 preserves simultaneous lifecycle failures without
placing throwable detail in Safe context. Phase 3H's root-owned harness proves
the selected synchronous PHP-CGI FastCGI RESPONDER profile externally without
changing this package or adding a production dependency. It is not a production
or supported server/client. Uncertain emission or shutdown requires external
process retirement, `Production` trust remains denied, and operational ownership
remains external. Phase 3 is complete; no next phase starts automatically.
