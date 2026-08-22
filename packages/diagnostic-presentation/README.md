# Kongono Diagnostic Presentation

## Purpose and boundary

`kongono/diagnostic-presentation` is the leaf integration boundary for
diagnostic presentation. Phase 0X implemented only immutable trusted-developer
projection values, their pure projector, and package-local context-free
construction failures; it did not render or disclose the projection. Phase 0Z
adds the pure trusted-developer HTML renderer without adding disclosure policy.

The package depends directly on API Metadata, Diagnostics, Kernel, Application
Graph, and Dependency Injection. Only Diagnostic Handler depends on it. Diagnostics
remains presentation-neutral, and the three originating packages retain their
identifiers, schemas, throw-site values, semantics, and separate recovery
markers.

Phase 1C adds the separate `kongono/diagnostic-activation` package and its typed
trusted-bootstrap permission; Phase 1D adds that package's typed
adapter-controlled delivery permission. Diagnostic Presentation does not depend
on either value, inspect them, or enforce them. The implemented Diagnostic
Handler consumes both packages through one-way dependencies; a future adapter
must still own permission composition and delivery. Phase 3E PHP CGI now owns
the literal bootstrap source and independent exact current-delivery evaluator;
Diagnostic Presentation remains independent from both.

Phase 1E adds Diagnostic Activation's pure final readonly
`TrustedDeveloperPresentationEligibility`, which returns true only when both
typed permissions are `Allowed` for one current delivery. Diagnostic
Presentation does not depend on, inspect, or enforce this value. Its projector
and renderer remain separate and directly callable low-level APIs.

## Trusted developer projection

`TrustedDeveloperDiagnosticProjector::project()` accepts exactly one Kernel,
Application Graph, or Dependency Injection contextual exception marker. It
derives `DeveloperDiagnosticOwner`, reads source metadata and context once,
copies every canonical field into new immutable values, and returns a detached
`TrustedDeveloperDiagnostic`.

The diagnostic contains only owner, identifier, title, operation, violated
invariant, remediation, and at most 16 ordered fields. Each field contains only
its canonical key, fixed label, exact `DiagnosticValueKind`, represented
`string|int|bool` value, unchanged `DiagnosticDisclosure`, and nullable text-
truncation state.

Both Safe and Protected fields remain present and classified exactly. Projection
does not authorize their use. A future outer integration must establish the two
Accepted permissions below before calling the projector or exposing its result.

## Trusted activation calling precondition

[ADR 0038](../../docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md)
accepts a fail-closed outer policy that this package neither owns nor enforces.
Phase 1H implements the neutral eligibility-first Handler sequencing. Phase 3E
implements PHP CGI's independent evidence sources, while Phase 3F implements
the concrete delivery and outer-adapter composition without changing
Presentation. Phase 3G adds the front-controller lifecycle and diagnostic 1019
without changing Presentation. Phase 3H adds root-owned external acceptance
without changing Presentation or adding a production dependency. [ADR
0039](../../docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md)
implements the first typed input in the separate Diagnostic Activation package.
[ADR
0040](../../docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md)
implements the second typed input there. Neither value evaluates its underlying
evidence or grants presentation access by itself, and combined enforcement
occurs only through the directly callable Handler presenter.

Before a future adapter accesses source metadata or context for presentation,
calls the projector, copies Safe or Protected fields, calls the renderer, or
delivers its output, both of these independent decisions must already be
affirmative:

- trusted bootstrap permission, validated and fixed immutably as `Allowed` for
  that future adapter instance before it serves work; and
- adapter-controlled delivery permission for the current delivery boundary.

Missing, invalid, ambiguous, failed, denied, or uncertain decisions prohibit
the trusted path. Diagnostic Presentation reads no raw configuration or request
and cannot infer either permission from environment labels, truthy strings,
client data, exception data, API status, heuristics, AI, ambient state, or a
previous operation. It owns no activation token, grant, policy, capability,
bootstrap decision, configuration type, adapter, or transport authorization
mechanism.

The projector and renderer remain directly callable low-level pure Experimental
APIs. Their exact calling precondition is that the outer caller has already
established both permissions. A direct call bypasses any future enforcement and
places the security obligation on that caller; PHP package visibility and a
package-local token cannot prove delivery authorization. This documentation
contract adds no executable check.

## Validation and failure

The Experimental values enforce the accepted key, prose, identifier, list,
field-count, scalar-kind, represented-text, and truncation bounds. Structurally
valid consumer-defined metadata remains consumer-defined; construction does not
claim a Kongono catalog assignment.

