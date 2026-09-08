# Kongono

Phase 14B implements the optional Experimental
`kongono/model-invocation-contract` neutral value package and the deterministic
`kongono/model-invocation-build` plan-evidence package and bounded
`kongono/model-invocation-runtime` package under the
provider-neutral model-invocation architecture accepted in
[ADR 0147](docs/adr/0147-provider-neutral-model-invocation-and-adapter-boundary.md),
[ADR 0148](docs/adr/0148-neutral-versioned-model-invocation-contract-foundation.md),
[ADR 0149](docs/adr/0149-deterministic-model-invocation-plan-artifact.md),
[ADR 0150](docs/adr/0150-bounded-provider-neutral-model-invocation-runtime.md),
[ADR 0151](docs/adr/0151-inert-model-result-to-agent-proposal-bridge.md),
[ADR 0152](docs/adr/0152-exact-current-single-profile-model-invocation-application-workflow.md),
[ADR 0153](docs/adr/0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md),
the [contract guide](docs/architecture/model-invocation-contract-foundation.md),
the [plan-artifact guide](docs/architecture/model-invocation-plan-artifact.md),
the [runtime guide](docs/architecture/model-invocation-runtime.md),
the [bridge guide](docs/architecture/model-result-agent-proposal-bridge.md),
the [application workflow guide](docs/architecture/model-invocation-application-workflow.md),
the [inspection guide](docs/architecture/model-invocation-inspection-and-evidence.md),
and the [fixed roadmap](docs/architecture/phase-14-roadmap.md). Its bounded
values remain inert evidence and cannot bypass Phase 13 authority. It adds no
provider SDK, credential lookup, adapter discovery, retry/fallback, registry,
command, recording, telemetry, or background work. Phase
14D directly executes one caller-supplied adapter under exact-current plan
binding and finite evidence budgets. Phase 14E validates one eligible normalized
action suggestion and maps it through trusted application configuration into
inert Agent proposal evidence. The bridge is not a proposal port, grant,
capability invocation, or proof of effect; raw model arguments never become
capability inputs. The graph was 73 acyclic production packages / 275 local
edges before Phase 14F. Phase 14F adds one exact-current single-profile
application workflow with deterministic inert generated wiring, a directly
supplied matching adapter, and one terminal Phase 14D operation. It does not
call the Phase 14E bridge. Phase 14G adds an explicit, offline, read-only,
stateless, presentation-neutral inspection leaf over an exact supplied
profile/wiring pair and optional completed detached runtime evidence. It loads
no generated PHP and emits only bounded Safe aggregate evidence. The current
graph is 75 acyclic production packages / 289 local edges; PHP inventories are
1,358 production / 686 package-test / 80 root-test files. Phase 14 is closed at
checkpoint `05bf8367b56e694a8c388ccb96cdc305cb6e40ae`.

Phase 13A accepts governed application Agent Principal architecture through
[ADR 0139](docs/adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md),
the [Agent Principal Platform guide](docs/architecture/agent-principal-platform.md),
and the [fixed Phase 13 roadmap](docs/architecture/phase-13-roadmap.md). Phase
13B now implements the opt-in neutral
[Agent Principal Contract Foundation](docs/architecture/agent-principal-contract-foundation.md)
under [ADR 0140](docs/adr/0140-neutral-versioned-agent-principal-contract-foundation.md).
Its immutable values grant no authority or execution. Phase 13C implements the
opt-in [Agent Declaration Artifact](docs/architecture/agent-declaration-artifact.md)
under [ADR 0141](docs/adr/0141-deterministic-agent-declaration-artifact.md):
deterministic canonical build, strict typed reconstruction, explicit
caller-relative currentness, and atomic content-addressed no-clobber
publication. Phase 13D now implements the opt-in
[Agent Admission and Authority Plan](docs/architecture/agent-admission-and-authority-plan.md)
under [ADR 0142](docs/adr/0142-deterministic-agent-admission-and-authority-plan-evidence.md).
It deterministically binds one exact current declaration to explicit completed
application-owned authentication and authorization evidence, denies undeclared
or unpermitted capability by default, and returns inert evidence. It performs
no authentication or authorization evaluation, model/provider request,
constraint enforcement, execution, or tool effect. Phase 13E now implements the
opt-in [Agent Principal Runtime](docs/architecture/agent-principal-runtime.md)
under [ADR 0143](docs/adr/0143-provider-neutral-bounded-agent-principal-runtime.md).
It synchronously coordinates one bounded proposal and at most one explicit
capability port, with exact declaration/plan binding, current-authority
rechecks, nine budgets, four cancellation/time checkpoints, reverse cleanup,
and deterministic receipts. It includes no provider SDK, ambient tools,
autonomous loop, retained session, or sandbox. Phase 13F now implements the
opt-in [Agent Application Workflow](docs/architecture/agent-application-workflow.md)
under [ADR 0144](docs/adr/0144-exact-current-single-profile-agent-application-workflow.md).
It supports one exact-current profile, canonical generated PHP wiring, explicit
contained loading, and one synchronous application operation at a time. It
adds no provider, registry, command, background work, or sandbox. Phase 13G now
implements optional [Agent Principal Inspection and Evidence](docs/architecture/agent-principal-inspection-and-evidence.md)
under [ADR 0145](docs/adr/0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md).
It revalidates one explicit profile/wiring pair and creates only deterministic
Safe aggregate inspection, optional Graph context, neutral Observability and
Security Audit projections, and role-addressed evidence. It records nothing,
executes no role or runtime, observes no live application/provider state, and
grants no authority. Phase 13H adds only the deterministic offline root external
acceptance harness and exact local checkpoint
`63376c802ac4672c21458b67b2fcadaf8e03a019`. Phase 13 is closed. Phase 14A adds
only the documentation architecture linked above; no executable Phase 14
behavior, release, push, tag, or Stable/1.0 claim is added.

