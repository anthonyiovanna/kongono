# Architecture Decision Records

## Phase 19A-H Asset Platform decisions

### [ADR 0188: Optional Asset Platform and deterministic build boundary](0188-optional-asset-platform-and-deterministic-build-boundary.md)

Accepted for Phase 19A. Defines the optional language-neutral Asset Platform,
explicit declarations, distinct identities, deterministic build evidence,
contained content-addressed publication, native or no-bundler flow, and
replaceable-adapter direction. It creates no schema, package, adapter, build, or
runtime.

### [ADR 0189: Exact-current asset resolution and browser delivery policy](0189-exact-current-asset-resolution-and-browser-delivery-policy.md)

Accepted for Phase 19A. Separates exact-current resolution evidence from URLs,
storage paths, content metadata, browser policy, HTTP delivery, CDN selection,
security policy, and external effects. It creates no resolver, URL policy,
browser integration, HTTP adapter, or delivery behavior.

### [ADR 0190: Neutral asset contract and bounded declaration vocabulary](0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)

Accepted for Phase 19B. Adds only the optional Experimental
`kongono/asset-contract` leaf: bounded immutable identities, closed asset and
content vocabularies, canonical declarations and relations, integrity evidence,
inert preload hints, delivery metadata, and nondisclosing diagnostics. It adds
no path, URL, filesystem, build, adapter, HTTP, view, or runtime API.

### [ADR 0191: Deterministic native asset graph and build artifact](0191-deterministic-native-asset-graph-and-build-artifact.md)

Accepted for Phase 19C. Adds only the optional Experimental
`kongono/asset-build` leaf: explicit complete bounded in-memory sources,
deterministic graph validation, fixed byte-identical native copying, canonical
artifact and exact-currentness validation, and contained content-addressed
no-clobber publication. It adds no adapter, subprocess, network, URL/HTTP/view
integration, runtime resolver, watcher, cache, command, Vite, or Node
dependency.

### [ADR 0192: Replaceable asset-build adapter and bounded Vite integration](0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)

Accepted for Phase 19D. Adds only the optional Experimental
`kongono/asset-adapter-contract` neutral boundary and separately optional
Experimental `kongono/asset-adapter-vite` fixed-profile implementation. One
explicit application-provided executable may run once without a shell in a
contained workspace; strict manifest translation, complete ordinary-file
output validation, neutral artifact validation, and cleanup keep tool output
untrusted. It installs no Node/Vite dependency and adds no network, package
installation, watcher/HMR, development server, runtime resolution, URL/HTTP/view
integration, or frontend behavior.

### [ADR 0193: Exact-current asset resolution and explicit browser/HTTP policy](0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)

Accepted for Phase 19E. Adds the optional Experimental effect-free resolver
over fully revalidated native or adapter evidence and explicit caller-owned
URL, cache, integrity, and preload policy.

### [ADR 0194: Supported asset application workflow and explicit View integration](0194-supported-asset-application-workflow-and-explicit-view-integration.md)

Accepted for Phase 19F. Adds the optional Experimental application workflow,
three explicit commands, copy-once native slice, and application-owned Phase
18 value mapping.

### [ADR 0195: Visibility-safe asset inspection and developer intelligence](0195-visibility-safe-asset-inspection-and-developer-intelligence.md)

Accepted for Phase 19G. Adds the optional Experimental offline evidence leaf
over one supplied current result, with bounded Safe/Protected projections and
no role execution or inspection effect.

### [ADR 0196: Phase 19 external asset acceptance and checkpoint](0196-phase-19-external-asset-acceptance-and-checkpoint.md)

Accepted for Phase 19H. Adds only root-owned deterministic fresh-process
acceptance, including native and protocol-emulated Vite-adapter evidence, and
the exact local checkpoint. Phase 19A-H are Accepted and Phase 19 is closed.
See the [external acceptance guide](../architecture/phase-19-external-asset-acceptance.md)
and [fixed roadmap](../architecture/phase-19-roadmap.md).

> **Current through Phase 18H:** Phase 17A-G accept the optional Experimental
> OpenAI Responses mapping, one-shot and pull-streaming adapters, strict
> structured and inert action-suggestion profiles, exact-current application,
> and visibility-safe inspection. Phase 17H accepts ADR 0178, adds the
> root-owned local provider-protocol acceptance proof, creates the exact local
> checkpoint, and closes Phase 17. Phase 18A accepts ADRs 0179 and 0180 plus
> the fixed roadmap as documentation architecture. Phase 18B accepts ADR 0181
> and the optional Experimental `kongono/view-contract` neutral leaf. Phase 18C
> accepts ADR 0182 and the optional Experimental deterministic `kongono/view-build`
> leaf. Phase 18D accepts ADR 0183 and the optional Experimental exact-current
> `kongono/view-runtime` leaf. Phase 18E accepts ADR 0184 and the optional
> Experimental `kongono/view-http` completed-result adapter. Phase 18F accepts
> ADR 0185, the optional Experimental `kongono/view-application` workflow,
> three view commands, and the copy-once example. Phase 18G accepts ADR 0186
> and the optional Experimental `kongono/view-inspection` offline evidence leaf.
> Phase 18H accepts ADR 0187, the root-owned local acceptance proof, and the
> exact checkpoint; Phase 18 is closed.
> The graph is 95 packages / 402 local edges / acyclic; PHP inventories are
> 1,861 / 962 / 96.

> **Current through Phase 16H:** Phase 16A accepts ADR 0163 as documentation
> architecture. Phase 16B accepts ADR 0164 and the optional Experimental
> `kongono/external-service-contract` neutral value and port package. Phase 16C
> accepts ADR 0165 and the optional Experimental
> `kongono/external-service-binding-build` package for deterministic canonical
> secret-free artifact and ordinary-PHP evidence from one explicit typed source.
> Validation is structural or caller-relative to one explicit current source;
> generated PHP is never loaded or executed. Phase 16D accepts ADR 0166 and the
> optional Experimental bounded `ext-sockets` system-resolver leaf. Phase 16E
> accepts ADR 0167 and the optional Experimental operation-scoped credential
> lease runtime. Phase 16F accepts ADR 0168 and the optional Experimental
> exact-current one-operation application coordinator. Phase 16G accepts ADR
> 0169 and the optional Experimental visibility-safe external-service inspection
> leaf. Phase 16H accepts ADR 0170, adds root-owned local external-service
> acceptance, creates the exact local checkpoint, and closes Phase 16. The
> graph is 87 packages / 355 local edges / acyclic; PHP inventories are 1,647 /
> 851 / 88. Phase 17 has not begun.

> **Current through Phase 15H:** Phase 15A accepts ADR 0155, Phase 15B accepts
> ADR 0156 and the optional Experimental neutral network-egress contract, and
> Phase 15C accepts ADR 0157 and deterministic inert offline policy evidence.
> Phase 15D accepts ADR 0158 and the optional Experimental bounded runtime over
> explicit caller-owned collaborators. Phase 15E accepts ADR 0159 and the
> optional Experimental cURL HTTP/1.1 adapter. Phase 15F accepts ADR 0160 and
> the optional Experimental exact-current single-profile application workflow.
> Phase 15G accepts ADR 0161 and the optional Experimental offline
> visibility-safe inspection and bounded-evidence leaf. Phase 15H accepts ADR
> 0162, adds bounded local external acceptance, and closes Phase 15 at one exact
> local checkpoint. The graph is 81 packages / 317 local edges / acyclic; PHP
> inventories are 1,514 / 768 / 84.
> No concrete resolver, credential provider, provider adapter, live inspection
> probe, inspection command, public compatibility certification, Stable/1.0
> claim or production-readiness claim exists. Phase 16 requires separate
> authority.