Invalid projection structure raises
`InvalidDiagnosticProjectionException` through the separate Public Presentation
`ExceptionInterface`. The Internal catalog owns 15 context-free identifiers from
`KGN-PRESENTATION-1001` through `KGN-PRESENTATION-1015`. The marker exposes only
diagnostic ID, title, and remediation and is unrelated to the Kernel, Graph, DI,
and Diagnostics markers.

Ambiguous multi-marker sources fail before any source accessor is called. A
native or consumer `Throwable` raised by an accessor propagates unchanged and no
later accessor is read.

## Persistent-runtime and security properties

Projection is synchronous, deterministic, stateless, bounded, and I/O-free. It
retains no source exception, context, source field, mutable list, cache,
registry, global, service, thread-local, fiber-local, or cross-operation state.
Each call creates a fresh diagnostic and fresh field values.

The projection contains no native message, cause, trace, class name, source,
stack, request, environment, SQL, provider payload, log data, telemetry,
incident, documentation location, localization data, or transport metadata. It
does no secret detection, sanitization, redaction, output escaping, rendering,
or trust selection.

## Trusted-developer HTML renderer

[ADR 0036](../../docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md)
accepts this package as owner of the Experimental
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`.
Phase 0Z implements its sole `render()` method. It consumes one already-
constructed `TrustedDeveloperDiagnostic` and returns a
complete accessible, deterministic, escaped HTML5 document with the exact five
visible regions: Diagnostic header, What happened, Why it happened, Context,
and How to fix it.

The renderer uses only text dynamic insertion, exact final-boundary
`htmlspecialchars()` escaping with HTML5 substitution and double encoding,
locale-independent scalar formatting, and one fixed local stylesheet. It
preserves owner, field order, kind, disclosure, value, and truncation state.
The full and empty golden fixtures lock the exact byte grammar. Input remains
bounded to 16 fields, the static shell plus stylesheet to 16 KiB, and complete
output to 96 KiB. Each call is synchronous and stateless, performs no I/O, and
retains no diagnostic or rendered result.

The renderer has no renderer-specific exception or fallback. Native engine
failure retains native identity. It adds no trust selection, projection,
transport, HTTP response, runtime integration, or production semantics. Both
future outer permissions must precede projection or rendering. Escaping does
not declassify Protected data.

## API status

The owner, field, diagnostic, projector, and HTML renderer are Experimental. The package
exception marker is Public, its abstract base and diagnostic catalog are
Internal, and the concrete invalid-projection failure is Experimental. Public
does not mean Stable under Kongono's pre-alpha maturity.

## Explicit non-goals

This package implements no activation decision or enforcement, configuration reader, served
page, JSON, Markdown, terminal or plaintext grammar, HTTP or CLI adapter,
trusted-mode selector, production model, allowlist, service binding, Kernel
hook, Graph integration, logging, telemetry, persistence, serialization,
localization, or incident system. Production must use a separately designed
deny-by-default model rather than redact the trusted developer projection.

## Related documentation

- [ADR 0034](../../docs/adr/0034-trusted-developer-diagnostic-projection.md)
- [ADR 0035](../../docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md)
- [ADR 0036](../../docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md)
- [ADR 0037](../../docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md)
- [ADR 0038](../../docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md)
- [ADR 0039](../../docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md)
- [ADR 0040](../../docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md)
- [ADR 0041](../../docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md)
- [ADR 0055](../../docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md)
- [Diagnostic Activation package](../diagnostic-activation/README.md)
- [Diagnostic Handler package](../diagnostic-handler/README.md)
- [Error-handling guide](../../docs/engineering/error-handling.md)
- [Security policy](../../SECURITY.md)

Phase 3F adds no projection or rendering behavior. Its Internal PHP CGI delivery
supplies only the independent typed current-delivery permission before lazy
request input access, while the Experimental entry passes a fresh delivery to
an already-composed Web Runtime. `Production` still denies trusted presentation;
`LoopbackDevelopment` still requires direct canonical loopback evidence with no
forwarded marker in addition to bootstrap permission. Generic conversion failure
does not expose diagnostic projection. Phase 3G adds the stateless PHP CGI
front controller and diagnostic 1019, whose Safe context contains only a fixed
token; its throwable accessors are outside Diagnostic Presentation and are not
Safe fields. One entry attempt precedes one supplied shutdown attempt.
`Production` remains denied and uncertain emission or shutdown requires external
process retirement. Phase 3H's test-owned harness proves the selected synchronous
PHP-CGI FastCGI RESPONDER profile but is not a production or supported
server/client; process, proxy, TLS, credential, log, monitoring, incident, and
deployment ownership remain external. No production package depends on the
harness. Phase 3 is complete, remains smaller than Stable API, 1.0, or a
production-hardening certification, and starts no next phase automatically. See [ADR
0056](../../docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md)
and [ADR 0057](../../docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md).
