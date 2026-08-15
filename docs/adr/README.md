# Architecture Decision Records

Architecture Decision Records (ADRs) capture decisions explicitly authorized for Kongono. They do not authorize implementation outside the current phase.

## Required structure

Every ADR contains:

- Title
- Status
- Date
- Context
- Decision
- Rationale
- Consequences
- Rejected alternatives
- What remains open

Phase 0A ADRs normally use `Accepted` because they record locked direction. Experimental mechanisms remain explicitly open inside the ADR rather than being promoted to accepted APIs.

## Index

1. [Kongono identity](0001-kongono-identity.md)
2. [PHP 8.5 minimum](0002-php-85-minimum.md)
3. [Monorepo for initial development](0003-monorepo-initial-development.md)
4. [Microkernel and capability architecture](0004-microkernel-capability-architecture.md)
5. [Language-neutral Application Graph](0005-language-neutral-application-graph.md)
6. [Persistent-runtime-first architecture](0006-persistent-runtime-first.md)
7. [Polyglot platform boundaries](0007-polyglot-platform-boundaries.md)
8. [Agents as Principals](0008-agents-as-principals.md)
9. [Secure Extension Platform](0009-secure-extension-platform.md)
10. [Vite integration strategy](0010-vite-integration-strategy.md)
11. [OpenTelemetry observability strategy](0011-opentelemetry-observability-strategy.md)
12. [Structured CLI contract](0012-structured-cli-contract.md)
13. [Documentation doctrine](0013-documentation-doctrine.md)
14. [PHP engineering toolchain](0014-php-engineering-toolchain.md)
15. [Root Composer lock for reproducible tooling](0015-root-composer-lock-for-reproducible-tooling.md)
16. [Strict types and PER-CS style](0016-strict-types-and-per-cs-style.md)
17. [PHPUnit as core test runner](0017-phpunit-as-core-test-runner.md)
18. [PHPStan maximum level without a baseline](0018-phpstan-max-without-baseline.md)
19. [Composer dependency-security policy](0019-composer-dependency-security-policy.md)
20. [Kernel and operation lifecycle boundary](0020-kernel-operation-lifecycle-boundary.md)
21. [Machine-checkable API status metadata](0021-machine-checkable-api-status.md)
22. [Immutable Application Graph structural snapshot](0022-immutable-application-graph-structural-snapshot.md)
23. [Explicit typed dependency resolution boundary](0023-explicit-typed-dependency-resolution.md)
24. [Exact direct-relation selection](0024-exact-direct-relation-selection.md)
25. [Exact whole-snapshot kind selection](0025-exact-whole-snapshot-kind-selection.md)
26. [Actionable Diagnostics and Error Boundaries](0026-actionable-diagnostics-and-error-boundaries.md)
27. [Package-Local Runtime Diagnostic Metadata](0027-package-local-runtime-diagnostic-metadata.md)
28. [Bounded Diagnostic Context and Disclosure](0028-bounded-diagnostic-context-and-disclosure.md)
29. [Presentation-Neutral Diagnostic Value Foundation](0029-presentation-neutral-diagnostic-value-foundation.md)
30. [Application Graph Diagnostic Context Integration](0030-application-graph-diagnostic-context-integration.md)
31. [Neutral API Metadata Ownership and Acyclic Package Dependencies](0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md)
32. [Kernel Lifecycle Diagnostic Context Integration](0032-kernel-lifecycle-diagnostic-context-integration.md)
33. [Dependency Injection Diagnostic Context Integration](0033-dependency-injection-diagnostic-context-integration.md)
34. [Trusted Developer Diagnostic Projection](0034-trusted-developer-diagnostic-projection.md)
35. [Trusted Developer Diagnostic Projection Implementation](0035-trusted-developer-diagnostic-projection-implementation.md)
36. [Accessible Trusted-Developer HTML Diagnostic Page](0036-accessible-trusted-developer-html-diagnostic-page.md) — Accepted architecture for the pure, escaped, accessible, bounded HTML renderer.
37. [Trusted-Developer HTML Diagnostic Renderer Implementation](0037-trusted-developer-html-diagnostic-renderer-implementation.md) — Accepted implementation of the exact stateless renderer, golden byte grammar, escaping, accessibility, and bounds without activation or transport integration.
38. [Fail-Closed Trusted-Developer Diagnostic Activation](0038-fail-closed-trusted-developer-diagnostic-activation.md) — Accepted two-gate policy with implemented denial-before-access Handler sequencing, while evidence evaluation, outer-adapter composition, transport, and delivery remain unimplemented.
39. [Typed Trusted-Developer Bootstrap Permission Foundation](0039-typed-trusted-developer-bootstrap-permission-foundation.md) — Accepted implementation of the Experimental unbacked bootstrap decision value in a separate acyclic package, without configuration, delivery permission, enforcement, or adapters.
40. [Typed Adapter-Controlled Delivery Permission Foundation](0040-typed-adapter-controlled-delivery-permission-foundation.md) — Accepted implementation of the neutral Experimental unbacked per-delivery decision value, while adapter evidence, composition, and delivery remain unimplemented.
41. [Pure Two-Permission Trusted-Presentation Eligibility](0041-pure-two-permission-trusted-presentation-eligibility.md) — Accepted implementation of the pure Experimental two-permission AND calculation, while external validation and outer-adapter composition remain unimplemented.
42. [Transport-Neutral Trusted Diagnostic Handler](0042-transport-neutral-trusted-diagnostic-handler.md) — Accepted and implemented eighth leaf with the Experimental pure eligibility-first presenter contract, without evidence evaluation, adapter behavior, transport, delivery, response, or production model.

## Process

Do not create or revise an architectural decision without architect authorization. A proposed decision must remain clearly marked as proposed and must not be implemented as if accepted. Superseded decisions should link to their replacements rather than being silently rewritten.