> **Phase 14A current boundary:** The provider-neutral model-invocation and
> adapter boundary, architecture guide, and fixed Phase 14 roadmap are Accepted
> documentation architecture only. Future invocation remains optional, uses an
> explicit application-supplied outer adapter, and can produce only untrusted
> normalized evidence. It cannot bypass Phase 13 authority. No Phase 14
> provider, SDK, profile, credential mechanism, network client, package, API,
> schema, command, adapter, runtime, or Agent bridge exists in that Phase 13
> boundary; Phase 14B adds its separate neutral contract package and Phase 14C
> adds only deterministic privacy-preserving plan evidence and publication.

> **Phase 13 closed boundary:**
> [ADR 0140](0140-neutral-versioned-agent-principal-contract-foundation.md)
> implements the opt-in neutral Agent Principal contract package, and
> [ADR 0141](0141-deterministic-agent-declaration-artifact.md) implements only
> deterministic declaration artifact build, strict typed validation, explicit
> caller-relative currentness, and content-addressed no-clobber publication.
> [ADR 0142](0142-deterministic-agent-admission-and-authority-plan-evidence.md)
> adds deterministic application-owned admission and inert authority-plan
> evidence. [ADR 0143](0143-provider-neutral-bounded-agent-principal-runtime.md)
> adds one synchronous provider-neutral runtime over explicit caller-owned
> proposal and capability ports. [ADR 0144](0144-exact-current-single-profile-agent-application-workflow.md)
> adds one exact-current application profile, deterministic generated PHP
> wiring, explicit contained loading, and one synchronous operation at a time.
> [ADR 0145](0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md)
> adds optional deterministic read-only Safe aggregate inspection and inert
> bounded cross-system evidence without recording, role execution, repair, or
> authority. The graph is 69 acyclic packages / 260 local production edges.
> [ADR 0146](0146-phase-13-external-agent-principal-acceptance-and-checkpoint.md)
> adds only root-owned deterministic offline external acceptance and the exact
> local checkpoint. Provider SDKs, profile registries, autonomous loops, and
> live monitoring do not exist. Phase 14A adds documentation architecture;
> Phase 14B adds the separate neutral contract package and Phase 14C adds the
> separate plan-artifact package. The current graph is 71 packages / 265 local
> edges / acyclic, and Phase 14D remains planned.

