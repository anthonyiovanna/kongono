# ADR 0038: Fail-Closed Trusted-Developer Diagnostic Activation

## Status

Accepted

## Date

2026-08-14

## Context

[ADR 0034](0034-trusted-developer-diagnostic-projection.md) establishes the
pure trusted-developer projection, [ADR
0036](0036-accessible-trusted-developer-html-diagnostic-page.md) selects its
HTML presentation, and [ADR
0037](0037-trusted-developer-html-diagnostic-renderer-implementation.md)
implements the pure renderer. Those low-level Experimental APIs are directly
callable, but neither callability, API status, projection, nor escaping proves
that exposing Safe and Protected diagnostic fields is authorized.

Phase 1A is architecture and documentation only. No activation type,
configuration reader, adapter, request or response, route, handler, served
page, production model, or runtime integration exists. Before a later adapter
can expose the trusted document, Kongono needs one fail-closed policy that
separates server-bootstrap permission from eligibility at a particular delivery
boundary.

## Decision

Trusted diagnostic presentation requires two affirmative, independent
permissions:

1. **Trusted bootstrap permission** means a server-controlled composition
   boundary deliberately enabled the trusted developer capability for the
   adapter instance before it began serving work.
2. **Adapter-controlled delivery permission** means the outer adapter
   independently determined that this specific delivery boundary is eligible
   to receive trusted developer output.

One permission never implies the other. Bootstrap permission does not establish
that an audience or connection is trusted. Delivery permission cannot enable a
capability that bootstrap left denied or absent. The Accepted truth table is:

| Bootstrap permission | Delivery permission | Trusted projection/rendering |
| --- | --- | --- |
| denied or absent | any value | prohibited |
| allowed | denied or uncertain | prohibited |
| allowed | allowed | eligible, subject to the later adapter contract |
| invalid or evaluation failure | any value | prohibited; never default to allowed |

Eligibility records the only state in which a future adapter may proceed; it
does not implement a call, response, route, handler, or delivery.

Both permissions must be affirmative before a future outer adapter:

- accesses a package exception's diagnostic metadata or context for
  presentation;
- invokes `TrustedDeveloperDiagnosticProjector`;
- copies Safe or Protected fields into the trusted model;
- invokes `TrustedDeveloperHtmlRenderer`; or
- delivers, stores, logs, transmits, or otherwise exposes the rendered HTML.

An originating exception may already own eager immutable bounded context under
its package-local failure contract. Denial neither erases nor mutates that
existing context; it prohibits later presentation access and copying. A future
adapter must not optimistically access, project, or render and then discard the
result after denial.

The bootstrap decision must reach a future adapter as an already validated,
explicit, typed, immutable, server-owned decision. Diagnostic Presentation
reads and parses no raw configuration. Absence is disabled. Invalid or
ambiguous raw configuration is rejected during bootstrap before any serving
work begins; it is never coerced into enabled behavior. Raw values such as
`development`, `local`, `debug`, `true`, `1`, `yes`, or an application-
environment label are not authority.

The delivery decision belongs to the future outer adapter and derives from
server-controlled transport ownership. No query parameter, route value, path,
header, cookie, form value, body, session value, user-supplied token, or other
client-controlled value may enable it. No request may upgrade absent or denied
bootstrap permission. No database value, provider payload, arbitrary
application callback, extension, plugin, agent output, prior request,
operation, tenant, user, fiber, or worker state may self-authorize or leak an
allowed decision. Uncertainty is denial, not a prompt, fallback, partial page,
or best effort. Exact transport-owned authentication, listener, operator, and
access-control mechanisms remain unselected.

`TrustedDeveloperDiagnosticProjector` and
`TrustedDeveloperHtmlRenderer` remain unchanged low-level pure Experimental
APIs. They cannot know whether their caller is authorized and do not enforce
these permissions. Direct PHP calls remain possible and put the security
obligation on the caller. A package-local token, hidden constructor, secret
string, marker interface, attribute, API-status value, DI service, or
capability object would not create a security boundary: PHP package visibility
cannot prove outer transport authorization.

Bootstrap validation failure rejects the future adapter's startup or
construction before it accepts work. Delivery denial or uncertainty bypasses
trusted presentation and follows a separately designed non-trusted failure
path. Its response, status, body, incident reference, logging, and transport
behavior remain open. An activation system must not catch a projector or
renderer failure and recursively invoke trusted rendering, replace the primary
failure with an activation failure, or invent fallback activation.

