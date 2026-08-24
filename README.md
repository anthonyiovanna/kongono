# Kongono

> **Build what comes next.**

Kongono is a planned modern application framework for applications that must be understandable to humans, software clients, tooling, infrastructure, and AI agents. PHP 8.5 is its primary application language.

## Who Kongono is designed for

- Humans building and operating applications
- Software consuming explicit, machine-readable contracts
- AI agents acting through governed, inspectable capabilities

## Current status

Phase 2A through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Kongono has exactly fourteen
acyclic first-party production packages and is fixture-runnable through
root-owned black-box test infrastructure. Run
`php tests/BlackBox/Fixture/run.php production matched-a` to exercise the real
Web Runtime, Web Application, routing, middleware, HTTP message, and Kernel path.
The [fixture model](docs/architecture/phase-2-runnable-fixture.md) documents its
fixed boundary.

The fixture is not a production application, supported template, framework CLI,
SAPI adapter, server, listener, emitter, deployment entrypoint, or
production-readiness proof. Phase 3B selects PHP `cgi-fcgi` in the FastCGI
`RESPONDER` role under CGI/1.1 as Kongono's first production host profile.
Phase 3C and [ADR 0053](docs/adr/0053-bounded-php-cgi-inbound-conversion.md)
implement bounded inbound conversion. Phase 3D and [ADR 0054](docs/adr/0054-bounded-php-cgi-response-emission.md)
implement fully staged, bounded response emission with computed length and no
streaming. Phase 3E and [ADR 0055](docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md)
implement a code-literal operator profile, fixed PHP CGI process prerequisites,
and independent per-delivery trust evidence. Phase 3F implements the concrete
delivery and thin entry into an already-composed Web Runtime. A post-commit
failure is uncertain, throws, and is never rolled back or retried. Phase 3G adds
the bounded front-controller lifecycle and deployment constraints. Phase 3H
completes external test-owned acceptance and the Phase 3 checkpoint without
adding a production listener, client, process manager, or deployment artifact. See the [PHP
CGI profile](docs/architecture/php-cgi-fastcgi-profile.md),
[Phase 3 roadmap](docs/architecture/phase-3-roadmap.md),
[routing model](docs/architecture/routing-model.md), [HTTP message
model](docs/architecture/http-message-model.md), [HTTP server
model](docs/architecture/http-server-model.md), and [Web Application
model](docs/architecture/web-application-model.md).

Kongono is **pre-alpha**. Phase 0M implements package-local runtime diagnostic
metadata for the deliberate failures currently raised by the Kernel, Application
Graph, and Dependency Injection packages. Each existing package exception marker
now exposes the exact diagnostic ID, title, and remediation from one owning
Internal enum catalog. The markers remain separate; there is no universal
Kongono catch interface. Existing exception messages remain unchanged and are
protected diagnostic detail.

Phase 0P implements the presentation-neutral `kongono/diagnostics` package.
Public closed schemas and immutable values support exact text, integer, and
boolean fields classified as Safe or Protected, schema-order capture, required
and optional fields, and bounded deterministic byte representation. Safe means
eligible for a later explicit disclosure policy, not automatically public.
Forbidden secret-bearing or unbounded material must be excluded before
construction; the package does not detect or redact it. Diagnostics construction
failures use their own fourth package marker and 11 diagnostic IDs.

Phase 0Q integrates all 11 Application Graph failures with their exact Graph-
owned bounded schemas and throw-site values. The Public Graph marker now exposes
`diagnosticContext(): DiagnosticContext`; each Graph exception eagerly owns one
immutable detached context while retaining its exact ID, title, remediation,
message, zero code, optional cause, and package-local catch boundary. Safe and
Protected classifications do not render or disclose any field.

Phase 0T integrates all six Kernel lifecycle failures with exact Kernel-owned
closed schemas and Safe state, count, or conservative scope-condition values.
The Public Kernel marker now exposes the same presentation-neutral
`diagnosticContext()` value while remaining unrelated to every other marker.

