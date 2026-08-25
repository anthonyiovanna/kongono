# Kongono Architecture

## Status and scope

This document is an index of Kongono's authorized architectural direction.
Kongono is pre-alpha. Phase 2A through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. The implemented topology contains
exactly fourteen acyclic production packages plus a root-owned, test-owned
runnable fixture. That fixture externally proves the real HTTP Message,
Routing, HTTP Server, Web Application, Web Runtime, diagnostic, and Kernel path;
it is not a fourteenth package, production application, supported template,
SAPI adapter, server, listener, emitter, or deployment surface.

The selected production PHP CGI integration now has a concrete delivery and
thin entry plus external test-owned acceptance. Phase 3B
selects PHP `cgi-fcgi` in the FastCGI `RESPONDER` role under CGI/1.1 as the
first exact host profile. Phase 3C and [ADR 0053](docs/adr/0053-bounded-php-cgi-inbound-conversion.md)
implement bounded inbound conversion. Phase 3D and [ADR 0054](docs/adr/0054-bounded-php-cgi-response-emission.md)
implement fully staged, bounded response emission. Phase 3E and [ADR 0055](docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md)
implement the literal typed operator profile, fixed native bootstrap validation,
and independent current-delivery permission evaluation. Only the bootstrap
validator and concrete delivery capture read `PHP_SAPI`; only bootstrap reads
the ten fixed INI values. Phase 3F implements the Internal concrete delivery
and Experimental thin entry. No production listener or deployment artifact
exists.
Streaming is unsupported, and post-commit failure is uncertain, throws, and is
never rolled back or retried. Phase 3G front-controller lifecycle and deployment
constraints are implemented; Phase 3H completes test-owned external acceptance
and the Phase 3 checkpoint.
The [PHP CGI profile](docs/architecture/php-cgi-fastcgi-profile.md) and [Phase 3
roadmap](docs/architecture/phase-3-roadmap.md) record the completed bounded outer
edge.
Kernel remains transport-neutral, Web Application retains the sole operation
scope per call, and Web Runtime retains failure and send/close orchestration.
Phase 0X adds the exact Diagnostic Presentation leaf and
pure trusted-developer projection above Phase 0U, which adds the exact acyclic DI-to-Diagnostics
edge and integrates all 25 Dependency Injection diagnostics across 26 throw
sites. Phase 0T integrates all six Kernel lifecycle failures, and Phase 0Q
integrates all 11 Application Graph failures, with exact package-owned bounded
diagnostic context on the presentation-neutral
`kongono/diagnostics` value foundation: bounded immutable schemas and values,
deterministic byte-safe text representation, structural closed-schema capture,
and package-local construction failures. All 42 original failures now own exact
bounded context as 6 Kernel, 11 Graph, and 25 DI schemas. Diagnostics
construction failures remain context-free. Current production behavior otherwise remains the
Phase 0J implementation: the PHP quality toolchain, the narrow
`kongono/kernel` lifecycle package, the immutable structural core with exact
direct-relation and whole-snapshot kind selectors in
`kongono/application-graph`, and explicit typed constructor resolution in
`kongono/dependency-injection`. Other statements about framework behavior
describe planned architecture unless explicitly identified as current
repository behavior.

## Package dependency topology

The current runtime has twenty-seven packages. `kongono/api-metadata` requires
PHP only and owns `Kongono\ApiMetadata\Api` and `ApiStatus`. Kernel,
Diagnostics, and Dependency Injection each require API Metadata; Kernel,
Application Graph, Dependency Injection, HTTP Message, Routing, and HTTP Server
also require Diagnostics. Routing consumes only the PSR-7 message interfaces
and does not depend on the HTTP Message package. HTTP Server depends on API
Metadata, Diagnostics, Routing, and the two PSR-15 interface packages; it does
not depend on the HTTP Message package. Web Application is a leaf above API
Metadata, Diagnostics, HTTP Server, Kernel, Routing, and the PSR interfaces; it
does not depend on the concrete HTTP Message package. Web Runtime is the top-level
delivery leaf above Web Application and the trusted-diagnostic foundations.
`kongono/php-cgi` is an outer leaf over API Metadata, Diagnostic Activation,
Diagnostics, HTTP Message, Web Runtime, PSR-7, and PHP. It has no direct Web
Application dependency. The root consumes all twenty-seven. This graph is acyclic,
and no package depends on Kernel merely for compatibility metadata.

