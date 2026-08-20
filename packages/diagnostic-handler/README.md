# Kongono Diagnostic Handler

## Purpose and package boundary

`kongono/diagnostic-handler` owns the neutral sequencing boundary between an
already-constructed trusted-presentation eligibility value and Kongono's
existing trusted diagnostic projector and HTML renderer. It depends directly
on API Metadata, Diagnostic Activation, Diagnostic Presentation, Kernel,
Application Graph, and Dependency Injection. It does not depend directly on
Diagnostics. No package depends on Diagnostic Handler; the framework root
consumes it as the eighth package.

Diagnostic Activation and Diagnostic Presentation remain independent lower
layers. Activation does not access diagnostics, and Presentation does not
evaluate activation. Diagnostic Handler is the only current package that
depends on both.

## Implemented contract

The package contains one production declaration:

```php
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperDiagnosticHtmlPresenter
{
    public function renderIfEligible(
        TrustedDeveloperPresentationEligibility $eligibility,
        KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
    ): ?string;
}
```

The presenter is directly callable. Direct callability, direct construction of
allowed permission values, or a non-null result does not prove authorization.
The caller must supply permissions legitimately established by the outer
adapter for the current delivery.

## Calling and result contract

The method evaluates `allowsTrustedPresentation()` before accessing the source
or invoking presentation code. A false result returns `null` immediately. No
diagnostic accessor, projection, model construction, or rendering occurs on
that path.

After a true result, the presenter projects the source through
`TrustedDeveloperDiagnosticProjector`, renders the detached model through
`TrustedDeveloperHtmlRenderer`, and returns that renderer's complete HTML bytes
unchanged. It does not catch, wrap, suppress, log, retry, or translate a
`Throwable`; eligibility, accessor, projection, model, and rendering failures
propagate unchanged.

`null` means only that trusted developer HTML was not produced because combined
eligibility was false. It is not an HTTP status or response, a fallback page, a
production diagnostic model, an incident identifier, proof that the failure was
handled, or evidence that any delivery occurred.

## Security, lifecycle, and concurrency

Evidence and authority remain adapter-owned. This package does not read raw
configuration or requests, evaluate bootstrap or delivery evidence,
authenticate an audience, select a listener, authorize a transport, or deliver
output. A future outer adapter must construct fresh delivery permission and
eligibility for each current delivery and must own the non-trusted path.

The presenter has no constructor, property, ambient state, cache, registry, or
I/O. Repeated and interleaved calls retain no permission, source, model, HTML,
or result, so one call cannot authorize another in a persistent runtime.

## Current limitations

This package implements no adapter, configuration parser, evidence evaluator,
request, response, route, middleware, controller, HTTP behavior, CLI behavior,
queue or worker behavior, delivery, production model, allowlist, redaction,
logging, telemetry, incident, persistence, serialization, localization, Kernel
lifecycle integration, DI registration, or Application Graph participation.
The presenter and all directly consumed presentation and activation APIs remain
Experimental.

## Related documentation

- [ADR 0034: Trusted-Developer Diagnostic Projection](../../docs/adr/0034-trusted-developer-diagnostic-projection.md)
- [ADR 0035: Trusted-Developer Diagnostic Projection Implementation](../../docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md)
- [ADR 0036: Accessible Trusted-Developer HTML Diagnostic Page](../../docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md)
- [ADR 0037: Trusted-Developer HTML Diagnostic Renderer Implementation](../../docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md)
- [ADR 0038: Fail-Closed Trusted-Developer Diagnostic Activation](../../docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md)
- [ADR 0039: Typed Trusted-Developer Bootstrap Permission Foundation](../../docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md)
- [ADR 0040: Typed Adapter-Controlled Delivery Permission Foundation](../../docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md)
- [ADR 0041: Pure Two-Permission Trusted Presentation Eligibility](../../docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md)
- [ADR 0042: Transport-Neutral Trusted Diagnostic Handler](../../docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md)
- [Security policy](../../SECURITY.md)
- [Error handling](../../docs/engineering/error-handling.md)
- [Package layout](../../docs/engineering/package-layout.md)
- [Diagnostic Activation package](../diagnostic-activation/README.md)
- [Diagnostic Presentation package](../diagnostic-presentation/README.md)

## Phase 2G consumer

Web Runtime now calls the presenter once only for supported application failures.
The handler still enforces eligibility before source access; denial returns
`null`, and any presentation failure falls back once to the fixed generic
response without recursion. The package does not emit, authenticate, log, retain
incidents, or own a Kernel scope. The outer integration remains ports-only and
Phase 2H remains the first runnable milestone.