Phase 0U integrates all 25 Dependency Injection diagnostics across their 26
existing throw sites. DI now independently requires Diagnostics, its Public
marker exposes `diagnosticContext()`, and its exceptions eagerly own exact
bounded contexts without changing definition validation, deterministic graph
checks, requested-service behavior, or transient-per-edge construction.

Phase 0S implements the neutral ownership accepted by ADR 0031 and increased
Kongono to five packages. `kongono/api-metadata` owns `Kongono\ApiMetadata\Api` and
`ApiStatus` and requires PHP only. Kernel, Diagnostics, Application Graph, and
Dependency Injection depend directly on it; Application Graph also retains its
one-way Diagnostics dependency. The resulting package graph is acyclic.

The old `Kongono\Kernel\Api\Api` and `ApiStatus` FQCNs were removed atomically
without an alias or compatibility shim. All 40 declarations present at Phase 0S
and their statuses remained unchanged. Phase 0S changes ownership, imports, Composer
topology, tests, tooling, and documentation only. Phases 0T and 0U add the
resulting acyclic Kernel-to-Diagnostics and DI-to-Diagnostics edges, but no
renderer, error page, or transport.

All 42 original Kernel, Graph, and DI failures now expose exact package-owned
bounded context: 6/11/25. Diagnostics construction failures expose no recursive
context. Kernel, Graph, and DI depend on Diagnostics independently; no dependency
creates a universal catch boundary or reverse dependency.
No sanitizer, served error page, trusted developer mode, production allowlist,
transport, serializer, source/documentation lookup, logger, telemetry, or incident
system is implemented.

Phase 0X implements `kongono/diagnostic-presentation` as the sixth package and a
leaf above API Metadata, Diagnostics, Kernel, Application Graph, and Dependency
Injection. Its four Experimental developer concepts provide a pure projector
and immutable detached values containing only exact package ownership, catalog
metadata, fixed context prose, and canonically ordered classified fields. Both
Safe and Protected classifications are preserved exactly. Projection is not
authorization. Phase 0X added no renderer, trusted-mode selector, production
model, transport, service binding, or other runtime integration.

Phase 0Z implements the Experimental pure
`TrustedDeveloperHtmlRenderer` selected in Phase 0Y. It consumes only the
implemented trusted projection and returns one complete accessible,
deterministic, escaped, bounded HTML5 document using a fixed local stylesheet.
Golden fixtures lock the full and empty byte grammar.

At the Phase 1A boundary, Kongono accepted a fail-closed activation architecture
but had not implemented enforcement. The planned outer composition had to
provide both affirmative trusted bootstrap permission for an immutable adapter
instance and an independent adapter-controlled delivery permission for the
current delivery before presentation access, projection, rendering, or
delivery. Missing, invalid, ambiguous, failed, denied, or uncertain decisions
could not enable trusted output, and no client-controlled value could enable
either permission. The projector and renderer remained directly callable only
as low-level pure Experimental APIs; calling them was not authorization. Phase
2G now implements the neutral Web Runtime adapter and delivery port, and Phase
2H proves that sequence through test-owned infrastructure. Phase 3E implements
literal operator selection and independent PHP CGI delivery-evidence
evaluation; Phase 3F implements native capture, conversion, emission, and
cleanup through the concrete delivery and thin entry. Phase 3G adds the bounded
front-controller lifecycle and documents fixed operator constraints. General
configuration, deployed hardening, and external production acceptance remain
unimplemented.

Phase 1C implements only the first typed input to that Accepted policy in the
new seventh package, `kongono/diagnostic-activation`. Its sole production type
is the Experimental unbacked enum
`TrustedDeveloperBootstrapPermission`, with `Denied`, `Allowed`, and
`allowsTrustedPresentation(): bool`. The enum carries an already-validated
server-bootstrap decision; it reads no raw configuration and does not prove
delivery authorization. At the end of Phase 1C, adapter-controlled delivery
permission, combined enforcement, adapters, HTTP behavior, and delivery remained
unimplemented.