Phase 1C implements `kongono/diagnostic-activation` as an acyclic package that
depends only on API Metadata and PHP. Phase 1D gives it exactly two Experimental
unbacked permission enums:
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission` and
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission`. Each has
ordered `Denied`, `Allowed` cases and `allowsTrustedPresentation(): bool`. The
first represents an already-validated decision fixed for one adapter/runtime
lifetime; the second represents an adapter-controlled decision for one current
delivery. Web Runtime now consumes both through its neutral fail-closed
sequence. Only Diagnostic Handler depends on
Diagnostic Activation, and the framework root consumes all eleven packages.

Phase 0X implements `kongono/diagnostic-presentation` as a leaf above this
graph. It depends directly on API Metadata, Diagnostics, Kernel, Application
Graph, and Dependency Injection. Only Diagnostic Handler depends on it, and it
creates no binding, service registration, Graph node, ambient discovery, or
reverse edge. Diagnostic Presentation does not depend on Diagnostic Activation;
the framework root consumes both independently.

Phase 1H implements `kongono/diagnostic-handler` as the eighth leaf package under
`Kongono\DiagnosticHandler\`. It requires PHP `>=8.5.0 <8.6.0` and depends
directly only on API Metadata, Diagnostic Activation, Diagnostic Presentation,
Kernel, Application Graph, and Dependency Injection. The three originating
packages are direct dependencies because their marker interfaces form the
handler's accepted source union; it has no direct dependency on Diagnostics.
Web Runtime depends on Diagnostic Handler, while Handler retains no reverse
edge. The root consumes it, and Handler is the only lower package depending on
both Diagnostic Activation and Diagnostic Presentation. The implemented
topology remains acyclic.

The former `Kongono\Kernel\Api\*` identities are unavailable and no alias or
forwarder exists. Graph and Kernel retain their diagnostic integrations exactly.
DI now independently requires Diagnostics for its 25 schemas. Phase 0U adds no
renderer, error page, transport, or other runtime capability.

Phase 2C implements the ninth package identity `kongono/http-message` at
`packages/http-message` under `Kongono\Http\Message\`. That low-level package
depends only on PHP, API Metadata, Diagnostics, `psr/http-message: ^2.0`, and
`psr/http-factory: ^1.1`.
The Kernel remains transport-neutral and PSR-free, and no third-party HTTP
message implementation or framework substrate owns Kongono's implementation.

Phase 2D implements `kongono/routing` at `packages/routing` under
`Kongono\Routing\`. Its dependencies are limited to PHP, API Metadata,
Diagnostics, and `psr/http-message`. Immutable route definitions and tables are
validated within strict byte/count bounds; matching reads only method and URI
path, compares exact encoded bytes, and selects by literal/parameter specificity
independent of declaration order. It performs no handler, middleware, DI, Graph,
Kernel, authorization, or I/O work and retains no request-specific state.

Phase 2E implements `kongono/http-server` at `packages/http-server` under
`Kongono\HttpServer\`. Its immutable pipeline compiles no more than 128 explicit
PSR-15 middleware layers around one terminal handler. Its routing handler
validates one complete canonical route-name-to-handler map, places a selected
untrusted `RouteMatch` under the reserved `RouteMatch::class` attribute, and
delegates unmatched requests without choosing status or body. It catches no
`Throwable`, supplies no error middleware, and owns no bootstrap, Kernel scope,
adapter, transport, emission, or ambient request state. Phase 2F implements the
separate Web Application composition and application-call scope boundary;
Phase 2G and ADR 0049 implement the outer delivery adapter without changing Web
Application's sole Kernel-scope ownership. Phase 2H's root-owned fixture
completes the runnable black-box milestone without becoming production behavior.

Kongono Framework targets PHP 8.5 and is designed for three first-class consumption surfaces: Human Experience, Software/API Experience, and Agent Experience. The framework is not a Laravel clone. It aims to preserve strong developer productivity while favoring explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

## Foundational model

The planned Application Graph is the authoritative, machine-readable representation of an application. It will derive from a language-neutral Intermediate Representation so that PHP, the first source-language frontend, does not permanently define the conceptual model. Phase 0J retains the source-language-neutral in-memory structural snapshot introduced in Phase 0D, the exact outgoing and incoming direct-relation selectors from Phase 0H, and adds only exact whole-snapshot node-kind and relation-kind selection. `nodesOfKind(NodeKind): array` and `relationsOfKind(RelationKind): array` compare kind values exactly within separate domains, return `[]` for absent kinds, and preserve canonical order and stored-object identity in fresh lists. Node selection scans all nodes in `O(N)` time, relation selection scans all relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open kind labels define no built-in vocabulary or schema. This is not combined endpoint-and-kind filtering, multiple-kind selection, predicates, traversal, reachability, neighbor selection, or a generic query system. Stable identifiers, domain schemas, metadata, provenance, visibility, serialization, discovery, compilation mechanics, persistence, mutation, framework integration, optimization promises, and the complete authoritative representation remain unresolved.

Kongono uses `packages/kernel` as its first physical monorepo package. Its Experimental state machine boots once, owns overlapping opaque operation scopes by object identity, rejects shutdown while work is active, and shuts down terminally. It is transport-, runtime-, and container-neutral. Independently installable capabilities and application architecture beyond this boundary remain planned.

`packages/dependency-injection` is a separate Experimental component. It maps canonical existing interfaces to explicit concrete implementations and ordered constructor dependencies, validates complete definition sets, and constructs transient instances on every dependency edge. It does not make the Kernel a container, provide PSR-11 or autowiring, select shared/scoped lifetimes, or integrate with the Application Graph.

The lifecycle is persistent-runtime-first: framework design may not assume all application state disappears after each request or operation. Runtime-specific integrations must remain adapters rather than kernel dependencies.

Phase 0M realizes the 42 package-owned diagnostic assignments through three
Internal backed-enum catalogs. The three separate Public package exception
markers expose `diagnosticId()`, `diagnosticTitle()`, and
`diagnosticRemediation()` as strings; no universal catch interface exists.
Existing messages remain unchanged, protected detail, and native causes retain
their identity. No structured context, renderer, development error page,
production error surface, HTTP or CLI mapping, logging, telemetry, retry,
cleanup orchestration, incident system, or localization exists. See the [error-handling engineering
guide](docs/engineering/error-handling.md) and the [ADR
index](docs/adr/README.md).

The presentation-neutral `kongono/diagnostics` package now owns Public
`DiagnosticContext`, `DiagnosticField`, their closed schema types, and the exact
Safe/Protected and text/integer/boolean vocabularies. It validates structural
schemas and creates eager, ordered, detached snapshots with at most 16 fields.
Text is represented deterministically as bounded ASCII tokens with an exact
512-byte ceiling and visible `...[truncated]` marker. Construction uses no ambient
state or I/O and retains no global per-failure values. Diagnostics has its own
fourth package-local marker and 11 construction diagnostic IDs.

Originating packages retain identifier meaning, exact catalog schemas,
throw-site values, and separate Public markers; structural schema validity does
not confer semantic ownership. Phase 0Q makes Graph the first semantic consumer:
its Internal catalog owns all 11 schemas, its throw sites supply only approved
values, and its Public marker adds `diagnosticContext()`. Graph messages and
catch ownership remain local. Phase 0T makes Kernel the second semantic consumer
for its six lifecycle failures. Phase 0U makes DI the third semantic consumer
for 25 schemas across 26 throw sites. Kernel, Graph, and DI markers independently
expose context; Diagnostics construction failures expose no recursive context.
Forbidden-material detection, redaction, general raw operator configuration,
broader evidence mechanisms and transport profiles, production allowlists,
serialization, source and documentation lookup, logging, telemetry, and
incidents remain future decisions. PHP CGI now supplies the selected literal
operator and exact host-evidence inputs, and Web Runtime enforces the supplied
two-gate eligibility for neutral response selection and delivery.

The implemented Presentation boundary provides a pure Experimental trusted-
developer projection over exactly the Kernel, Graph, or DI contextual marker.
It derives the exact owner `kernel`, `application_graph`, or
`dependency_injection` and copies only identifier, title, operation, invariant,
remediation, and canonically ordered bounded fields with their unchanged kind,
Safe/Protected disclosure, and truncation state. Multi-marker sources are
rejected before accessor use; arbitrary `Throwable`, the Diagnostics marker,
messages, causes, traces, source, requests, environment, transports, and
metadata bags are excluded. The projection retains no source object, cache, or
cross-operation state and performs no I/O.

Phase 1A accepted the fail-closed activation policy outside Diagnostic
Presentation. At that phase, a future outer composition boundary had to hold an explicit
immutable trusted bootstrap permission for its adapter instance and the adapter
must independently authorize the current delivery. Both must be affirmative
before presentation access, projection, rendering, or delivery; absence,
invalidity, ambiguity, evaluation failure, denial, and uncertainty prohibit the
trusted path. Client input, raw environment labels, heuristics, AI output, and
ambient or previous-operation state cannot enable either permission. The
trusted developer model cannot be reused and redacted for production;
production requires a separate deny-by-default model. Projection and rendering
remain separate. Phase 0Z implements the Experimental
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`
in the existing Diagnostic Presentation leaf. It consumes only the trusted
projection and returns one complete escaped HTML5 document without adding a
package edge, runtime binding, Graph participation, or reverse dependency. It
is stateless and fixed by two golden fixtures.