Phase 12 is closed by the root-owned
[external Extension Platform acceptance](docs/architecture/phase-12-external-acceptance.md).
It exercises the accepted stack without changing production packages or their
authority. Trusted in-process PHP remains non-sandboxed, inspection remains
detached aggregate evidence, and Kongono remains Experimental and pre-1.0.

> **Phase 12 current boundary:** Phases 12A-12H are implemented to their
> accepted boundaries. Phase 12H adds only root-owned external acceptance and
> checkpoint evidence; the production graph remains 63 packages / 226 acyclic
> local edges.

> **Phase 13 current boundary:** Phase 13B adds the neutral contract package,
> Phase 13C adds deterministic declaration artifacts, Phase 13D adds
> deterministic admission and inert authority-plan evidence, Phase 13E adds
> the provider-neutral one-operation runtime, and Phase 13F adds one optional
> exact-current profile plus generated/explicit application wiring, and Phase
> 13G adds optional visibility-safe read-only inspection. The graph is 69
> acyclic production packages / 260 local edges. Requests, plans,
> profiles, and generated identity are not grants; receipts are bounded claims;
> inspection, Graph context, observations, audit evidence, and verification
> PASS are not grants; trusted in-process PHP is not sandboxed. Phase 13H adds
> only external evidence and closes the milestone at checkpoint
> `63376c802ac4672c21458b67b2fcadaf8e03a019`.

> **Build what comes next.**

Kongono is a planned modern application framework for applications that must be understandable to humans, software clients, tooling, infrastructure, and AI agents. PHP 8.5 is its primary application language.

## Who Kongono is designed for

- Humans building and operating applications
- Software consuming explicit, machine-readable contracts
- AI agents acting through governed, inspectable capabilities

## Current status

Phase 11 is complete at checkpoint
`081ae7f50ad4081bb3890a63a652a0bf782a6da1`; the graph now contains exactly 59
acyclic first-party production packages and 206 local
production edges. The official
one-shot `kongo` executable implements exactly `application-graph:inspect`,
`application:build`,
`observability:check`, `observability:inspect`, `security:check`, and
`security:inspect`; the five inspect/check commands are read-only. Phase 11A is
historical and complete. Phase 11B implements the separate versioned
Application Graph contract foundation. Phase 11C implements explicit IR claims,
complete deterministic admission, and canonical immutable Graph facts without
changing runtime behavior. Phase 11D adds Composition projection, Phase 11E adds
canonical artifact handling, and Phase 11F adds bounded aggregate inspection
plus the one explicit CLI consumer without AI or operational authority.

The earlier Phase 2 fixture remains runnable through root-owned black-box test
infrastructure. Run
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

The official executable identity accepted in Phase 8A is exactly `kongo`; that
architecture-only phase implemented neither an alias nor an executable.

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

## Completed Phase 5 application composition milestone

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e`. The supported pre-1.0 slice now
includes an explicit application composition root, deterministic build/check,
generated readable PHP constructor wiring, exact-current loading, existing web
lifecycle integration, the copy-once minimal skeleton, and root-owned external
application acceptance.

The supported runtime path consumes one complete current build artifact before
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

## Completed Phase 6 Deterministic Data Platform

Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05` with twenty-seven production
packages. Phase 6A accepts the Deterministic Data Platform direction as documentation and
architecture only. It keeps five conceptual layers separate: low-level database
execution, explicit SQL plans and dialect compilation, schema and migrations,
optional generated Data Mapper support, and composition with operation-scoped
lifecycle.

Raw SQL remains a first-class parameter-bound escape hatch. Provider
capabilities and unsupported behavior stay visible. Connections, transactions,
results, repositories, and mappers must use explicit boundaries rather than
static facades, ambient connection lookup, or service location. Request-time
mapping reflection, runtime proxies, hidden lazy loading, and Active Record as
the core model are rejected defaults.

