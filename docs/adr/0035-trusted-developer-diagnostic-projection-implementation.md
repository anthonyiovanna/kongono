# ADR 0035: Trusted Developer Diagnostic Projection Implementation

## Status

Accepted

## Date

2026-08-14

## Context

[ADR 0034](0034-trusted-developer-diagnostic-projection.md) assigns diagnostic
presentation to a separate leaf package and fixes the trust, disclosure,
information, and persistent-runtime boundaries for its first trusted-developer
projection. Kernel, Application Graph, and Dependency Injection already expose
package-owned bounded context through three separate Public exception markers.
Diagnostics owns only presentation-neutral schemas and values.

The implementation must combine those three sources without creating a
universal exception boundary, runtime integration, renderer, trusted-mode
selector, production model, or reverse package dependency. It must also defend
the projection boundary from structurally invalid consumer implementations of
the existing Public markers.

## Decision

Create the sixth Composer package, `kongono/diagnostic-presentation`, under the
namespace `Kongono\DiagnosticPresentation\`. It is a leaf that depends directly
on API Metadata, Application Graph, Dependency Injection, Diagnostics, and
Kernel. The framework root consumes all six packages. No existing package
depends on Diagnostic Presentation, so the package graph remains acyclic.

The package contains exactly eight production declarations:

- Experimental string-backed `DeveloperDiagnosticOwner`;
- Experimental final readonly `TrustedDeveloperDiagnosticField`;
- Experimental final readonly `TrustedDeveloperDiagnostic`;
- Experimental final readonly `TrustedDeveloperDiagnosticProjector`;
- Internal string-backed `DiagnosticPresentationDiagnostic`;
- Public package-local `ExceptionInterface`;
- Internal abstract `DiagnosticPresentationException`; and
- Experimental final `InvalidDiagnosticProjectionException`.

The owner vocabulary is exactly `kernel`, `application_graph`, and
`dependency_injection`. The projector accepts exactly the existing Kernel,
Application Graph, or Dependency Injection contextual marker union. It checks
all three memberships before any accessor use and rejects dual- or triple-marker
sources as ambiguous.

Projection reads identifier, title, remediation, context, operation, invariant,
and every field accessor exactly once in the prescribed order. It derives owner
and identifier prefix from the sole marker, validates consumer-supplied
structure, and returns a fresh detached snapshot. A structurally valid custom
marker identifier remains consumer-defined metadata; validation does not add it
to a Kongono catalog.

`TrustedDeveloperDiagnostic` contains only owner, identifier, title, operation,
violated invariant, remediation, and up to 16 canonically ordered fields.
`TrustedDeveloperDiagnosticField` contains only key, label, exact scalar kind,
already-represented scalar value, unchanged Safe or Protected disclosure, and
nullable text-truncation state. Input and returned arrays are detached while the
immutable field values may be shared.

The package defines 15 context-free `KGN-PRESENTATION-1001` through
`KGN-PRESENTATION-1015` construction failures, in order: ambiguous owner,
invalid identifier, title, remediation, operation, or invariant; non-list
fields; invalid field entry; too many fields; duplicate field key; invalid
field key or label; value-kind mismatch; invalid represented text; and invalid
truncation state. The Public Presentation marker exposes only identifier,
title, and remediation strings and remains unrelated to the four existing
package markers. Native or consumer `Throwable` instances raised by source
accessors propagate unchanged and stop further access.

Both Safe and Protected fields are preserved exactly because the model is for
an already-authorized trusted developer boundary. Projection does not authorize
trusted use, select trust, disclose output, redact, sanitize, escape, render, or
perform I/O. It retains no source, context, field, cache, registry, ambient
state, or cross-operation value. These bounds preserve persistent-runtime
isolation.

## Rationale

A leaf package can integrate all three contextual exception families without
moving presentation into Diagnostics or introducing reverse dependencies.
Exact immutable values and a pure single-read projector provide a deterministic
boundary for later presentation work while preserving package ownership,
classification, native failure identity, and bounded resource use.

Package-local context-free failures avoid recursive diagnostic projection.
Structural validation protects the boundary from unsupported custom marker
implementations without turning reflection or catalog lookup into runtime
authority.

## Consequences

Consumers may explicitly construct the Experimental values or call the
Experimental projector after an outer boundary has already authorized trusted
developer use. Every projection returns a fresh diagnostic and fresh field
objects, includes both disclosure classes, and contains no originating object.

The framework now has six package directories, seven Composer manifests, 48
production declarations, five separate package exception markers, and 68
unique catalog identifiers. Diagnostic Presentation adds no runtime binding,
transport, output grammar, service, Graph relation, lifecycle hook, logging, or
telemetry.

Phase 0Y architecture note: [ADR 0036](0036-accessible-trusted-developer-html-diagnostic-page.md)
selected the accessible server-rendered HTML format and exact final-boundary
escaping rule. It changed no Phase 0X implementation fact and created no
renderer.

Phase 0Z implementation note: [ADR 0037](0037-trusted-developer-html-diagnostic-renderer-implementation.md)
implements the pure renderer, exact template/CSS bytes, final-boundary escaping,
golden fixtures, and ten focused tests without changing this projection
contract. Trusted activation, transport, production presentation, and optional
providers remain future.

Phase 1A architecture note: [ADR
0038](0038-fail-closed-trusted-developer-diagnostic-activation.md) accepts the
fail-closed rule that affirmative trusted bootstrap permission and independent
adapter-controlled delivery permission must precede presentation access,
projection, copying, rendering, and delivery. The rule is not implemented here.
The projector remains directly callable as a pure low-level Experimental API,
which cannot establish authorization. Exact decision types, configuration,
adapter, transport, production, and operational mechanisms remain open.

## Rejected alternatives

- Adding projection to Diagnostics is rejected because Diagnostics remains
  presentation-neutral.
- Adding projection to Kernel, Application Graph, or Dependency Injection is
  rejected because no originating package owns cross-package presentation.
- A universal exception or diagnostic marker is rejected because recovery
  remains package-local.
- Accepting arbitrary `Throwable`, strings, arrays, or metadata bags is rejected
  because the source and projected contracts are closed.
- Catalog lookup or a registry is rejected because structural validity is not
  semantic ownership and persistent state is unnecessary.
- Catch-all wrapping is rejected because accessor failures retain native or
  consumer identity.
- A global, ambient, cached, or retained projector is rejected for persistent-
  runtime isolation.
- Trust selection inside the projector is rejected because authorization
  belongs to a future server-controlled outer integration.
- Reusing and redacting the trusted model for production is rejected because
  production requires a separate deny-by-default model.
- Rendering or output escaping here is rejected because projection and concrete
  output grammars are separate responsibilities.

## What remains open

- Exact typed bootstrap decision, server configuration validation and reload,
  outer adapter, and delivery-authorization mechanisms.
- Safe copy and export formats.
- Native cause-chain, source, stack-frame, and documentation providers.
- A separate deny-by-default production model and explicit allowlists.
- HTTP, CLI, and other transport mappings plus status and exit policy.
- Localization, logging, telemetry, incidents, serialization, and representation
  of simultaneous primary, projection, and rendering failures.