The current PHP CGI composition is:

```text
CgiWebEntry receives an already-composed WebRuntimeAdapter
    -> CgiWebDelivery supplies request, delivery permission, send, close
        -> WebRuntimeAdapter owns fail-closed response selection and delivery
            -> WebApplication owns one Kernel operation scope
            -> generic failure or eligible trusted presenter output
```

Operator composition constructs and boots the application and runtime, then
invokes `CgiWebEntry`; the external host still owns listening, process and
deployment policy, and caller-controlled application shutdown. Web Runtime
performs the neutral adapter sequence without changing Web Application's sole
Kernel-scope ownership.

Both permissions remain outside Diagnostic Presentation. Phase 1A added no
package edge, service, runtime integration, configuration mechanism, activation
type, adapter, served page, or production surface. Phase 1C implemented only
the first typed input in the separate Diagnostic Activation package. Phase 1D
implemented only the second neutral typed input. At that boundary no
configuration reader, delivery-evidence evaluation, combined enforcement,
adapter, transport, or production surface existed. Phase 2G now implements the
neutral Web Runtime adapter and fail-closed response-selection/delivery
sequence from the two supplied decisions. Phase 3E implements literal operator
selection and exact current-delivery evidence evaluation, and Phase 3F captures
that evidence and performs concrete PHP CGI conversion and emission. Phase 3H
adds root-owned external acceptance for the selected profile. A general
configuration system, production host listener/process policy, and deployment
hardening remain unimplemented. `Allowed` on either enum is
insufficient by itself, and direct case selection or presentation calls do not
prove authority.

