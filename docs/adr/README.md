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
68. [Supported Application Composition and Build Boundary](0068-supported-application-composition-and-build-boundary.md) — Accepts the Phase 5 composition root, deterministic build-before-runtime, persistent-lifecycle, security/provenance, and fixed A-H milestone boundary; Phase 5A is documentation only.
69. [Generated Typed Wiring and No Runtime Service Location](0069-generated-typed-wiring-and-no-runtime-service-location.md) — Accepts generated readable PHP constructor wiring and rejects runtime service location, ambient containers, property-hook DI, request-time reflection, hidden autowiring, and unsupported performance claims.
70. [Versioned Composition Source and Provenance](0070-versioned-composition-source-and-provenance.md) — Accepts the singular inert version-1 source, fixed grammar, bounds, safe-read contract, canonical model, deterministic validation, provenance, and invalidation without implementing them.
71. [Roots, Bindings, Lifetimes, and Cleanup](0071-roots-bindings-lifetimes-and-cleanup.md) — Accepts canonical inputs/services/bindings/typed roots, constructor restrictions, three lifetimes, reachability, reverse cleanup, and deterministic failure precedence without runtime implementation.
72. [Deterministic Composition Artifact and Publication](0072-deterministic-composition-artifact-and-publication.md) — Implements the Experimental build-time package, exact source/model/provenance identities, current validation, and atomic content-addressed no-clobber publication without generated wiring or runtime integration.
73. [Generated Typed Wiring and Lifecycle Boundary](0073-generated-typed-wiring-and-lifecycle-boundary.md) — Implements canonical typed PHP wiring, explicit application/operation lifetime support, and the typed-aggregate correction while reserving loading and integration for Phase 5F.
74. [Generated Assembly and Web Runtime Integration](0074-generated-assembly-web-runtime-integration.md) — Implements exact-current generated loading, one-use startup reflection, the single-root web profile, and existing Web/PHP-CGI lifecycle integration without runtime service location.
75. [Minimal Application Skeleton and Workflow](0075-minimal-application-skeleton-and-workflow.md) — Implements the copy-once minimal skeleton, reusable content-addressed build/check workflow, and bounded one-request PHP-CGI development run without a mutable pointer, listener, deployment automation, or automatic upgrade.
76. [Phase 5 External Application Acceptance](0076-phase-5-external-application-acceptance.md) — Accepts the root-owned 28-scenario external application harness, offline mirrored installation, deterministic result, fail-closed state matrix, nondisclosure, cleanup, and Phase 5 checkpoint evidence boundary.
77. [Layered Data Platform and Explicit SQL Boundary](0077-layered-data-platform-and-explicit-sql-boundary.md) — Accepts the five-layer Deterministic Data Platform direction, explicit SQL escape hatch, provider honesty, operation ownership, and anti-ORM boundary.
78. [Deterministic Data Plans and Operation-Scoped Persistence](0078-deterministic-data-plans-and-operation-scoped-persistence.md) — Accepts deterministic plan evidence, capability-aware provider execution, operation-scoped resources, and explicit transaction ownership.
79. [Neutral Database Session and Provider-Capability Contract](0079-neutral-database-session-and-provider-capability-contract.md) — Accepts the bounded neutral statement, value, session, result, capability, and transaction vocabulary.
80. [Explicit Database Result, Transaction, and Failure Lifecycle](0080-explicit-database-result-transaction-and-failure-lifecycle.md) — Accepts explicit result/stream states, one-attempt transaction terminals, reverse cleanup, combined failures, and no retry.
81. [PostgreSQL PDO Adapter and Real Provider Proof](0081-postgresql-pdo-adapter-and-real-provider-proof.md) — Implements the first internal PDO PostgreSQL adapter and isolated PostgreSQL 17 proof profile.
82. [Immutable Database SQL Plans and PostgreSQL Dialect Compilation](0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md) — Implements bounded immutable plans and a stateless PostgreSQL 17 compiler while preserving raw parameter-bound statements.
83. [Deterministic Schema Migrations and PostgreSQL Execution](0083-deterministic-schema-migrations-and-postgresql-execution.md) — Implements the typed catalog/planner and explicit PostgreSQL 17 migration execution boundary.
84. [Compile-Time Data Mapping and Generated Repositories](0084-compile-time-data-mapping-and-generated-repositories.md) — Implements explicit compile-time mapping evidence and generated typed Data Mappers/repositories without ORM runtime behavior.
85. [Generated Data Application and Operation-Owned Sessions](0085-generated-data-application-and-operation-owned-sessions.md) — Implements mapping-before-composition, exact-current loading, one-operation-root integration, and operation-owned session cleanup.
86. [Phase 6 External Data Platform Acceptance and Checkpoint](0086-phase-6-external-data-platform-acceptance-and-checkpoint.md) — Accepts the root whole-platform PostgreSQL 17 proof, local observational measurement protocol, nondisclosure, cleanup, and exact Phase 6 checkpoint boundary.
87. [Explicit Persistence Model and Compile-Time ORM Boundary](0087-explicit-persistence-model-and-compile-time-orm-boundary.md) — Accepts optional Deterministic Persistence above Phase 6, plain PHP domain ownership, compile-time metadata and ordinary generated PHP direction, explicit load plans, and rejection of runtime-magic ORM defaults.
88. [Operation-Scoped Identity, Change Tracking, and Transaction Authority](0088-operation-scoped-identity-change-tracking-and-transaction-authority.md) — Accepts operation-bounded identity and change state, explicit flush and caller-owned transactions, terminal retirement, concurrency distinction, bounded cascades, and strict nondisclosure.
89. [Neutral Persistence Contracts and Package Boundary](0089-neutral-persistence-contracts-and-package-boundary.md) — Implements the Experimental neutral identity, concurrency, state, value-free change, context-interface, flush-result, diagnostic, and exception vocabulary without a persistence runtime.
90. [Deterministic Persistence Model and Artifact Compilation](0090-deterministic-persistence-model-and-artifact-compilation.md) — Implements one strict application-owned persistence source, current mapping and domain validation, a detached deterministic model, provenance artifact, distinct structural/current proofs, and guarded content-addressed publication without runtime ORM behavior.
91. [Deterministic Generated Persistence State Evidence](0091-deterministic-generated-persistence-state-evidence.md) — Implements deterministic content-addressed ordinary PHP state adapters, non-executing structural and exact-current validation, and guarded publication without a persistence runtime.