Phase 6C implements the two Experimental boundaries accepted by Phase 6B: neutral
`kongono/database` under `Kongono\Database\`, and the separate first PostgreSQL
PDO adapter `kongono/database-pdo-pgsql` under
`Kongono\DatabasePdoPgsql\`. The adapter depends on the neutral package; the
neutral package does not depend on it. PostgreSQL 17.2 through `pdo_pgsql` is
the proved first profile, not a universal PDO or portability claim.

The version-1 contract fixes explicit connectors and operation-owned sessions,
immutable parameter-bound raw SQL with an explicit row/completion expectation,
bounded values and duplicate-safe rows, session-reported capabilities, one
active stream and transaction, explicit transaction states, deterministic
cleanup, combined failure preservation, no automatic retry, and non-disclosing
errors. No ambient connection, native handle, service locator, hidden loading,
request-time reflection, proxy, or Active Record default is accepted.

Phase 6D adds Experimental `kongono/database-sql` immutable Select, Insert,
Update, and Delete plans plus the explicit PostgreSQL 17 compiler package
`kongono/database-sql-pgsql`. Plans are bounded, inspectable, capability-aware,
and parameterize every value occurrence. Update and Delete require predicate or
explicit all-row intent. Direct parameter-bound `DatabaseStatement` raw SQL
remains supported and is not deprecated.

Phase 6E adds the Experimental `kongono/database-migration` authoritative typed
catalog and pure planner plus `kongono/database-migration-pgsql` PostgreSQL 17
compiler/executor. Exact-prefix history, canonical identities, two destructive
gates, one-attempt advisory locking, transactional history/DDL, and explicit
uncertainty remain visible. Phase 6F adds the optional Experimental
`kongono/database-mapping` package with explicit application-owned metadata,
build-time record validation, provenance-bearing artifacts, and generated typed
mappers/repositories. Generated output is derived and is not checked in. Raw
SQL and typed plans remain first-class. Phase 6G brings the current topology to
27 production packages with exact-current outer loading/composition,
operation-owned sessions, and an optional data skeleton. Phase 6H adds the
root-owned PostgreSQL 17 whole-platform acceptance command, a local
observational four-scenario measurement baseline, documentation reconciliation,
and the exact Phase 6 checkpoint. ORM behavior, pooling, retry, migration
startup, runtime telemetry, further providers, and deployment remain absent.

Kongono remains pre-1.0, uses the current PHP 8.5 minor policy, and is not
production-ready. See [ADR 0077](docs/adr/0077-layered-data-platform-and-explicit-sql-boundary.md),
[ADR 0078](docs/adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md),
[ADR 0079](docs/adr/0079-neutral-database-session-and-provider-capability-contract.md),
[ADR 0080](docs/adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md),
[ADR 0081](docs/adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md),
[ADR 0082](docs/adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md),
[ADR 0083](docs/adr/0083-deterministic-schema-migrations-and-postgresql-execution.md),
[ADR 0084](docs/adr/0084-compile-time-data-mapping-and-generated-repositories.md),
[ADR 0085](docs/adr/0085-generated-data-application-and-operation-owned-sessions.md),
[ADR 0086](docs/adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md),
the [Data Platform guide](docs/architecture/data-platform.md), the
[Phase 6 external acceptance guide](docs/architecture/phase-6-external-acceptance.md), the
[database contract](docs/architecture/database-contract.md), the
[SQL plan guide](docs/architecture/database-sql-plans.md), the
[mapping guide](docs/architecture/database-mapping.md), the
[migration guide](docs/architecture/database-migrations.md), the
[engineering contract tables](docs/engineering/database-contracts.md), the
[performance baseline guide](docs/engineering/database-performance-baseline.md), and the
[Phase 6 roadmap](docs/architecture/phase-6-roadmap.md).

## Completed Phase 7 Deterministic Persistence milestone

Phase 7A accepts an optional upper persistence capability commonly called an
ORM, but retains Kongono's explicit build, lifecycle, security, and I/O
boundaries. Plain PHP domain classes stay application-owned. Future mapping must
be deterministic compile-time evidence and ordinary generated PHP; runtime
Reflection mapping, proxies, magic properties, private-state bypass, hidden lazy
loading, Active Record inheritance, service location, and automatic flush are
rejected defaults.

Any future persistence context, identity map, snapshot, and change set belongs
to one operation and is retired before fallible cleanup. Flush remains explicit,
the caller owns transactions, uncertain failure closes the context without
automatic replay, and relationship/load-plan/cascade behavior must be declared
and bounded. Direct raw SQL, immutable plans, migrations, generated Data Mappers,
and direct repositories remain first-class without the persistence layer.

Phase 7B adds the Experimental `kongono/persistence` package. Phase 7C adds
Experimental `kongono/persistence-build` as production package twenty-nine. The
neutral package contains operation-scoped identity,
opaque concurrency-token, state, immutable value-free change/flush, context
interface, diagnostic, and exception contracts. The build package validates the
sole application-owned persistence JSON against current mapping evidence and
typed domain construction, then emits a detached canonical model and provenance
artifact with separate structural/current validation and guarded publication.
Phase 7D deterministically emits content-addressed ordinary PHP adapters for
public hydration, public extraction, typed identity, snapshots, and neutral
change evidence. Structural and exact-current validation happen without loading
candidate bytes, and guarded publication is atomic and no-clobber. It implements
no runtime loader or database authority. Phase 7E now adds one concrete
operation-scoped context and explicit single-entity unit of work. One explicit
flush passes the caller's exact `DatabaseExecutor` to one change executor; the
context never owns transactions, retries, cleanup, SQL, provider choice, or
generated-state loading. Applied writes synchronize or retire, zero-applied
absence/conflict remains Active, and uncertain failure releases references and
fails terminally. Phase 7F adds explicit bounded one-level relationship loads,
cascade policies, and generated aggregate repositories with caller-owned
database/executor authority. Phase 7G adds the Experimental exact-current
persistence-application loader and Composition workflow. Phase 7H closes the
thirty-package milestone with isolated PostgreSQL 18.6 acceptance, distinct
PostgreSQL 17.2 Phase 6 regression, four named local timing summaries, complete
cleanup/nondisclosure proof, documentation, and an exact checkpoint. See
[ADR 0087](docs/adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](docs/adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
[ADR 0089](docs/adr/0089-neutral-persistence-contracts-and-package-boundary.md),
[ADR 0090](docs/adr/0090-deterministic-persistence-model-and-artifact-compilation.md),
[ADR 0091](docs/adr/0091-deterministic-generated-persistence-state-evidence.md),
[ADR 0092](docs/adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md),
[ADR 0093](docs/adr/0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md),
[ADR 0094](docs/adr/0094-exact-current-generated-persistence-application-integration.md),
[ADR 0095](docs/adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md),
the [Deterministic Persistence guide](docs/architecture/deterministic-persistence.md),
and the [Phase 7 roadmap](docs/architecture/phase-7-roadmap.md).

## Implemented Phase 8A through Phase 8H Structured CLI edge

The official executable is exactly `kongo`; no alias is accepted.
Phase 8A separates immutable command meaning, one explicit invocation producing
a neutral result, and human or versioned machine presentation plus outer process
adaptation. The neutral engine does not read ambient process, stream,
environment, directory, terminal, signal, clock, randomness, credential, or
registry state.

Command definitions must become deterministic provenance-bearing build evidence
and fail closed when missing, stale, partial, mixed, ambiguous, unsupported, or
unverifiable. Runtime scanning, Reflection discovery, mutable registries,
service location, hidden interaction, direct handler printing, and handler
process exit are rejected. Human and machine output share one result and
diagnostic identity; machine mode never silently prompts, mutation requires
honest dry-run and explicit authority, and each invocation owns explicit cleanup
with sequential same-process isolation.

Phase 8C extends the Experimental `kongono/console` package with immutable
specifications and deterministic parsing of explicit tokens. Sensitive values
use a separate carrier and mutation-capable commands require exactly one of
plan or apply. The separate Experimental `kongono/console-build` package
compiles one inert versioned `kongono.commands.json` source to canonical model
and artifact evidence and validates complete exact-current bytes. It adds no
publication, loading, handler binding, registry, discovery, executor, concrete
operation, command, presenter, executable, installer, deployment behavior, or
Stable API. Phase 8D adds immutable exact handler bindings, one fresh operation
per dispatch, explicit cancellation evidence, bounded reverse cleanup, combined
failure preservation, reentrancy rejection, and sequential reuse in the
separate `kongono/console-runtime` package. It adds no presentation, process
adapter, executable, real command, registry/discovery, or application
integration. Phase 8E adds bounded neutral result records, deterministic plain
human output, canonical version-1 result/help JSON, a shared exit map, and one
explicit one-write delivery adapter. Protected values are omitted. The package
graph then contained thirty-four packages. Phase 8F adds the Experimental
`kongono/console-commands` package with the exact read-only
`graph:inspect`, `composition:inspect`, and `quality:inspect` family. Its typed
handlers consume only accepted capabilities, run through Console Runtime, and
share the Phase 8E human/machine presentation contract. Exact generated
Composition wiring proves one application-owned command family and fresh
operation roots without creating `kongo`, a native process adapter, mutation,
or ambient authority. The graph now contains thirty-five packages and the PHP
8.5 current-minor policy remains unchanged. See
[ADR 0096](docs/adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](docs/adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
[ADR 0098](docs/adr/0098-neutral-structured-cli-contracts-and-package-boundary.md),
[ADR 0099](docs/adr/0099-deterministic-command-parsing-and-definition-evidence.md),
[ADR 0100](docs/adr/0100-operation-scoped-command-execution-and-cleanup.md),
[ADR 0101](docs/adr/0101-deterministic-command-presentation-and-process-adaptation.md),
[ADR 0102](docs/adr/0102-first-read-only-framework-capability-commands.md),
[ADR 0103](docs/adr/0103-supported-kongo-edge-and-honest-application-build.md),
the [Structured CLI guide](docs/architecture/structured-cli.md), and the
[Phase 8 roadmap](docs/architecture/phase-8-roadmap.md).

Phase 8G implements the official extensionless `kongo` executable and exactly
`application:build`. `--plan` performs a non-mutating dry run and returns the
predicted content-addressed output identities; `--apply` grants explicit
authority to publish those outputs atomically, within the requested directory,
and without clobbering existing content. No prompt exists and
`--no-interaction` is automation-safe. Human and machine forms share one result
and exit map. Phase 8F snapshot handlers remain programmatic until truthful
application snapshots exist. No alias, second command, daemon, watcher,
installer, deployment manager, or shell is introduced. Phase 8H externally
proves both executable edges, human/machine parity, non-mutating plan, explicit
apply, exit-3 no-clobber conflicts, recovery, nondisclosure, and cleanup through
`composer acceptance:phase8`. See [ADR 0104](docs/adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md)
and the [external acceptance guide](docs/architecture/phase-8-external-acceptance.md).
## Implemented Phase 9A through Phase 9H Identity and Access Security boundary

Phase 9A defines how Kongono may later establish identity facts and enforce
application-owned access policy without hiding authority in middleware,
sessions, roles, globals, containers, or provider objects. Authentication and
authorization are separate: authentication success is evidence, not
permission. Authorization is deny by default and only an exact `Permit` for an
exact operation context, action, resource, current policy evidence, and explicit
environmental facts may reach a protected capability.

The accepted direction keeps security state immutable, explicit, and
operation-scoped for persistent-runtime safety. Enforcement occurs before the
protected read or side effect. Credentials remain opaque and nondisclosing;
policy evidence is deterministic, versioned, provenance-bound, and fail closed.
Transport, disclosure, audit, and telemetry remain separate from decision
authority.

Phase 9B implements the Experimental `kongono/identity-access` package. Its
immutable provider-neutral vocabulary includes exact principal kinds and
references, typed authentication outcomes, inert authorization decisions,
canonical application-owned actions/resource types, opaque resource
references, explicit anonymous/authenticated Security Context, and bounded
non-disclosing diagnostics. Authentication success grants no permission, and
`Permit` evaluates or enforces nothing by itself.

The package depends only on API Metadata, Diagnostics, and PHP. It adds no
authenticator, verifier, credential, cryptography, policy evaluator, decision
receipt, session, HTTP or Composition integration, provider, enforcement,
command, audit, telemetry, or application workflow.

Phase 9C implements the separate Experimental `kongono/authentication` package.
It consumes ordered caller-owned evidence, selects one explicitly registered
verifier by exact mechanism, owns one verify/retire attempt, preserves primary
and cleanup failures, and validates explicit authentication facts against
caller-supplied current time, minimum strength, maximum age, and validity.
Authenticated facts remain evidence rather than permission, equality at
validity/age/strength bounds remains valid, and the orchestrator retains no
per-call state across sequential or reentrant calls.

Authentication depends only on API Metadata, Diagnostics, Identity Access, and
PHP. It adds no concrete verifier/provider, credential, password, token, key,
cryptography, session/cookie transport, HTTP adapter, authorization evaluator,
policy, Security Context construction, Composition wiring, command, audit,
telemetry, application workflow, or Phase 9D behavior.

Phase 9D implements the separate Experimental `kongono/authorization` package.
Callers independently supply expected name/version/provenance identity and
opaque policy evidence plus explicit environmental evidence. Mismatch or
unsupported policy returns Indeterminate without evaluator execution. The sole
exact evaluator's Permit, Deny, or Indeterminate result is preserved; failure
never permits. Bounded receipts omit principal/resource IDs and evidence
payloads and grant no authority.

Authorization depends only on API Metadata, Diagnostics, Identity Access, and
PHP. Phase 9D itself adds no policy language/build/loader, role or permission
semantics, ambient guard, protected-capability enforcement, Security Context
construction, HTTP/session transport, Composition wiring, database enforcement,
audit, telemetry, AI authority, or workflow.

Phase 9E implements Composition source version 2 with explicit
application/operation input lifetimes while preserving version-1 inputs as
application-lifetime. Generated applications retain only application inputs;
operation inputs are validated before lease or construction and flow directly
through ordinary constructor calls. The new Experimental
`kongono/security-runtime` package constructs fresh anonymous or exactly
verified authenticated contexts and owns their generated-operation lifecycle.
Authentication success still grants no authorization.

Phase 9F adds the Experimental `kongono/security-http` and
`kongono/security-enforcement` packages. Security HTTP implements exactly the
same-origin HTTPS `browser.session.v1` profile with the host-only
`__Host-kongono_session` cookie and session-bound `X-Kongono-CSRF` evidence.
The transport syntax and deterministic cookie emitter provide no session truth,
cryptography, storage, login, or authorization. Security Enforcement evaluates
one exact request and invokes one bound protected capability only for exact
`Permit`; authentication success, routing, middleware, and receipts grant no
authority. Both packages retain no ambient current-user, session, context, or
decision state.

Phase 9G adds rule-free exact-current policy identity evidence, one supported
request-to-operation-to-enforcement workflow, a copy-once security skeleton,
and the read-only `security:inspect` and `security:check` commands. Kongono
remains pre-1.0 with exactly forty-four acyclic production packages. No second
HTTP/provider profile, broad identity product, autonomous policy, deployment
manager, or broad identity product exists. Phase 9H adds only root-owned
external proof through `composer acceptance:phase9` and the exact local Phase 9
checkpoint. Kongono remains pre-1.0; this is not certified security,
production readiness, universal portability, or Phase 10 runtime behavior. See
[ADR 0105](docs/adr/0105-explicit-identity-authentication-and-authorization-boundary.md),
[ADR 0106](docs/adr/0106-fail-closed-security-context-and-policy-separation.md),
[ADR 0107](docs/adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md),
[ADR 0108](docs/adr/0108-deterministic-explicit-authentication-orchestration.md),
[ADR 0109](docs/adr/0109-deterministic-deny-by-default-authorization-evaluation.md),
[ADR 0110](docs/adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md),
[ADR 0111](docs/adr/0111-secure-browser-session-transport-and-capability-enforcement.md),
[ADR 0112](docs/adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md),
[ADR 0113](docs/adr/0113-phase-9-external-security-acceptance-and-checkpoint.md),
the [Identity and Access Security guide](docs/architecture/identity-and-access-security.md),
the [operation-scoped Security Context guide](docs/architecture/operation-scoped-security-context.md),
the [browser-session security and capability enforcement guide](docs/architecture/browser-session-security-and-capability-enforcement.md),
the [security application workflow](docs/architecture/security-application-workflow.md),
the [Phase 9 external acceptance guide](docs/architecture/phase-9-external-acceptance.md),
and the [Phase 9 roadmap](docs/architecture/phase-9-roadmap.md).

## Phase 10 Observability architecture and neutral contracts

Phase 10A defines bounded operational-evidence architecture, Phase 10B adds the
Experimental `kongono/observability` neutral vocabulary, Phase 10C adds the
Experimental `kongono/tracing` operation runtime, and Phase 10D adds the
Experimental `kongono/metrics` aggregation runtime. Diagnostics, traces, metrics, operational
events, security audit evidence, authorization receipts, and product analytics
remain different domains. Signals explain or correlate behavior but cannot
identify a principal, grant permission, change policy, repair application
state, hide a primary failure, or become business truth.

Future framework signals are structured, typed, versioned, bounded, and
schema-allowlisted. Context is immutable, explicit, operation-scoped, and
released during cleanup. There is no global logger, static tracer, current span,
global meter/provider, thread or fiber local, service locator, or automatic
environment configuration. Clocks, identifiers, propagation, randomness, and
sampling remain explicit collaborators. Disclosure rules continue to deny
credentials, session/CSRF evidence, policy bodies, request/database values,
personal data, provider payloads, and unbounded metric labels by default.

OpenTelemetry is the selected future interoperability direction at an outer
adapter boundary. No OpenTelemetry SDK, Composer dependency, exporter, OTLP
transport, collector, backend, global provider, or lower-package coupling exists
because of Phase 10B. Exporter backpressure and failure must eventually be
bounded and visible without corrupting primary outcomes; security audit evidence
remains separate and carries no current durability promise.

The package supplies explicit uncorrelated/correlated context, bounded scalar
attributes, versioned signal identity, closed kind/status values, one immutable
observation envelope, and one recorder interface with no implementation. It
depends only on API Metadata, Diagnostics, and PHP, and no existing production
packages other than Tracing and Metrics depend on it. Tracing depends only on Observability,
API Metadata, Diagnostics, and PHP; no other production package depends on
Tracing. Metrics independently depends on the same four foundations; no
production package depends on Metrics. The production graph is exactly
forty-nine acyclic packages, the active
PHP window remains `>=8.5.0 <8.6.0`, and Kongono remains pre-1.0. See
[ADR 0114](docs/adr/0114-explicit-observability-and-operational-evidence-boundary.md),
[ADR 0115](docs/adr/0115-structured-signals-context-and-exporter-neutrality.md),
[ADR 0116](docs/adr/0116-neutral-observability-contracts-and-package-boundary.md),
[ADR 0117](docs/adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md),
[ADR 0118](docs/adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md),
the [Observability and Operational Evidence guide](docs/architecture/observability-and-operational-evidence.md),
and the fixed [Phase 10 roadmap](docs/architecture/phase-10-roadmap.md). Tracing
has explicit typed propagation, caller-owned clock/IDs/sampling/recording,
bounded root/child lifecycle and attributes, deterministic Record/Drop mapping,
idempotent cleanup, isolation, and visible nondisclosing failures. Metrics owns
finite numbers, units, canonical schema dimensions, four instrument kinds,
histogram boundaries, bounded plans, immediate aggregation, and an explicit
Active/Collected/Retired lifecycle. It limits instruments to 64 schema series
and plans to 256, emits deterministic observations only after releasing
retained collection state, and fails visibly on ownership, schema, overflow,
lifecycle, or recorder errors. No dynamic labels, raw measurement log,
event/audit pipeline, exporter, backend, OpenTelemetry dependency, or
application integration exists inside Metrics.

Phase 10E adds separate Experimental `kongono/operational-events` and
`kongono/security-audit` sibling packages, bringing the graph to exactly
forty-nine acyclic packages and 166 local edges. Both depend only on
Observability, API Metadata, Diagnostics, and PHP and expose no conversion API.
Finite schemas admit declared typed scalar values, canonicalize attributes, and
derive Safe or Protected disclosure from fields actually supplied. Operational
events retain six explicit severities and caller-supplied neutral status;
security audit evidence retains explicit outcome, retention intent, and the
exact `not_guaranteed` durability truth. A valid call records exactly one
bounded observation; invalid data records none, and recorder failure is visible,
nondisclosing, final, and unretried. See
[ADR 0119](docs/adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md).
No logger facade, exporter, buffer, store, retention enforcement, compliance
claim, application integration, or Stable API is added. Phase 10F is the sole
possible next handoff and requires separate authenticated authority.

Phase 10F implements the Experimental `kongono/opentelemetry` outer adapter and
accepts [ADR 0120](docs/adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md).
The sole version-1 OTLP HTTP/protobuf profile uses direct generated request
classes, stable 1.38.0 semantic conventions, fixed trace/metric/log suffixes,
and one caller-supplied transport. Recording is bounded and non-I/O; flush and
close are explicit, synchronous, no-drop, partial-progress aware, and
nondisclosing. Operational events and audit evidence retain separate log
scopes, and audit durability remains `not_guaranteed`. Histogram evidence is
losslessly emitted as a named gauge family because no numeric bucket boundaries
exist to support a native OTLP Histogram.

At the Phase 10F checkpoint, the production graph was exactly fifty acyclic
packages and 169 local edges. No SDK, provider, globals, auto-configuration, environment discovery,
HTTP client, retry, backend, application workflow, or Stable API is added.
Phase 10G adds the explicit upper workflow below without widening that adapter.

Phase 10G now provides deterministic observability configuration and one
explicit application workflow. `kongono/observability-build` produces and
checks content-addressed exact-current evidence, while
`kongono/observability-application` coordinates tracing, metrics, operational
events, security audit, and the selected OpenTelemetry bridge without globals,
environment discovery, or hidden defaults. The copy-once
`observability-minimal` skeleton uses a zero-network example transport.

The official `kongo` family contains exactly six commands:
`application-graph:inspect`, `application:build`, `observability:check`,
`observability:inspect`, `security:check`, and `security:inspect`. The graph contains 56 acyclic
production packages and 182 local edges.

Phase 10H closes the milestone with root-owned external proof through
`composer acceptance:phase10` and one exact local checkpoint. The profile
copies and installs the observability skeleton offline, parses selected-profile
payloads with pinned protobuf classes, proves signal/lifecycle/failure and
read-only workflow boundaries, reruns Phase 8 and Phase 9 compatibility, and
removes all owned temporary state. It adds no production package, dependency,
exporter, provider, backend, collector, durable audit store, deployment, or
Phase 11 behavior. Kongono remains pre-1.0. See the
[Phase 10 external acceptance guide](docs/architecture/phase-10-external-acceptance.md).

## Phase 11A and 11B authoritative Application Graph foundation

Phase 11A accepted [ADR 0123](docs/adr/0123-authoritative-application-graph-and-language-neutral-ir-boundary.md),
the [Authoritative Application Graph guide](docs/architecture/authoritative-application-graph.md),
and the fixed [Phase 11 roadmap](docs/architecture/phase-11-roadmap.md).

The future language-neutral IR is the producer-facing input boundary. The
future authoritative Graph is the validated canonical immutable derivation of
admitted records and explicit accepted artifacts for one exact application
state. Only that deterministic pipeline may establish Graph facts; Graph facts
remain descriptive and grant no identity, authorization, secret, filesystem,
process, network, database, deployment, or production authority.

The existing Experimental Phase 0J structural snapshot remains unchanged. Its
opaque graph-local keys and open kind labels are not stable Phase 11 identities
or framework vocabularies. Phase 11B accepts
[ADR 0124](docs/adr/0124-versioned-application-graph-contract-foundation.md)
and implements `kongono/application-graph-contract`: four typed stable digest
identities, contract version 1, closed Composition vocabularies, typed schemas
and catalog, bounded provenance, visibility, disclosure, and non-disclosing
diagnostics. It adds no IR records, Graph construction, Composition projection,
serialization, artifact, command, query, runtime integration, extension
behavior, consumer access, or Chie/Hantei/Oshie access. Each later 11C-through-11H row requires separate
authenticated authority; none begins automatically. Kongono remains pre-1.0
and not production-ready.

Phase 11F now adds only the immutable aggregate artifact inspection and the
supported one-path `application-graph:inspect` command. The command returns
bounded identities and closed-vocabulary counts, exposes no individual fact,
makes no external-currentness claim, and grants no authorization. Phase 11G
adds the separate `kongono/application-graph-intelligence` package for one
role-addressed Safe aggregate view and independent six-check verification. It
does not execute Chie, Hantei, or Oshie or expose raw or individual Graph facts.
Phase 11H is complete. Phase 12A now accepts only the governed Extension
Platform architecture in [ADR 0131](docs/adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md),
the primary [Extension Platform guide](docs/architecture/extension-platform.md),
and the [fixed Phase 12 roadmap](docs/architecture/phase-12-roadmap.md). Extension
selection is explicit; declaration, admission, grant, and execution remain
separate; capabilities are attributable and deny by default; and contribution
claims are non-authoritative IR inputs until admitted. Phase 12B adds only the
neutral Extension Contract package, depending on API Metadata and Diagnostics.
Its values are inert: construction is not admission/currentness/trust, requests
are not grants, contributions are not Graph facts, visibility is not
authorization, lifecycle is not hosting, and category is not a security
decision. Phase 12C adds the separate `kongono/extension-build` package for
canonical declaration evidence, exact structural reconstruction,
caller-relative currentness, and content-addressed no-clobber publication. It
adds no public loader, discovery, grant, Composition, host, or execution
authority. Phase 12D now adds the separate deterministic Extension Admission
package for exact selection, explicit conflict winners, attributable Graph IR,
and complete structural admission. Its output is still IR rather than an
authoritative Graph. That checkpoint was 60 packages / 212 local edges. Phase
12E now adds explicit capability grants and inert generated extension wiring in
the build-only `kongono/extension-composition` package. Phase 12F adds the
bounded `kongono/extension-host` package for exact one-file loading, typed
application and operation lifecycle, reverse cleanup, leases, and deterministic
failure preservation. Trusted/in-process PHP is not sandboxed. The current
Phase 12G adds detached aggregate-only extension inspection and the read-only
`extension:inspect` command. It establishes neither currentness nor authority
and never loads generated PHP. The graph is 63 packages / 226 local edges;
Phase 12H closes the milestone with root-owned external evidence only, and Kongono remains Experimental and pre-1.0.

## Completed Phase 14 provider-neutral model invocation

Phases 14A-H are implemented and Phase 14 is closed. The [external acceptance
guide](docs/architecture/phase-14-external-acceptance.md) describes the
deterministic offline root harness and exact local checkpoint. Its explicit fake
adapter is test evidence, not provider integration or authority. Kongono remains
Experimental, pre-1.0, and fully usable without AI, a provider, credentials, or
a network.

## Accepted Phase 15A-15H governed network-egress foundation and evidence

Phase 15A accepts [ADR 0155](docs/adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md).
Phase 15B accepts [ADR 0156](docs/adr/0156-neutral-versioned-network-egress-contract-foundation.md)
and implements the optional Experimental
[neutral contract package](docs/architecture/network-egress-contract-foundation.md).
It reuses PSR-7 request and response interfaces without making PSR-18 the
control plane and never reads or claims deep immutability for body streams.
Origins, authority and policy references, capability evidence, responses, and
receipts remain evidence rather than permission or external truth. Phase 15C
accepts [ADR 0157](docs/adr/0157-deterministic-network-egress-policy-artifact.md)
and the optional Experimental deterministic
[policy-artifact package](docs/architecture/network-egress-policy-artifact.md).
It creates and validates inert offline evidence from one explicit typed policy
and can publish it content-addressed without overwrite. Phase 15D accepts [ADR
0158](docs/adr/0158-bounded-provider-neutral-network-egress-runtime.md) and the
optional Experimental [bounded runtime](docs/architecture/network-egress-runtime.md),
which coordinates one direct attempt over explicit caller-owned ports with
address binding, finite budgets, cancellation, bounded transfer evidence, and
deterministic close. Phase 15F accepts
[ADR 0160](docs/adr/0160-exact-current-single-profile-network-egress-application-workflow.md)
and the optional Experimental exact-current single-profile
[application workflow](docs/architecture/network-egress-application-workflow.md).
Phase 15G accepts [ADR 0161](docs/adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
and the optional Experimental
[inspection leaf](docs/architecture/network-egress-inspection-and-evidence.md).
It revalidates exact supplied profile/wiring evidence and can summarize one
exactly paired detached operation/result without loading generated PHP,
networking, recording, replay, repair, authority evaluation, or developer-role
execution. The graph is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15H adds bounded local external acceptance,
and Phase 15 is closed at one exact local checkpoint.

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

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15A-H are Accepted and Phase 15 is closed.
No first-party resolver, credential provider, provider adapter, live
inspection probe, inspection command, public compatibility certification,
Stable/1.0 claim, or production-readiness claim exists.