Phase 1E implements only the pure combined calculation as the final readonly
Experimental `TrustedDeveloperPresentationEligibility` in Diagnostic Activation.
It stores both typed decisions for one current delivery and returns true only
when both are `Allowed`. Direct construction or a true result does not prove
authority. At Phase 1E, evidence evaluation, presentation access, and
enforcement remained outer-adapter responsibilities. Web Runtime now performs
neutral enforcement from explicitly supplied decisions; it still does not
authenticate raw evidence. The package graph is unchanged.

Phase 1H implements the Experimental final readonly
`Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter` as the
transport-neutral consumer of that eligibility value. Its sole method,
`renderIfEligible(TrustedDeveloperPresentationEligibility,
KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface):
?string`, checks eligibility before any source accessor. A false result returns
`null` immediately. A true result invokes the existing projector and then the
existing HTML renderer and returns the renderer's string unchanged. It catches
nothing; every `Throwable` propagates unchanged. `null` says only that trusted
developer HTML was not produced because combined eligibility was false. The
handler validates no evidence, proves no authority, chooses no transport or
non-trusted response, and retains no ambient or cross-delivery state. Direct use
of it or the lower-level presentation APIs is not authorization. Diagnostic
Activation and Diagnostic Presentation remain directly callable independent
lower layers. Diagnostic Handler itself owns no adapter, configuration reader,
evidence evaluator, transport, delivery, response, or production model. Web
Runtime now owns the neutral adapter/delivery/generic/trusted sequence. PHP CGI
now owns literal operator and exact delivery-evidence evaluation plus concrete
conversion and emission; Phase 3H adds test-owned external proof for that
selected profile. Production host listener/process policy and deployment
hardening remain unimplemented.

## Boundaries

- PHP 8.5 is the primary application language.
- TypeScript and JavaScript belong to browser/frontend integration.
- Rust may later support high-performance framework tooling.
- Go may be selected later for specific infrastructure or runtime integrations.
- Vite is the intended default bundler integration; Kongono will own an Asset Platform abstraction, not a bundler.
- OpenTelemetry is the intended instrumentation model; exporters will remain replaceable.
- OpenAPI is intended to be generated from authoritative application metadata.
- MCP and A2A are adapters around Kongono concepts and do not define its domain model.

No first-party listener, general SAPI/protocol adapter, domain Graph schema, complete Graph compiler, capability package, or framework API for those planned boundaries exists yet. The implemented PHP CGI delivery, structural snapshot, and Kernel lifecycle must not be read as selecting broader forms of them; the neutral Web Runtime adapter is already implemented.