> **Phase 12F current boundary:** ADR 0132 is the historical neutral contract
> foundation. ADR 0133 implements deterministic declaration artifacts,
> caller-relative currentness, and atomic no-clobber publication. The graph is
> 62 packages / 220 acyclic local edges; Phase 12F is implemented and Phase 12G
> has not started.


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
- [ADR 0114: Explicit Observability and Operational Evidence Boundary](0114-explicit-observability-and-operational-evidence-boundary.md)
- [ADR 0115: Structured Signals, Explicit Context, and Exporter Neutrality](0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0116: Neutral Observability Contracts and Package Boundary](0116-neutral-observability-contracts-and-package-boundary.md)
- [ADR 0117: Operation-Scoped Tracing Lifecycle and Typed Propagation](0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
- [ADR 0118: Bounded-Cardinality Metric Aggregation and Explicit Collection Lifecycle](0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
- [ADR 0119: Structured Operational Events and Bounded Security Audit Evidence](0119-structured-operational-events-and-bounded-security-audit-evidence.md)
- [ADR 0120: Explicit OpenTelemetry OTLP HTTP Protobuf Bridge](0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md)

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
implemented; Kongono remains pre-1.0 and Phase 10 runtime behavior is
unimplemented.
See [ADR 0111](0111-secure-browser-session-transport-and-capability-enforcement.md)
and [ADR 0112](0112-deterministic-security-policy-evidence-and-supported-application-workflow.md),
plus the [external acceptance guide](../architecture/phase-9-external-acceptance.md).

ADR 0011 remains a byte-identical earlier Accepted strategic direction for
OpenTelemetry-oriented observability. Phase 10A does not supersede, downgrade,
or edit it. ADRs 0114 and 0115 refine that direction into the Accepted modern
Observability and Operational Evidence boundary: separated signal domains,
typed structured evidence, explicit operation context, nondisclosure and
cardinality budgets, bounded lifecycle/backpressure, exporter neutrality, and
outer OpenTelemetry interoperability.

Phase 10B accepts ADR 0116 and adds the Experimental `kongono/observability`
neutral contract package over only API Metadata, Diagnostics, and PHP. The
Phase 10B graph was exactly forty-five acyclic production packages. Phase 10C
accepts ADR 0117 and adds the Experimental `kongono/tracing` package over only
Observability, API Metadata, Diagnostics, and PHP. Phase 10D accepts ADR 0118
and adds the independent Experimental `kongono/metrics` package over the same
foundations. Phase 10E adds the separate Experimental Operational Events and
Security Audit siblings over the same foundations, bringing the current graph
to exactly forty-nine acyclic packages and 166 local edges. No other production
package depends on Tracing, Metrics, Operational Events, or Security Audit. See the
[Observability and Operational Evidence guide](../architecture/observability-and-operational-evidence.md)
and fixed [Phase 10 roadmap](../architecture/phase-10-roadmap.md). Phase 10C
implements bounded operation/span lifecycle, typed propagation without carrier
parsing, deterministic Record/Drop mapping, idempotent cleanup, isolation, and
nondisclosing collaborator/recording failures. Phase 10D implements bounded
metric schemas and plans, immediate Counter/UpDownCounter/Gauge/Histogram
aggregation, deterministic observations, and explicit Active/Collected/Retired
collection cleanup. Phase 10E accepts ADR 0119 and adds the separate sibling
Experimental `kongono/operational-events` and `kongono/security-audit` packages
over only Observability, API Metadata, Diagnostics, and PHP. Finite schemas,
canonical typed values, supplied-field disclosure, explicit event severity and
status, explicit audit outcome and retention intent, and `not_guaranteed`
durability produce bounded observations through one explicit recorder. The
packages do not depend on or convert to one another. The current graph is
exactly forty-nine acyclic packages and 166 local edges. No logger facade,
buffer, retry, exporter, store, retention enforcement, tamper evidence,
compliance claim, application integration, or Stable API is implemented. Phase
10F is the sole possible next handoff and requires separately authenticated
architect authority.

Phase 10F accepts ADR 0120 and implements the outer Experimental
`kongono/opentelemetry` package. The locked `kongono-otlp-http-protobuf`
profile version 1 directly builds generated protobuf requests and hands bytes
to one explicit transport with bounded queue, backpressure, partial progress,
flush, close, cleanup, disclosure, and isolation semantics. The package depends
only on PHP, API Metadata, Diagnostics, Observability, generated OTLP protobuf
1.10.0, and stable semantic conventions 1.38.0. The graph is exactly fifty
acyclic packages and 169 local edges. No SDK/provider/global state, HTTP client,
retry, backend, application workflow, or durable audit claim is added.

Phase 10G accepts ADR 0121 and adds deterministic observability configuration,
the supported application workflow, the copy-once observability skeleton, and
the two read-only Console commands. Phase 10H accepts
[ADR 0122](0122-phase-10-external-observability-acceptance-and-checkpoint.md)
and closes the Phase 10 milestone with root-owned external acceptance plus one
exact local checkpoint. The harness is test evidence only: it adds no production
exporter, provider, backend, collector, durable audit store, deployment behavior,
or Stable API. See the
[Phase 10 external acceptance guide](../architecture/phase-10-external-acceptance.md).
Kongono remains pre-1.0.

### [ADR 0123: Authoritative Application Graph and Language-Neutral IR Boundary](0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)

Accepted 2026-08-28. Phase 11A establishes the producer-IR versus derived-Graph
separation, exact-state authority, identity/schema/provenance/visibility and
determinism requirements, non-authority security boundary, consumer omission
semantics, and fixed Phase 11A-H dependency order. It is documentation-only;
the Phase 0J structural package remains unchanged and no later Graph capability
is implemented.

### [ADR 0124: Versioned Application Graph Contract Foundation](0124-versioned-application-graph-contract-foundation.md)

Accepted 2026-08-28. Phase 11B creates the separate Experimental
`kongono/application-graph-contract` package with exact version-1 identities,
closed Composition vocabularies, typed schema descriptors and catalog, bounded
producer/source provenance, visibility, disclosure, and non-disclosing
diagnostics. The Phase 0J package remains unchanged; IR records, Graph
construction, projection, artifacts, commands, consumers, and operational
authority remain unimplemented.

### [ADR 0125: Language-Neutral Application Graph IR and Deterministic Construction](0125-language-neutral-application-graph-ir-and-deterministic-construction.md)

Accepted 2026-08-28. Phase 11C creates the separate Experimental
`kongono/application-graph-build` package for exact version-1 IR claims,
complete schema and endpoint admission, duplicate and bound rejection, and
canonical immutable Graph facts. Construction is all-or-nothing, deterministic,
state-free, and non-disclosing. Composition projection, serialization,
currentness, publication, inspection, consumers, and AI authority remain later
separately governed work.

### [ADR 0126: Supported Composition-to-IR Projection and Application Graph Build](0126-supported-composition-to-ir-projection-and-application-graph-build.md)

Accepted 2026-08-28. Phase 11D implements the pure in-memory Composition v2
projection and one-call Graph build in `kongono/application-graph-composition`.

### [ADR 0127: Canonical Application Graph Artifact, Currentness, and Publication](0127-canonical-application-graph-artifact-currentness-and-publication.md)

Accepted 2026-08-28. Phase 11E implements pure canonical Graph artifacts,
content-derived Graph and artifact identities, caller-relative exact-current
validation, explicit guarded loading, and atomic content-addressed no-clobber
publication. Builder metadata is stable package/contract evidence and does not
attest to runtime source-code bytes.

### [ADR 0128: Bounded Application Graph Inspection and Supported CLI Consumption](0128-bounded-application-graph-inspection-and-supported-cli-consumption.md)

Accepted 2026-08-29. Phase 11F adds the artifact-owned immutable aggregate
inspection and the supported one-path `application-graph:inspect` command. It
returns only exact identities and closed-vocabulary counts through the existing
ordinal Console result, makes no external-currentness claim, and exposes no
individual Graph fact.

### [ADR 0129: Visibility-Safe Application Graph Developer Intelligence](0129-visibility-safe-application-graph-developer-intelligence.md)

Accepted 2026-08-30. Phase 11G adds the separate Experimental
`kongono/application-graph-intelligence` package. One role-addressed Safe
aggregate view is derived only from a validated Phase 11F inspection and can be
independently verified through six bounded checks. It exposes no individual
Graph fact, runs no Chie, Hantei, or Oshie role, and grants no runtime,
authorization, mutation, registry, model, provider, or network authority.

### [ADR 0130: Phase 11 External Application-Graph Acceptance and Checkpoint](0130-phase-11-external-application-graph-acceptance-and-checkpoint.md)

Accepted 2026-08-30. Phase 11H adds only the root-owned external 40-case
application-graph proof and the exact local Phase 11 checkpoint. The harness is
test evidence, adds no production capability, and the checkpoint excludes every
implementation directive and task report. Phase 11 is complete at checkpoint
`081ae7f50ad4081bb3890a63a652a0bf782a6da1`; Phase 12A is documentation only.

### [ADR 0131: Governed Extension Platform and Attributable Contribution Boundary](0131-governed-extension-platform-and-attributable-contribution-boundary.md)

Accepted 2026-08-30. Phase 12A defines explicit application selection; keeps
declaration, admission, capability grant, and execution separate; records
honest trusted/in-process semantics; and treats extension contributions as
attributable language-neutral IR inputs rather than automatic Graph facts or
authority. It fixes the Phase 12A-H dependency order as documentation only.
Phase 12A adds no package or runtime and is historical and complete.

### [ADR 0132: Neutral Versioned Extension Contract Foundation](0132-neutral-versioned-extension-contract-foundation.md)

Accepted and implemented by Phase 12B. The Experimental
`kongono/extension-contract` package depends only on API Metadata and
Diagnostics and supplies inert versioned declaration evidence. Local
construction is not admission, currentness, or trust; requests are not grants,
contributions are not Graph facts, visibility is not authorization, lifecycle
is not hosting, and category is not a security decision. The topology is
58 packages / 203 acyclic local edges, no Application Graph integration exists,
and Phase 12C builds deterministic evidence above that neutral foundation.

### [ADR 0133: Deterministic Extension Declaration Artifact](0133-deterministic-extension-declaration-artifact.md)

Accepted and implemented by Phase 12C. The Experimental
`kongono/extension-build` package preserves exact Phase 12B fields in canonical
version-1 JSON, derives declaration-set and complete-artifact identities,
reconstructs every neutral value during strict validation, compares currentness
only with caller-supplied declarations, and publishes atomically without
clobber. The artifact is not admission, a grant, a Graph fact, loaded code, or
execution authority; no public loader exists. The Phase 12C topology was 59 packages / 206
acyclic local edges. [ADR 0134](0134-deterministic-extension-selection-admission-and-graph-ir-projection.md)
accepts Phase 12D exact application-owned selection, explicit conflict winners,
and attributable structurally validated Graph IR. The Phase 12D topology was 60
packages / 212 local edges at that checkpoint.

### [ADR 0135: Explicit Extension Capability Grants and Generated Wiring](0135-explicit-extension-capability-grants-and-generated-wiring.md)

Accepted and implemented by Phase 12E. The Experimental
`kongono/extension-composition` package consumes exact Phase 12D admission,
requires one exact application-owned grant or denial for every request,
validates exact trusted/in-process implementation and provider symbols at build
time, and emits deterministic ordinary typed PHP. Generated bytes are inert:
validation does not execute them and optional publication is contained,
content-addressed, atomic, initial-only, idempotent, and no-clobber. This adds no
runtime host, loader, resolver, container, sandbox, external protocol,
authorization semantics, or generated autoloading. Its checkpoint topology was
61 packages / 217 local edges.

### [ADR 0136: Bounded Trusted In-Process Extension Host Lifecycle](0136-bounded-trusted-in-process-extension-host-lifecycle.md)

Accepted and implemented by Phase 12F. The Experimental
`kongono/extension-host` package binds one exact generated file to its artifact
and grant plan, performs one bounded load and one-time shape validation, then
owns ordered application and operation lifecycle with reverse cleanup, exact
leases, retirement, and deterministic failure preservation. Trusted PHP remains
non-sandboxed. The current topology is 62 packages / 220 local edges; Phase 12G
is implemented under [ADR 0137](0137-visibility-safe-extension-inspection-and-developer-intelligence-evidence.md)
as detached Safe aggregate inspection without currentness, authority, host
observation, or generated-PHP loading. The current topology is 63 packages /
226 local edges. [ADR 0138](0138-phase-12-external-extension-platform-acceptance-and-checkpoint.md)
accepts the root-owned Phase 12 external acceptance process and exact local
checkpoint. This evidence changes no production package or authority boundary.

### [ADR 0139: Governed Agent Principals and Deterministic Authority Boundary](0139-governed-agent-principals-and-deterministic-authority-boundary.md)

Accepted 2026-08-31. Phase 13A defines an Agent Principal as an application
actor kind whose identity grants no authentication, authorization, delegation,
consent, capability, visibility, or execution authority. Model, provider,
prompt, retrieval, memory, extension, Graph, inspection, and developer-tool
output cannot make authoritative allow/deny decisions. Future capabilities are
explicit, typed, bounded, attributable, and denied by default; future work has
explicit lifecycle ceilings, cancellation, revocation, retirement, and cleanup.
Phase 13A is documentation only and fixes the separately authorized Phase
13A-H dependency order without implementing a package, schema, provider,
protocol, runtime, command, workflow, or Agent Principal behavior.

### [ADR 0140: Neutral Versioned Agent Principal Contract Foundation](0140-neutral-versioned-agent-principal-contract-foundation.md)

Accepted 2026-08-31. Phase 13B implements the opt-in Experimental
`kongono/agent-principal-contract` package for Agent-only Principal references,
bounded intent and proposal values, capability requests, inert constraints,
external evidence references, provenance, visibility, and receipt claims. The
package grants no authority and performs no work; Phase 13C's separate artifact
boundary does not change those contract semantics.

### [ADR 0141: Deterministic Agent Declaration Artifact](0141-deterministic-agent-declaration-artifact.md)

Accepted 2026-08-31. Phase 13C implements the opt-in Experimental
`kongono/agent-declaration-build` package for one explicit application-owned
canonical declaration artifact, strict typed reconstruction, explicit
caller-relative currentness, and atomic content-addressed no-clobber
publication. The artifact is declaration evidence rather than authentication,
admission, authority, grants, provider/model work, execution, or a receipt.
Phase 13D is implemented separately and does not change declaration-artifact
authority semantics.

### [ADR 0142: Deterministic Agent Admission and Inert Authority-Plan Evidence](0142-deterministic-agent-admission-and-authority-plan-evidence.md)

Accepted 2026-08-31. Phase 13D implements the opt-in Experimental
`kongono/agent-admission` package. It binds one exact current declaration to
explicit completed application-owned authentication and authorization evidence,
denies undeclared and unpermitted capability by default, and returns immutable
admission and authority-plan evidence. The package evaluates no credential or
policy, grants and enforces nothing, performs no model or tool work, and adds no
runtime integration within that package. Phases 13E through 13G are implemented
separately; Phase 13H is completed at the Phase 13 checkpoint.

### [ADR 0143: Provider-Neutral Bounded Agent Principal Runtime](0143-provider-neutral-bounded-agent-principal-runtime.md)

Accepted 2026-08-31. Phase 13E implements the opt-in Experimental
`kongono/agent-runtime` package. It binds one exact declaration to one matching
admitted plan, rechecks caller-owned current-authority evidence before each
possible effect, applies all nine explicit budgets, and coordinates at most one
proposal and one exact capability invocation with fixed cancellation/time
checkpoints, deterministic receipts, and reverse cleanup. It adds no provider
SDK, ambient registry, application workflow, autonomous loop, retained session,
or sandbox. Phases 13F and 13G are implemented separately; Phase 13H is
completed at the Phase 13 checkpoint.

### [ADR 0144: Exact-Current Single-Profile Agent Application Workflow](0144-exact-current-single-profile-agent-application-workflow.md)

Accepted 2026-08-31. Phase 13F implements the opt-in Experimental
`kongono/agent-application` package. It binds one exact current declaration
artifact and matching admitted plan into one immutable profile, deterministic
content-addressed PHP wiring, explicit snapshot/reverified loading, and one
synchronous application operation at a time. Cleanup ownership transfers
exactly once to the Phase 13E runtime. Profiles and generated identity are
evidence rather than authority; no provider, registry, command, retained
session, background work, live monitoring, or sandbox is added. Phase 13G is
implemented separately; Phase 13H is completed at the Phase 13 checkpoint.

### [ADR 0145: Visibility-Safe Agent Principal Inspection and Bounded Evidence](0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md)

Accepted 2026-08-31. Phase 13G implements the optional Experimental
`kongono/agent-inspection` upper evidence leaf. It revalidates one exact
supplied profile/wiring pair, optionally summarizes one completed runtime
result, and creates Safe aggregate Graph, Observability, Security Audit, and
role-addressed evidence without loading, execution, recording, repair, hidden
data access, live-state observation, or authority. The graph is 69 packages /
260 local edges / acyclic.

### [ADR 0146: Phase 13 External Agent Principal Acceptance and Checkpoint](0146-phase-13-external-agent-principal-acceptance-and-checkpoint.md)

Accepted 2026-08-31. Phase 13H adds the root-owned 40-case deterministic
offline Agent Principal process harness and one exact local checkpoint. It
changes no production PHP, package manifest, dependency, lockfile, topology,
or authority boundary. The harness is bounded, cleanup-complete,
repository-preserving, and disclosure-safe test evidence. Phase 13 is closed at
checkpoint `63376c802ac4672c21458b67b2fcadaf8e03a019`; there is no push, tag,
release, or Stable/1.0 claim.

### [ADR 0147: Provider-Neutral Model Invocation and Adapter Boundary](0147-provider-neutral-model-invocation-and-adapter-boundary.md)

Accepted 2026-08-31. Phase 14A defines the optional provider-neutral
model-invocation boundary above application-owned policy and beside the Phase 13
authority flow. Neutral contracts remain independent of vendor SDKs; concrete
adapters are explicit application-supplied outer leaves; credentials remain
application-owned and outside profiles and evidence; provider output,
normalized results, proposed actions, usage/cost evidence, and receipts remain
non-authoritative. Phase 14A is documentation only and implements no provider,
SDK, profile, credential mechanism, network client, package, API, schema,
command, adapter, runtime, Agent bridge, or Phase 14B behavior. See the
[architecture guide](../architecture/provider-neutral-model-invocation.md) and
[fixed roadmap](../architecture/phase-14-roadmap.md).

### [ADR 0148: Neutral Versioned Model-Invocation Contract Foundation](0148-neutral-versioned-model-invocation-contract-foundation.md)

Accepted 2026-08-31. Phase 14B implements only the optional Experimental
`kongono/model-invocation-contract` package for immutable bounded provider/model
identities, declared capabilities, one application-owned profile reference,
requests, normalized results, inert action suggestions, stream events,
usage/cost claims, lifecycle vocabulary, visibility, receipts, and
non-disclosing failures. It depends only on API Metadata and Diagnostics. It
performs no provider/model call, network, credential lookup, adapter execution,
plan build, Agent bridge, authority effect, serialization, persistence,
recording, or role work. The graph is 70 packages / 262 local edges / acyclic.
See the [contract guide](../architecture/model-invocation-contract-foundation.md).

### [ADR 0149: Deterministic Model-Invocation Plan Artifact](0149-deterministic-model-invocation-plan-artifact.md)

Accepted 2026-09-01. Phase 14C implements only the optional Experimental
`kongono/model-invocation-build` package. One explicit typed source binds one
exact neutral request, application-selected adapter identity, and finite
one-attempt budget. Canonical request and source projections are hashed; raw
messages are omitted from the compact plan artifact. Structural validation is
strict, currentness rebuilds only from one supplied complete source, and
publication is content-addressed, atomic, idempotent for identical bytes, and
no-clobber. Plans, budgets, labels, and digests remain inert evidence. There is
no provider/model call, credential, network, adapter execution, runtime, Agent
bridge, workflow, registry, discovery, or command. The graph is 71 packages /
265 local edges / acyclic. See the [plan-artifact guide](../architecture/model-invocation-plan-artifact.md).

### [ADR 0150: Bounded Provider-Neutral Model-Invocation Runtime](0150-bounded-provider-neutral-model-invocation-runtime.md)

Accepted 2026-09-01. Phase 14D implements only the optional Experimental
`kongono/model-invocation-runtime` package. One exact-current plan binds one
directly supplied identity-matching adapter and one synchronous pull session.
Explicit cancellation, monotonic time, output/stream/usage/cost/evidence
budgets, receipts, and deterministic close are enforced without provider SDK,
credential lookup, registry, retry/fallback, Agent bridge, workflow, command,
recording, telemetry, or background work. The graph is 72 packages / 269 local
edges / acyclic. See the [runtime guide](../architecture/model-invocation-runtime.md).

### [ADR 0151: Inert Model-Result to Agent-Proposal Bridge](0151-inert-model-result-to-agent-proposal-bridge.md)

Accepted 2026-09-01. Phase 14E implements only the optional Experimental
`kongono/model-invocation-agent-bridge` package. It validates one successful,
retired, cleanup-complete, one-attempt action-suggestion result and maps it
through exact application-owned action, capability, and usage configuration
into inert Phase 13 proposal evidence. The bridge is not an `AgentProposalPort`,
authorization, capability grant or invocation, or proof of effect. Raw model
arguments are hashed and counted but omitted from proposal and capability
inputs. The graph is 73 packages / 275 local edges / acyclic. See the
[bridge guide](../architecture/model-result-agent-proposal-bridge.md).

### [ADR 0152: Exact-Current Single-Profile Model-Invocation Application Workflow](0152-exact-current-single-profile-model-invocation-application-workflow.md)

Accepted 2026-09-01. Phase 14F implements only the optional Experimental
`kongono/model-invocation-application` package. It proves one exact-current
profile through Phase 14C, generates and explicitly loads deterministic inert
PHP wiring, binds one directly supplied identity-matching adapter, and delegates
one terminal synchronous operation to Phase 14D. It contains no provider SDK,
credential discovery, registry/default, retry/fallback, second profile, CLI,
retained session, background work, deployment, Agent port, or bridge dependency.
The graph is 74 packages / 280 local edges / acyclic. See the
[application workflow guide](../architecture/model-invocation-application-workflow.md).

### [ADR 0153: Visibility-Safe Model-Invocation Inspection and Bounded Evidence](0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md)

Accepted 2026-09-01. Phase 14G implements only the optional Experimental
`kongono/model-invocation-inspection` upper evidence leaf. It revalidates one
exact supplied profile/wiring pair without loading generated PHP, optionally
summarizes one completed detached runtime result without replay, and projects
only bounded inert Graph, Observability, Security Audit, and role-addressed
developer-intelligence evidence. It has no provider, credentials, registry,
retry/fallback, command, recording, repair, role execution, telemetry delivery,
or authority. The graph is 75 packages / 289 local edges / acyclic. See the
[inspection guide](../architecture/model-invocation-inspection-and-evidence.md).

- [ADR 0154: Phase 14 External Model-Invocation Acceptance and Checkpoint](0154-phase-14-external-model-invocation-acceptance-and-checkpoint.md)
  closes Phase 14 with root-owned offline external-process evidence and one
  exact local checkpoint. The fake adapter remains test evidence rather than a
  provider integration, and all model-invocation evidence remains inert.

### [ADR 0155: Explicit Outbound HTTP and Governed Network Egress Boundary](0155-explicit-outbound-http-and-governed-network-egress-boundary.md)

Accepted 2026-09-07. Phase 15A establishes documentation architecture for an
optional general egress platform beneath future external-service adapters.
Applications retain current authority, policy, credentials, endpoint and
transport selection, and service semantics. URI, origin, resolution, selected
address, attempt, response, and receipt remain distinct evidence. The decision
selects no client, transport, DNS, TLS, proxy, credential source, provider,
package, API, dependency, command, or network behavior. Phase 15B subsequently
accepts the separate neutral contract foundation. See the
[egress guide](../architecture/outbound-http-and-network-egress.md) and
[Phase 15 roadmap](../architecture/phase-15-roadmap.md).

### [ADR 0156: Neutral Versioned Network-Egress Contract Foundation](0156-neutral-versioned-network-egress-contract-foundation.md)

Accepted 2026-09-07. Phase 15B implements the optional Experimental
`kongono/network-egress-contract` inert value package. It reuses PSR-7
message interfaces without reading body streams or making PSR-18 the control
plane. Origins, authority and policy references, capabilities, responses,
outcomes, and receipts remain evidence rather than permission or external
truth. Diagnostics are closed and nondisclosing. No resolver, transport,
credential, provider, runtime, retry engine, or network behavior exists.
Phase 15C subsequently accepts the separate deterministic policy-artifact
boundary. See the [contract guide](../architecture/network-egress-contract-foundation.md).

### [ADR 0157: Deterministic Network-Egress Policy Artifact](0157-deterministic-network-egress-policy-artifact.md)

Accepted 2026-09-07. Phase 15C implements the optional Experimental
`kongono/network-egress-policy-build` package. One explicit typed
application-owned source becomes canonical inert offline evidence with
distinct source and artifact digests, strict structural and caller-relative
current validation, and atomic content-addressed no-clobber publication.
Exact origins remain Protected and diagnostic failures remain nondisclosing.
No resolver, DNS, socket, TLS implementation, credential provider, proxy
discovery, transport, runtime authorization, command, loader, or network
effect exists. See the [policy-artifact guide](../architecture/network-egress-policy-artifact.md).

### [ADR 0158: Bounded Provider-Neutral Network-Egress Runtime](0158-bounded-provider-neutral-network-egress-runtime.md)

Accepted 2026-09-07. Phase 15D implements the optional Experimental
`kongono/network-egress-runtime` coordinator for one direct synchronous attempt
over exact-current policy evidence and explicit caller-owned authority,
resolution, transport, cancellation, and clock ports. It binds one selected
address, enforces finite budgets and pull-based transfer evidence, never
consumes body streams, preserves uncertainty, and closes every opened session.
It supplies no concrete networking, credentials, provider integration,
registry, retry, redirect, command, or application workflow. See the [runtime
guide](../architecture/network-egress-runtime.md).

### [ADR 0159: Explicit cURL HTTP/1.1 Egress Transport Profile](0159-explicit-curl-http11-egress-transport-profile.md)

Accepted 2026-09-07. Phase 15E implements the optional Experimental
`kongono/network-egress-curl` adapter with exact selected-address binding,
explicit in-memory CA trust, strict TLS verification, fixed HTTP/1.1 scope,
bounded PSR-7 mapping, one-shot lifecycle, and nondisclosing numeric failure
mapping. See the [transport guide](../architecture/network-egress-curl-transport.md).

### [ADR 0160: Exact-Current Single-Profile Network-Egress Application Workflow](0160-exact-current-single-profile-network-egress-application-workflow.md)

Accepted 2026-09-08. Phase 15F implements the optional Experimental
`kongono/network-egress-application` workflow with one canonical profile,
deterministic generated wiring, exact-current validation, content-addressed
no-clobber publication, explicit contained loading, capability preflight, and
one terminal operation delegated to a fresh Phase 15D runtime. See the
[application workflow guide](../architecture/network-egress-application-workflow.md).

### [ADR 0161: Visibility-Safe Network-Egress Inspection and Bounded Evidence](0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)

Accepted 2026-09-08. Phase 15G implements the optional Experimental
`kongono/network-egress-inspection` upper evidence leaf. It revalidates one
exact supplied application profile and generated-wiring value and optionally
summarizes one exactly paired detached operation/runtime result. It emits only
bounded inert Graph, Observability, Security Audit, and role-addressed evidence
without generated-code loading, network access, authority evaluation,
recording, replay, repair, certification, or role execution. See the
[inspection guide](../architecture/network-egress-inspection-and-evidence.md).

## Phase 15E cURL transport and Phase 15F application workflow status

Phase 15E accepts ADR 0159 and implements the optional Experimental
`kongono/network-egress-curl` HTTP/1.1 adapter. It binds the canonical origin
to the single Phase 15D selected address, uses explicit in-memory CA-bundle
bytes with strict TLS verification, and performs one bounded synchronous
transfer without proxy, redirect, cookie, netrc, automatic authentication,
retry, pooling, or PSR-18 control-plane behavior.

Phase 15F accepts ADR 0160 and implements the optional Experimental
`kongono/network-egress-application` exact-current single-profile workflow.
It derives canonical profile and generated-wiring evidence from one supplied
policy source, validates and publishes the wiring without overwrite, loads one
exact contained generated class, checks required transport capabilities before
resolution, and delegates one terminal synchronous operation to a fresh Phase
15D runtime. Authority currentness, resolver, transport, cancellation, and
clock collaborators remain explicit and caller-owned.

Phase 15G accepts ADR 0161 and implements the optional Experimental
`kongono/network-egress-inspection` leaf described above. The current
topology is 81 packages / 317 local edges / acyclic, with PHP inventories
1,514 / 768 / 84. Phase 15A-H are Accepted and Phase 15 is closed at one exact
local checkpoint. No first-party resolver,
credential provider, provider adapter, live inspection probe, inspection
command, public compatibility certification, Stable/1.0 claim,
or production-readiness claim exists.

### [ADR 0162: Phase 15 External Network-Egress Acceptance and Checkpoint](0162-phase-15-external-network-egress-acceptance-and-checkpoint.md)

Accepted 2026-09-08. Phase 15H adds the root-owned deterministic external
acceptance harness. Its only network exception is a bounded IPv4 loopback
HTTP/1.1 exchange that proves selected-address binding without ambient DNS,
credentials, a proxy, or a production endpoint. Phase 15A-H are Accepted and
Phase 15 is closed at one exact local checkpoint. The harness remains test
evidence rather than authority, certification, production readiness, or proof
of remote effect. See the [external acceptance guide](../architecture/phase-15-external-acceptance.md).

## [ADR 0163: Explicit External-Service Binding and Secret-Lease Boundary](0163-explicit-external-service-binding-and-secret-lease-boundary.md)

Accepted 2026-09-08. Phase 16A fixes the documentation architecture for
secret-free service binding evidence, selected-address-bound resolution
evidence, and operation-scoped credential leases above the accepted Phase 15
egress platform. Credential access and network access remain independent,
secrets remain application-owned, and PHP memory zeroization is not claimed.
Phase 16B-H are Planned and separately authorized; no Phase 16 package,
resolver, credential provider, provider adapter, schema, command, or runtime
exists. See the [developer guide](../architecture/external-service-binding-and-secret-leases.md)
and [Phase 16 roadmap](../architecture/phase-16-roadmap.md).

## [ADR 0164: Neutral External-Service Binding and Credential-Lease Contracts](0164-neutral-external-service-binding-and-credential-lease-contracts.md)

Accepted 2026-09-08. Phase 16B implements only the optional Experimental
`kongono/external-service-contract` package: 27 production declarations and 15
tests for secret-free binding evidence, credential lease ports, exact Phase 15
resolver compatibility, lifecycle/results, and actionable nondisclosing
failures. The package supplies no implementation, artifact, runtime, provider,
command, network behavior, or later-phase authority.

## [ADR 0165: Deterministic External-Service Binding Artifact and Generated PHP](0165-deterministic-external-service-binding-artifact-and-generated-php.md)

Accepted 2026-09-08. Phase 16C implements only the optional Experimental
`kongono/external-service-binding-build` package. One explicit immutable
secret-free typed source deterministically produces one canonical versioned
JSON artifact and ordinary generated PHP. Structural validation and
caller-relative validation against one explicit current source yield inert
evidence only; generated PHP is never loaded or executed. Independent explicit
publishers are path-safe, content-addressed, atomic, idempotent for identical
bytes, and no-clobber. Credential access and network authority remain separate
and absent. Phase 16D-H and Phase 17 remain Planned and separately authorized.

## [ADR 0166: Explicit Bounded System Resolver Adapter](0166-explicit-bounded-system-resolver-adapter.md)

Accepted 2026-09-09. Phase 16D adds only the optional Experimental
`kongono/external-service-resolver-system` leaf. One explicit profile bounds
one operating-system `getaddrinfo()` result through PHP 8.5 `ext-sockets`.
The adapter requires `SOL_TCP`, omits `ai_family` for the fixed
unspecified-family PHP-binding policy, validates every entry, collapses exact
duplicates, reuses Phase 15 classification, orders candidates deterministically,
and returns one selected member as evidence. It performs no connection,
authorization, credential access, cache, retry, fallback, provider behavior,
or DNS TTL/DNSSEC/provenance/deadline certification. Phase 16F-H and Phase 17
remain Planned and separately authorized.

## [ADR 0167: Operation-Scoped Credential Lease Runtime](0167-operation-scoped-credential-lease-runtime.md)

Accepted 2026-09-09. Phase 16E adds only the optional Experimental
`kongono/external-service-credential-runtime` package. One caller-selected
source, matching secret-free request, consumer, and monotonic clock coordinate
one synchronous lease operation with at most one material-access attempt and
exactly one retirement attempt for every acquired lease. Borrowed material
passes directly from the lease to the consumer; the runtime never receives it.
Failures preserve exact primary and retirement identities behind bounded safe
terminal evidence. The package supplies no source, store, provider or network
behavior, refresh, renewal, registry, cache, retry, fallback, background work,
reliable-zeroization guarantee, or preemptive deadline. Phase 16F-H and Phase
17 remain Planned and separately authorized.

## [ADR 0168: Exact-Current External-Service Binding Application](0168-exact-current-external-service-binding-application.md)

Accepted 2026-09-09. Phase 16F adds only the optional Experimental
`kongono/external-service-application` package. One exact-current binding and
one exact-current Phase 15 application profile compose with explicit caller-
owned credential, resolver, transport, authority, cancellation, clock, and
request-factory collaborators for one terminal synchronous operation. Request
construction occurs once while material is borrowed, and Phase 16E retires
every acquired lease exactly once. Results preserve exact detached credential
and egress evidence without widening effect claims. No provider, credential
source, resolver, transport, discovery, retry, fallback, command, inspection,
external acceptance, production-readiness, or reliable-zeroization claim is
added. Phase 16G is Accepted; Phase 16H and Phase 17 remain Planned and
separately authorized.

## [ADR 0169: Visibility-Safe External-Service Inspection and Bounded Evidence](0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)

Accepted 2026-09-09. Phase 16G adds only the optional Experimental
`kongono/external-service-inspection` upper evidence leaf. It revalidates one
explicit exact-current Phase 16F profile and may summarize one matching detached
completed result as Protected digests, closed vocabulary, and Safe bounded
aggregates. It performs no credential access, generated-code loading, DNS,
networking, provider invocation, authority evaluation, recording, replay,
repair, live probe, role execution, or certification. Credential cleanup does
not prove physical erasure, and possible effects remain uncertain. Phase 16H
and Phase 17 remain Planned and separately authorized.

## [ADR 0170: Phase 16 External-Service Acceptance and Checkpoint](0170-phase-16-external-service-acceptance-and-checkpoint.md)

Accepted 2026-09-09. Phase 16H adds only the root-owned deterministic local
external-service acceptance harness and one exact local checkpoint. The proof
uses a numeric loopback resolver check, one `.invalid` selected-address HTTP/1.1
exchange through the real cURL adapter, one inert test-owned credential lease,
and offline inspection evidence. It adds no production declaration, package,
dependency, provider adapter, production credential source, authority, public
network destination, compatibility, deployment, release, Stable/1.0, or
production-readiness claim. Phase 16A-H are Accepted, Phase 16 is closed, and
Phase 17A-H are Accepted and Phase 17 is closed. Phase 18A-F are Accepted;
Phase 18G-H remain Planned.

## [ADR 0171: First-Party OpenAI Responses Adapter Boundary](0171-first-party-openai-responses-adapter-boundary.md)

Accepted 2026-09-09 for Phase 17A only. It fixes the architecture and A-H
roadmap for one optional outer OpenAI Responses API adapter composed over
Phase 14 neutral invocation, Phase 16 exact-current service binding and
credential leases, and Phase 15 governed egress. OpenAI is not Kongono's
default provider or a generic compatibility promise. Provider material remains
bounded untrusted evidence, action suggestions execute nothing, and credential,
network, tool, and effect authority remain independently application-owned.
Phase 17A adds no PHP, package, API, codec, SDK, credential, network call,
command, Chie integration, or runtime behavior. Phase 17B-H later became
Accepted. Phase 18A-F are Accepted; Phase 18G-H remain Planned.

## [ADR 0172: Deterministic OpenAI Responses Offline Protocol Mapping](0172-deterministic-openai-responses-offline-protocol-mapping.md)

Accepted 2026-09-10 for Phase 17B only. It adds the optional Experimental
`kongono/model-provider-openai-responses` leaf with exact mapping version
`kongono-openai-responses/1`, an exact text-and-usage profile, deterministic
bounded request encoding, strict bounded duplicate-safe response decoding,
typed untrusted provider evidence, synthetic fixtures, and nondisclosing
diagnostics. It adds no SDK, credential, endpoint, HTTP transport, network
call, runtime adapter session, streaming, tools, application workflow, Chie
connection, or live support claim. Phase 17A-H are Accepted and Phase 17 is
closed. Phase 18A-F are Accepted; Phase 18G-H remain Planned.

## [ADR 0173: One-Shot OpenAI Responses Invocation Adapter](0173-one-shot-openai-responses-invocation-adapter.md)

Accepted 2026-09-10 for Phase 17C only. It composes the exact offline mapping
through one fresh Phase 16 application and Phase 15 governed-egress attempt,
strictly closes one bounded terminal JSON body, and normalizes one Phase 14
text result without retry, fallback, SDK, ambient state, or live support claim.

## [ADR 0174: Pull-Based OpenAI Responses SSE Streaming](0174-pull-based-openai-responses-sse-streaming.md)

Accepted 2026-09-10 for Phase 17D only. It adds one Internal consumer-driven
SSE session for the exact text, streaming, and usage profile, with bounded
framing and JSON, closed event ordering, snapshot equality, contiguous neutral
events, deterministic cleanup, and no background reader, reconnect, replay,
WebSocket surface, or live-provider claim. Phase 17D is Accepted; Phase 17E is
Current.

## [ADR 0175: Strict Structured Output and Inert OpenAI Function-Call Mapping](0175-strict-structured-output-and-inert-openai-function-call-mapping.md)

Accepted 2026-09-10 for Phase 17E only. It adds exact mapping version
`kongono-openai-responses/2` for one non-streaming strict structured-result
profile and one non-streaming inert function-call suggestion profile. Explicit
closed schemas and returned values are bounded, duplicate-safe, and locally
validated without coercion. Function calls remain evidence and are never
resolved, invoked, authorized, returned to the provider, or converted into a
second operation or application effect. Version 1 text and streaming remain
accepted. Phase 17A-H are Accepted and Phase 17 is closed. Phase 18A-F are
Accepted; Phase 18G-H remain Planned.

## [ADR 0176: Exact-current OpenAI Responses application profile and supported workflow](0176-exact-current-openai-responses-application-workflow.md)

Accepted 2026-09-10 for Phase 17F only. It binds exact Phase 14 invocation-
application, Phase 17 provider, and Phase 16 external-service-application
profiles into one bounded deterministic identity, delegates explicit build and
write-free check operations to the accepted Phase 14 workflow, and supplies
one single-use application-owned run composition over explicit collaborators.
It adds no registry, defaults, discovery, SDK, alternate networking stack,
retry, replay, tool execution, Chie application, live compatibility,
certification, or production-readiness claim. Phase 17F and Phase 17H are
Accepted; Phase 17 is closed. Phase 18A-F are Accepted; Phase 18G-H remain
Planned.

## [ADR 0177: Visibility-safe OpenAI Responses inspection and bounded evidence](0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md)

Accepted 2026-09-11 for Phase 17G only. It adds the optional Experimental
`kongono/model-provider-openai-responses-inspection` leaf, which revalidates one
supplied exact-current Phase 17F profile and may summarize one matching detached
completed Phase 14 runtime result. Canonical inspection, Graph projection,
Observability and Security Audit values, role-addressed intelligence, and
independent verification expose only Protected digests, closed tokens, and Safe
aggregates. The package is offline, read-only, stateless, and visibility-safe;
it performs no payload access, credential access, networking, provider
observation, authority evaluation, replay, repair, recording, exporting,
certification, or role execution. Phase 17A-H are Accepted and Phase 17 is
closed. Phase 18A-F are Accepted; Phase 18G-H remain Planned.

## [ADR 0178: Phase 17 OpenAI Responses local acceptance and checkpoint](0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md)

Accepted 2026-09-11 for Phase 17H only. It adds the root-owned deterministic
local provider-protocol harness and explicit `composer acceptance:phase17`
entry. Fresh children prove the four exact text, pull-SSE, strict structured,
and inert function-call profiles through the accepted Phase 14-17 composition,
with owned loopback, explicit selected-address evidence, one-use credential
leases, real cURL HTTP/1.1 transport, bounded processes and streams,
nondisclosure, inspection, concurrency, and cleanup. It contacts no public
endpoint, validates no real account or credential, and makes no compatibility,
certification, deployment, production-readiness, or Stable/1.0 claim. Phase
17A-H are Accepted and Phase 17 is closed. Phase 18A-F are Accepted; Phase
18G-H remain Planned.

## [ADR 0179: Server-rendered presentation and compiled view boundary](0179-server-rendered-presentation-and-compiled-view-boundary.md)

Accepted 2026-09-12 for Phase 18A documentation architecture only. It selects
an optional application-owned view boundary, deterministic build-time
compilation to content-addressed ordinary PHP, exact-current contained loading,
closed bounded presentation data, separate neutral rendering and PSR-7
adaptation, operation isolation, and explicit package/API direction. Routing
continues to select handlers, not templates. It adds no package, PHP, grammar,
compiler, renderer, loader, command, HTTP adapter, workflow, or runtime.

## [ADR 0180: Context-aware output safety and presentation lifecycle](0180-context-aware-output-safety-and-presentation-lifecycle.md)

Accepted 2026-09-12 for Phase 18A documentation architecture only. It requires
compile-time known output contexts, safe-by-default contextual escaping,
ordinary strings as data, denial of unsupported URL/JavaScript/CSS/raw/event
contexts, bounded non-disclosing diagnostics, synchronous operation-scoped
rendering, partial-output rejection, deterministic cleanup, and sequential and
overlapping isolation. Phase 18B accepts only the separate neutral contract
boundary below; Phase 18A-F are Accepted and Phase 18G-H remain Planned.

## [ADR 0181: Neutral view contract and closed presentation values](0181-neutral-view-contract-and-closed-presentation-values.md)

Accepted 2026-09-12 for Phase 18B. It adds only the optional Experimental
`kongono/view-contract` leaf: canonical identities, six closed bounded value
kinds, recursive schemas, two inert output-context tokens, explicit render
limits and lifecycle vocabulary, validated requests, completed output and
derived metadata, failed-state evidence, and nine nondisclosing diagnostics.
It adds no source syntax, parser, compiler, generated PHP, loader, escaper,
renderer, filesystem or network access, HTTP dependency, command, application
workflow, inspection, acceptance harness, deployment, or Stable API. Phase
18C is accepted separately by ADR 0182; Phase 18A-F are Accepted and Phase
18G-H remain Planned.

## [ADR 0182: Deterministic structured view source and compiled artifact](0182-deterministic-structured-view-source-and-compiled-artifact.md)

Accepted 2026-09-12 for Phase 18C. It adds only the optional Experimental
`kongono/view-build` leaf for one explicit closed duplicate-aware JSON source
language, schema-resolved references, canonical model and complete source map,
four distinct content identities, syntax-validated inert ordinary PHP, exact
structural/current validation, and atomic content-addressed two-file no-clobber
publication. Generated PHP is never loaded or executed. It adds no renderer,
dynamic escaper, HTTP adapter, application workflow, command, discovery, cache,
watcher, inspection, external acceptance, or Stable/1.0 API. Phase 18A-F are
Accepted; Phase 18G-H remain Planned.

## [ADR 0183: Exact-current compiled view runtime and bounded rendering](0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)

Accepted 2026-09-12 for Phase 18D. It adds only the optional Experimental
`kongono/view-runtime` leaf. One caller-selected exact identity authenticates a
fixed contained `artifact.json` and `compiled.php`; one generated class is
loaded or exactly reused, and each synchronous render owns bounded operation-
local state, contextual HTML escaping, retirement, and partial-output rejection.
No source is parsed or compiled during rendering. Generated code and the
deployment filesystem are trusted inputs. It adds no HTTP adapter, application
workflow, command, discovery, mutable cache, watcher, inspection, external
acceptance, Stable/1.0 API, compatibility, or production-readiness claim. See
the [compiled-view runtime guide](../architecture/compiled-view-runtime.md).
Phase 18A-F are Accepted; Phase 18G-H remain Planned.

## [ADR 0184: Explicit completed-view PSR-7 response adaptation](0184-explicit-completed-view-to-psr-7-response-adaptation.md)

Accepted 2026-09-12 for Phase 18E. It adds only the optional Experimental
`kongono/view-http` leaf for exact adaptation of one completed neutral render
result to PSR-7 under explicit caller-owned policy and caller-selected PSR-17
factories. It derives only content length, verifies exact final state, and
exposes no partial response on failure. It adds no routing, controller,
middleware, security defaults, cookies, redirects, caching, server, emitter,
workflow, command, or production-readiness claim. See the
[compiled-view HTTP guide](../architecture/compiled-view-http.md). Phase 18A-F
are Accepted; Phase 18G-H remain Planned.

## [ADR 0185: Supported compiled-view application workflow](0185-supported-compiled-view-application-workflow.md)

Accepted 2026-09-13 for Phase 18F. It adds the optional Experimental
`kongono/view-application` upper composition leaf, exact explicit plan/build/
check/load/run boundaries, three supported view commands, and one copy-once
minimal rendered application example. Run checks currentness and never
compiles; check and inspect are write-free; application code retains routing,
response, browser-security, and failure-presentation policy. It adds no
discovery, mutable current pointer, watcher, daemon, installer, cache, asset
pipeline, deployment manager, automatic migration, Stable/1.0 API, or
production-readiness claim. See the [compiled-view application guide](../architecture/compiled-view-application-workflow.md).
Phase 18A-F are Accepted; Phase 18G-H remain Planned.

## [ADR 0186: Visibility-safe compiled-view inspection and developer intelligence](0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)

Accepted 2026-09-13 for Phase 18G. It adds the optional Experimental
`kongono/view-inspection` upper evidence leaf over one supplied exact-current
application profile. It structurally revalidates in-memory artifact evidence
without I/O or execution and emits only bounded Safe/Protected developer
evidence. Graph remains `not-established`, neutral projections are
`constructed-not-recorded`, role identifiers select purposes without role
execution, and verification is independent and repair-free. See the
[compiled-view inspection guide](../architecture/compiled-view-inspection-and-intelligence.md).
Phase 18A-H are Accepted and Phase 18 is closed.

## [ADR 0187: Phase 18 server-rendered presentation acceptance and checkpoint](0187-phase-18-server-rendered-presentation-acceptance-and-checkpoint.md)

Accepted 2026-09-13 for Phase 18H. It adds only the root-owned bounded local
external-process proof and the exact Phase 18 checkpoint. The proof composes
the real build, runtime, HTTP, application, copied-skeleton, CGI, and inspection
boundaries without public networking, ambient DNS, live services, or new
production API. See the [Phase 18 acceptance guide](../architecture/phase-18-server-rendered-presentation-acceptance.md).
Phase 18A-H are Accepted and Phase 18 is closed.