Phase 1D adds the second neutral typed input in the same package: the
Experimental unbacked `AdapterControlledDeliveryPermission` enum with `Denied`,
`Allowed`, and `allowsTrustedPresentation(): bool`. It represents only an
adapter's already-determined permission for one current delivery. The adapter
owns all evidence, evaluation, and authority; missing, failed, uncertain, or
unverified evidence must fail closed. Both typed inputs exist and neither
`Allowed` case suffices alone. Their pure conjunction and neutral Handler
sequencing are implemented. Phase 2G supplies the neutral adapter and delivery
sequence from explicitly supplied decisions. Phase 3E supplies PHP CGI's
literal operator and exact delivery-evidence decisions, and Phase 3F supplies
native capture plus concrete conversion, emission, and cleanup. A general
configuration system, listener/process integration, deployment hardening, and
external production acceptance remain unimplemented.

Phase 1E adds the pure Experimental final readonly
`TrustedDeveloperPresentationEligibility`. It stores the two typed decisions
for one current delivery and returns true only for `Allowed` plus `Allowed`.
This calculation validates no external evidence, proves no authority, and
performs no enforcement, diagnostic access, adapter, transport, or side effect.

Phase 1H implements the eighth leaf, `kongono/diagnostic-handler`, and its
Experimental final readonly
`Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter`. Its sole
`renderIfEligible(...)` method checks the supplied
`TrustedDeveloperPresentationEligibility` before any source accessor, return
`null` immediately when ineligible, and otherwise project the contextual Kernel,
Graph, or Dependency Injection failure, render it, and return the exact HTML
unchanged. It catches nothing, validates no evidence, proves no authority, and
chooses no adapter, transport, or non-trusted response. Diagnostic Handler is
the only current package depending on both independent lower layers, Diagnostic
Activation and Diagnostic Presentation. It adds no binding, served page,
configuration, evidence evaluation, delivery, response, or production model.
Those are package-local limitations: Web Runtime owns the neutral generic or
trusted response selection and delivery sequence, while PHP CGI now supplies
the selected concrete delivery and thin entry. The external host still owns
listening, process management, deployment, and shutdown policy.

The broader originating-package behavior remains the accepted Phase 0U implementation:
the PHP quality toolchain and eight packages, including neutral API metadata,
both typed diagnostic permission inputs, Kernel lifecycle, the Application Graph structural
snapshot, and explicit typed dependency resolution. The Graph supports exact
`outgoingRelations(NodeKey): array` and `incomingRelations(NodeKey): array`
selection over immediately stored relations, plus exact whole-snapshot
`nodesOfKind(NodeKind): array` and `relationsOfKind(RelationKind): array`
selection. The kind selectors keep node-kind and relation-kind domains separate,
preserve canonical order and stored-object identity in fresh lists, return `[]`
for absent kinds, and scan without indexes or caches. Kinds remain open
producer-defined labels; these methods do not establish a vocabulary, combine
endpoint and kind filters, traverse the Graph, or form a generic query system.
The resolver remains explicit and transient per dependency edge. The Graph
remains a structural prerequisite rather than the complete authoritative
Application Graph. Development or production error pages, HTTP or CLI mappings,
logging, telemetry, retry, broader resource cleanup, incident storage, other
production SAPI adapters, broader capabilities, and a working `kongo` CLI
remain unimplemented.

Nothing is Stable. Public API status means supported consumer use under Kongono's pre-1.0 release maturity; it does not imply an unchangeable or production-ready contract. Experimental and open architecture may evolve.

## Core principles

- Explicit, typed, deterministic, inspectable behavior over hidden magic
- A small microkernel with independently installable capabilities
- A language-neutral Application Graph as the planned authoritative application model
- Progressive architecture that can grow beyond an MVC edge
- Persistent-runtime safety from the beginning
- First-class Human, Software/API, and Agent experiences
- Standards-first interoperability behind Kongono-owned abstractions
- Documentation that explains purpose, contracts, security, lifecycle, and failure
- Actionable, package-owned diagnostics with implemented presentation-neutral
  bounded structural values and exact Kernel, Graph, and DI exception context,
  plus an implemented pure trusted-developer projection and HTML rendering
  boundary, fail-closed Web Runtime enforcement, and the selected PHP CGI trust
  and delivery integration; broader transport profiles and production policy
  remain future work

## Documentation