## Detailed documentation

- [Architecture overview](docs/architecture/overview.md)
- [Phase 2 roadmap](docs/architecture/phase-2-roadmap.md)
- [Phase 3 roadmap](docs/architecture/phase-3-roadmap.md)
- [PHP CGI FastCGI profile](docs/architecture/php-cgi-fastcgi-profile.md)
- [HTTP message model](docs/architecture/http-message-model.md)
- [Routing model](docs/architecture/routing-model.md)
- [HTTP server model](docs/architecture/http-server-model.md)
- [Web Application model](docs/architecture/web-application-model.md)
- [Application Graph](docs/architecture/application-graph.md)
- [Microkernel and capabilities](docs/architecture/microkernel.md)
- [Explicit typed dependency resolution](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Progressive architecture](docs/architecture/progressive-architecture.md)
- [Runtime model](docs/architecture/runtime-model.md)
- [Agent model](docs/architecture/agent-model.md)
- [Extension platform](docs/architecture/extension-platform.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture Decision Records](docs/adr/README.md)

## Phase 2 runnable fixture milestone

Phase 2A through 2H are complete. [ADR 0050](docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md)
records the test-owned runnable fixture that exercises the thirteen-package path
through the Public `WebDeliveryInterface`. One real Web Application retains the
sole Kernel operation scope; Web Runtime retains trust, response delivery, and
cleanup ownership. External black-box tests prove routing, middleware order,
generic and trusted failure selection, exact stream cleanup, and sequential
isolation.

The CLI subprocess runner is bounded test infrastructure, not a framework CLI,
production SAPI/server/listener/emitter, deployment surface, supported template,
full framework, 1.0 release, or production-ready system. Production runtime,
transport authentication, operations, and broader capabilities remain open.

## Selected Phase 3 production edge

[ADR 0051](docs/adr/0051-phase-3-production-http-integration-milestone.md)
accepts Phase 3 as the bounded path to one supported synchronous production HTTP
integration profile. [ADR 0052](docs/adr/0052-php-cgi-fastcgi-responder-profile.md)
selects PHP `cgi-fcgi`, CGI/1.1, and the FastCGI `RESPONDER` role. ADR 0053 and
Phase 3C implement bounded inbound conversion; ADR 0054 and Phase 3D implement
bounded response staging and emission; ADR 0055 and Phase 3E implement immutable
operator bootstrap and independent current-delivery permission evaluation. The
Phase 3F delivery captures the closed native projection and opens the input
resource lazily; only fixed bootstrap validation reads native process
prerequisites. The host still owns FastCGI framing, listening,
connections, processes, TLS, and deployment. The emitter stages the complete
bounded response before SAPI commitment. Phase 3F's `CgiWebDelivery` now
implements the adapter port without moving lifecycle ownership. Phase 3G alone
is next.

Invalid profile input fails before `WebApplication::handle()` and the implemented
Internal `CgiWebDelivery` maps it through Web Runtime to the fixed generic 500
response. The delivery projects only bounded native CGI evidence, opens
`php://input` lazily, emits once, and releases one-delivery resources explicitly.
Experimental `CgiWebEntry` accepts an already-composed `WebRuntimeAdapter`.
PHP CGI now depends directly on Web Runtime and still has no direct Web
Application dependency. Phase 3G adds the stateless Experimental
`CgiFrontController`, the 1019 simultaneous-failure contract, and the selected
profile's [deployment constraints](docs/engineering/php-cgi-deployment.md).
Entry and explicit caller-supplied application shutdown are attempted once in
that order. `Production` trusted diagnostics remain denied; uncertain emission
or shutdown requires external process retirement. Phase 3H proves this path
through an operator-supplied listener using root-owned test infrastructure; no
production listener, client, server configuration, or deployment artifact is
added. The intrinsic query ceiling remains 8,192 bytes while exact target/query
composition makes 8,190 bytes the externally representable maximum.
Phase 3 remains neither the full framework, a Stable API, 1.0, production
readiness, nor production-hardening certification.

## Accepted Phase 4 planning boundary

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A adds documentation
architecture only and creates no package or dependency edge. The implemented
topology remains exactly fourteen acyclic production packages.

The accepted direction places planned Kongono Chie, Kongono Hantei, and Kongono
Oshie above one future versioned, inspectable, AI-independent knowledge
foundation. Chie builds, Hantei independently proves, and Oshie teaches. These
developer companions are distinct from application Agent Principals and are
not implemented. The deterministic framework remains usable offline and
without AI.

