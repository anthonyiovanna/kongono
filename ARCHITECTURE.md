# Kongono Architecture

## Status and scope

This document is an index of Kongono's authorized architectural direction.
Kongono is pre-alpha. Phase 7 is complete at checkpoint
`b09b0c654f8b67f7cac3807f839f665e8c5b5469`, tree
`c0b6fed92dbb43d0bb91891b4e4496e84c0c85e4`. Phase 8G adds the upper
`kongono/console-application` boundary at the Phase 8 checkpoint. Phase 9G now
adds deterministic rule-free policy identity evidence and the supported
protected application workflow through `kongono/security-policy-build` and
`kongono/security-application`, bringing the current topology to exactly
forty-four acyclic production packages. The official one-shot `kongo`
executable implements exactly `application:build`, `security:check`, and
`security:inspect`; the two security commands are read-only.

The earlier Phase 2 checkpoint remains
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Its root-owned, test-owned runnable
fixture externally proves the real HTTP Message,
Routing, HTTP Server, Web Application, Web Runtime, diagnostic, and Kernel path;
it is not a production package, production application, supported template,
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

The current graph has forty-four production packages. `kongono/api-metadata` requires
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
Application dependency. Console depends only on API Metadata and Diagnostics.
Console Build depends
only on Console, API Metadata, Diagnostics, Hash, JSON, and PHP. Console Runtime
depends only on Console, API Metadata, Diagnostics, and PHP. Console Presentation
depends only on Console, API Metadata, Diagnostics, and PHP. Console Commands is
an upper capability package over Console, Console Runtime, Application Graph,
Composition, Hantei, API Metadata, and Diagnostics; Console Build is development
only for its definition proof. Identity Access depends only on API Metadata,
Diagnostics, and PHP. Authentication depends only on API Metadata, Diagnostics,
Identity Access, and PHP. Authorization depends only on API Metadata,
Diagnostics, Identity Access, and PHP. Security Runtime is an outer package over
API Metadata, Authentication, Composition, Diagnostics, Identity Access, and
PHP. Security HTTP is an outer adapter over API Metadata, Authentication,
Diagnostics, Security Runtime, PSR HTTP Message interfaces, and PHP. Security
Enforcement is a separate outer boundary over API Metadata, Authorization,
Diagnostics, Identity Access, and PHP. No inward production package depends on
either. The root consumes all forty-four. This graph is acyclic, and no package depends on
Kernel merely for compatibility metadata.

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