## Rationale

Separating process/bootstrap intent from one delivery decision prevents a
global developer switch from becoming proof that every audience is trusted,
and prevents a locally eligible delivery from enabling a capability the server
did not start. Evaluating both gates before presentation access also avoids
unnecessary copies of Protected fields. Fail-closed treatment of missing,
invalid, ambiguous, failed, and uncertain decisions keeps errors from becoming
expanded disclosure.

## Security implications

No environment label, truthy string, request or other client assertion,
exception property, diagnostic identifier, context value, hostname,
filesystem presence, network reachability, heuristic, AI output, mutable
global, superglobal, registry, service locator, thread-local, fiber-local,
cached ambient lookup, or previous-operation state can authorize trusted
output.

Safe remains eligibility metadata rather than automatic public disclosure.
Protected remains trusted-only. Forbidden material remains excluded from
capture rather than becoming a third classification. HTML escaping prevents
markup injection but neither authorizes disclosure nor declassifies Protected
data. Production requires a separate deny-by-default model with explicit field
allowlists and must not reuse the trusted model and redact it afterward.

## Lifecycle and concurrency

The future bootstrap decision is immutable for the lifetime of one adapter
instance. Changing raw configuration cannot mutate a live shared decision; an
explicit restart or a newly constructed adapter instance is required before a
changed decision takes effect. The exact reload lifecycle remains open.

The adapter evaluates delivery permission for the current delivery boundary
without storing it in ambient or static current state. One operation's result
cannot authorize another operation, tenant, user, fiber, worker delivery, or
concurrent call. This decision selects no Kernel integration, operation-scope
field, reset hook, container lifetime, singleton, recycle policy, concurrency
primitive, or adapter type.

## Performance implications

Permission evaluation should be constant bounded work before projection, but
this decision selects no executable performance guarantee or API. Phase 1A
adds no I/O, allocation contract, cache, network call, filesystem read,
environment lookup, log, metric, trace, event, incident, retained runtime state,
HTTP or CLI behavior, configuration mechanism, or other side effect.

## Consequences

The two-gate fail-closed semantics are Accepted architecture. Later adapter
work must place both decisions outside Diagnostic Presentation and enforce them
before presentation access, projection, copying, rendering, or delivery. The
existing projector and renderer remain pure and unchanged, and direct use does
not establish authorization.

No enforcement exists after Phase 1A. No adapter, configuration mechanism,
served page, production presentation, logger, telemetry path, incident system,
or runtime integration is created by this decision.

## Rejected alternatives

- A single bootstrap or delivery switch is rejected because neither decision
  proves the other.
- Default-enabled, truthy-string, environment-label, heuristic, AI, ambient,
  cached, or previous-operation activation is rejected because ambiguity or
  stale state must fail closed.
- Request-controlled activation is rejected because a client assertion cannot
  authorize expanded disclosure.
- Projecting or rendering before denial and discarding afterward is rejected
  because Protected data must not be accessed or copied unnecessarily.
- Authorization inside the projector, renderer, API-status metadata, or a
  package-local token is rejected because low-level PHP callability and package
  structure are not outer transport security boundaries.
- Mutable live bootstrap decisions and cross-delivery permission reuse are
  rejected for persistent-runtime and concurrency isolation.
- Recursive trusted rendering, fallback activation, and best-effort partial
  pages are rejected because failure or uncertainty must not widen disclosure.
- Reusing and redacting the trusted developer model for production is rejected
  because production needs a separate deny-by-default model and allowlists.

## What remains open

- Exact typed bootstrap decision API and ownership.
- Exact server configuration source, schema, loader, validation failure, and
  reload lifecycle.
- Exact outer adapter package and composition contract.
- Exact delivery-authorization mechanism, including any listener or operator
  identity policy.
- HTTP request/response ownership, status, content type, cache, and CSP.
- A separate production model and explicit allowlists.
- Safe copy/export formats and other renderers or transports.
- Cause, source, stack, and version-matched documentation providers.
- Localization.
- Logging, telemetry, incident storage, and serialization.
- Primary, activation, projection, rendering, transport, and cleanup
  multi-failure representation.
- Runtime adapter, Kernel lifecycle, DI wiring, Application Graph, extension,
  CLI, frontend, and agent integration.
