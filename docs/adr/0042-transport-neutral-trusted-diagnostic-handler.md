# ADR 0042: Transport-Neutral Trusted Diagnostic Handler

## Status

Accepted

## Date

2026-08-15

## Context

Diagnostic Activation owns two typed permission decisions and the pure
`TrustedDeveloperPresentationEligibility` conjunction for one delivery.
Diagnostic Presentation owns the pure
`TrustedDeveloperDiagnosticProjector`, detached trusted model, and
`TrustedDeveloperHtmlRenderer`. Those low-level Experimental presentation APIs
remain directly callable. Diagnostic Handler now provides the neutral
eligibility-first coordinator without making either lower layer depend on it.

The future outer adapter must continue to establish bootstrap permission from
validated server configuration and independently establish delivery permission
from current-delivery evidence. The missing neutral boundary is only the
deterministic sequencing between an already-created eligibility value and the
existing projection and rendering operations. It must deny before any source
access without taking ownership of evidence, authorization, transport, or the
future non-trusted failure response.

Phase 1G recorded this architecture only. Phase 1H implements its exact neutral
sequencing boundary as Kongono's eighth package. It adds no binding, adapter,
evidence evaluator, transport, delivery, response, or production model.

## Decision

The implemented eighth package is:

```text
Composer package: kongono/diagnostic-handler
Namespace: Kongono\DiagnosticHandler\
Status after Phase 1H: implemented Experimental leaf package
```

It is a leaf and may depend directly only on:

```text
php >=8.5
kongono/api-metadata
kongono/diagnostic-activation
kongono/diagnostic-presentation
kongono/kernel
kongono/application-graph
kongono/dependency-injection
```

The Kernel, Application Graph, and Dependency Injection edges are direct
because the public method names their three separate Public contextual
exception markers. Those types will not be erased to `object`, `Throwable`, a
universal Kongono marker, an adapter DTO, or a callback. The handler does not
directly require `kongono/diagnostics`; Diagnostic Presentation retains its own
direct Diagnostics dependency. Diagnostic Presentation does not depend on
Diagnostic Activation; both are direct handler dependencies. No existing
package depends on Diagnostic Handler, and the framework root consumes all
eight packages.

```text
api-metadata -----------+
diagnostic-activation --+
diagnostic-presentation +
kernel -----------------+
application-graph ------+
dependency-injection ---+--> diagnostic-handler
```

The implementation boundary contains exactly one production
declaration:

```php
Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter
```

It is a stateless `final readonly class` with exactly one
`#[Api(ApiStatus::Experimental)]`, no declared instance or static property, no
explicit constructor, and no interface, trait, parent, factory, alias, or
service registration. Its sole declared public method uses these exact
aliases and signature:

```php
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;

public function renderIfEligible(
    TrustedDeveloperPresentationEligibility $eligibility,
    KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
): ?string;
```

The parameter order and the source union are exact. Passing the already-caught
source object as an argument is not itself diagnostic presentation access. The
future outer adapter remains responsible for avoiding source accessor use while
it establishes both permission decisions.

The implemented execution order is exact:

1. Call `$eligibility->allowsTrustedPresentation()`.
2. If it returns `false`, return `null` immediately.
3. Only after `true`, create or invoke `TrustedDeveloperDiagnosticProjector`
   with `$source`.
4. Pass the detached trusted model to `TrustedDeveloperHtmlRenderer`.
5. Return the complete HTML string unchanged.

No source accessor, projection, field copy, model construction, or rendering
may occur before eligibility returns true. Trusted HTML is never produced
speculatively and discarded after denial.

`null` means exactly that trusted developer HTML was not produced because
combined eligibility was false. It is not an HTTP status or body, production
diagnostic model, incident identifier, logging instruction, proof that the
source was handled, retry signal, empty HTML document, fallback page, or
successful delivery. A future outer adapter must select the still-undesigned
non-trusted failure path. On success, the renderer's complete non-null string is
returned without editing, wrapping, prepending, appending, minifying, caching,
classifying, or transporting it.

The handler catches no `Throwable`. Failures from eligibility evaluation,
source accessors, projection validation, trusted-model construction, or HTML
rendering propagate unchanged. It adds no wrapper, exception family,
diagnostic identifier, fallback, retry, recursion, cleanup model, log, metric,
trace, incident record, or best-effort output. This rule does not assume the
current total eligibility method or current renderer can never throw.

The handler enforces sequencing only for the values it receives. It does not
authenticate its caller, establish either input's provenance, or prove an
audience, listener, connection, or transport is trusted. Direct construction of
`Allowed` plus `Allowed` remains possible, and a true result is neither a
capability token nor authorization proof. The future outer adapter remains the
sole owner of bootstrap provenance, current-delivery evidence, evaluation,
authentication, listener policy, connection policy, and audience authorization.
Client-controlled values cannot select or upgrade either permission. Absence,
failure, ambiguity, uncertainty, staleness, and unverifiable evidence become
denial before the handler is called. PHP visibility cannot make direct projector
or renderer calls impossible. Production presentation remains a separate
deny-by-default model with explicit allowlists.

The implemented class is stateless and safe for persistent runtimes. One call
corresponds to one current delivery and retains no permission, source, model,
HTML, result, or cross-delivery state. It uses no global, static, singleton,
registry, container-current, worker-current, thread-local, fiber-local,
tenant-current, request-current, previous-operation, or cache state.
Interleaved and repeated calls depend only on explicit arguments, and one call
cannot authorize another. A future adapter may retain its immutable bootstrap
permission for its instance lifetime but must evaluate a fresh delivery
permission and construct eligibility for every current delivery. Exact adapter
lifecycle and restart behavior remain open.