Phase 4A through 4H are implemented and externally accepted at the bounded
Phase 4 checkpoint `358e412929d6bb7316195d3a1748c346a2de0e1b`. Phase 5A
accepted ADRs 0068 and 0069 plus the fixed [Phase 5 roadmap](../architecture/phase-5-roadmap.md)
as documentation architecture. Later Phase 5 increments implement the bounded
artifact, generated wiring, runtime integration, and application workflow while
Graph and DI remain unintegrated. Phase 5B
now applies ADR 0059's `>=8.5.0 <8.6.0` range to the root and all eighteen
packages. PHP 8.5 is the sole compatibility-certified minor, with PHP 8.5.9 on
Windows proved; no PHP 8.6, PHP 9, prerelease, nightly, or automatic
future-minor support is claimed. This installability-metadata migration adds no
runtime semantics, dependency, public API, polyfill, shim, release/LTS promise,
or composition/build capability. Phase 5B is accepted and its bounded PHP
constraint is current.

Phase 5C accepts ADRs 0070 and 0071 plus the
[composition source guide](../architecture/composition-source.md) and
[declaration guide](../engineering/composition-declarations.md). The sole
version-1 source is inert application-owned
`<application-root>/kongono.composition.json`; the source/declaration/lifetime
contract is accepted. Phase 5D implements `kongono/composition` as the
nineteenth package with strict validation, canonical model and artifact bytes,
raw-source/project/symbol/builder provenance, exact current validation, and
content-addressed publication. Phase 5E accepts ADR 0073 and adds corrected
typed generation, isolated lifetimes, leases, cleanup, failure composition,
validation, and explicit publication in that package. Phase 5F accepts ADR 0074
and adds the exact-current loader, one-use startup reflection, exact single-root
web profile, and thin existing PHP-CGI bridge. Phase 5G accepts ADR 0075 and
adds one copy-once minimal skeleton plus reusable exact build/check and bounded
one-request application commands. It adds no framework CLI, container, mutable
pointer, new transport, listener, automatic upgrade, deployment behavior, or
Phase 5H work. Copied source stays application-owned; DI and Graph remain
unintegrated and existing lifecycle ownership remains unchanged. Phase 5H
accepts ADR 0076 and adds only the root-owned external harness, documentation
reconciliation, and exact Phase 5 checkpoint. The milestone is a supported
pre-1.0 application-composition slice, not Stable API, a full framework,
production-readiness certification, deployment behavior, or Phase 6 authority.

Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05` with twenty-seven production
packages. Phase 7A accepts ADRs 0087 and 0088, the
[Deterministic Persistence guide](../architecture/deterministic-persistence.md),
and the fixed [Phase 7 roadmap](../architecture/phase-7-roadmap.md) as
architecture. Phase 7B accepts ADR 0089 and adds the Experimental neutral
`kongono/persistence` contract package. Phase 7C accepts ADR 0090 and adds the
Experimental `kongono/persistence-build` package, bringing the current graph to
twenty-nine packages. Direct Phase 6 SQL and Data Mapper paths remain first-
class. The package provides bounded build-time source/model/artifact evidence,
then Phase 7D adds deterministic content-addressed ordinary PHP state adapters,
non-executing structural/current validation, and guarded publication. Phase 7E
accepts [ADR 0092](0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md)
and implements one operation-scoped single-entity context over a caller-owned
database executor. Phase 7F accepts ADR 0093 and adds a separate repository
source, bounded neutral aggregate evidence, and deterministic stateless generated
repositories. It adds no generated-code loading, transaction ownership, SQL
generation, or Composition integration. Phase 7G accepts ADR 0094 and adds the
outer exact-current loading/Composition workflow. Phase 7H accepts ADR 0095 and
closes the thirty-package milestone through PostgreSQL 18.6 external acceptance,
PostgreSQL 17.2 historical regression, named performance evidence,
documentation, cleanup/nondisclosure, and the exact checkpoint.

- [ADR 0093: Explicit load plans, bounded cascades, and generated aggregate repositories](0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md)
- [ADR 0094: Exact-current generated persistence application integration](0094-exact-current-generated-persistence-application-integration.md)
- [ADR 0095: Phase 7 external persistence acceptance and checkpoint](0095-phase-7-external-persistence-acceptance-and-checkpoint.md)
- [ADR 0096: Explicit Structured CLI and Automation Boundary](0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097: Deterministic Command Lifecycle and Presentation Separation](0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [ADR 0098: Neutral Structured CLI Contracts and Package Boundary](0098-neutral-structured-cli-contracts-and-package-boundary.md)
- [ADR 0099: Deterministic Command Parsing and Definition Evidence](0099-deterministic-command-parsing-and-definition-evidence.md)
- [ADR 0100: Operation-Scoped Command Execution and Cleanup](0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0101: Deterministic Command Presentation and Process Adaptation](0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0102: First Read-Only Framework Capability Commands](0102-first-read-only-framework-capability-commands.md)
- [ADR 0103: Supported `kongo` Edge and Honest Application Build](0103-supported-kongo-edge-and-honest-application-build.md)
- [ADR 0104: Phase 8 External CLI Acceptance and Checkpoint](0104-phase-8-external-cli-acceptance-and-checkpoint.md)
- [ADR 0105: Explicit Identity, Authentication, and Authorization Boundary](0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106: Fail-Closed Security Context and Policy Separation](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0107: Neutral Identity and Access Contracts and Package Boundary](0107-neutral-identity-and-access-contracts-and-package-boundary.md)
- [ADR 0108: Deterministic Explicit Authentication Orchestration](0108-deterministic-explicit-authentication-orchestration.md)
- [ADR 0109: Deterministic Deny-by-Default Authorization Evaluation](0109-deterministic-deny-by-default-authorization-evaluation.md)
- [ADR 0110: Operation-Scoped Security Context and Generated Composition Inputs](0110-operation-scoped-security-context-and-generated-composition-inputs.md)
- [ADR 0111: Secure Browser-Session Transport and Capability Enforcement](0111-secure-browser-session-transport-and-capability-enforcement.md)
- [ADR 0112: Deterministic Security Policy Evidence and Supported Application Workflow](0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
- [ADR 0113: Phase 9 External Security Acceptance and Checkpoint](0113-phase-9-external-security-acceptance-and-checkpoint.md)

Phase 7 is complete at checkpoint
`b09b0c654f8b67f7cac3807f839f665e8c5b5469` with thirty acyclic production
packages. Phase 8A accepts ADRs 0096 and 0097, the
[Structured CLI guide](../architecture/structured-cli.md), and the fixed
[Phase 8 roadmap](../architecture/phase-8-roadmap.md) as documentation
architecture. Phase 8B accepts ADR 0098 and adds the Experimental neutral
`kongono/console` package. Phase 8C accepts ADR 0099, adds deterministic
already-tokenized parsing, and adds Experimental `kongono/console-build` for
canonical exact-current command-definition evidence. Phase 8D accepts ADR 0100
and adds Experimental `kongono/console-runtime` for explicit handler binding,
one fresh operation per dispatch, cancellation, reverse cleanup, combined
failure preservation, and sequential isolation. Phase 8E accepts ADR 0101,
adds bounded result records and Experimental `kongono/console-presentation`.
Phase 8F accepts ADR 0102 and adds Experimental `kongono/console-commands` with
three exact read-only capability handlers and generated Composition wiring.
Phase 8G accepts ADR 0103 and adds Experimental
`kongono/console-application`, bringing the topology to thirty-six packages.
The official one-shot `kongo` executable implements exactly
`application:build`: non-mutating `--plan`, explicit `--apply`, no prompt, and
shared human/machine result and exit semantics. Phase 8F snapshots remain
programmatic rather than fabricated. Phase 8H accepts ADR 0104 and adds only
root-owned real-process acceptance plus the exact local Phase 8 checkpoint.
Conflicting output remains a no-clobber Precondition at exit 3; exit 6 remains
Infrastructure. Command meaning, explicit invocation, and presentation remain
separate. Phase 8A through Phase 8H are implemented.

Phase 9A accepts ADRs 0105 and 0106, the
[Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md) as
documentation architecture only. It separates identity, authentication,
authorization, transport, enforcement, disclosure, audit, and telemetry;
requires deny-by-default exact authorization, protected-capability enforcement,
immutable operation-scoped security state, deterministic policy evidence, and
nondisclosure; and adds no package, code, API, provider, session, policy engine,
schema, command, or runtime.

Phase 9B accepts ADR 0107 and adds Experimental `kongono/identity-access` as the
thirty-seventh acyclic production package. Its immutable neutral values express
principal kinds/references, typed authentication outcomes, inert authorization
decisions, canonical application actions and resource types, opaque resource
references, and explicit anonymous/authenticated Security Context. The package
owns bounded non-disclosing diagnostics and exceptions but no authenticator,
verifier, credential, cryptography, policy evaluator, session, HTTP or
Composition integration, enforcement, provider, command, audit, telemetry, or
workflow.

Phase 9C accepts ADR 0108 and adds Experimental `kongono/authentication` as the
thirty-eighth acyclic production package. It selects one explicitly registered
verifier by exact mechanism from zero or one caller-owned evidence object,
owns one verify/retire attempt lifecycle, preserves primary and cleanup
failures, and validates explicit facts against caller-supplied strength,
freshness, validity, and current-instant requirements. Authenticated facts grant
no permission. No credential, concrete provider, session/cookie transport, HTTP
adapter, authorization/policy evaluation, Security Context construction,
Composition wiring, audit, telemetry, command, application workflow, or Phase
9D behavior is added.

Phase 9D accepts ADR 0109 and adds Experimental `kongono/authorization` as the
thirty-ninth acyclic production package. It compares independently expected
policy identity with opaque policy evidence, selects only one exact evaluator,
preserves Permit/Deny/Indeterminate, fails closed on mismatch, unsupported
policy, or evaluator failure, and returns only a bounded non-authoritative
receipt. It adds no policy language/build/loader, role or permission semantics,
ambient guard, protected-capability enforcement, Security Context construction,
HTTP/session transport, Composition wiring, database enforcement, audit,
telemetry, AI authority, or workflow.

Phase 9E accepts ADR 0110, evolves Composition with explicit version-2
application/operation input lifetimes while preserving version-1 inputs as
application-lifetime, and adds Experimental `kongono/security-runtime` as the
fortieth acyclic production package. Fresh anonymous or exactly verified
authenticated contexts flow into one generated operation and are never retained
by generated application state. Authentication success grants no authorization.
That Phase 9E boundary itself implemented no enforcement, HTTP credential/
session transport, login, cookie profile, policy build/currentness, workflow,
or skeleton. See
[ADR 0109](0109-deterministic-deny-by-default-authorization-evaluation.md).
[ADR 0110](0110-operation-scoped-security-context-and-generated-composition-inputs.md)
records the implemented Phase 9E boundary.

Phase 9F accepts ADR 0111 and adds Experimental `kongono/security-http` and
`kongono/security-enforcement` as the forty-first and forty-second acyclic
production packages. Security HTTP implements exactly the same-origin HTTPS
`browser.session.v1` profile with `__Host-kongono_session` and session-bound
`X-Kongono-CSRF` evidence; syntax and emission provide no session truth,
cryptography, storage, login, or authorization. Security Enforcement evaluates
one exact request and invokes the protected capability only for exact Permit.
Authentication success, routing, middleware, and receipts grant no authority,
and no ambient current-user/session/decision state exists. No second profile/
provider or account product. Phase 9G accepts ADR 0112 and adds Experimental
`kongono/security-policy-build` and `kongono/security-application` as the
forty-third and forty-fourth acyclic production packages. Their rule-free
artifact identifies exact current policy/build bytes, while the supported
workflow preserves application-owned decisions and exact-Permit enforcement.
The copy-once skeleton and read-only inspect/check commands add no provider or
framework policy authority. Phase 9H accepts ADR 0113 and adds only root-owned
external acceptance plus the exact local checkpoint. Phase 9A through 9H are
implemented; Kongono remains pre-1.0 and Phase 10 is unimplemented.
See [ADR 0111](0111-secure-browser-session-transport-and-capability-enforcement.md)
and [ADR 0112](0112-deterministic-security-policy-evidence-and-supported-application-workflow.md),
plus the [external acceptance guide](../architecture/phase-9-external-acceptance.md).