Phase 5B applies `>=8.5.0 <8.6.0` to the root and all eighteen package
manifests. PHP 8.5 is the one active compatibility-certified minor, and this
execution proves PHP 8.5.9 on Windows. Eligible stable 8.5 patches remain in
range; a later minor still requires the complete separately accepted
compatibility gate. PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support are not claimed.

See the [developer-intelligence architecture](docs/architecture/developer-intelligence.md),
[Phase 4 roadmap](docs/architecture/phase-4-roadmap.md), and
[PHP support policy](docs/engineering/php-support-policy.md). Phase 4A does not
implement knowledge artifacts, assistants, patch proposals, auto-fix,
autonomous operations, production AI, Stable API, or 1.0.

## Accepted Phase 4B knowledge architecture

[ADR 0061](docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md)
and the [knowledge-foundation architecture](docs/architecture/knowledge-foundation.md)
accept the semantic source-authority, exact source-state, provenance, conflict,
citation, deterministic-derivation, disclosure, and framework/workspace
separation contract. The six source classes and six claim classes are
architecture concepts, not implemented enums or schema rows.

Phase 4B adds no package and no dependency edge; the topology remains fourteen
acyclic production packages. No artifact, generator, validator, inventory,
schema, digest, command, retrieval system, model, workspace read, Graph export,
or role runtime exists. Phase 4C is next in the unchanged sequential roadmap
and must separately select the concrete AI-independent artifact slice.

## Implemented Phase 4C knowledge slice

Phase 4C adds the fifteenth acyclic production package, `kongono/knowledge`, as
a low-level leaf depending only on API Metadata and Diagnostics. It completely
generates and validates one canonical, versioned, inspectable 31-source
`phase4-foundation` artifact in memory and may publish it once under its
content-addressed name with atomic no-clobber semantics. The artifact is derived
evidence rather than authority, is not checked in, and supports only exact
whole-source citations. The package consumes no Application Graph input and
implements no retrieval, search, model, workspace access, patch, or production
dependency.

## Implemented Phase 4D read-only Chie slice

## Implemented Phase 4E independent Hantei slice

The optional `kongono/hantei` package runs the fixed `framework-quality-v1`
Composer profile against one exact Git-visible subject. It binds reports to the
current framework-knowledge artifact, preserves raw tool output only in bounded
memory, and returns derived `PASS`, `FAIL`, or `BLOCKED` evidence. Hantei depends
on Knowledge directly, not Chie, and owns no repair or repository-mutation path.
Phase 4D adds the sixteenth acyclic production package, `kongono/chie`, as a
leaf depending only on API Metadata, Diagnostics, and Knowledge. It validates
one explicit artifact against one explicit framework root before returning an
exact cited section for one of six topics or bounded literal occurrences of one
supplied Safe diagnostic identifier. It is deterministic and offline. It has
no free-form prompt, arbitrary workspace read, recommendation, patch, Hantei
verdict, write, dependency, Git, process-launch, network, deployment, or
production authority. See [ADR 0063](docs/adr/0063-read-only-chie-explanation-and-diagnosis.md)
and the [read-only Chie guide](docs/architecture/read-only-chie.md).

## Implemented Phase 4F read-only Oshie slice

The Experimental `kongono/oshie` package teaches six closed topics from exact current artifact sources and checks deterministic package-owned answers. Lessons and exercise outcomes carry exact citations; practice omits the accepted-answer field and checks omit submitted input. Accepted documentation remains authoritative. See [ADR 0065](docs/adr/0065-cited-version-matched-oshie-learning-and-deterministic-exercises.md) and the [read-only Oshie guide](docs/architecture/read-only-oshie.md).

## Implemented Phase 4G reviewable Chie proposal slice

Chie now constructs one deterministic, unapplied exact-line candidate for an
explicit current explanatory documentation source. The canonical response binds
the current artifact, source set, target, base, proposed content, reversible
diffs, exact provenance, proposal identity, fixed warnings, and a pending human
application/fresh-Hantei handoff. Safe candidate input is a caller disclosure
assertion, not sanitization. Chie performs no apply, write, Git, Hantei, model,
provider, network, or deployment action. Hantei cannot verify an unapplied
candidate, and Oshie is not a proposal source. See [ADR 0066](docs/adr/0066-explicit-reviewable-chie-patch-proposals.md)
and the [proposal guide](docs/architecture/reviewable-chie-patch-proposals.md).