## Completed Phase 6 Deterministic Data Platform

Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05` with twenty-seven production
packages. Phase 6A accepts documentation architecture for a layered Deterministic Data
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

## Completed Phase 7 Deterministic Persistence milestone

Phase 7A accepts an optional Deterministic Persistence capability above Phase 6,
providing the developer-facing role commonly called an ORM without classic
runtime-magic ORM machinery. Neutral Database, raw parameter-bound SQL,
immutable plans, migrations, generated Data Mappers, and direct repositories
remain first-class paths that require no persistence capability. Lower Phase 6
packages cannot depend upward on the future layer.

Application domain objects remain plain PHP classes owned by the application.
Constructors and named factories preserve invariants; hydration cannot write
private state through Reflection, serialization tricks, or uninitialized-object
bypasses. Future metadata is deterministic, versioned, bounded, reviewable, and
compiled before runtime into ordinary generated PHP evidence. Runtime mapping
Reflection, scanning, proxies, `eval`, mutable registries, Active Record base
classes, magic properties, service location, and hidden lazy I/O are rejected.

Future persistence contexts, identity maps, snapshots, and change sets are
bounded to one explicit operation. An explicit flush is the only accepted write
boundary; callers retain transaction authority, and terminal or uncertain
failure retires the context without automatic replay. Relationships, aggregate
boundaries, load plans, ordering, and cascades must be explicit and bounded.
Diagnostics remain non-disclosing, and persistent workers must release all
operation-owned domain references.

Phase 7A remains accepted architecture. Phase 7B adds the neutral Experimental
`kongono/persistence` package. Phase 7C adds the upper Experimental
`kongono/persistence-build` package, bringing the graph to twenty-nine packages. It
defines operation-scoped identity and state vocabulary, opaque concurrency
tokens, immutable value-free changes and flush results, a read-only-plus-retire
context interface, and bounded diagnostic/exception contracts. Phase 7C also
validates one strict application-owned persistence source against current mapping
evidence and build-time-reflected domain contracts, producing a detached model
and canonical provenance artifact with structural/current validation and safe
publication. Phase 7D adds deterministic content-addressed ordinary PHP state
adapters which use public application construction and accessors for hydration,
extraction, identity, snapshots, and neutral change evidence. Validation is
non-executing and both structural and exact-current; publication remains atomic
and no-clobber. Phase 7E adds one operation-scoped concrete context and explicit
single-entity unit of work inside `kongono/persistence`. It accepts a caller-
owned `DatabaseExecutor` only for one explicit flush, executes at most one
reviewed change, distinguishes absent/conflict from terminal uncertainty, and
releases operation-owned references on failure or retirement. It does not load
generated state, own transactions, or generate SQL. Phase 7F adds only explicit
one-level load/cascade evidence and deterministic generated aggregate repositories
over caller-supplied database/load/cascade executors. Phase 7G adds an upper
exact-current loader and Composition workflow while leaving provider and
transaction authority explicit. Phase 7H closes the thirty-package milestone
with root-owned PostgreSQL 18.6 acceptance, historical PostgreSQL 17.2 Phase 6
regression, deterministic performance summaries, documentation, and an exact
checkpoint.
See [ADR 0087](docs/adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
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

Phase 8A accepts a deterministic automation-first Structured CLI edge. Phase 8B
adds `kongono/console` as the thirty-first production package. The official executable
is exactly `kongo`, with no alias. Command meaning, one explicit invocation and
neutral result, and human or machine presentation plus process adaptation are
separate. Handlers do not print, style output, read global input, prompt
invisibly, or call `exit`; presenters do not execute behavior or redefine
success.

The neutral engine receives explicit tokens, ports, and detached values rather
than ambient process, stream, environment, directory, terminal, signal, clock,
randomness, credential, or registry state. Definition discovery must use
deterministic provenance-bearing build evidence and fail closed; runtime
scanning, Reflection discovery, mutable registries, plugin probing, and network
discovery are rejected. Human and versioned machine presentations derive from
one neutral result, with declared interaction, honest dry-run behavior, bounded
nondisclosing diagnostics, explicit reverse cleanup, and sequential same-
process isolation.

Neutral CLI contracts remain below capability adapters. The future executable
is an upper composition leaf, and command handlers receive explicit typed
collaborators rather than a resolver, container, service locator, or property-
hook dependency source. Phase 8C extends Console with immutable input and
command specifications, a pure already-tokenized parser, explicit mutation
tokens, and a separate secure-input carrier. The new thirty-second package,
`kongono/console-build`, compiles one inert `kongono.commands.json` source to
canonical versioned model/artifact evidence and validates exact-current bytes
through Console invariant owners. It adds no publication, load, registry,
discovery, handler binding, execution, concrete operation, command, presenter,
executable, or deployment behavior. Phase 8D adds immutable explicit handler
bindings and the separate `kongono/console-runtime` package. Each bound dispatch
uses one fresh operation, one explicit cancellation check, bounded reverse
cleanup, safe primary-plus-cleanup failure preservation, same-instance
reentrancy rejection, and sequential same-process isolation. It adds no
presentation, process adapter, executable, real command, discovery, service
location or Composition integration. Phase 8E adds bounded neutral result
records and deterministic human/machine result and help presentation in the
separate `kongono/console-presentation` package. One explicit port receives one
completed document; Protected values are omitted and only their count is
disclosed. Phase 8F adds the separate Experimental
`kongono/console-commands` upper package and the exact read-only
`graph:inspect`, `composition:inspect`, and `quality:inspect` command family.
Typed handlers consume explicit accepted capabilities, execute through Console
Runtime, and use exact generated Composition roots; no native process adapter,
executable, mutation, service location, or ambient authority is added. See
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

Phase 8G adds the official extensionless `kongo` Composer binary and exactly
`application:build` in the Experimental `kongono/console-application` upper
package. `--plan` is a non-mutating dry run; `--apply` is explicit authority to
publish the two predicted Composition outputs using their existing atomic,
content-addressed, contained, no-clobber publishers. No prompt, alias, second
command, daemon, watcher, installer, deployment manager, or shell is added.
Human and machine forms share one result and exit map. Phase 8F snapshot
handlers remain programmatic until an application can supply truthful
snapshots. Phase 8H adds root-owned real-process acceptance under
[ADR 0104](docs/adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md) and
the [Phase 8 external acceptance guide](docs/architecture/phase-8-external-acceptance.md).
It proves both executable edges, plan/apply truth, exit-3 conflict no-clobber,
deterministic nondisclosure, cleanup, and the exact local checkpoint without
adding production behavior.

## Implemented Phase 9A through Phase 9H Identity and Access Security boundary

Phase 9A accepts provider-neutral Identity and Access Security architecture. It
separates identity, authentication, authorization, credential/session
transport, protected-capability enforcement, disclosure, audit, and telemetry.
Authentication establishes bounded validated facts only; it never grants
authorization. Only an exact fail-closed `Permit` for one immutable
operation-scoped Security Context, exact application-owned Action and Resource,
exact current Policy Evidence, and explicit environmental facts may proceed.

Security context construction is an outer-boundary responsibility and later
typed Composition wiring must pass it explicitly into operation collaborators.
No current-user global, ambient `Auth`, thread local, service location, request
singleton authority, or Kernel security lifecycle is accepted. Transport
middleware may adapt evidence or reject a request, but enforcement belongs at
the protected capability before any protected read or effect. Policy evidence
must be deterministic, versioned, provenance-bound, and fail closed.

Dependency direction is inward: neutral security contracts remain below
provider, session, HTTP, application-workflow, and executable adapters. Specific
outer adapters may depend on neutral contracts; lower packages cannot depend
upward. Composition remains the explicit construction owner. Phase 9B adds only
the Experimental `kongono/identity-access` package over API Metadata,
Diagnostics, and PHP, bringing the graph to exactly thirty-seven acyclic
production packages. It implements five principal kinds, opaque exact
principal/resource identifiers and references, eight typed authentication
outcomes, inert Permit/Deny/Indeterminate decisions, canonical application-owned
Action/ResourceType names, explicit anonymous/authenticated Security Context,
and bounded non-disclosing diagnostics and exceptions.

These current contracts validate and carry values only. They perform no
authentication, authorization, policy evaluation, domain lookup, enforcement,
I/O, ambient state access, or lifecycle integration. No authenticator, verifier,
credential, cryptography, policy engine, receipt, session profile, HTTP or
Composition integration, provider, schema, command, audit, telemetry, or
application workflow is introduced by Phase 9B.

Phase 9C adds the separate Experimental `kongono/authentication` package over
API Metadata, Diagnostics, Identity Access, and PHP. It accepts zero through
eight ordered caller-owned evidence objects, rejects multiple evidence as
Ambiguous, selects one explicitly registered verifier by exact mechanism, and
owns one verify/retire attempt lifecycle. Authentication facts carry principal,
mechanism, unnamed ordinal strength, explicit authentication and validity
instants, and no permission. Freshness and strength use explicit caller inputs
with inclusive validity, age, and strength comparisons. Primary and cleanup
failures retain exact object identity through bounded non-disclosing exceptions.
The orchestrator keeps only immutable verifier registration and no current-call
state, so sequential and reentrant calls remain isolated.

No concrete verifier/provider, credential, password, token, key, cryptography,
session/cookie transport, HTTP adapter, authorization evaluator, policy,
Security Context construction, Composition wiring, command, audit, telemetry,
application workflow, or Phase 9D behavior is introduced by Phase 9C.

Phase 9D adds the separate Experimental `kongono/authorization` package over
API Metadata, Diagnostics, Identity Access, and PHP. Exact name, version, and
provenance identify independently expected and opaque evidence policy state.
Mismatch or unsupported policy returns Indeterminate without evaluator
execution. The sole exact evaluator receives the identical request once; its
Permit, Deny, or Indeterminate is preserved, and failure never permits. A
bounded receipt records only decision, reason, expected policy, Action,
Resource Type, Security Context state, and environment profile and grants no
authority. The engine retains only immutable evaluator registration and no call
state across sequential or reentrant calls.

No policy language/build/loader, role or permission semantics, ambient guard,
protected-capability enforcement, Security Context construction/lifecycle,
HTTP/session transport, Composition wiring, database enforcement, audit,
telemetry, AI authority, or workflow is introduced by Phase 9D.

Phase 9E evolves Composition with explicit version-2 application and operation
input lifetimes while preserving all version-1 inputs as application-lifetime.
Generated application state never retains operation inputs, and validation
precedes lease acquisition and constructor execution. The Experimental
`kongono/security-runtime` outer package explicitly constructs fresh anonymous
or exactly verified authenticated contexts and owns their generated-operation
lifecycle. Authentication success grants no authorization.

Phase 9F adds the outer Experimental `kongono/security-http` package with the
sole same-origin HTTPS `browser.session.v1` profile and the separate
Experimental `kongono/security-enforcement` package. The former turns one
bounded `__Host-kongono_session` cookie and applicable `X-Kongono-CSRF`
evidence into an anonymous or exactly authenticated generated operation. The
latter binds identical context plus equal Action and Resource to one
authorization evaluation and invokes the protected capability only for exact
Permit. Session syntax/emission is not session truth, cryptography, storage,
login, or authorization; authentication success, routing, middleware, and
receipts grant no authority. No ambient current-user/session/decision state
exists.

Phase 9G adds `kongono/security-policy-build` and
`kongono/security-application`. Policy evidence is rule-free identity and
currentness only; application-owned evaluator decisions remain authoritative,
and only exact Permit at the enforcer executes. The workflow owns one
request-to-operation-to-enforcement lifecycle and exact cleanup. The graph is
exactly forty-four acyclic packages. No second provider/profile, broad identity
product, autonomous policy, agent-delegation runtime, or deployment manager
exists. Phase 9H adds only the root-owned external security acceptance profile
and exact local checkpoint. It proves the selected boundaries through fresh
process and temporary application state, adds no production authority, and
leaves Phase 10 unimplemented. See
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