- [Architecture](ARCHITECTURE.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture overview](docs/architecture/overview.md)
- [Phase 2 roadmap](docs/architecture/phase-2-roadmap.md)
- [Phase 3 roadmap](docs/architecture/phase-3-roadmap.md)
- [Phase 4 roadmap](docs/architecture/phase-4-roadmap.md)
- [Developer-intelligence architecture](docs/architecture/developer-intelligence.md)
- [PHP support policy](docs/engineering/php-support-policy.md)
- [PHP CGI FastCGI profile](docs/architecture/php-cgi-fastcgi-profile.md)
- [HTTP message model](docs/architecture/http-message-model.md)
- [Routing model](docs/architecture/routing-model.md)
- [API Metadata package](packages/api-metadata/README.md)
- [Diagnostic Activation package](packages/diagnostic-activation/README.md)
- [Kernel package](packages/kernel/README.md)
- [Application Graph structural package](packages/application-graph/README.md)
- [Dependency Injection package](packages/dependency-injection/README.md)
- [Diagnostics package](packages/diagnostics/README.md)
- [Diagnostic Presentation package](packages/diagnostic-presentation/README.md)
- [Diagnostic Handler package](packages/diagnostic-handler/README.md)
- [HTTP Message package](packages/http-message/README.md)
- [Routing package](packages/routing/README.md)
- [HTTP Server package](packages/http-server/README.md)
- [Web Application package](packages/web-application/README.md)
- [Documentation standard](docs/documentation-standard.md)
- [Engineering toolchain](docs/engineering/README.md)
- [Dependency Injection engineering guide](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Glossary](docs/glossary.md)
- [Architecture Decision Records](docs/adr/README.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

The official future CLI name is `kongo`; no executable is implemented in this phase.

Phase 2A through 2H are complete across thirteen acyclic production packages and
one test-owned runnable fixture. Run the bounded proof with
`php tests/BlackBox/Fixture/run.php production matched-a`; see the
[fixture model](docs/architecture/phase-2-runnable-fixture.md). The runner is
test infrastructure, not the future `kongo` CLI or a production server.

The milestone proves the real Kernel, Web Application, routing, middleware, Web
Runtime, generic failure, trusted diagnostic, and exact cleanup path. It does
not supply a listener, SAPI integration, production emitter, authentication,
authorization, deployment, logging, telemetry, full framework, 1.0 release, or
production-readiness claim.

Phase 3B selects the exact PHP `cgi-fcgi` FastCGI Responder architecture. Phase
3C implements bounded explicit inbound conversion, 3D bounded response staging
and emission, and 3E immutable operator bootstrap plus independent delivery
permission evaluation. Phase 3F now implements the Internal one-delivery PHP CGI
adapter and Experimental thin entry. PHP CGI projects the closed server allowlist,
opens `php://input` only during request conversion, and owns exact body/input
cleanup; malformed profile input still becomes Web Runtime's generic 500 with no
semantic 4xx mapping. Its new direct dependency is Web Runtime, not Web
Application. Phase 3G adds `CgiFrontController`: one entry attempt followed by
one explicit supplied application-shutdown attempt, with deterministic four-way
failure handling and diagnostic 1019 when both fail. `Production` still denies
trusted output; development still needs independent direct, non-forwarded
canonical loopback evidence. The [deployment
contract](docs/engineering/php-cgi-deployment.md) records operator constraints
without supplying a production listener, server configuration, or deployment
artifact. The [external acceptance guide](docs/architecture/phase-3-external-acceptance.md)
records test-owned proof through the actual selected host. Application composition and partial-boot cleanup remain caller
responsibilities; uncertain emission or shutdown requires external process
retirement. The intrinsic 8,192-byte query ceiling remains unchanged; exact
target/query composition makes 8,190 bytes the effective external maximum.
Phase 3 is closed, and no later phase starts automatically.

## Phase 4 developer-intelligence milestone

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A accepted the architecture
for one versioned, inspectable, AI-independent knowledge foundation and three
separated roles: Kongono Chie helps you build it, Kongono Hantei independently
proves it works, and Kongono Oshie helps you understand it. Phases 4C through
4G implement the deterministic artifact and bounded Experimental role packages;
Phase 4H supplies their root-owned external acceptance proof. No provider,
model, production integration, or application Agent Principal is implemented.

The deterministic framework remains usable offline and without AI. Developer
companions are distinct from application Agent Principals. The accepted PHP
policy targets one compatibility-certified stable minor at a time. Phase 5B
applies `>=8.5.0 <8.6.0` to the root and all eighteen packages; PHP 8.5 is the
active line and PHP 8.5.9 on Windows is the proved environment. Eligible stable
8.5 patches remain in range. PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support are not claimed. Phase 4 remains smaller than the full framework, Stable
API, 1.0, auto-fix, autonomous operations, or production AI.

Phase 4B now accepts the architecture-only [versioned knowledge-source and
provenance contract](docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md).
The [knowledge foundation](docs/architecture/knowledge-foundation.md) defines
six semantic source-authority classes, six claim classes, exact source-state
matching, visible conflict, bounded provenance, disclosure-before-inclusion,
and strict separation of framework knowledge from workspace evidence.

No knowledge artifact, package, source inventory, schema, digest, generator,
validator, command, index, retrieval system, model, workspace reader, or Chie,
Hantei, or Oshie runtime exists. Phase 4C is the next sequential boundary and
must select a concrete deterministic, AI-independent artifact slice under a
separately authenticated directive.

## Implemented Phase 4C framework knowledge

Phase 4C adds the fifteenth acyclic package, `kongono/knowledge`. It
deterministically generates and validates one canonical artifact from the exact
closed 31-source `phase4-foundation` inventory and may atomically publish a
content-addressed initial file without overwrite. The artifact is inspectable
derived evidence, not authority, and is never checked in. Citations resolve
whole source records in one exact source set only. Run `composer knowledge:check`
for the no-write in-memory proof. Retrieval, search, models, arbitrary workspace
reads, mutation, Graph input, and production integration remain unimplemented.

## Implemented Phase 4D read-only Chie

## Implemented Phase 4E read-only Hantei

Kongono now includes an optional independent deterministic verifier. Hantei runs
the fixed eight-stage framework quality profile against one unchanged exact
subject and emits bounded `PASS`, `FAIL`, or `BLOCKED` evidence. It consumes the
knowledge foundation without depending on Chie and never repairs repository
state. Phase 4F Oshie remains planned.
Phase 4D adds the sixteenth acyclic package, `kongono/chie`. Given one explicit
current Phase 4C artifact, it returns the exact cited section for one of six
closed topics or locates bounded literal occurrences of one supplied Safe
diagnostic identifier. It exposes answered, unsupported, unavailable, stale,
and blocked states in deterministic canonical JSON. It has no free-form prompt,
model, provider, network, arbitrary workspace reader, recommendation, patch,
verification, Git, process, deployment, or production authority. See the
[read-only Chie guide](docs/architecture/read-only-chie.md).

## Implemented Phase 4F read-only Oshie

Oshie now provides a closed six-lesson catalog, exact cited source lessons, deterministic practice prompts, and exact-answer checks over one current Phase 4C artifact. It retains no learner state and has no model, provider, executable-example, mutation, verifier, Git, deployment, or production authority. See the [read-only Oshie guide](docs/architecture/read-only-oshie.md).

## Implemented Phase 4G reviewable Chie proposals

Chie can now construct one explicit deterministic documentation candidate from
the exact current Knowledge artifact. It returns reversible unified diffs,
exact provenance and identities, fixed warnings, and a pending human-application
and fresh-Hantei handoff. The candidate is derived review material, not accepted
authority, and Chie does not apply, verify, stage, commit, publish, deploy, use a
model, or access a network. See the [proposal guide](docs/architecture/reviewable-chie-patch-proposals.md).

Phase 4H completes the root-owned
[28-scenario external acceptance](docs/architecture/phase-4-external-acceptance.md).
The proof binds Knowledge, Chie, Oshie, and real Hantei evidence to one current
artifact/source identity, keeps local Knowledge/Chie/Oshie available with
network access denied, distinguishes synthetic Hantei evaluator evidence, and
proves candidate, disclosure, mutation, and cleanup boundaries. Phase 4A
through 4H are implemented and externally accepted at the bounded checkpoint.
Kongono remains pre-1.0 and not production-ready.

## Accepted Phase 5A application composition architecture

Phase 4 is checkpointed at `358e412929d6bb7316195d3a1748c346a2de0e1b`.
Phase 5A accepts architecture and documentation only for a supported
application composition root, an explicit deterministic build/check step, and
generated readable PHP constructor wiring. Current application construction is
still caller-owned, the runnable fixture is still test-only, and no composition
artifact, generator, application template, command, or runtime integration has
been implemented.

The future runtime path will consume one complete current build artifact before
accepting work. Runtime service location, ambient containers, resolver or
container injection into application services, property-hook dependency
resolution, request-time reflection or scanning, hidden autowiring, and runtime
`eval` are rejected. The current Experimental resolver and structural
Application Graph remain unchanged and unintegrated.

Phase 5B applies the rolling current-minor Composer/platform migration as
installability metadata only. It adds no runtime semantics, package,
dependency, public API, polyfill, shim, composition/build capability, or
release/LTS promise. Phase 5B is accepted and the bounded PHP range is current.

Phase 5C accepts a contract for one inert version-1 source at
`<application-root>/kongono.composition.json`. It defines canonical inputs,
services, bindings, typed roots, application/operation/transient lifetimes,
cleanup, bounds, deterministic validation, safe reading, and provenance.
Phase 5D implements the Experimental `kongono/composition` package: strict
source/symbol validation, canonical model construction, exact provenance,
versioned structural/current validation, and explicit content-addressed
no-clobber publication. Phase 5E extends it with canonical typed PHP generation,
exact generated identities, non-executing validation/publication, and explicit
application/operation lifetime support. Version 1 uses only individual
`service`, typed `input`, and `default` arguments; applications declare a typed
aggregate service for plural collaboration. Phase 5F adds the explicit exact-
current generated-file loader, one-use startup reflection, exact single-root web
profile, and thin existing PHP-CGI front-controller bridge. Phase 5G adds the
copy-once `skeletons/minimal/` template, reusable content-addressed build/check
workflow, and application-owned `app:build`, `app:check`, and bounded one-request
`app:run` scripts. There is no framework CLI, container, service locator,
mutable current pointer, new transport, listener, automatic skeleton upgrade,
deployment behavior, or automatic upgrade. Phase 5H adds the root-owned
`composer acceptance:phase5` 28-scenario external proof and exact checkpoint;
it adds no production capability or Phase 6 work. Existing Kernel, Web
Application, Web Runtime, and PHP-CGI lifecycle ownership is preserved.
See the
[composition and build guide](docs/architecture/application-composition-and-build.md),
[composition source guide](docs/architecture/composition-source.md),
[Phase 5 roadmap](docs/architecture/phase-5-roadmap.md),
[generated wiring guide](docs/engineering/generated-wiring.md),
[declaration guide](docs/engineering/composition-declarations.md),
[ADR 0068](docs/adr/0068-supported-application-composition-and-build-boundary.md),
[ADR 0069](docs/adr/0069-generated-typed-wiring-and-no-runtime-service-location.md),
[ADR 0070](docs/adr/0070-versioned-composition-source-and-provenance.md), and
[ADR 0071](docs/adr/0071-roots-bindings-lifetimes-and-cleanup.md), and
[ADR 0072](docs/adr/0072-deterministic-composition-artifact-and-publication.md).
[ADR 0073](docs/adr/0073-generated-typed-wiring-and-lifecycle-boundary.md).
[ADR 0074](docs/adr/0074-generated-assembly-web-runtime-integration.md),
[ADR 0075](docs/adr/0075-minimal-application-skeleton-and-workflow.md),
[ADR 0076](docs/adr/0076-phase-5-external-application-acceptance.md),
the [generated runtime integration guide](docs/architecture/generated-runtime-integration.md), and
the [minimal application workflow](docs/architecture/minimal-application-workflow.md), and
the [Phase 5 external acceptance guide](docs/architecture/phase-5-external-acceptance.md).
