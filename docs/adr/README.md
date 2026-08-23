# Architecture Decision Records

### [ADR 0064: Independent Deterministic Hantei Verification](0064-independent-deterministic-hantei-verification.md)

Accepts and implements the Phase 4E closed eight-check verifier, exact subject
identity, bounded process authority, non-disclosing evidence, and independent
Knowledge-without-Chie package boundary.

### [ADR 0066: Explicit Reviewable Chie Patch Proposals](0066-explicit-reviewable-chie-patch-proposals.md)

Accepts the one explicit deterministic current-documentation candidate rule,
reversible diff and provenance contract, and pending human/fresh-Hantei handoff.
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
43. [Phase 2 Runnable Web Framework Milestone](0043-phase-2-runnable-web-framework-milestone.md) — Accepted bounded Phase 2A-H first-runnable-web roadmap and black-box acceptance boundary; Phase 2A is documentation-only.
44. [HTTP Message and Interoperability Architecture](0044-http-message-and-interoperability-architecture.md) — Accepted PSR-7, selected server-side PSR-17, and PSR-15 contracts with a first-party Kongono message boundary.
45. [First-Party HTTP Message and Factory Implementation](0045-first-party-http-message-and-factory-implementation.md) — Accepted implementation of the five selected PSR-7 server-message roles and five stateless PSR-17 factories, without routing, middleware, adapters, emission, or client HTTP.
46. [Deterministic Route Declaration and Matching](0046-deterministic-route-declaration-and-matching.md) — Accepted implementation of bounded, byte-exact method/path declaration and deterministic PSR-7 request matching in the separate tenth package, without handler execution or authorization.
47. [Explicit PSR-15 Middleware and Terminal Routing](0047-explicit-psr-15-middleware-and-terminal-routing.md) — Accepted implementation of the explicit bounded immutable middleware pipeline and complete route-name terminal mapping in the separate eleventh package, without bootstrap, adapter, error middleware, or response emission.
48. [Web Application Bootstrap and Kernel Operation Ownership](0048-web-application-bootstrap-and-kernel-operation-ownership.md) — Accepted implementation of the twelfth package, validation-before-boot composition, exact application-call Kernel scope closure, failure preservation, fail-closed retirement, and explicit shutdown without an outer adapter or response delivery.
49. [Outer Web Runtime and Safe Failure Delivery](0049-outer-web-runtime-and-safe-failure-delivery.md) — Accepted implementation of the thirteenth package, Public delivery port, fixed generic or eligible trusted response selection, exact send/close orchestration, and fail-closed retirement.
50. [Phase 2 Runnable Fixture and Checkpoint](0050-phase-2-runnable-fixture-and-checkpoint.md) — Accepted test-owned subprocess fixture, external black-box proof, persistent-operation isolation, and exact Phase 2 checkpoint boundary.
51. [Phase 3 Production HTTP Integration Milestone](0051-phase-3-production-http-integration-milestone.md) — Accepted bounded path to one supported synchronous production HTTP integration profile; Phase 3A is architecture, documentation reconciliation, and contributor tooling only.
52. [PHP cgi-fcgi FastCGI Responder Profile](0052-php-cgi-fastcgi-responder-profile.md) — Accepted architecture-only selection of PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER`, the future `kongono/php-cgi` leaf, and its bounded input, emission, trust, cleanup, and acceptance contracts.
53. [Bounded PHP CGI Inbound Conversion](0053-bounded-php-cgi-inbound-conversion.md) — Accepted Phase 3C implementation of explicit bounded caller-supplied CGI snapshot conversion into a first-party server request, without ambient host reads, delivery, emission, trust, or production entry.
54. [Bounded PHP CGI Response Emission](0054-bounded-php-cgi-response-emission.md) — Accepted Phase 3D implementation of fully staged bounded response emission with computed length and fail-closed post-commit uncertainty, without concrete delivery, operator trust, or production entry.
55. [Immutable PHP CGI Operator Bootstrap and Trust Inputs](0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md) — Accepted Phase 3E implementation of literal typed operator selection, fixed native prerequisites, and independent fail-closed current-delivery evidence, without concrete delivery, general configuration, or production entry.
56. [PHP CGI Production Delivery and Entry Composition](0056-php-cgi-production-delivery-and-entry-composition.md) — Accepted Phase 3F concrete one-delivery adapter and thin entry into Web Runtime.
57. [Bounded PHP CGI Front-Controller Lifecycle and Deployment Constraints](0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md) — Accepted Phase 3G explicit entry/shutdown lifecycle and selected-profile operator constraints.
58. [Phase 3 External PHP-CGI Acceptance and Checkpoint](0058-phase-3-external-php-cgi-acceptance-and-checkpoint.md) — Accepted Phase 3H test-owned external listener proof and exact Phase 3 checkpoint.
59. [Rolling Current-Minor PHP Baseline](0059-rolling-current-minor-php-baseline.md) — Accepted one-compatibility-certified-minor policy; Phase 4A changes no current Composer constraint or lockfile byte.
60. [Human-Centered Developer-Intelligence Family](0060-human-centered-developer-intelligence-family.md) — Accepted Chie/Hantei/Oshie separation, AI-independent knowledge direction, and security/provenance boundary; no role is implemented.

## Process

Do not create or revise an architectural decision without architect authorization. A proposed decision must remain clearly marked as proposed and must not be implemented as if accepted. Superseded decisions should link to their replacements rather than being silently rewritten.

## Current milestone context

- [ADR 0050: Phase 2 Runnable Fixture and Checkpoint](0050-phase-2-runnable-fixture-and-checkpoint.md) — Accepted; Phase 2 complete at checkpoint `647053d4bddc4c76090bca9bf3d693faa5880c52`.
- [ADR 0051: Phase 3 Production HTTP Integration Milestone](0051-phase-3-production-http-integration-milestone.md) — Accepted; Phase 3A architecture and documentation only.
- [ADR 0052: PHP cgi-fcgi FastCGI Responder Profile](0052-php-cgi-fastcgi-responder-profile.md) — Accepted; Phase 3B architecture and documentation only.
- [ADR 0053: Bounded PHP CGI Inbound Conversion](0053-bounded-php-cgi-inbound-conversion.md) — Accepted; Phase 3C bounded inbound conversion implemented.
- [ADR 0054: Bounded PHP CGI Response Emission](0054-bounded-php-cgi-response-emission.md) — Accepted; Phase 3D bounded response staging/emission implemented.
- [ADR 0055: Immutable PHP CGI Operator Bootstrap and Trust Inputs](0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md) — Accepted; Phase 3E immutable operator bootstrap and independent delivery permission evaluation implemented.

Kongono has exactly fourteen acyclic production packages and a root-owned,
test-owned runnable fixture through the Public delivery port. Web Application
retains the sole Kernel scope. The fixture is not a production application,
supported template, listener, SAPI adapter, emitter, or deployment surface.

ADR 0052 and the [PHP CGI profile](../architecture/php-cgi-fastcgi-profile.md)
select PHP `cgi-fcgi`, CGI/1.1, and FastCGI `RESPONDER`. ADR 0053 implements
bounded inbound conversion, ADR 0054 implements bounded fully staged response
emission, ADR 0055 implements typed operator bootstrap plus independent
current-delivery trust evidence, and [ADR 0056](0056-php-cgi-production-delivery-and-entry-composition.md)
implements the concrete Internal delivery and Experimental thin entry into an
already-composed Web Runtime. PHP CGI depends directly on Web Runtime, not Web
Application. ADR 0057 implements the Experimental stateless front controller,
one entry attempt followed by one supplied application-shutdown attempt, and
diagnostic 1019 for simultaneous failure. The deployment constraints are
documented, and Phase 3H supplies external test-owned proof through an operator-
supplied listener without adding a production listener, server configuration,
or deployment artifact. `Production` trust remains denied; uncertain emission
or shutdown requires external process retirement. No
full-framework, Stable API, 1.0, production-readiness, or
production-hardening claim follows.

- [0057 - Bounded PHP CGI Front-Controller Lifecycle and Deployment Constraints](0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md)
- [0058 - Phase 3 External PHP-CGI Acceptance and Checkpoint](0058-phase-3-external-php-cgi-acceptance-and-checkpoint.md)

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A is the current Accepted
architecture boundary: ADR 0059 records one rolling compatibility-certified PHP
minor without changing the mixed `>=8.5` and `^8.5` metadata, and ADR 0060
records planned Kongono Chie, Kongono Hantei, and Kongono Oshie above one future
AI-independent knowledge foundation. No role, knowledge artifact, patch
proposal, package, command, provider, auto-fix, autonomous operation, or
production AI is implemented.

- [0059 - Rolling Current-Minor PHP Baseline](0059-rolling-current-minor-php-baseline.md)
- [0060 - Human-Centered Developer-Intelligence Family](0060-human-centered-developer-intelligence-family.md)
- [0061 - Versioned Knowledge-Source and Provenance Contract](0061-versioned-knowledge-source-and-provenance-contract.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)

Phase 4B is the current Accepted architecture boundary. ADR 0061 defines exact
source-state, source authority, provenance, conflict, citation, deterministic
derivation, disclosure, and framework/workspace separation semantics. It adds
no package, artifact, schema, digest, command, generator, validator, retrieval
system, model, workspace reader, or developer-intelligence runtime. Phase 4C is
the next sequential boundary under separate authentication.

### [ADR 0062: Deterministic Framework-Knowledge Artifact Package and Format](0062-deterministic-framework-knowledge-artifact.md)

Accepted 2026-08-22. Phase 4C implements the acyclic `kongono/knowledge`
package, exact closed 31-source inventory, canonical format and identities,
whole-source citations, complete generation/current validation, bounded
diagnostics, and optional content-addressed atomic initial publication. The
artifact remains derived evidence; no checked-in artifact, model, retrieval,
workspace reader, Graph input, role runtime, mutation, or production dependency
is introduced.

### [ADR 0063: Read-Only Chie Explanation and Diagnostic-Observation Boundary](0063-read-only-chie-explanation-and-diagnosis.md)

Accepted 2026-08-22. Phase 4D implements the acyclic `kongono/chie` package as
an offline consumer of one explicit current Phase 4C artifact. It returns exact
cited sections for six closed topics or bounded literal occurrences of one
supplied Safe diagnostic identifier and exposes unsupported, unavailable,
stale, and blocked states. It adds no free-form prompt, arbitrary workspace
access, model, provider, network, patch, verification, write, Git, deployment,
or production authority.

65. [Cited Version-Matched Oshie Learning and Deterministic Exercises](0065-cited-version-matched-oshie-learning-and-deterministic-exercises.md) — Accepts the Phase 4F closed six-record curriculum, exact cited lessons, deterministic exact-answer checks, and read-only non-authoritative teaching boundary.
66. [Explicit Reviewable Chie Patch Proposals](0066-explicit-reviewable-chie-patch-proposals.md) — Accepts one deterministic unapplied exact-line documentation candidate with reversible diffs, exact provenance, and a pending human/fresh-Hantei handoff.
67. [Cross-Role Developer-Intelligence External Acceptance](0067-cross-role-developer-intelligence-external-acceptance.md) — Accepts the root-owned 28-scenario external harness, shared current identities, offline and failure proof, real versus synthetic Hantei evidence, temporary-only writes, and unchanged role authority.

Phase 4A through 4H are implemented and externally accepted at the bounded
Phase 4 checkpoint. The milestone remains Experimental and pre-1.0; no Phase 5
capability is selected or implied.