Denied execution is one eligibility evaluation followed by `null`. Allowed
execution adds only the existing bounded projection and HTML rendering work.
The handler introduces no retained cache, I/O, serialization, network or
filesystem access, logging, telemetry, events, reflection, container lookup, or
Graph query. No throughput, latency, allocation, or response-size promise is
selected beyond the existing component contracts.

Current after Phase 1H, the repository has eight packages. Diagnostic
Activation owns the two permission enums and pure eligibility class; Diagnostic
Presentation owns the projector, detached model, and HTML renderer. Those
low-level APIs remain directly callable. Diagnostic Handler is the only current
package that depends on both lower layers and implements the exact
`TrustedDeveloperDiagnosticHtmlPresenter` contract with denial-before-access
tests. No configuration mechanism, evidence evaluator, adapter, transport,
delivery, response, or production presentation model exists.

## Rationale

A separate leaf coordinates two already-separated responsibilities without
placing presentation access in Diagnostic Activation or making Diagnostic
Presentation depend on activation policy. The exact marker union preserves
package-owned catch boundaries and keeps the public dependency topology
inspectable. Evaluating eligibility before access makes denial deterministic
and avoids unnecessary access to Protected diagnostic data while leaving
security-critical evidence and transport authority with the adapter that owns
them.

Returning `null` gives the future adapter one transport-neutral denied outcome
without selecting a response model. Unchanged success and unchanged failure
propagation keep orchestration from becoming presentation formatting, recovery,
or operational policy.

## Security implications

The implemented handler is a deterministic sequencing coordinator inside a
correctly composed outer security boundary, not a boundary that authenticates
its caller. Its sequencing rule is useful only when the adapter supplies
legitimately established inputs. Direct low-level calls remain possible and
carry their existing caller obligations.

No request value, environment label, truthy string, heuristic, AI output,
exception property, ambient state, or prior delivery may grant or widen either
permission. Escaped HTML remains trusted-developer output containing both Safe
and Protected fields; escaping is not authorization or declassification. The
trusted model remains prohibited for production use.

## Lifecycle and concurrency

The handler has no state and retains no argument or result. Each call is
isolated to one current delivery, is safe to interleave with other calls, and
cannot transfer eligibility across tenants, users, requests, jobs, workers,
threads, fibers, or later operations. It establishes no singleton, container
lifetime, runtime hook, operation scope, reset behavior, or restart contract.

## Performance implications

Denied work is bounded to the eligibility check and immediate `null`. Allowed
work is bounded by the existing projector and renderer contracts. The
handler adds no I/O, discovery, cache, registry, retained allocation, or
performance guarantee.

## Consequences

The neutral consumption boundary now exists: the handler consumes
`TrustedDeveloperPresentationEligibility`, denies before source access, and
otherwise sequences the existing projector and renderer and returns their exact
HTML. The package name, namespace, dependency direction, class, API status,
method signature, parameter order, return meaning, failure behavior, and
persistent-runtime contract are implemented and no longer open.

Phase 1H introduces the package manifest, source, tests, autoload mapping, and
root dependency. It introduces no binding, service, adapter, evidence
evaluation, response, transport, delivery behavior, or production model.

## Rejected alternatives

- Placing enforcement inside Diagnostic Activation is rejected because that
  package owns neutral decision values, not diagnostic access or presentation.
- Adding an Activation dependency to Diagnostic Presentation is rejected
  because presentation remains a low-level pure leaf.
- Putting orchestration in the framework root is rejected because the selected
  capability needs explicit package ownership and dependency direction.
- Erasing the source union to `Throwable`, `object`, a universal marker, an
  adapter DTO, or a callback is rejected because it hides the three direct
  package contracts.
- Accepting raw permissions, booleans, strings, arrays, request values,
  configuration, or evidence is rejected because the handler consumes only the
  completed eligibility value and supported source union.
- Projecting or rendering before eligibility is rejected because denial must
  precede presentation access.
- A capability token, grant, secret, hidden constructor, interface, registry,
  service locator, or ambient state as proof is rejected because it cannot
  establish outer transport authority.
- Returning an empty string, trusted model, union of unrelated results,
  response object, status code, or production model on denial is rejected;
  denial returns only `null`.
- Catching, wrapping, logging, retrying, falling back, or recursively rendering
  a failure is rejected because failures propagate unchanged.
- Combining delivery-evidence evaluation with presentation orchestration is
  rejected because evidence remains adapter-owned.
- HTTP, CLI, queue, worker, or other transport-specific behavior is rejected
  from the neutral handler.
- Implementing the package, class, dependency, or test during Phase 1G is
  rejected because this phase is architecture and documentation only.

## What remains open

- Exact outer adapter packages, construction, composition, and transport
  contracts.
- Bootstrap configuration source, schema, parser, validation failure, reload,
  and restart behavior.
- Delivery evidence, authentication, listener, connection, proxy, operator,
  and audience policy.
- The non-trusted failure response and separate production diagnostic model.
- HTTP, CLI, queue, worker, and every other delivery behavior.
- Simultaneous primary, orchestration, projection, rendering, transport, and
  cleanup failure policy.
- Logging, telemetry, incidents, serialization, localization, persistence, and
  version-matched documentation lookup.
- Kernel lifecycle, DI wiring, Application Graph, extension, frontend, and
  agent integration.
- Future API stabilization and compatibility promotion.