Phase 4H supplies the root-owned 28-scenario
[external acceptance boundary](docs/architecture/phase-4-external-acceptance.md).
It proves one current artifact/source identity across real local role commands,
offline Knowledge/Chie/Oshie behavior, a real Hantei PASS, explicitly synthetic
Hantei FAIL/BLOCKED evaluation, inert untrusted instructions, non-disclosure,
temporary cleanup, and no repository mutation or authority elevation. Phase 4A
through 4H are implemented and externally accepted at the bounded checkpoint.
Kongono remains pre-1.0 and not production-ready.

## Completed Phase 5 composition and application workflow

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e` with nineteen acyclic production
packages. It implements one supported application composition root,
deterministic build-before-runtime behavior, generated typed PHP constructor
wiring, exact-current loading, existing-web lifecycle integration, one copy-once
minimal skeleton, and root-owned external acceptance.

The supported composition root owns construction, startup order, retained
application-lifetime objects, operation factories, shutdown order, and partial-
boot cleanup. It is not Kernel: Kernel retains its current lifecycle-neutral
boundary. Production startup must verify one complete current deterministic
artifact before accepting work; request handling never discovers, reflects,
scans, autowires, rebuilds, downloads, or silently accepts stale output.

The supported production direction rejects ambient containers, resolver or
container injection into application services, runtime service lookup,
property-hook dependency resolution, request-time reflection/scanning,
runtime `eval`, and remote generated code. The existing Experimental runtime
resolver remains separate and unchanged; the supported generated boundary does
not integrate or deprecate it. The current Application Graph also remains
unchanged and has no selected build integration.

Phase 5B applies the already-Accepted Composer/platform migration to
`>=8.5.0 <8.6.0` without adding runtime semantics, a package, dependency,
polyfill, shim, public API, release/LTS promise, or composition/build
capability. Phase 5B is accepted and the bounded PHP constraint is current.

Phase 5C accepts the contract for exactly one inert application-owned
`<application-root>/kongono.composition.json`, schema
`"kongono.composition"`, integer version `1`, canonical symbols, typed inputs,
explicit services/bindings/roots, application/operation/transient lifetimes,
reverse-order cleanup, deterministic validation, bounds, source safety, and
provenance. Phase 5D now implements the nineteenth acyclic package,
`kongono/composition`, which validates that source and inspected symbols,
builds a canonical semantic model, preserves exact raw-source/project/builder/
symbol provenance in a versioned artifact, validates currentness, and publishes
content-addressed bytes without overwrite. Phase 5E now adds deterministic
typed PHP generation and isolated application/operation lifetime support in
that package. Version 1 accepts only `service`, `input`, and `default`; plural
collaboration uses an explicit application-owned typed aggregate. The
generated boundary owns eager roots, three lifetimes, leases, reverse cleanup,
closed/retired state, non-executing validation, and explicit no-clobber
publication. Phase 5F adds one exact-current generated-file loader, bounded
one-use startup reflection, and the exact single-root web profile. Existing Web
Application Kernel-operation ownership, Web Runtime delivery ownership, and
PHP-CGI front-controller ownership remain unchanged. Copied application source
remains application-owned; DI and Graph remain unintegrated. Phase 5G adds only
application-owned Composer scripts and the copy-once skeleton. Phase 5H adds
only root-owned external acceptance: a deterministic 28-scenario offline
install/build/check/run/failure/isolation/nondisclosure/cleanup matrix and the
exact Phase 5 checkpoint. It adds no framework CLI, mutable current pointer,
new transport, listener, deployment behavior, automatic upgrade, production
capability, or Phase 6 work. See
[ADR 0068](docs/adr/0068-supported-application-composition-and-build-boundary.md),
[ADR 0069](docs/adr/0069-generated-typed-wiring-and-no-runtime-service-location.md),
[ADR 0070](docs/adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](docs/adr/0071-roots-bindings-lifetimes-and-cleanup.md),
[ADR 0072](docs/adr/0072-deterministic-composition-artifact-and-publication.md),
[ADR 0073](docs/adr/0073-generated-typed-wiring-and-lifecycle-boundary.md),
[ADR 0074](docs/adr/0074-generated-assembly-web-runtime-integration.md),
[ADR 0075](docs/adr/0075-minimal-application-skeleton-and-workflow.md),
[ADR 0076](docs/adr/0076-phase-5-external-application-acceptance.md),
the [composition and build guide](docs/architecture/application-composition-and-build.md),
the [generated composition wiring guide](docs/architecture/generated-composition-wiring.md),
the [generated runtime integration guide](docs/architecture/generated-runtime-integration.md),
the [composition source guide](docs/architecture/composition-source.md),
the [minimal application workflow](docs/architecture/minimal-application-workflow.md),
the [Phase 5 external acceptance guide](docs/architecture/phase-5-external-acceptance.md),
the [Phase 5 roadmap](docs/architecture/phase-5-roadmap.md),
the [generated wiring guide](docs/engineering/generated-wiring.md),
the [application workflow guide](docs/engineering/application-workflow.md), and
the [declaration guide](docs/engineering/composition-declarations.md).

## Accepted Phase 6B low-level database contract architecture

Phase 6A accepts documentation architecture for a layered Deterministic Data
Platform. Its five conceptual layers are low-level connectivity and execution;
explicit SQL/query/command plans and dialect compilation; schema and migration
work; optional generated Data Mapper and repository ergonomics; and composition
with operation-scoped lifecycle. These layers are not a monolithic ORM and do
not move database responsibility into Kernel.

Raw SQL remains an explicit parameter-bound escape hatch. Provider capability
requirements remain visible, unsupported behavior fails closed, and portability
or performance claims require real evidence. Static facades, ambient
connections, service location, request-time mapping reflection or proxy
generation, hidden lazy loading, property-hook infrastructure lookup, and
Active Record as the core model are rejected defaults.

Operation-owned resources and transactions require explicit acquisition,
cleanup, concurrency, and failure composition suitable for persistent runtimes.
Derived plans and generated mapping remain deterministic evidence rather than
authority. Data diagnostics retain the existing non-disclosure policy.

Phase 6B accepts the architecture implemented by Phase 6C in two Experimental packages. Neutral
`kongono/database` under `Kongono\Database\` depends only on PHP, API Metadata,
and Diagnostics. The first separate adapter,
`kongono/database-pdo-pgsql` under `Kongono\DatabasePdoPgsql\`, depends on PHP,
`ext-pdo`, `ext-pdo_pgsql`, and the neutral package. It adapts PostgreSQL
through PDO `pdo_pgsql` only; this first proof profile is not a portability
claim, and the neutral package never depends on the adapter.

The Accepted version-1 contract fixes explicit connectors, operation-owned
sessions, immutable parameter-bound unparsed raw SQL with a `Rows` or
`Completion` expectation, exact scalar/binary values and bounds, canonical
session capabilities, forward-only duplicate-label-safe rows, one active stream
and transaction, explicit transaction state/options, reverse cleanup,
primary-plus-cleanup failure retention, no automatic retry, and non-disclosing
failure categories. Kernel and every Phase 5 lifecycle owner remain unchanged.

Phase 6D adds two one-way higher-layer packages. Experimental
`kongono/database-sql` owns immutable bounded Select, Insert, Update, Delete,
predicate, capability, and non-disclosing compilation contracts. Experimental
`kongono/database-sql-pgsql` owns a stateless PostgreSQL 17 compiler that emits
ordinary immutable `DatabaseStatement` values. Explicit all-row mutation intent
is retained in plans; every executable value becomes an ordered parameter.
Direct parameter-bound raw SQL remains supported.

Phase 6E adds the one-way `kongono/database-migration` and
`kongono/database-migration-pgsql` layers. The caller-owned catalog, exact-prefix
history, canonical identities, explicit destructive policy, and PostgreSQL 17
transaction/lock execution remain separate from mapping and runtime ownership.
Phase 6F adds the optional Experimental `kongono/database-mapping` leaf over
API Metadata, Database, Database SQL, and Diagnostics. Its explicit inert source,
build-time record Reflection, canonical artifact, and generated typed
mappers/repositories remain separate from migration and runtime ownership.
Generated output is derived and not checked in; raw SQL and typed plans remain
first-class. Phase 6G brings the current graph to 27 packages with the outer
Experimental database-application workflow, exact-current generated loading,
operation-owned sessions, and optional data skeleton. Phase 6H adds only the
root-owned whole-platform PostgreSQL 17 acceptance proof, local observational
measurement baseline, documentation reconciliation, and exact checkpoint. No
ORM, pool, framework CLI, migration startup, deployment entrypoint, runtime
telemetry, or further provider exists. Kongono remains pre-1.0 and not
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
[migration guide](docs/architecture/database-migrations.md), the
[mapping guide](docs/architecture/database-mapping.md), the
[engineering contract tables](docs/engineering/database-contracts.md), the
[performance baseline guide](docs/engineering/database-performance-baseline.md), and the
[Phase 6 roadmap](docs/architecture/phase-6-roadmap.md).
